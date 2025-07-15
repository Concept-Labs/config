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
 *  "@import<:type>": "<source>" | ["<source>", ...]
 * 
 * where:
 * 
 * - <:type> is one of the following:
 *      - merge: merge the imported data into the current data
 *      - replace: replace the current data with the imported data
 *      - merge-missing: merge the imported data into the current data only if the keys do not exist in the current data
 *      - if <:type> is not provided, it defaults to "merge-missing"
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

            $mode = explode(':', $path)[1] ?? RecursiveApi::MERGE_PRESERVE;

            foreach (is_array($value) ? $value : [$value] as $include) {
                RecursiveDotApi::merge(
                    $subjectData,                           // to the subject data
                    $this->getIncludeData($include),        // from the include data
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
