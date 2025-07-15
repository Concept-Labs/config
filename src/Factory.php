<?php

namespace Concept\Config;

use Concept\Arrays\RecursiveDotApi;
use Concept\Config\Context\Context;
use Concept\Config\Context\ContextInterface;

class Factory
{
    private ?ConfigInterface $config = null;
    private ?ContextInterface $context = null;
    private array $sources = [];
    private array $configOverrides = [];
    private array $plugins = [];
    private array $composer = [];
    private bool $doParse = true;

    public function reset(): static
    {
        $this->config = null;
        $this->context = null;
        $this->sources = [];
        $this->configOverrides = [];
        $this->plugins = [];
        $this->doParse = true;

        return $this;
    }

    
    public function create(): ConfigInterface
    {
        $config = new Config();

        //with context
        $config->withContext($this->getContext());

        //with plugins
        foreach ($this->plugins as $priority => $plugins) {
            foreach ($plugins as $plugin) {
                $config->getParser()->registerPlugin($plugin, $priority);
            }
        }

        //with sources
        //$data = [];
        foreach ($this->sources as $source) {
            //$config->getResource()->read($data, $source, $this->parse);
            $config->import($source, $this->doParse);
        }
        //$config->hydrate($data);

        //with overrides
        $config->import($this->configOverrides, $this->doParse);

        return $config;
    }

    public function export(string $target): static
    {
        $this->create()->export($target);

        return $this;
    }

    public function getContext(): ContextInterface
    {
        return $this->context ??= new Context();
    }

    public function withContext(ContextInterface|array $context): static
    {
        $this->getContext()->replace(
            ($context instanceof ContextInterface)
                ? $context->toArray()
                : $context
        );

        return $this;
    }


    public function withFile(string $file): static
    {
        $this->sources[] = $file;

        return $this;
    }

    public function withGlob(string $pattern): static
    {
        $this->sources = array_merge($this->sources, glob($pattern));

        return $this;
    }

    public function withOverrides(array $overrides): static
    {
        RecursiveDotApi::replace($this->configOverrides, $overrides);

        return $this;
    }

    public function withPlugin(string|callable $plugin, int $priority = 0): static
    {
        $this->plugins[$priority] ??= [];

        $this->plugins[$priority][] = $plugin;

        return $this;
    }

    public function withComposer(string $path): static
    {
        $this->composer[] = $path;

        return $this;
    }

    public function withParse(bool $parse = true): static
    {
        $this->doParse = $parse;

        return $this;
    }
        
    
    
}