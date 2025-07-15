<?php
namespace Concept\Config\Parser\Plugin\Directive;

use Concept\Arrays\RecursiveDotApi;
use Concept\Config\Parser\Exception\InvalidArgumentException;
use Concept\Config\Parser\ParserInterface;
use Concept\Config\Parser\Plugin\AbstractPlugin;
use Concept\Config\Resource\ResourceInterface;


class ComposerPlugin extends AbstractPlugin
{
    
    /**
     * Check if the value can be processed by the plugin
     * 
     * @param string $value
     * 
     */
    protected function match(string $subject): bool
    {
        $parts = RecursiveDotApi::path($subject);
        return $parts && str_contains(end($parts), '@composer');
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        if ($this->match($path)) {

            $composerData = [];

            $value = is_array($value) ? $value : [$value];

            foreach ($value as $include) {
                RecursiveDotApi::replaceTo(
                    substr($path, 0, strrpos($path, '.')),
                    $composerData, 
                    $this->getComposerData($include)
                );
            }

            
            return ParserInterface::VALUE_TO_REMOVE;
        }

        return $next($value, $path, $subjectData);
    }

    /**
     * Resolve the value
     * 
     * @param mixed $source
     * @param string $path
     * @return mixed
     */
    protected function getIncludeData(mixed $source)//: ?array
    {
        $data = [];
        $this->getResource()->read($data, $source);

        if (!is_array($data)) {
            throw new InvalidArgumentException("Invalid @import source: \"$source\"");
        }

        return $data;
    }

    /**
     * Get the resource
     * 
     * @return ResourceInterface
     */
    protected function getResource(): ResourceInterface
    {
        return $this->getConfig()->getResource();
    }

    
}
