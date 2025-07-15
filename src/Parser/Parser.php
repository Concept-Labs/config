<?php
namespace Concept\Config\Parser;

use Concept\Config\Parser\Plugin\PluginInterface;
use Concept\Arrays\RecursiveDotApi;
use Concept\Config\ConfigInterface;
use Concept\Config\Parser\Exception\InvalidArgumentException;

use Generator;

class Parser implements ParserInterface
{
    /**
     * The plugins
     * 
     * @var array<int, array<string, PluginInterface|callable>>
     */
    private array $plugins = [];

    /**
     * The plguin middleware stack
     * 
     * @var callable
     */
    private $pluginMiddlewareStack = null;

    /**
     * The post directives
     * 
     * @var callable[]
     */
    private array $postDirectivesQueue = [];

    public function __construct(private ConfigInterface $config)
    {
    }

    /**
     * Get the config
     * 
     * @return ConfigInterface
     */
    protected function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    /**
     * {@inheritDoc}
     */
    public function registerPlugin(PluginInterface|callable|string $plugin, int $priority = 0): static
    {
        /**
         * Reset the plugin middleware stack
         */
        $this->pluginMiddlewareStack = null;

        $this->plugins[$priority] ??= [];

        $plugin = 
            is_string($plugin) && !is_callable($plugin)
                ? $this->createPlugin($plugin)
                : $plugin;

        $this->plugins[$priority][] = $plugin;

        krsort($this->plugins[$priority]);

        /**
         * Sort the plugins by priority
         */
        ksort($this->plugins);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getPlugin(string $plugin): PluginInterface|callable
    {
        foreach ($this->plugins as $plugins) {
            if (isset($plugins[$plugin])) {
                return $plugins[$plugin];
            }
        }

        throw new \InvalidArgumentException("Plugin {$plugin} not found");
    }

    

    /**
     * {@inheritDoc}
     */
    public function parse(array &$data, bool $resolveNow = true): static
    {
        $this->preProcess($data);

        /**
         * Lazy processing
         * Plugins may modify the data in place
         * e.g. 
         *  load a file and merge it with the data
         *  remove/add/merge data from external sources
         */
        $this->parseNode($data, null, $data);

        /**
         * Resolve the data after all plugins have been applied
         */
        if ($resolveNow) {
            $this->resolve($data, $data);
        }

        $this->postProcess($data);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function prepare(array &$data): static
    {
        $this->parse($data, false);

        return $this;
    }

    /**
     * Plug a node
     * 
     * @param array $node     The node to plug
     * @param array $dataRef  The reference to the data
     * @param string $path    The path of the node
     * 
     * @return static
     */
    protected function parseNode(
        array &$node,
        ?string $path,
        array &$subjectData
    ): static
    {
        $pluginStack = $this->getCallStack();

        foreach ($node as $key => &$value) {
            $curPath = (null !== $path ) ? "{$path}.{$key}" : $key;


            if (
                is_array($value) 
                //&& !$this->isDirective($key)
            ) {
                $this->parseNode($value, $curPath, $subjectData);
                //continue;
            }


            $value = //new Resolver(
                //fn () => 
                $pluginStack($value, $curPath, $subjectData)
            //)
            ;

            if (ParserInterface::VALUE_TO_REMOVE === $value) {
                RecursiveDotApi::unset($node, $key);
                continue;
            }
            
        }

        return $this;
    }

    /**
     * Check if the key is a directive
     * 
     * @param string $key
     * 
     * @return bool
     */
    protected function isDirective(string $key): bool
    {
        return str_starts_with($key, '@');
    }

   /**
    * {@inheritDoc}
    */
    public function resolve(array &$data): static
    {
/**
 @todo ??? 
 * */        
return $this;

        /**
         * Use the RecursiveDotApi::walk() 
         * to provide the dot-path to node instead of the simple key
         */
        RecursiveDotApi::walk(
            $data,
            $path ?? '',
            function (mixed &$value, string $path) use (&$data) {
                while ($value instanceof ResolvableInterface) {
                    $value = $value();
                }

                if (ParserInterface::VALUE_TO_REMOVE === $value) {
                    RecursiveDotApi::unset($data, $path);
                }
            }
        );

        //$this->postProcess($data);

        return $this;
    }

    /**
     * Pre-process the data
     * 
     * @param array $data
     * 
     * @return static
     */
    protected function preProcess(array &$data): static
    {
        return $this;
    }

    /**
     * Post-process the data
     * 
     * @param array $data
     * 
     * @return static
     */
    protected function postProcess(array &$data): static
    {
        
        foreach ($this->postDirectivesQueue as $directive) {
            $directive();
        }

        $this->postDirectivesQueue = [];

        return $this;
    }

    /**
     * Get the call stack
     * 
     * @return callable
     */
    protected function getCallStack(): callable
    {
        return $this->pluginMiddlewareStack ??= $this->aggregateCallStack();
    }

    /**
     * Get the call stack
     * 
     * @return callable
     */
    protected function aggregateCallStack(): callable
    {
        $plugins = $this->getPlugins();

        return 
            array_reduce(
                iterator_to_array($plugins),
                fn ($next, $plugin) =>
                    fn ($value, $path, &$subjectData) =>
                        $plugin($value, $path, $subjectData, $next),
                fn ($value) => $value
            );
    }

     /**
     * Get sorted plugins
     * 
     * @return Generator
     */
    protected function getPlugins(): Generator
    {
        foreach ($this->plugins as $priority => &$plugins) {
            foreach ($plugins as $plugin) {
                yield $plugin;
            }
        }
    }

    /**
     * Instantiate a plugin
     * 
     * @param string $plugin
     * 
     * @return PluginInterface
     * @throws InvalidArgumentException
     */
    protected function createPlugin(string $plugin): PluginInterface
    {
        if (!class_exists($plugin) || !is_subclass_of($plugin, PluginInterface::class)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid plugin class %s. Must implement %s',
                    $plugin,
                    PluginInterface::class
                )
            );
        }
        return new $plugin($this->getConfig());
    }
   
}