<?php
namespace Ctl\Config;

class Config implements ConfigInterface
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
     * The constructor
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->setData($config);
    }

    /**
     * {@inheritDoc}
     */
    public function setData(array $data): void
    {
        $this->config = $data;
    }

    /**
     * {@inheritDoc}
     */
    public function withData(array $data): ConfigInterface
    {
        $config = clone $this;
        $config->reset();
        $config->setData($data);

        return $config;
    }

    /**
     * {@inheritDoc}
     */
    public function withPath(string ...$paths)
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
        $path = implode(self::PATH_SEPARATOR, $paths);

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

    /**
     * {@inheritDoc}
     */
    public function has(string ...$paths): bool
    {
        $value = $this->get(...$paths);
        return null !== $value;
    }


    /**
     * Get all as array
     *
     * @return array
     */
    public function all(): array
    {
        return $this->get('');
    }

    /**
     * {@inheritDoc}
     */
    public function mergeFrom(array $values):void
    {
        $this->config = array_replace_recursive($this->config, $values);
    }

    /**
     * {@inheritDoc}
     */
    public function pushState(): void
    {
        array_push($this->state, $this->config);
    }

    /**
     * {@inheritDoc}
     */
    public function popState(): void
    {
        if (null !== $state = array_pop($this->state)) {
            $this->config = $state;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->config = [];
        $this->state = [];
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