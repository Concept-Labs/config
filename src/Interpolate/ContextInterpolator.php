<?php
namespace Concept\Config\Interpolate;

use Concept\Config\ConfigInterface;

class ContextInterpolator extends AbstractInterpolator
{
    
    public function __invoke(ConfigInterface $config): string
    {
        return preg_replace_callback(
            '/\$\{([a-zA-Z0-9_\.]+)\}/', 
            fn ($match) 
                => $config->getContext()->get($match[1]) ?? $match[0],
            $this->getValue()
        );
    }

    public static function match(mixed $value): bool
    {
        return is_string($value) && str_contains($value, '${');
    }

    
}