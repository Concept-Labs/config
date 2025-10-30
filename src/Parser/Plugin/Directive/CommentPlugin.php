<?php
namespace Concept\Config\Parser\Plugin\Directive;

use Concept\Arrays\RecursiveDotApi;
use Concept\Config\Parser\ParserInterface;
use Concept\Config\Parser\Plugin\AbstractPlugin;


class CommentPlugin extends AbstractPlugin
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
        return $parts && str_starts_with(end($parts), '--');
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        if ($this->match($path)) {

            //remove the comment directive
            unset($subjectData[$path]);
            //let the parser know that the value has been removed
            return ParserInterface::ABANDONED_NODE;
        }

        return $next($value, $path, $subjectData);
    }
    
}
