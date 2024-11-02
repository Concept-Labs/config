<?php
namespace Concept\Config;

use ArrayIterator;
use ReflectionClass;
use Traversable;
use Concept\Config\Exception\InvalidConfigDataException;

class Config implements ConfigInterface
{

    /**
     * Storage
     *
     * @var array
     */
    protected array $config = [];

    protected array $loaded = [];

    /**
     * States backup stack.
     *
     * @var array<array>
     */
    protected array $state = [];

    private ?string $staticDir = null;

    /**
     * Chache
     * 
     * @var array
     */
    //protected array $cache = [];

    const ETC_AUTOLOADS = [
        'etc/config.json',
        'etc/config.local.json',
    ];

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->config = [];
        $this->state = [];
        $this->loaded = [];
//        $this->cache = [];
    }

    /**
     * @deprecated
     * The constructor
     *
     * @param array $config
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initialize the config
     * 
     * @return self
     */
    protected function init(): self
    {
        return $this->etc();
    }

    /**
     * Get the static directory
     * 
     * @return string
     */
    protected function staticDir(): string
    {
        if (null !== $this->staticDir) {
            return $this->staticDir;
        }
        
        return $this->staticDir = dirname((new ReflectionClass(get_called_class()))->getFileName());
    }

    /**
     * Load the config files from etc directory
     * 
     * @return self
     */
    protected function etc(): self
    {
       
        return $this->autoload(static::ETC_AUTOLOADS, $this->staticDir());
    }

    /**
     * Autoload the config files
     * 
     * @param string[] $autoloads
     * @param string $basePath
     * 
     * @return self
     */
    protected function autoload(array $autoloads, string $basePath): self
    {
        foreach ($autoloads as $pattern) {

            if (strpos($pattern, DIRECTORY_SEPARATOR) !== 0) {
                /**
                 * Prepend the base path if the pattern is not absolute
                 */
                $pattern = $basePath . DIRECTORY_SEPARATOR . $pattern;
            }

            foreach (glob($pattern) as $path) {
                $this->load($path);
            }
            
        }

        return $this;
    }

    /**
     * Check if the config file can be supported
     * 
     * @param string $path
     * 
     * @return bool
     */
    protected function canSupport(string $path): bool
    {
        return pathinfo($path, PATHINFO_EXTENSION) === 'json';
    }

    /**
     * {@inheritDoc}
     */
    public function load(string $path): self
    {
        if ($this->isLoaded($path)) {
            return $this;
        }

        $dataArray = [];

        if (!file_exists($path)) {
            throw new InvalidConfigDataException(sprintf('Config file not found: %s', $path));
        }

        if (!is_readable($path)) {
            throw new InvalidConfigDataException(sprintf('Config file not readable: %s', $path));
        }

        if (!$this->canSupport($path)) {
            throw new InvalidConfigDataException(sprintf('Unsupported config file: %s', $path));
        }

        try {

            $dataArray = json_decode(file_get_contents($path), true, JSON_THROW_ON_ERROR);

            if (!is_array($dataArray)) {
                throw new InvalidConfigDataException(sprintf('Invalid config file: %s', $path));
            }

            $this->autoload($dataArray['autoload'] ?? [], dirname($path));

            unset($dataArray['autoload']);

        } catch (\JsonException $e) {
            throw new InvalidConfigDataException(sprintf('Error loading config file %s: %s', $path, $e->getMessage()));
        }

        $this->merge($dataArray);

        $this->loaded[] = $path;

        return $this;
    }

    protected function isLoaded(string $path): bool
    {
        return in_array($path, $this->loaded);
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
    public function setData(?array $data = null): self
    {
        $this->config = $data ?? [];

        return $this;
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

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
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
    public function asJson(): string
    {
        return json_encode($this->asArray(), JSON_PRETTY_PRINT);
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
        $this->config = $this->merge($values);
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
            preg_quote(static::PATH_SEPARATOR)
        );
    }
}