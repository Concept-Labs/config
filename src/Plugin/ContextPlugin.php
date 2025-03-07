<?php
namespace Concept\Config\Plugin;

use Concept\Config\ConfigInterface;
use Concept\Config\Plugin\ConfigPluginInterface;


class ContextPlugin implements ConfigPluginInterface
{

    public function getPriority(): int
    {
        return 100;
    }

    public function process(string $path, mixed $value, ConfigInterface $config, callable $next): mixed
    {

        if (
            is_string($value) 
            && strstr($value, '${') !== false
        ) {
            $value = preg_replace_callback(
                '/\${(.+?)}/',
                function ($matches) use ($config) {
                    return $config->getContext()->get($matches[1]) ?? $matches[0];
                },
                $value,
                -1,
                $count
            );
        }

        return $next($path, $value, $config);
    }
    
}