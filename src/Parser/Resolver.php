<?php
namespace Concept\Config\Parser;

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
    public function __invoke(): mixed
    {
        return ($this->resolver)();
    }
}