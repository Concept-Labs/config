<?php
namespace Concept\Config\Parser\Plugin;

interface PluginInterface
{
    /**
     * Process the value
     * 
     * @param mixed     $value  The value to be processed
     * @param string    $path   The path of the value
     * @param callable  $next   The next callable to call
     * 
     * @return mixed            The processed value
     */
    public function __invoke(
        mixed $value,
        string $path,
        array &$subjectData,
        callable $next
    ): mixed;
}