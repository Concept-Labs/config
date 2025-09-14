<?php

namespace Concept\Config;

use Concept\Arrays\DotArray\DotArrayInterface;
use Concept\Arrays\RecursiveDotApi;
use Concept\Config\Storage\Storage;
use Concept\Config\Storage\StorageInterface;
use Concept\Config\Context\ContextInterface;
use Concept\Config\Context\Context;
use Concept\Config\Resource\ResourceInterface;
use Concept\Config\Resource\Resource;
use Concept\Config\Parser\ParserInterface;
use Concept\Config\Parser\ResolvableInterface;
use Concept\Config\Parser\ParserFactory;

class Config implements ConfigInterface
{

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
     * @var ResourceInterface
     */
    private ?ResourceInterface $resource = null;

    /**
     * The parser
     * 
     * @var ParserInterface
     */
    private ?ParserInterface $parser = null;

    /**
     * Constructor
     * 
     * @param array $data
     * @param array $context
     */
    public function __construct(array $data = [], array $context = [])
    {
        $this->configStorage = new Storage($data);
        $this->context = (new Context($context))->withEnv(getenv());

        $this->init();
    }

    public function __clone()
    {
        $this->configStorage = clone $this->configStorage;
        $this->context = clone $this->context;
        $this->resource = null; // Reset resource to ensure a new instance is created
        $this->parser = null; // Reset parser to ensure a new instance is created
    }

    public function reset(): static
    {
        $this->configStorage->reset();
        $this->context = new Context();
        $this->resource = null;
        $this->parser = null;

        return $this;
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
    public function dotArray(): DotArrayInterface
    {
        return $this->getStorage();
    }

    /**
     * Get the storage
     * 
     * @return StorageInterface
     */
    protected function getStorage(): StorageInterface
    {
        return $this->configStorage;
    }

    /**
     * Initialize the config
     * 
     * @return static
     */
    protected function init(): static
    {
        return $this;
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
                $current = $current();
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

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function import(string|array|ConfigInterface $source, bool $parse = false): static
    {
        if ($source instanceof ConfigInterface) {
            $source = $source->toArray();
        }

        $importData = [];

        $this->getResource()->read($importData, $source, $parse);

        $this->getStorage()->replace($importData);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function importTo(string|array|ConfigInterface $source, string $path, bool $parse = false): static
    {
        if ($source instanceof ConfigInterface) {
            $source = $source->toArray();
        }

        $importData = [];

        $this->getResource()->read($importData, $source, $parse);

        $this->getStorage()->replace($importData, $path);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function export(string $target): static
    {
        $this->getResource()
            ->write(
                $target, $this->getStorage()->toArray()
            );

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
        return $this->resource ??= new Resource($this);
    }

    /**
     * {@inheritDoc}
     */
    public function getParser(): ParserInterface
    {
        return $this->parser ??= ParserFactory::create($this);
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): \Traversable
    {
        return $this->getStorage()->getIterator();
    }
}

