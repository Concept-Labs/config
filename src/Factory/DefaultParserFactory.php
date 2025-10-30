<?php

namespace Concept\Config\Factory;

use Concept\Config\Parser\Parser;
use Concept\Config\Parser\ParserInterface;
use Concept\Config\ConfigInterface;

/**
 * Default implementation of ParserFactoryInterface
 * 
 * Creates standard Parser instances. The parser can work with or without
 * a config instance. When a config is provided, plugins will have access
 * to it for resolving references and accessing context.
 */
class DefaultParserFactory implements ParserFactoryInterface
{
    /**
     * Optional config instance for parser and plugins
     * 
     * @var ConfigInterface|null
     */
    private ?ConfigInterface $config = null;

    /**
     * Constructor
     * 
     * @param ConfigInterface|null $config Optional config instance
     */
    public function __construct(?ConfigInterface $config = null)
    {
        $this->config = $config;
    }

    /**
     * Set the config instance
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
     * {@inheritDoc}
     */
    public function create(): ParserInterface
    {
        return new Parser($this->config);
    }
}
