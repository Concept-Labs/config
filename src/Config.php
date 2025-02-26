<?php
namespace Concept\Config;

use ReflectionClass;
use Concept\Config\Exception\InvalidConfigDataException;
use Concept\Config\PathAccess\PathAccessTrait;

class Config implements ConfigInterface
{

    use PathAccessTrait;

    /**
     * Storage
     *
     * @var array
     */
    protected array $data = [];

    protected array $loaded = [];
    private ?string $staticDir = null;
    /**
     * States backup stack.
     * @todo: save to file/db etc.
     *
     * @var array<array>
     */
    protected array $state = [];
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
    public function reset(): static
    {
        $this->data = [];
        $this->loaded = [];
        $this->state = [];

        return $this;
    }

    /**
     * Initialize the config
     * 
     * @return static
     */
    protected function init(): static
    {
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
     * @return static
     */
    protected function autoloadEtc(): static
    {
       
        return $this->autoload(static::ETC_AUTOLOADS, $this->staticDir());
    }

    /**
     * Autoload the config files
     * 
     * @param string[] $autoloads
     * @param string $basePath
     * 
     * @return static
     */
    protected function autoload(array $autoloads, string $basePath): static
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
    public function loadJsonFile(string $path): static
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

        // $dataArray = array_replace_recursive(
        //     $this->include(dirname(realpath($path)), $dataArray),
        //     $dataArray
        // );

        if (!is_array($dataArray)) {
            throw new InvalidConfigDataException(sprintf('Invalid config file: %s', $path));
        }

        $this->autoload($dataArray['autoload'] ?? [], dirname($path));

        unset($dataArray['autoload']);

        $this->merge($dataArray);

        $this->loaded[] = $path;

        return $this;
    }

    protected function processIncludes(string $originalPath, array $data): array
    {

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->processIncludes($originalPath, $value);
            }

            if (preg_match('/^@include:(.*)$/', $value, $matches)) {
                $includePath = $matches[1];
                if (strpos($includePath, DIRECTORY_SEPARATOR) !== 0) {
                    $includePath = dirname($originalPath) . DIRECTORY_SEPARATOR . $includePath;
                }
                $data[$key] = $this->readJson($includePath);
            }
        }
        
        return $data;
    }

    /**
     * @deprecated
     * {@inheritDoc}
     */
    public function load(string $path): static
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

     /**
     * {@inheritDoc}
     */
    public function pushState(): static
    {
        array_push($this->state, $this->data);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function popState(): static
    {
        if (null !== $state = array_pop($this->state)) {
            $this->data = $state;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function resetState(): static
    {
        while (null !== $state = array_pop($this->state)) {
            $this->data = $state;
        }

        return $this;
    }

}