<?php
namespace Concept\Config\Plugin;

use Concept\Config\ConfigInterface;

interface ConfigPluginInterface
{
    public function process(string $path, mixed $value, ConfigInterface $config, callable $next): mixed;
    public function getPriority(): int;
}