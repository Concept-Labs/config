<?php
namespace Concept\Config\Parser;

use Concept\Config\ConfigInterface;

class Resolver implements ResolvableInterface
{
    
    /**
     * The resolver
     * 
     * @var callable
     */
    private $resolver;

    /**
     * Constructor
     * 
     * @param callable $resolver
     */
    public function __construct(callable $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(ConfigInterface $config): mixed
    {
        return ($this->resolver)($config);
    }
}