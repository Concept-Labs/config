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
     * 
     */
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
    public function parse(array &$data): static
    {
        $this->parseNode($data, null, $data);

        return $this;
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

            if (ParserInterface::VALUE_TO_REMOVE === $value) {
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