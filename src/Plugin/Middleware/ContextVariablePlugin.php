<?php
namespace Concept\Config\Plugin\Middleware;

use Concept\Config\ConfigInterface;
use Concept\Config\Plugin\ConfigPluginInterface;

class ContextVariablePlugin implements ConfigPluginInterface
{

    public function getPriority(): int
    {
        return 100;
    }

    public function process(string $path, mixed $value, ConfigInterface $config, callable $next): mixed
    {
        if (is_string($value) && preg_match_all('/\$\{([a-zA-Z0-9_]+)\}/i', $value, $matches)) {
            foreach ($matches[1] as $match) {
                $var = $config->getContext($match);
                if ($var !== null) {
                    $value = str_replace('${' . $match . '}', $var, $value);
                }
            }
        }

        return $next($path, $value);
    }
    
}