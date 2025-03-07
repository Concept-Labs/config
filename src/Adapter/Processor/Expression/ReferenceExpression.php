<?php
namespace Concept\Config\Adapter\Processor\Expression;

use Concept\Config\ConfigInterface;
use Concept\Config\Adapter\Processor\Expression\ExpressionProcessorInterface;

class ReferenceExpression implements ExpressionProcessorInterface
{
    static array $includeStack = [];
    public static function expr(string $path, mixed &$value, ConfigInterface $config, ...$args): mixed
    {
        $reference = $args[0] ?? null;
        if (null !== $reference) {
            $value = $config->get($reference) ?? $value;
        }

        return $value;
    }
}