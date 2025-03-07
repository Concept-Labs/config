<?php
namespace Concept\Config\Adapter\Processor;

use Concept\Config\ConfigInterface;

interface ProcessorInterface
{
    public static function process(
        string $key, 
        mixed &$value, 
        ConfigInterface $config, 
        callable $next
    ): void;

    public static function getPriority(): int;
}