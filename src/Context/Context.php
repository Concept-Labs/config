<?php

namespace Concept\Config\Context;

use Concept\Arrays\DotArray\DotArray;

class Context extends DotArray implements ContextInterface
{
    /**
     * {@inheritDoc}
     */
    public function withEnv(array $env): static
    {
        return $this->withSection('ENV', $env);
    }

    /**
     * Add a section to the context
     * 
     * @param string $section
     * @param array $data
     * 
     * @return static
     */
    protected function withSection(string $section, array $data): static
    {
        $this->set($section, $data);

        return $this;
    }
}