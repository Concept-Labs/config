<?php

namespace Concept\Config\Resource;

use Concept\Arrays\RecursiveDotApi;
use Concept\Config\Contract\ParserProviderInterface;
use Concept\Config\Parser\ParserInterface;
use Concept\Config\Resource\Exception\InvalidArgumentException;
use Throwable;

/**
 * Resource handler for configuration I/O operations
 * 
 * This class is responsible for reading and writing configuration data
 * from various sources using registered adapters. It maintains separation
 * of concerns by accepting dependencies through constructor injection.
 */
class Resource implements ResourceInterface
{
    /**
     * Stack of sources being processed (for circular reference detection)
     * 
     * @var array<int, string>
     */
    private array $sourceStack = [];

    /**
     * Optional parser provider for parsing configuration data
     * 
     * @var ParserProviderInterface|null
     */
    private ?ParserProviderInterface $parserProvider = null;

    /**
     * Constructor with dependency injection
     * 
     * @param AdapterManagerInterface $adapterManager The adapter manager for handling different file formats
     */
    public function __construct(
        private AdapterManagerInterface $adapterManager
    ) {
    }

    /**
     * Set the parser provider
     * 
     * This allows the resource to access a parser when needed for parsing
     * configuration data during read operations.
     * 
     * @param ParserProviderInterface $parserProvider The parser provider
     * 
     * @return static
     */
    public function setParserProvider(ParserProviderInterface $parserProvider): static
    {
        $this->parserProvider = $parserProvider;
        
        return $this;
    }

    /**
     * Get the parser from the parser provider
     * 
     * @return ParserInterface|null The parser instance or null if no provider is set
     */
    protected function getParser(): ?ParserInterface
    {
        return $this->parserProvider?->getParser();
    }

    /**
     * {@inheritDoc}
     */
    public function read(array &$data, string|array $source, bool $withParser = true): static
    {
        $fragment = false;

        if (is_array($source)) {
       
            $data = array_merge($data, $source); //copy array data

            return $this;

        } 

        /**
         * Handle URL fragments for nested imports
         */
        $fragment = parse_url($source, PHP_URL_FRAGMENT);

        /**
         * Get the absolute path of the source
         */
        $source = $this->absolutePath(strtok($source, '#'));

        /**
         * Detect circular references
         */
        if ($this->hasSource($source)) {
            throw new InvalidArgumentException('Circular reference detected');
        }

        /**
         * Push the source to the stack
         */
        $this->pushSource($source);

        try {
            /**
             * Read the source using the appropriate adapter
             */
            $data = $this
                ->getAdapter($source)
                ->read($source);

        } catch (Throwable $e) {
            throw new InvalidArgumentException(
                sprintf(
                    'Error reading source: "%s": %s',
                    $source,
                    $e->getMessage()
                )
            );
        }

        if ($withParser && ($parser = $this->getParser())) {
            /**
             * Parse the data using the configured parser
             */
            $parser->parse($data);
        }

        /**
         * Pop the last source
         */
        $this->popSource();

        /**
         * If a fragment is specified, extract that part of the data
         */
        if ($fragment) {
            $data = &RecursiveDotApi::get($data, $fragment);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function write(mixed $target, array $data): static
    {
        //$target = $this->absolutePath($target);

        $this->getAdapter($target)->write($target, $data);

        return $this;
    }

    /**
     * Get the adapter
     * 
     * @param string $uri
     * @return AdapterInterface
     */
    protected function getAdapter(string $uri): AdapterInterface
    {
        return $this->getAdapterManager()->getAdapter($uri);
    }

    /**
     * Get the adapter manager
     * 
     * @return AdapterManagerInterface
     */
    protected function getAdapterManager(): AdapterManagerInterface
    {
        return $this->adapterManager;
    }

    /**
     * Get the absolute path
     * 
     * @param string $source
     * @return string
     */
    protected function absolutePath(string $source): string
    {
        // Handle empty source - throw exception instead of creating invalid path
        if ($source === '') {
            throw new InvalidArgumentException('Source path cannot be empty');
        }
        
        if (
            filter_var($source, FILTER_VALIDATE_URL) 
            || str_starts_with($source, DIRECTORY_SEPARATOR)
        ) {
            return $source;
        }

        // if the source is relative to the cwd
        return $this->cwd() . DIRECTORY_SEPARATOR . $source;
    }

    /**
     * Get the current working directory
     * depends on the last source
     * 
     * @return string
     */
    protected function cwd(): string
    {
        $lastSource = $this->lastSource();
        
        // If no source in stack, return current working directory
        if ($lastSource === '') {
            return getcwd() ?: '.';
        }
        
        return dirname($lastSource);
    }

    /**
     * Push the source to the stack
     * 
     * @param string $source
     * @return void
     */
    protected function pushSource(string $source): void
    {
        $this->sourceStack[] = $source;
    }

    /**
     * Pop the last source
     * 
     * @return void
     */
    protected function popSource(): void
    {
        array_pop($this->sourceStack);
    }

    /**
     * Check if the source is already in the stack
     * 
     * @param string $source
     * @return bool
     */
    protected function hasSource(string $source): bool
    {
        return in_array($source, $this->sourceStack, true);
    }

    /**
     * Get the last source
     * 
     * @return string
     */
    protected function lastSource(): string
    {
        return $this->sourceStack[count($this->sourceStack) - 1] ?? '';

        //return end($this->sourceStack) ?: '';
    }
}
