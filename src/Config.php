<?php

namespace Concept\Config;

use Concept\Arrays\RecursiveApi;
use Concept\Arrays\RecursiveDotApi;
use Concept\Config\Storage\Storage;
use Concept\Config\Storage\StorageInterface;
use Concept\Config\Context\ContextInterface;
use Concept\Config\Context\Context;
use Concept\Config\Resource\ResourceInterface;
use Concept\Config\Resource\Resource;
use Concept\Config\Parser\ParserInterface;
use Concept\Config\Parser\ResolvableInterface;
use Concept\Config\Contract\ParserProviderInterface;
use Concept\Config\Factory\StorageFactoryInterface;
use Concept\Config\Factory\DefaultStorageFactory;
use Concept\Config\Factory\ResourceFactoryInterface;
use Concept\Config\Factory\DefaultResourceFactory;
use Concept\Config\Factory\ParserFactoryInterface;
use Concept\Config\Factory\DefaultParserFactory;

/**
 * Main configuration class
 * 
 * This class orchestrates configuration management by coordinating between
 * storage, parser, resource, and context components. It follows SOLID principles
 * by accepting dependencies through factory interfaces, enabling flexible
 * component replacement while maintaining backward compatibility.
 */
class Config implements ConfigInterface, ParserProviderInterface
{
    static int $nInstances = 0;

    /**
     * The storage
     * 
     * @var StorageInterface
     */
    private StorageInterface $configStorage;

    /**
     * The context
     * 
     * @var ContextInterface
     */
    private ?ContextInterface $context = null;

    /**
     * The resource
     * 
     * @var ResourceInterface|null
     */
    private ?ResourceInterface $resource = null;

    /**
     * The parser
     * 
     * @var ParserInterface|null
     */
    private ?ParserInterface $parser = null;

    /**
     * The lazy resolvers
     * 
     * @var array<int, ResolvableInterface>
     */
    private array $lazyResolvers = [];

    /**
     * Factory for creating storage instances
     * 
     * @var StorageFactoryInterface
     */
    private StorageFactoryInterface $storageFactory;

    /**
     * Factory for creating resource instances
     * 
     * @var ResourceFactoryInterface
     */
    private ResourceFactoryInterface $resourceFactory;

    /**
     * Factory for creating parser instances
     * 
     * @var ParserFactoryInterface
     */
    private ParserFactoryInterface $parserFactory;

    /**
     * Constructor with optional dependency injection
     * 
     * Accepts data and context for immediate configuration, plus optional
     * factory instances for creating internal components. This design follows
     * the Dependency Inversion Principle while maintaining backward compatibility.
     * 
     * @param array $data Initial configuration data
     * @param array $context Initial context data
     * @param StorageFactoryInterface|null $storageFactory Optional factory for creating storage
     * @param ResourceFactoryInterface|null $resourceFactory Optional factory for creating resources
     * @param ParserFactoryInterface|null $parserFactory Optional factory for creating parsers
     */
    public function __construct(
        array $data = [],
        array $context = [],
        ?StorageFactoryInterface $storageFactory = null,
        ?ResourceFactoryInterface $resourceFactory = null,
        ?ParserFactoryInterface $parserFactory = null
    ) {
        self::$nInstances++;
        
        // Use provided factories or create defaults
        $this->storageFactory = $storageFactory ?? new DefaultStorageFactory();
        $this->resourceFactory = $resourceFactory ?? new DefaultResourceFactory();
        $this->parserFactory = $parserFactory ?? new DefaultParserFactory($this);
        
        // Set config reference for parser factory if it supports it
        if ($this->parserFactory instanceof DefaultParserFactory) {
            $this->parserFactory->setConfig($this);
        }
        
        // Create storage and context using factories
        $this->configStorage = $this->storageFactory->create($data);
        $this->context = (new Context($context))->withEnv(getenv());
    }

    /**
     * Clone the config instance
     * 
     * Creates a deep copy of the configuration with cloned storage and context.
     * Resource and parser instances are not cloned to avoid state duplication.
     */
    public function __clone()
    {
        $this->configStorage = clone $this->configStorage;
        $this->context = clone $this->context;
        $this->resource = null;
        $this->parser = null; 
        $this->lazyResolvers = [];
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): static
    {
        $this->configStorage->reset();
        $this->context->reset();
        $this->resource = null; 
        $this->parser = null; 
        $this->lazyResolvers = [];

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(string $path)
    {
        return $this->get($path);
    }

    /**
     * {@inheritDoc}
     */
    public function prototype(): static
    {
        return (clone $this)->reset();
    }

    /**
     * {@inheritDoc}
     */
    public static function fromArray(array $data, array $context = []): static
    {
        return new static($data, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function &dataReference(): array
    {
        return $this->getStorage()->reference();
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(array $data): static
    {
        $this->getStorage()->hydrate($data);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withContext(ContextInterface|array $context): static
    {
        $this->getContext()->replace(
            ($context instanceof ContextInterface)
                ? $context->toArray()
                : $context
        );

        return $this;
    }
    
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->getStorage()->toArray();
    }

    /**
     * {@inheritDoc}
     */
    // public function dotArray(): DotArrayInterface
    // {
    //     return $this->getStorage();
    // }

    /**
     * {@inheritDoc}
     */
    public function query(string $query): mixed
    {
        return $this->getStorage()->query($query);
    }

    /**
     * Get the storage instance
     * 
     * Returns the internal storage object that manages configuration data
     * using dot notation access patterns.
     * 
     * @return StorageInterface The storage instance
     */
    protected function getStorage(): StorageInterface
    {
        return $this->configStorage;
    }

    /**
     * {@inheritDoc}
     */
    public function node(string $path, bool $copy = true): static
    {
        $dotNode = $this->getStorage()->node($path, $copy);

        return new static($dotNode->toArray(), $this->getContext()->toArray());
    }
    

    /**
     * {@inheritDoc}
     */
    public function &get(string $path, mixed $default = null): mixed
    {
        $current = &$this->getStorage()->reference();
        $path = trim($path, '.');
        foreach (RecursiveDotApi::path($path) as $key) {

            if (!is_array($current) || !array_key_exists($key, $current)) {
                $defaultValue = $default;
                return $defaultValue;
            }

            $current = &$current[$key];

            /**
             * If the current value is a ResolvableInterface, resolve it
             */
            while ($current instanceof ResolvableInterface) {
                $current = $current($this);
            }
        }

        return $current;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $path, mixed $value): static
    {
        $this->getStorage()->set($path, $value);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $path): bool
    {
        return $this->getStorage()->has($path);
    }

    /**
     * {@inheritDoc}
     */
    public function load(string|array|ConfigInterface $source, bool $parse = false): static
    {
        if ($source instanceof ConfigInterface) {
            $source = $source->toArray();
        }

        $this->getStorage()->reset();

        $this->getResource()->read(
            $this->getStorage()->reference(),
            $source, 
            $parse
        );

        $this->resolveAll();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function import(string|array|ConfigInterface $source): static
    {
        if ($source instanceof ConfigInterface) {
            $source = $source->toArray();
        }

        $importData = [];

        $this->getResource()->read($importData, $source);

        $this->getStorage()->replace($importData);

        $this->resolveAll();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function importTo(string|array|ConfigInterface $source, string $path): static
    {
        if ($source instanceof ConfigInterface) {
            $source = $source->toArray();
        }

        $importData = [];

        $this->getResource()->read($importData, $source);


        $this->getStorage()->replace($importData, $path);

        $this->resolveAll();

        return $this;
    }

    /**
     * Resolve all configuration values
     *
     * Processes all lazy resolvers and walks through the configuration
     * data to resolve any ResolvableInterface instances. This ensures
     * all deferred resolutions are completed.
     *
     * @return static The config instance for method chaining
     */
    protected function resolveAll(): static
    {
        $this->processLazyResolvers();
        
        $this->resolveAllValues();

        return $this;
    }
    
    /**
     * Process @extends directives in the configuration
     * 
     * Walks through the configuration and merges nodes that have @extends directives
     * with their base nodes. This is done after initial resolution to ensure
     * referenced nodes exist and have been resolved.
     * 
     * @param array &$data The data array to process
     * 
     * @return static The config instance for method chaining
     */
    protected function processExtendsDirectives(array &$data): static
    {
        $this->walkExtendsNodes($data);
        return $this;
    }
    
    /**
     * Recursively walk through data and process @extends directives
     * 
     * @param array &$node The current node being processed
     * 
     * @return void
     */
    protected function walkExtendsNodes(array &$node): void
    {
        // First, recursively process all child nodes
        foreach ($node as $key => &$value) {
            if (is_array($value)) {
                $this->walkExtendsNodes($value);
            }
        }
        
        // Then check if this node has @extends directive
        if (isset($node['@extends'])) {
            $extendsPath = $node['@extends'];
            
            // Get the base data to extend from
            $extendsData = $this->get($extendsPath);
            
            if ($extendsData === null) {
                throw new \InvalidArgumentException(sprintf(
                    'The path "%s" does not exist in the configuration.',
                    $extendsPath
                ));
            }
            
            if (!is_array($extendsData)) {
                throw new \InvalidArgumentException(sprintf(
                    'The path "%s" does not point to a valid configuration array.',
                    $extendsPath
                ));
            }
            
            // Copy current node data (excluding @extends)
            $currentData = [];
            foreach ($node as $k => $v) {
                if ($k !== '@extends') {
                    $currentData[$k] = $v;
                }
            }
            
            // Merge: base data first, then current data (current overrides base)
            $mergedData = $extendsData;
            RecursiveApi::merge(
                $mergedData,
                $currentData,
                RecursiveApi::MERGE_OVERWRITE
            );
            
            // Replace the node with merged data
            $node = $mergedData;
        }
    }

    /**
     * Walk through data and resolve ResolvableInterface instances
     *
     * Recursively walks through the configuration data array and resolves
     * any values that implement ResolvableInterface by calling them with
     * the config instance.
     *
     * @param array &$data The data array to walk through
     * 
     * @return static The config instance for method chaining
     */
    protected function walkResolve(array &$data): static
    {
        RecursiveApi::walk(
            $data,
            function (&$value) {
                while ($value instanceof ResolvableInterface) {
                    $value = $value($this);
                }
            }
        );

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function export(string $target): static
    {

        $this->walkResolve($this->getStorage()->reference());

        $this->getResource()
            ->write(
                $target, $this->getStorage()->toArray()
            );

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addLazyResolver(ResolvableInterface $resolver): static
    {
        $this->lazyResolvers[] = $resolver;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveLazy(): static
    {
        return $this->processLazyResolvers();
    }
    
    /**
     * Resolve all configuration values including extends directives
     * 
     * This is a public method that can be called after parsing to ensure
     * all Resolvable values and @extends directives are processed.
     * 
     * @return static The config instance for method chaining
     */
    public function resolveAllValues(): static
    {
        // First pass: resolve all ResolvableInterface instances
        $this->walkResolve($this->getStorage()->reference());
        
        // Second pass: process @extends directives
        $this->processExtendsDirectives($this->getStorage()->reference());
        
        // Third pass: resolve any new ResolvableInterface instances created by extends
        $this->walkResolve($this->getStorage()->reference());
        
        return $this;
    }

    /**
     * Process all pending lazy resolvers
     *
     * Executes all registered lazy resolvers and clears the resolver queue.
     * Lazy resolvers are callables that are deferred until all configuration
     * loading is complete, enabling forward references.
     *
     * @return static The config instance for method chaining
     */
    protected function processLazyResolvers(): static
    {
        foreach ($this->lazyResolvers as $resolver) {
            $resolver($this);
        }

        $this->lazyResolvers = [];

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getContext(): ContextInterface
    {
        return $this->context;
    }

    /**
     * {@inheritDoc}
     */
    public function getResource(): ResourceInterface
    {
        if ($this->resource === null) {
            $this->resource = $this->resourceFactory->create();
            $this->resource->setParserProvider($this);
        }
        
        return $this->resource;
    }

    /**
     * {@inheritDoc}
     */
    public function getParser(): ParserInterface
    {
        return $this->parser ??= $this->parserFactory->create();
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): \Traversable
    {
        return $this->getStorage()->getIterator();
    }
}

