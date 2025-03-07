<?php
namespace Concept\Config;

use Concept\Config\Adapter\Adapter;
use Psr\SimpleCache\CacheInterface;
use Concept\Config\Adapter\AdapterInterface;
use Concept\Config\Context\Context;
use Concept\Config\Context\ContextInterface;
use Concept\Config\PathAccess\PathAccess;
use Concept\Config\PathAccess\PathAccessInterface;
use Concept\Config\Plugin\ContextPlugin;
use Concept\Config\Plugin\ReferencePlugin;
use Concept\Config\Traits\PluginsTrait;
use ReflectionClass;

class Config extends PathAccess implements ConfigInterface
{
    use PluginsTrait;

    /**
      @debug: remove
     */
    static $instances = [];


    private ?PathAccessInterface $context = null;

    private ?PluginManagerInterface $pluginManager = null;
    private ?AdapterInterface $adapter = null;

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

    protected array $sourceStack = [];

    /**
     * Chache
     * 
     * @var array
     */
    //protected array $cache = [];

    public function __construct(
        array $data = []
        //private AdapterIterface $adapter, ?CacheInterface $cache = null
    )
    {
        $this->data = $data;
        /**
         @todo remove debug
         */
        static::$instances[] = \WeakReference::create($this);


        /**
          @todo: think how to aggregate plugins
         */
        $this->getPluginManager()
            ->add(new ContextPlugin($this))
            ->add(new ReferencePlugin($this))
            //->add(new ExpressionPlugin($this))
            ;

    }

    /**
     * Get the plugin manager
     * 
     * @return PluginManagerInterface
     */
    protected function getPluginManager(): PluginManagerInterface
    {
        
        return $this->pluginManager ??= (new PluginManager($this));
    }

    public function getAdapter(): AdapterInterface
    {
        return $this->adapter ??= new Adapter($this);
    }
    
    private function getCache(): ?CacheInterface
    {
        return $this->cache;// ??= new LRUCache($this->cacheSize);
    }

    /**
     * {@inheritDoc}
     */
    public function load(mixed $source, bool $preProcess = true): static
    {
        $data = $this->getAdapter()->import($source, $preProcess);

        $this->hydrate($data);

        if ($preProcess) {
            //$this->processPlugins();
        }

        return $this;
    }

    public function getSourceSatack(): array
    {
        return $this->sourceStack;
    }

    /**
     * {@inheritDoc}
     */
    public function import(mixed $source, bool $preProcess = true): static
    {
        array_push($this->sourceStack, $source);

        $data = $this->getAdapter()->import($source);

        $this->merge($data);
        
        if ($preProcess) {
            //$this->processPlugins();
        }

        array_pop($this->sourceStack);

        return $this;
    }

    public function importTo(string $path, mixed $source, bool $preProcess = true): static
    {
        $data = $this->getAdapter()->import($source);

        
        $this->mergeTo($path, $data);
        
        if ($preProcess) {
            //$this->processPlugins();
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function export(string $target, bool $preprocess = true): static
    {
        if ($preprocess) {
            //$this->processPlugins();
        }

        $this->getAdapter()->export($target);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $path = '', bool $forceProcess = false ): mixed
    {
        // if ($this->getCache() && $this->getCache()->has($path)) {
        //     return $this->getCache()->get($path);
        // }

        

        // if ($forceProcess || $this->isRuntimeProcess()) {
        //     /**
        //      @todo think about this. how to process the array values
        //      */
        //     if (is_array($value)) {
        //         $this->processNode($value);
        //     } else {
        //         $value = $this->getPluginManager()->process($path, $value, $this);
        //     }
        // }

        // if ($forceProcess || $this->isRuntimeProcess()) {
        //     $this->processNode($path);
        // }

        $value = parent::get($path);

        // if ($this->getCache()) {
        //     $this->getCache()->set($path, $value);
        // }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getRaw(string $path = ''): mixed
    {
        return parent::get($path);
    }

    /**
     * {@inheritDoc}
     * 
     * Process the value before setting it
     */
    public function set(string $path, $value): static
    {
        /**
         * We have to process the value before setting it?
         */
        //$value = $this->getPluginManager()->process($path, $value, $this);

        parent::set($path, $value);

        return $this;
    }

    /**
     * {@inheritDoc}
     * 
     * Merge context if the data is a ConfigInterface
     */
    public function merge(array|PathAccessInterface $data): static {

        parent::merge($data);

        if ($data instanceof ConfigInterface) {
            $this->getContext()->merge($data->getContext());
        }

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

    public function withContext(ContextInterface $context): static
    {
        $this->context = $context;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addContext(array $data): static
    {
        $this->getContext()->merge($data);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getContext(): ContextInterface
    {
        return $this->context ??= new Context();
    }

    /**
     * Merge data
     * 
     * @param array $data
     * 
     * @return array
     */
   

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
    //             $this->import($path);
    //         }
            
    //     }

    //     return $this;
    // }

}