<?php
namespace Concept\Config\Parser\Plugin\Directive;

use Concept\Arrays\RecursiveApi;
use Concept\Arrays\RecursiveDotApi;
use Concept\Config\Parser\ParserInterface;
use Concept\Config\Parser\Plugin\AbstractPlugin;
use Concept\Config\Parser\Resolver;

/**
 * Class ExtendsPlugin
 *
 * This plugin is responsible for handling the "extends" directive within the configuration parser.
 * It allows the inclusion of external configuration files or resources into the current configuration context.
 * The plugin ensures that imported configurations are properly parsed and integrated.
 * 
 * Syntax:
 *  "@extends": "path<:mode>"
 * 
 * where:
 * - "path" is the path to the configuration file or resource to be imported
 *      - the path must be absolute
 * - <:mode> is one of the following:
 *     - RecursiveApi::MERGE_COMBINE: merge the imported data into the current data
 *     - RecursiveApi::MERGE_OVERWRITE: overwrite the current data with the imported data
 *     - RecursiveApi::MERGE_PRESERVE: preserve the current data and ignore the imported data
 *
 *
 * @package ConceptLabs\Config\Parser\Plugin\Directive
 */
class ExtendsPlugin extends AbstractPlugin
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
        return $parts && str_starts_with(end($parts), '@extends'); // faster than regex
    }
    
    /**
     * {@inheritDoc}
     */
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        if ($this->match($path)) {
            $extendsPath = $value;
            
            // Calculate the target path (remove .@extends from the end)
            $pathTo = substr($path, 0, strrpos($path, '.@extends'));
            
            // Get the current node data BEFORE parsing removes it
            // Navigate to the parent node and capture its data (excluding @extends)
            $currentNodeData = [];
            
            // Navigate to the parent node in subjectData
            if ($pathTo !== '') {
                $pathParts = explode('.', $pathTo);
                $temp = $subjectData;
                foreach ($pathParts as $key) {
                    if (!isset($temp[$key]) || !is_array($temp[$key])) {
                        // Path doesn't exist or isn't an array, use empty data
                        $temp = [];
                        break;
                    }
                    $temp = $temp[$key];
                }
            } else {
                // Root level @extends in the subject data
                $temp = $subjectData;
            }
            
            // Copy the current node data (excluding @extends)
            if (is_array($temp)) {
                foreach ($temp as $k => $v) {
                    if ($k !== '@extends') {
                        $currentNodeData[$k] = $v;
                    }
                }
            }
            
            // Check if we're in a nested parse context
            // If parse depth > 1, we're in a nested context (from @import/@include)
            $parseDepth = $this->getConfig()->getParser()->getParseDepth();
            
            if ($parseDepth > 1) {
                // In nested context: resolve immediately using the parent config
                try {
                    $extendsData = $this->getConfig()->get($extendsPath);

                    if ($extendsData === null) {
                        throw new \InvalidArgumentException(sprintf(
                            'The path "%s" does not exist in the configuration.',
                            $extendsPath
                        ));
                    }

                    if (!is_array($extendsData)) {
                        throw new \InvalidArgumentException(sprintf(
                            'The path "%s" does not point to a valid configuration array.',
                            $extendsPath
                        ));
                    }

                    // Merge immediately
                    $mergedData = $extendsData;
                    \Concept\Arrays\RecursiveApi::merge(
                        $mergedData,
                        $currentNodeData,
                        \Concept\Arrays\RecursiveApi::MERGE_OVERWRITE
                    );

                    // Update the subject data directly
                    if ($pathTo !== '') {
                        // Navigate to parent and update
                        $pathParts = explode('.', $pathTo);
                        $temp = &$subjectData;
                        foreach ($pathParts as $key) {
                            if (!isset($temp[$key])) {
                                $temp[$key] = [];
                            }
                            $temp = &$temp[$key];
                        }
                        // Replace with merged data
                        $temp = $mergedData;
                    } else {
                        // Root level - replace entire subjectData
                        foreach ($mergedData as $k => $v) {
                            $subjectData[$k] = $v;
                        }
                        // Remove any keys not in merged data
                        foreach ($subjectData as $k => $v) {
                            if (!isset($mergedData[$k]) && $k !== '@extends') {
                                unset($subjectData[$k]);
                            }
                        }
                    }
                    
                    // Abandon this node
                    return ParserInterface::ABANDONED_NODE;
                    
                } catch (\Exception $e) {
                    // If we can't resolve in nested context, fall back to lazy resolution
                }
            }
            
            // Add lazy resolver to handle extends after all parsing is done
            $this->getConfig()->addLazyResolver(
                new Resolver(
                    function ($config) use ($extendsPath, $pathTo, $currentNodeData) {
                        /**
                         * Get the base data to extend from
                         */
                        $extendsData = $config->get($extendsPath);

                        if ($extendsData === null) {
                            throw new \InvalidArgumentException(sprintf(
                                'The path "%s" does not exist in the configuration.',
                                $extendsPath
                            ));
                        }

                        if (!is_array($extendsData)) {
                            throw new \InvalidArgumentException(sprintf(
                                'The path "%s" does not point to a valid configuration array.',
                                $extendsPath
                            ));
                        }

                        /**
                         * Merge: Start with base data (extendsData), 
                         * then merge current data on top (currentData overrides base)
                         * Note: merge() modifies the first parameter by reference
                         */
                        $mergedData = $extendsData; // Start with a copy of the base
                        \Concept\Arrays\RecursiveApi::merge(
                            $mergedData,
                            $currentNodeData,
                            \Concept\Arrays\RecursiveApi::MERGE_OVERWRITE
                        );

                        /**
                         * Set the merged data back to the config
                         */
                        $config->set($pathTo, $mergedData);
                    }
                )
            );
            
            // Let the parser know that this node should be removed
            return ParserInterface::ABANDONED_NODE;
        }

        return $next($value, $path, $subjectData);
    }
    
}
