<?php

namespace Concept\Config\Context;

use Concept\Arrays\DotArray\DotArrayInterface;

interface ContextInterface extends DotArrayInterface
{
    public function withEnv(array $env): static;
}