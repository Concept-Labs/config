<?php
namespace Concept\Config\Adapter\Processor\Expression;

use Concept\Config\ConfigInterface;

interface ExpressionProcessorInterface
{
    public static function expr(string $key, mixed &$value, ConfigInterface $config, ...$args): mixed;
}