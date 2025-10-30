<?php
namespace Concept\Config\Parser\Plugin;

use Concept\Config\Parser\Plugin\AbstractPlugin;
use Concept\Config\Resource\ResourceInterface;

use Concept\Config\Exception\InvalidArgumentException;

class IncludePlugin extends AbstractPlugin
{
    /**
     * Get the match pattern
     * 
     * @return string
     */
    protected function getMatchPattern(): string
    {
        return '/@include\((.*?)\)/i';
    }

    /**
     * Check if the value can be processed by the plugin
     * 
     * @param string $value
     * 
     */
    protected function match(mixed $value): bool
    {
        return is_string($value) && str_starts_with($value, '@include(');
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        if ($this->match($value)) { //check w/o regex first
            if(preg_match($this->getMatchPattern(), $value, $matches)) {
                $source = isset($matches[1]) 
                    ? $matches[1] 
                    : throw new InvalidArgumentException("Invalid @include syntax: $value");        

                return $this->getIncludeData($source);
            }
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

        // Don't parse the included data yet - let the main parse handle it
        // so that plugins like @extends can access the full config context
        $this->getResource()->read($data, $source, false);


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
