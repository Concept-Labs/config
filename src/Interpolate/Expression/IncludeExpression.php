<?php
namespace Concept\Config\Interpolate\Expression;

use Concept\Config\ConfigInterface;
use Concept\Config\Interpolate\InterpolatorInterface;

class IncludeExpression extends AbstractExpressionInterpolator
{
    
    public static function match(mixed $value): bool
    {
        throw new \Exception('Cannot match. Use ExpressionInterpolator instead.');
    }

    public function __invoke(ConfigInterface $config, ?array $args = null)
    {
        return $config->getAdapter()
            ->import(
                $this->getIncludePath(
                    $args[InterpolatorInterface::OPTIONS_EXPRESSION_ARGUMETS]    
                )
            );
    }

    private function getIncludePath(string $include): string
    {
        $origSource = $this->getOptions(InterpolatorInterface::OPTIONS_SOURCE);
        $dir = dirname($origSource);

        $path = $dir . '/' . trim($include);

        return $path;
    }


}