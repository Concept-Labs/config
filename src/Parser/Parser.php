<?php
namespace Concept\Config\Parser;

use Concept\Config\Parser\Plugin\PluginInterface;
use Concept\Arrays\RecursiveDotApi;
use Concept\Config\ConfigInterface;
use Concept\Config\Parser\Exception\InvalidArgumentException;

use Generator;

/**
 * Configuration parser with plugin system
 * 
 * This class processes configuration data through a middleware-style plugin
 * system. Plugins can be registered with priorities to control execution order.
 * The parser maintains separation of concerns by optionally accepting a
 * ConfigInterface for plugins that need access to the full configuration context.
 */
class Parser implements ParserInterface
{
    /**
     * The plugins
     * 
     * @var array<int, array<int, PluginInterface|callable>>
     */
    private array $plugins = [];

    /**
     * The plugin middleware stack
     * 
     * @var callable|null
     */
    private $pluginMiddlewareStack = null;

    /**
     * Track parse depth to prevent premature lazy resolver processing
     * 
     * @var int
     */
    private int $parseDepth = 0;

    /**
     * Optional config instance for plugins that need access to config
     * 
     * @var ConfigInterface|null
     */
    private ?ConfigInterface $config = null;

    /**
     * Constructor
     * 
     * @param ConfigInterface|null $config Optional config instance for plugins that need it
     */
    public function __construct(?ConfigInterface $config = null)
    {
        $this->config = $config;
    }

    /**
     * Set the config instance
     * 
     * This allows setting the config after construction, useful for breaking
     * circular dependencies during object creation.
     * 
     * @param ConfigInterface $config The config instance
     * 
     * @return static
     */
    public function setConfig(ConfigInterface $config): static
    {
        $this->config = $config;
        
        return $this;
    }

    /**
     * Get the config instance
     * 
     * @return ConfigInterface|null The config instance or null if not set
     */
    protected function getConfig(): ?ConfigInterface
    {
        return $this->config;
    }

    /**
     * {@inheritDoc}
     */
    public function getParseDepth(): int
    {
        return $this->parseDepth;
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
    public function parse(array &$data): static
    {
        $this->parseDepth++;
        
        try {
            
            $this->parseNode($data, null, $data);
            
        } finally {
            $this->parseDepth--;
        }

        // After parsing is complete at top level, resolve lazy resolvers
        if ($this->parseDepth === 0 && $this->config !== null) {
            $this->config->resolveLazy();
        }

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

            if (is_array($value) ) {
                $this->parseNode($value, $curPath, $subjectData);
            }

            $value = //new Resolver(
                //fn () => 
                $pluginStack($value, $curPath, $subjectData)
            //)

            ;

            if (ParserInterface::ABANDONED_NODE === $value) {
                RecursiveDotApi::unset($node, $key);
                continue;
            }

        }

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
     * Creates a plugin instance from a class name. If the parser has a config
     * instance, it will be passed to plugins that accept it.
     * 
     * @param string $plugin The plugin class name
     * 
     * @return PluginInterface The instantiated plugin
     * @throws InvalidArgumentException If the plugin class is invalid
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
        
        // Pass config to plugin if available, otherwise create without it
        return $this->config !== null 
            ? new $plugin($this->config)
            : new $plugin();
    }
   
}