<?php
namespace Concept\Config;

use Psr\SimpleCache\CacheInterface;
use Concept\Config\Adapter\Adapater;
use Concept\Config\Adapter\Export\ExportAdapter;
use Concept\Config\Cache\LRUCache;
use Concept\Config\PathAccess\PathAccessTrait;
use Concept\Config\Plugin\Middleware\ContextVariablePlugin;
use Concept\Config\Plugin\PluginManager;
use Concept\Config\Plugin\PluginManagerInterface;
use ReflectionClass;

class Config implements ConfigInterface
{
    use PathAccessTrait;

    /**
      @debug: remove
     */
    static $instances = [];


    /**
     * Storage
     *
     * @var array
     */
    protected array $data = [];

    private array $context = [];

    private ?PluginManagerInterface $pluginManager = null;

    //protected array $loaded = [];

    private ?string $staticDir = null;


    protected ?CacheInterface $cache = null;
    protected int $cacheSize = 1000;

    /**
     * States backup stack.
     * @todo: save to file/db etc.
     *
     * @var array<array>
     */
    protected array $state = [];

    /**
     * The created from path
     * Stores the path of the node that was created from
     * 
     * @var array
     */
    private array $createdFromPath = [];

    /**
     * Chache
     * 
     * @var array
     */
    //protected array $cache = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
        /**
         @todo remove debug
         */
        //static::$instances[] = \WeakReference::create($this);


        $this->getPluginManager()->add(
            new ContextVariablePlugin($this)
        );

    }

    public function export(string $path): static
    {
        ExportAdapter::export(
            $this->compile(),
            $path
        );

        return $this;
    }


    /**
     * {@inheritDoc}
     */
    public function reset(): static
    {
        $this->data = [];
        $this->state = [];
        //$this->loaded = [];
        return $this;
    }

    
    private function getCache(): ?CacheInterface
    {
        
        return $this->cache;// ??= new LRUCache($this->cacheSize);
    }

    /**
     * @deprecated
     * {@inheritDoc}
     */
    public function load(mixed $source, bool $merge = true): static
    {
        $data = Adapater::load($source);

        if ($merge) {
            $this->merge($data);
        } else {
            $this->data = $data;
        }

        //$this->getPluginManager()->afterLoad($source, $data);

        return $this;

    }

    /**
     * Check if the config file is already loaded
     * 
     * @param string $path
     * 
     * @return bool
     */
    // protected function isLoaded(string $path): bool
    // {
    //     return in_array($path, $this->loaded);
    // }

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

    /**
     * {@inheritDoc}
     */
    public function setContext(array $context): static
    {
        $this->context = $context;

        return $this;
    
    }

    /**
     * {@inheritDoc}
     */
    public function addContext(array $context): static
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getContext(?string $path = null): mixed
    {
        if (null === $path) {
            return $this->context;
        }

        return $this->context[$path] ?? null;
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
     * Get the plugin manager
     * 
     * @return PluginManagerInterface
     */
    protected function getPluginManager(): PluginManagerInterface
    {
        return $this->pluginManager ??= new PluginManager($this);
    }

    /**
     * Merge data
     * 
     * @param array $data
     * 
     * @return array
     */
    protected function compile(?string $path = null): array
    {
        $data = $this->data;

        if (null !== $path) {
            $data = $this->get($path);
        }

        return $this->compileNode($data);
    }

    /**
     * Merge data
     * 
     * @param array $data
     * 
     * @return array
     */
    protected function compileNode(array $node): array
    {
        $data = [];

        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->compileNode($value);
            } else {
                $data[$key] = $this->getPluginManager()->process($key, $value);
            }
        }

        return $data;
    }

    /**
     * Autoload the config files
     * 
     * @param string[] $autoloads
     * @param string $basePath
     * 
     * @return static
     */
    // protected function autoload(array $autoloads, string $basePath): static
    // {
    //     foreach ($autoloads as $pattern) {

    //         if (strpos($pattern, DIRECTORY_SEPARATOR) !== 0) {
    //             /**
    //              * Prepend the base path if the pattern is not absolute
    //              */
    //             $pattern = $basePath . DIRECTORY_SEPARATOR . $pattern;
    //         }

    //         foreach (glob($pattern) as $path) {
    //             $this->load($path);
    //         }
            
    //     }

    //     return $this;
    // }

}