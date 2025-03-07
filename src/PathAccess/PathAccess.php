<?php
namespace Concept\Config\PathAccess;

use Traversable;
use ArrayIterator;
use Concept\Config\Interpolate\InterpolatorInterface;

class  PathAccess implements PathAccessInterface
{
    const PATH_SEPARATOR = '.';


    /**
     * Storage
     *
     * @var array
     */
    protected array $data = [];

    /**
     * The created from path
     * Stores the path of the node that was created from
     * 
     * @var array
     */
    private array $createdFromPath = [];


    /**
     * Initialize the config
     * 
     * @return static
     */
    public static function fromArray(array $data = []): static
    {
        return (new static())->hydrate($data);
    }

    /**
     * {@inheritDoc}
     */
    public function asArray(): array
    {
        return $this->data;
    }

    /**
     * {@inheritDoc}
     */
    public function asArrayCopy(?array $array = null): array
    {
        $array = $array ?? $this->data;
        $result = [];
        foreach ($array as $key => $value) {
            $result[$key] = is_array($value) ? $this->asArrayCopy($value) : $value;
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function &asArrayRef(): array
    {
        return $this->data;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(int $flags = 0): mixed
    {
        return json_encode($this->asArray(), $flags);
    }

    /**
     * Get the iterator
     * 
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }

    public function hydrate(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @deprecated
     * {@inheritDoc}
     */
    public function setData(array $data): static
    {
        return $this->hydrate($data);
    }


    /**
     * {@inheritDoc}
     */
    public function withData(array $data): static
    {
        return (new static())->hydrate($data);
    }

    /**
     * {@inheritDoc}
     */
    public function fromPath(string $path): ?static
    {
        return $this->from($path);
    }

    /**
     * {@inheritDoc}
     * 
      @todo: avoid to do references or not?
     */
    public function from(string $path): ?static
    {
        $data = $this->get($path);

        if (!is_array($data)) {
            return null;
            throw new \InvalidArgumentException('Data must be an array. Given Path: ' . $path);
        }
        
        $fromConfig = (new static())->hydrate($data);
        /**
          @todo: test this change
          @deprecated: not needed
         */
        //$this->getAdapter()->export($this->asArrayCopy(), $fromConfig);

        $fromConfig->createdFromPath[] = $path;

        return $fromConfig;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $path = ''): mixed
    {
        $reference = &$this->data;

        foreach ($this->splitPath($path) as $key) {
            /**
                @wtf first version was?
             */
            //if (!is_array($reference) || !array_key_exists($key, $reference)) {
            if (is_array($reference) && !array_key_exists($key, $reference)) {
                return null;
            }

            $reference = &$reference[$key];
            
            if ($reference instanceof InterpolatorInterface) {
                $reference = $reference($this);
            }
        }

        

        return $reference;
    }

    protected function &getRef(string $path = ''): mixed
    {
        $reference = &$this->data;

        foreach ($this->splitPath($path) as $key) {
            if (!is_array($reference) || !array_key_exists($key, $reference)) {
                return null;
            }

            $reference = &$reference[$key];
        }

        return $reference;
    }

    /**
     * {@inheritDoc}
       @todo: fiind more faster way to do this
     */
    public function has(string $path): bool
    {
        return null !== $this->get($path);
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $path, $value): static
    {
        /**
         * Set the value
         */
        $keys = $this->splitPath($path);
        $lastKey = array_pop($keys);
        $reference = &$this->data;
        foreach ($keys as $key) {
            if (!is_array($reference)) {
                //must be an array to continue
                $reference = [];
            }
            if (!key_exists($key, $reference)) {
                $reference[$key] = [];
            }
            $reference = &$reference[$key];
        }
        $reference[$lastKey] = $value;

        // if ($this->getCache()) {
        //     $this->getCache()->set($path, $value);
        // }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function unset(string $path): static
    {
        $keys = $this->splitPath($path);
        $lastKey = array_pop($keys);
        $reference = &$this->data;
        foreach ($keys as $key) {
            if (!is_array($reference) || !key_exists($key, $reference)) {
                return $this;
            }
            $reference = &$reference[$key];
        }
        unset($reference[$lastKey]);

        return $this;
    }

     /**
     * {@inheritDoc}
     */
    // public function merge(array|PathAccessInterface $data): static
    // {
    //     $this->data = array_replace_recursive(
    //         $this->data,
    //         $data instanceof PathAccessInterface ? $data->asArray() : $data
    //     );

    //     return $this;
    // }

    /**
      @todo: improve this. 
    */
    public function merge(array|PathAccessInterface $data): static {

        $source = $data instanceof PathAccessInterface 
            ? $data->asArray() 
            : $data;

        $this->mergeArrays($this->data, $source);

        return $this;
    }

    protected function mergeArrays(array &$target, array $source): void {
        foreach ($source as $key => $value) {
            if (is_array($value) && array_key_exists($key, $target) && is_array($target[$key])) {
                $this->mergeArrays($target[$key], $value); 
            } else {
                $target[$key] = $value;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function mergeTo(string $path, array|PathAccessInterface $data): static
    {
        $this->set(
            $path,
            array_replace_recursive(
                $this->get($path) ?? [],
                $data instanceof PathAccessInterface ? $data->asArray() : $data
            )
        );

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function move(string $from, string $to, bool $merge = true): static
    {
        $value = $this->get($from);
        if ($merge) {
            $this->mergeTo($to, $value);
        } else {
            $this->set($to, $value);
        }
        
        $this->unset($from);

        return $this;
    }
   
    
    /**
     * Split the path string by a separator. Default is @see const PATH_DEFAULT_SEPARATOR
     * next example works but it is not recommended:
     * 
     * @param string $path the Path string
     * 
     * @return array
     */
    protected function splitPath(string $path): array
    {
        return array_filter(
            explode(static::PATH_SEPARATOR, $path)
        );

        /**
         * More complex implementation but with more features
         * Separator will be ignored inside double quotes.
         *  e.g. `"11.2".3.5."another.key"` 
         *  equals to array access like $array["11.2"]["3"]["5"]["another.key"]
         *
         */
        return
            array_filter( // Remove empty items
                array_map( // Trim double quotes
                    fn($item) => trim($item, '"'),
                    preg_split($this->getSplitRegexp(), $path)
                )
            );
    }

    /**
     * @param string ...$paths
     * 
     * @return string
     */
    public static function path(string ...$paths): string
    {
        return is_array($paths) && count($paths) > 1 
            ? join(static::PATH_SEPARATOR, $paths) 
            : array_shift($paths) ?? '';
    }

    /**
     * Get the regular expression pattern for splitting the path.
     *
     * @return string
     */
    protected function getSplitRegexp(): string
    {
        return sprintf(
            '/%s(?=(?:[^"]*"[^"]*")*(?![^"]*"))/',
            preg_quote(static::PATH_SEPARATOR)
        );
    }
}