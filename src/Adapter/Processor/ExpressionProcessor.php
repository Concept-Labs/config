<?php
namespace Concept\Config\Adapter\Processor;

use Concept\Config\Adapter\Processor\Expression\ExpressionProcessorInterface;
use Concept\Config\Adapter\Processor\Expression\IncludeExpression;
use Concept\Config\Adapter\Processor\Expression\MergeExpression;
use Concept\Config\Adapter\Processor\Expression\ReferenceExpression;
use Concept\Config\Adapter\Processor\ProcessorInterface;
use Concept\Config\ConfigInterface;
use Concept\Config\Exception\RuntimeException;

class ExpressionProcessor implements ProcessorInterface
{

    const FUNCTION_REGEX = '/@([a-zA-Z0-9_]+)\((.*?)\)/sui';
    private static array $expressions = [
        'include' => IncludeExpression::class,
        'merge' => MergeExpression::class,
        'reference' => ReferenceExpression::class,
        // 'env' => EnvExpression::class,
        // 'const' => ConstExpression::class,
        // 'var' => VarExpression::class,

        // 'default' => DefaultExpression::class,
        // 'if' => IfExpression::class,
        // 'switch' => SwitchExpression::class,
        // 'case' => CaseExpression::class,
        // 'isset' => IssetExpression::class,
        // 'empty' => EmptyExpression::class,
        // 'notempty' => NotEmptyExpression::class,
        // 'eval' => EvalExpression::class,
        // 'evalif' => EvalIfExpression::class,
        // 'evalswitch' => EvalSwitchExpression::class,
        // 'evalcase' => EvalCaseExpression::class,
        // 'evalisset' => EvalIssetExpression::class,
        // 'evalempty' => EvalEmptyExpression::class,
        // 'evalnotempty' => EvalNotEmptyExpression::class,
        // 'evaldefault' => EvalDefaultExpression::class,
        // 'evalenv' => EvalEnvExpression::class,
        // 'evalconst' => EvalConstExpression::class,
        // 'evalvar' => EvalVarExpression::class,
    ];

    public static function getPriority(): int
    {
        return 999;
    }

    public static function process(
        string $key, 
        mixed &$value, 
        ConfigInterface $config, 
        callable $next
    ): void {
        if (is_string($value)) 
        {
            
            preg_replace_callback(
                static::FUNCTION_REGEX, 
                function ($matches) use ($key, &$value, $config, $next) {
                    $expr = static::getExpression($matches[1]);
                    $value = $expr($key, $value, $config, ...array_map('trim', explode(',', $matches[2])));
                }, 
                $value,
                -1,
                $count
            );
        }

        $next($key, $value, $config);
    }

    protected function processKey(string $key, mixed &$value, ConfigInterface $config, callable $next): void
    {
        if (is_string($value)) 
        {
            
            preg_replace_callback(
                static::FUNCTION_REGEX, 
                function ($matches) use ($key, &$value, $config, $next) {
                    $expr = static::getExpression($matches[1]);
                    $value = $expr($key, $value, $config, ...array_map('trim', explode(',', $matches[2])));
                }, 
                $value,
                -1,
                $count
            );
        }

        $next($key, $value, $config);
    }

    protected static function getExpression(string $expression): callable
    {
        $expression = static::$expressions[$expression] 
            ?? throw new RuntimeException(
                sprintf("Expression plugin not found: %s", $expression)
            );
            /**
              @todo: add dynamic? @see resolveExpressionClass()
             */
            //static::resolveExpressionClass($expression);

        return 
            static function($path, &$value, ConfigInterface $config, ...$args) use ($expression) {
                return $expression::expr($path, $value, $config, ...$args);
            };
    }

    /**
       @dangerous: this method is not recommended to use
     * Resolve the expression class
     * 
     * @param string $expression
     * 
     * @return string
     */
    protected static function resolveExpressionClass(string $expression): string
    {
        $expression = str_replace(['_',' '], '', strtolower($expression));
        $class = static::class;
        $class = substr($class, 0, strrpos($class, '\\'));
        $class = $class . '\\Expression\\' . ucfirst($expression).'Expression';

        if (!class_exists($class) || !is_subclass_of($class, ExpressionProcessorInterface::class)) {
            throw new RuntimeException(
                sprintf("Expression plugin not found: %s", $class)
            );
        }

        return static::$expressions[$expression] = $class;
        
    }
    
}