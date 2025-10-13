<?php

namespace Concept\Config\Resource;

use Concept\Arrays\RecursiveDotApi;
use Concept\Config\ConfigInterface;
use Concept\Config\Parser\ParserInterface;
use Concept\Config\Resource\Exception\InvalidArgumentException;
use Concept\Config\Resource\Adapter\JsonAdapter;
use Concept\Config\Resource\Adapter\PhpAdapter;
use Throwable;

class Resource implements ResourceInterface
{
    private array $sourceStack = [];

    private ?AdapterManagerInterface $adapterManager = null;

    /**
     * Dependency injection
     * 
     * @param ConfigInterface $config
     */
    public function __construct(private ConfigInterface $config)
    {
        /**
         @todo: create adapters configuration to be able to register adapters dynamically
         */
        $this->adapterManager = 
            (new AdapterManager())
                ->registerAdapter(JsonAdapter::class)
                ->registerAdapter(PhpAdapter::class)
                //->registerAdapter(YamlAdapter::class)
                //->registerAdapter(IniAdapter::class)
        ;
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
     * Get the parser
     * 
     * @return ParserInterface
     */
    protected function getParser(): ParserInterface
    {
        return $this->getConfig()->getParser();
    }

    /**
     * {@inheritDoc}
     */
    public function read(array &$data, string|array $source, bool $withParser = true): static
    {
        $fragment = false;

        if (is_array($source)) {
        /**
         @todo: parse array ?
        */
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

        if ($withParser && $this->getParser()) {
            /**
             * Parse the data using the configured parser
             */
            $this->getParser()->parse($data);
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
