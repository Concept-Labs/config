<?php
namespace Concept\Config\Parser\Plugin\Directive;

use Concept\Arrays\RecursiveApi;
use Concept\Arrays\RecursiveDotApi;
use Concept\Config\Parser\Exception\InvalidArgumentException;
use Concept\Config\Parser\ParserInterface;
use Concept\Config\Parser\Plugin\AbstractPlugin;
use Concept\Config\Resource\ResourceInterface;

/**
 * Class ImportPlugin
 *
 * This plugin is responsible for handling the "import" directive within the configuration parser.
 * It allows the inclusion of external configuration files or resources into the current configuration context.
 * The plugin ensures that imported configurations are properly parsed and integrated.
 * 
 * Syntax:
 *  "@import<:mode>": "<source>" | ["<source>", ...]
 * 
 * where:
 *
 * - <:mode> is one of the following:
 *      - RecursiveApi::MERGE_COMBINE: merge the imported data into the current data
 *      - RecursiveApi::MERGE_OVERWRITE: overwrite the current data with the imported data
 *      - RecursiveApi::MERGE_PRESERVE: preserve the current data and ignore the imported data
 *      - RecursiveApi::MERGE_NATIVE: use the native PHP array_merge_recursive() function
 *      - if <:mode> is not provided, it defaults to RecursiveApi::MERGE_PRESERVE
 *
 * - <source> is the path or array of paths to the configuration file or resource to be imported
 *      - the path can be relative or absolute
 *      - the path can be a file path or a URL
 * 
 *
 * @package ConceptLabs\Config\Parser\Plugin\Directive
 */
class ImportPlugin extends AbstractPlugin
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
        return $parts && str_contains(end($parts), '@import'); // faster than regex
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        if ($this->match($path)) {

            $mode = explode(':', $path)[1] ?? RecursiveApi::MERGE_COMBINE; // default mode: combine values will add new values and overwrite existing ones

            foreach (is_array($value) ? $value : [$value] as $import) {
                RecursiveDotApi::merge(
                    $subjectData,                           // to the subject data
                    $this->getImportData($import),        // from the import data
                    substr($path, 0, strrpos($path, '.')),  // to the parent node
                    $mode                                   // using the specified mode
                );
            }
            //remove the import directive
            unset($subjectData[$path]);
            //let the parser know that the value has been removed
            return ParserInterface::VALUE_TO_REMOVE;
        }

        return $next($value, $path, $subjectData);
    }

    /**
     * Get the data from the import source
     * 
     * @param mixed $source
     * @return mixed
     */
    protected function getImportData(mixed $source)//: ?array
    {
        $data = [];

        $parsed = parse_url($source);
        if (!isset($parsed['scheme'])) {
            $source = glob($source, GLOB_BRACE);
        }

        foreach (is_array($source) ? $source : [$source] as $src) {
            $d = [];
            $this->getResource()->read($d, $src);

            if (!is_array($d) || empty($d)) {
                throw new InvalidArgumentException("Invalid @import source: \"$src\"");
            }

            RecursiveDotApi::merge(
                $data, 
                $d, 
                null, 
                RecursiveApi::MERGE_COMBINE // default mode: combine values. will add new values and overwrite existing ones
            );
            
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
