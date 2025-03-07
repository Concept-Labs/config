<?php
namespace Concept\Config\Interpolate;

use Concept\Config\ConfigInterface;

class ExpressionInterpolator extends AbstractInterpolator
{

    const EXPRESSION_REGEX = '/@([a-zA-Z0-9_\.]+)\((.*?)\)/i';

    public static function match(mixed $value): bool
    {
        return is_string($value) && preg_match(static::EXPRESSION_REGEX, $value);
    }

    public function __invoke(ConfigInterface $config)
    {
        if (!preg_match_all(
            static::EXPRESSION_REGEX,
            $this->getValue(),
            $matches,
            PREG_SET_ORDER
        )) {
            return $this->getValue();
            //throw new \InvalidArgumentException('Invalid expression');
        }

        $value = $this->getValue();

        foreach ($matches as $match) {
            $value = $this->getExpressionInterpolator($value, $match[1])
                ->__invoke(
                    $config, 
                    [
                        InterpolatorInterface::OPTIONS_EXPRESSION => $match[0],
                        InterpolatorInterface::OPTIONS_EXPRESSION_ARGUMETS => $match[2]
                    ]
                );
        }

        return $value;
    }

   
    private function getExpressionInterpolator(
        string $value,
        string $expression,
    ): InterpolatorInterface {

        if (empty($expression)) {
            throw new \InvalidArgumentException("Invalid expression: $expression");
        }

        $expression = sprintf(
            '%s\\Expression\\%sExpression', 
            __NAMESPACE__,
            ucfirst($expression)
        );
        

        if (!class_exists($expression) || !is_subclass_of($expression, InterpolatorInterface::class)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid expression ("%s"). Trying to allocate class: %s',
                    $this->getValue(),
                    $expression
                )
            );
        }

        return $expression::create(
                $value,
                $this->getOptions()
            );

    }
}