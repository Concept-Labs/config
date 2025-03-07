<?php
namespace Concept\Config\Plugin;

use Concept\Config\ConfigInterface;
use Concept\Config\Exception\RuntimeException;
use Concept\Config\Plugin\ConfigPluginInterface;


class ReferencePlugin implements ConfigPluginInterface
{

    const REFERENCE_PREFIX = '@';

    public function getPriority(): int
    {
        return 100;
    }

    public function process(string $path, mixed $value, ConfigInterface $config, callable $next): mixed
    {
        if (is_string($value) && strpos($value, static::REFERENCE_PREFIX) === 0) 
        {
            $reference = trim(substr($value, strspn($value, static::REFERENCE_PREFIX)));
            /**
             * The reference path is the path to the reference value
             * Multiple "@" characters indicate the level of the reference
             */
            $referencePath = $this->getReferencePath($path, $value, $reference);
            $value = $config->get($referencePath);

            if (null === $value) {
                throw new RuntimeException(
                    sprintf('Reference not found: "%s"', $referencePath)
                );
            }
        }

        return $next($path, $value, $config);
    }

    protected function getReferencePath(string $path, string $value, $reference): string
    {
        $level = strspn($value, static::REFERENCE_PREFIX) - 1;
        $pathNodes = explode('.', $path);
        $pathNodes = array_slice($pathNodes, 0, $level);
        $pathNodes[] = $reference;

        return implode('.', $pathNodes);
    } 
    
}