<?php
namespace Concept\Config;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

class Config implements ConfigInterface, IteratorAggregate
{

    /**
     * Storage
     *
     * @var array
     */
    protected array $config = [];

    /**
     * States backup stack.
     *
     * @var array<array>
     */
    protected array $state = [];

    /**
     * Chache
     * 
     * @var array
     */
    //protected array $cache = [];

    /**
     * @deprecated
     * The constructor
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->setData($config);
    }

    /**
     * Get the iterator
     * 
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->config);
    }

    /**
     * {@inheritDoc}
     */
    public function setData(?array $data = null): void
    {
        $this->config = $data ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function withData(?array $data = null): self
    {
        $config = clone $this;
        $config->reset();
        $config->setData($data ?? []);

        return $config;
    }

    /**
     * @deprecated
     * {@inheritDoc}
     */
    public function withPath(string ...$paths)
    {
        return $this->fromPath(...$paths);
    }

    /**
     * {@inheritDoc}
     */
    public function fromPath(string ...$paths)
    {
        return $this->withData(
            $this->get(...$paths)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function get(string ...$paths)
    {
        /**
         * Each parameter can be part of path so join them together
         */
        $path = $this->createPath(...$paths);

        return array_reduce(// Lookup by the path
            $this->splitPath($path), 
            function ($reference, $key) {
                if (!is_array($reference) || !key_exists($key, $reference)) {
                    return null;
                }
                return $reference[$key];
            },
            $this->config
        );
    }

    public function set(string $path, $value): self
    {
        $keys = $this->splitPath($path);
        $lastKey = array_pop($keys);
        $reference = &$this->config;
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

        return $this;
    }

    public function unset(string ...$paths): self
    {
        $path = $this->createPath(...$paths);

        $keys = $this->splitPath($path);
        $lastKey = array_pop($keys);
        $reference = &$this->config;
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
    public function has(string ...$paths): bool
    {
        return null !== $this->get(...$paths);
    }


    /**
     * @deprecated
     *
     * @return array
     */
    public function all(): array
    {
        return $this->asArray();
    }

    /**
     * {@inheritDoc}
     */
    public function asArray(): array
    {
        return $this->get('');
    }

     /**
     * {@inheritDoc}
     */
    public function merge(array $data): self
    {
        $this->config = array_replace_recursive($this->config, $data);

        return $this;
    }

    /**
     * @deprecated
     * {@inheritDoc}
     */
    public function mergeFrom(array $values):void
    {
        $this->config = array_replace_recursive($this->config, $values);
    }

    /**
     * {@inheritDoc}
     */
    public function pushState(): self
    {
        array_push($this->state, $this->config);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function popState(): self
    {
        if (null !== $state = array_pop($this->state)) {
            $this->config = $state;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->config = [];
        $this->state = [];
//        $this->cache = [];
    }
    
    /**
     * Split the path string by a separator. Default is @see const PATH_DEFAULT_SEPARATOR
     * Separator will be ignored inside double quotes.
     * e.g. `"11.2".3.5."another.key"` equals to an array access like $array["11.2"]["3"]["5"]["another.key"]
     *
     * @param string $path the Path string
     * 
     * @return array
     */
    protected function splitPath(string $path): array
    {
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
        return implode(self::PATH_SEPARATOR, $paths);
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