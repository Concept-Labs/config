<?php

namespace Concept\Config;

use Concept\Arrays\RecursiveDotApi;
use Concept\Config\Context\Context;
use Concept\Config\Context\ContextInterface;

class Factory
{
    private ?ContextInterface $context = null;
    private array $sources = [];
    private array $configOverrides = [];
    private array $plugins = [];
    private bool $doParse = true;

    /**
     * Reset the factory to its initial state
     *
     * @return static
     */
    public function reset(): static
    {
        $this->context = null;
        $this->sources = [];
        $this->configOverrides = [];
        $this->plugins = [];
        $this->doParse = true;

        return $this;
    }

    /**
     * Create a new Config instance
     *
     * @return ConfigInterface
     */
    public function create(): ConfigInterface
    {
        $config = new Config();

        $config->withContext($this->getContext());

        //with plugins
        foreach ($this->plugins as $priority => $plugins) {
            foreach ($plugins as $plugin) {
                $config->getParser()->registerPlugin($plugin, $priority);
            }
        }

        //with sources
        foreach ($this->sources as $source) {
            $config->import($source, $this->doParse);
        }

        //with overrides
        $config->import($this->configOverrides, $this->doParse);

        return $config;
    }

    /**
     * Export the configuration to a file
     *
     * @param string $target
     * @return static
     */
    public function export(string $target): static
    {
        $this->create()->export($target);

        return $this;
    }

    /**
     * Get the context
     *
     * @return ContextInterface
     */
    public function getContext(): ContextInterface
    {
        return $this->context ??= new Context();
    }

    /**
     * Set the context
     *
     * @param ContextInterface|array $context
     * @return static
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
     * Add a file to the sources
     *
     * @param string $file
     * @return static
     */
    public function withFile(string $file): static
    {
        $this->sources[] = $file;

        return $this;
    }

    /**
     * Add a glob pattern to the sources
     *
     * @param string $pattern
     * @return static
     */
    public function withGlob(string $pattern): static
    {
        $this->sources = array_merge($this->sources, glob($pattern));

        return $this;
    }

    /**
     * Add configuration overrides
     *
     * @param array $overrides
     * @return static
     */
    public function withOverrides(array $overrides): static
    {
        RecursiveDotApi::replace($this->configOverrides, $overrides);

        return $this;
    }

    /**
     * Add a plugin to the factory
     *
     * @param string|callable $plugin
     * @param int $priority
     * @return static
     */
    public function withPlugin(string|callable $plugin, int $priority = 0): static
    {
        $this->plugins[$priority] ??= [];

        $this->plugins[$priority][] = $plugin;

        return $this;
    }

    /**
     * Enable or disable parsing of the configuration files
     *
     * @param bool $parse
     * @return static
     */
    public function withParse(bool $parse = true): static
    {
        $this->doParse = $parse;

        return $this;
    }
   
}