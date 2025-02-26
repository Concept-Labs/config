<?php
namespace Concept\Config\PathAccess;

use Psr\SimpleCache\CacheInterface;
use Concept\Config\Cache\LRUCache;
use Traversable;
use ArrayIterator;

trait PathAccessTrait
{
    private string $pathsSeparator = '.';
    
     

    protected ?CacheInterface $cache = null;
    protected int $cacheSize = 1000;

    private array $createdFromPath = [];

    /**
     * Initialize the config
     * 
     * @return static
     */
    protected function init(): static
    {
        return $this;
    }

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

    public function &asArrayRef(): array
    {
        return $this->data;
    }

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

    private function getCache(): CacheInterface
    {
        return $this->cache ?? new LRUCache($this->cacheSize);
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
     * Get the path separator
     */
    protected function getPathSeparator(): string
    {
        return $this->pathsSeparator;
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
    public function fromPath(string ...$paths): ?static
    {
        return $this->from(...$paths);
    }

    /**
     * {@inheritDoc}
     */
    public function from(string ...$paths): ?static
    {
        $data = $this->get(...$paths);

        if (!is_array($data)) {
            return null;
            throw new \InvalidArgumentException('Data must be an array. Given Path: ' . $this->createPath(...$paths));
        }
        $fromConfig = $this->withData(
            $data
        );

        $fromConfig->createdFromPath = $paths;

        return $fromConfig;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreatedFromPath(): array
    {
        return $this->createdFromPath;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string ...$paths)
    {
        $path = $this->createPath(...$paths);

        if ($this->getCache()->has($path)) {
            return $this->getCache()->get($path);
        }

        $reference = $this->data;

        foreach ($this->splitPath($path) as $key) {
            if (!is_array($reference) || !array_key_exists($key, $reference)) {
                return null; 
            }

            $reference = $reference[$key]; 
        }

        $this->getCache()->set($path, $reference);

        return $reference;
    }


    /**
     * {@inheritDoc}
     */
    public function has(string ...$paths): bool
    {
        return $this->_has(...$paths);
    }

    /**
     * @todo: implement more efficient has() method
     */
    protected function _has(string ...$paths): bool
    {
        return null !== $this->get(...$paths);
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

        $this->getCache()->set($path, $value);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function unset(string ...$paths): static
    {
        $path = $this->createPath(...$paths);

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
     * Separator will be ignored inside double quotes.
     * e.g. `"11.2".3.5."another.key"` equals to an array access like $array["11.2"]["3"]["5"]["another.key"]
     *
     * @param string $path the Path string
     * 
     * @return array
     */
    protected function splitPath(string $path): array
    {
        return array_filter(
            explode($this->getPathSeparator(), $path)
        );
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
    public function createPath(string ...$paths): string
    {
        return is_array($paths) && count($paths) > 1 
            ? join($this->getPathSeparator(), $paths) 
            : array_shift($paths) ?? '';
    }

    /**
     * {@inheritDoc}
     */
    public function path(string ...$paths): string
    {
        return $this->createPath(...$paths);
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
            preg_quote($this->getPathSeparator())
        );
    }
}