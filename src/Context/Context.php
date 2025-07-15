<?php

namespace Concept\Config\Context;

use Concept\Arrays\DotArray\DotArray;

class Context extends DotArray implements ContextInterface
{
    public function withEnv(array $env): static
    {
        return $this->withSection('ENV', $env);
    }

    public function withSection(string $section, array $data): static
    {
        $this->set($section, $data);

        return $this;
    }
}