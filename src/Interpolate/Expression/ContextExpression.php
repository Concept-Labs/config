<?php
namespace Concept\Config\Interpolate\Expression;

use Concept\Config\ConfigInterface;
use Concept\Config\Interpolate\InterpolatorInterface;

class ContextExpression extends AbstractExpressionInterpolator
{
    
    public static function match(mixed $value): bool
    {
        throw new \Exception('Cannot match. Use ExpressionInterpolator instead.');
    }

    public function __invoke(ConfigInterface $config, ?array $args = null)
    {
        if(null === $args || !is_array($args)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Arguments are required: %s, %s',
                    InterpolatorInterface::OPTIONS_EXPRESSION,
                    InterpolatorInterface::OPTIONS_EXPRESSION_ARGUMETS
                )
            );
        }

        $expression = $args[InterpolatorInterface::OPTIONS_EXPRESSION] ?? null;
        $contextKey = $args[InterpolatorInterface::OPTIONS_EXPRESSION_ARGUMETS] ?? null;

        if (null === $expression || !is_string($expression)) {
            throw new \InvalidArgumentException('Correct expression is required: <@context(<key>)>');
        }

        if (null === $contextKey || !is_string($contextKey)) {
            throw new \InvalidArgumentException('Context key is required: @context(<key>)');
        }

        $contextValue = $config->getContext()->get($contextKey);
        
        if (null === $contextValue) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Context key not found: %s',
                    $contextKey
                )
            );
        }

        return str_replace(
            $expression,
            $contextValue,
            $this->getValue()
        );
            
    }

    private function getIncludePath(/*string $value*/): string
    {
        $source = $this->getOptions('#source');
        $dir = dirname($source);
        $path = $dir . '/' . trim($this->getValue());

        return $path;
    }


}