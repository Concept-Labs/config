<?php
namespace Concept\Config;

use ReflectionClass;
use Concept\Config\Exception\InvalidConfigDataException;
use Concept\PathAccess\PathAccess;

class Config extends PathAccess implements ConfigInterface
{
    protected array $loaded = [];
    private ?string $staticDir = null;
    // static private ?string $vendorDir = null;

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
        parent::reset();
        $this->loaded = [];
//        $this->cache = [];
    }

    /**
     * Initialize the config
     * 
     * @return self
     */
    protected function init(): self
    {
        parent::init();

        return $this->autoloadEtc();
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
    protected function autoloadEtc(): self
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

    protected function readJson(string $path): array
    {
        try {
            return json_decode(file_get_contents($path), true, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new InvalidConfigDataException(sprintf('Error loading config file %s: %s', $path, $e->getMessage()));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function loadJsonFile(string $path): self
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

        $dataArray = $this->readJson($path);

        if (!is_array($dataArray)) {
            throw new InvalidConfigDataException(sprintf('Invalid config file: %s', $path));
        }

        $this->autoload($dataArray['autoload'] ?? [], dirname($path));

        unset($dataArray['autoload']);

        $this->merge($dataArray);

        $this->loaded[] = $path;

        return $this;
    }

    /**
     * @deprecated
     * {@inheritDoc}
     */
    public function load(string $path): self
    {
        return $this->loadJsonFile($path);
    }

    /**
     * Check if the config file is already loaded
     * 
     * @param string $path
     * 
     * @return bool
     */
    protected function isLoaded(string $path): bool
    {
        return in_array($path, $this->loaded);
    }
}