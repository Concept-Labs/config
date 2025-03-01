<?php
namespace Concept\Config\PathAccess;

use Traversable;
use ArrayIterator;

trait PathAccessTrait
{

    const PATH_SEPARATOR = '.';

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
     * {@inheritDoc}
     */
    public function reset(): static
    {
        $this->data = [];
        $this->createdFromPath = [];
        $this->cache = null;

        return $this;
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
    public function setPathSeparator(string $separator): static
    {
        $this->pathsSeparator = $separator;

        return $this;
    }


    /**
     * {@inheritDoc}
     */
    public function withData(array $data): static
    {
        $clone = clone $this;
        $clone->reset()->hydrate($data);

        return $clone;
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
     */
    public function from(string $path): ?static
    {
        $data = $this->get($path);

        if (!is_array($data)) {
            return null;
            throw new \InvalidArgumentException('Data must be an array. Given Path: ' . $this->createPath(...$paths));
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
    public function getCreatedFromPath(): array
    {
        return $this->createdFromPath;
    }

    // public function &getRef(string ...$paths)
    // {
    //     $path = $this->createPath(...$paths);

    //     if ($this->getCache()->has($path)) {
    //         return $this->getCache()->get($path);
    //     }

    //     $reference = &$this->data;

    //     foreach ($this->splitPath($path) as $key) {
    //         if (!is_array($reference) || !array_key_exists($key, $reference)) {
    //             return null; 
    //         }

    //         $reference = &$reference[$key]; 
    //     }

    //     $this->getCache()->set($path, $reference);

    //     return $reference;
    // }

    /**
     * {@inheritDoc}
     */
    public function get(string $path = '', mixed $default = null, bool $byRef = false): mixed
    {
        $value = $this->getRaw($path, $default, $byRef);
        //$value = $this->getPluginManager()->process($path, $value);

        return $value;
    }

    /**
     * {@}
     */
    public function getRaw(string $path = '', mixed $default = null, bool $byRef = false): mixed
    {
        if ($this->getCache() && $this->getCache()->has($path)) {
            return $this->getCache()->get($path);
        }

        $reference = $this->getValueByPath($path, $this->data);

        // $reference = &$this->data;

        // foreach ($this->splitPath($path) as $key) {
        //     if (!is_array($reference) || !array_key_exists($key, $reference)) {
        //         $null = null;
        //         return $null; 
        //     }

        //     $reference = &$reference[$key]; 
        // }

        
        if ($byRef) {
            $value = &$reference;
        } else {
            $value = $reference;
            //$value = is_array($reference) ? array_merge([], $reference) : $reference;
        }

        if ($this->getCache()) {
            $this->getCache()->set($path, $value);
        }

        return $value ?? $default;
    }

    protected function getValueByPath(string $path, array &$data): mixed
    {
        $reference = &$data;

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
     */
    public function has(string $path): bool
    {
        return $this->_has($path);
    }

    /**
     * @todo: implement more efficient has() method
     */
    protected function _has(string $path): bool
    {
        return null !== $this->get($path);
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $path, $value): static
    {
        $keys = $this->splitPath($path);
        $lastKey = array_pop($keys);
        $reference = &$this->data;
        foreach ($keys as $key) {
            if (!is_array($reference)) {
                $reference = [];
            }
            if (!key_exists($key, $reference)) {
                $reference[$key] = [];
            }
            $reference = &$reference[$key];
        }
        $reference[$lastKey] = $value;
        if ($this->getCache()) {
            $this->getCache()->set($path, $value);
        }

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
        $source = $data instanceof PathAccessInterface ? $data->asArray() : $data;
        $this->mergeArrays($this->data, $source); // Виклик без $this-> для статичного контексту
        return $this;
    }
    protected function mergeArrays(array &$target, array $source): void {
        foreach ($source as $key => $value) {
            if (is_array($value) && array_key_exists($key, $target) && is_array($target[$key])) {
                $this->mergeArrays($target[$key], $value); // Рекурсія для вкладених масивів
            } else {
                $target[$key] = $value; // Пряме присвоєння
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