<?php

namespace Concept\Config;

use Concept\Arrays\DotArray\DotArrayInterface;
use Concept\Config\Context\ContextInterface;
use Concept\Config\Parser\ParserInterface;
use Concept\Config\Resource\ResourceInterface;

interface ConfigInterface //extends DotArrayInterface
{

    public function toArray(): array;

    public function dotArray(): DotArrayInterface;

    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): static;

    public function has(string $key): bool;

    public function load(string|array|ConfigInterface $source, bool $parse = false): static;

    public function import(string|array|ConfigInterface $source, bool $parse = false): static;
    public function importTo(string|array|ConfigInterface $source, string $path, bool $parse = false): static;

    public function export(string $target): static;

    public function withContext(ContextInterface|array $context): static;

    public function getContext(): ContextInterface;

    public function getResource(): ResourceInterface;

    public function getParser(): ParserInterface;
   
}
    