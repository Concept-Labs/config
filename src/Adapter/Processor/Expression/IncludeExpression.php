<?php
namespace Concept\Config\Adapter\Processor\Expression;

use Concept\Config\ConfigInterface;
use Concept\Config\Adapter\Processor\Expression\ExpressionProcessorInterface;

class IncludeExpression implements ExpressionProcessorInterface
{
    static array $includeStack = [];
    public static function expr(string $path, mixed &$value, ConfigInterface $config, ...$args): mixed
    {
        $include = $args[0] ?? null;

        $currentSource = $config->get('___current_source') ?? false;

        $dir = $currentSource ? dirname($currentSource) : false;

        if (null !== $include) {

            $include = strpos($include, '/') !== 0 && $dir
                ? $dir.'/'. $include
                : $include;

            if (in_array($include, static::$includeStack)) {
                throw new \Exception('Circular include detected: ' . implode(' -> ', static::$includeStack) . ' -> ' . $include);
            }

            array_push(static::$includeStack, $include);

            $value = $config->getAdapter()->import($include);
            
            array_pop(static::$includeStack);
            /**
            @todo: what if imported data contains expressions?
            thats because processor is called inside adapter
            */
        }

        

        return $value;
    }
}