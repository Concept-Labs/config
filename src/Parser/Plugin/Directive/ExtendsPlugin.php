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
            // Add lazy resolver to process extends after parsing is complete
            $this->getConfig()->addLazyResolver(
                new Resolver(
                    function ($config) use ($path, $value) {
                        $extendsPath = $value;

                        // Calculate the target path (remove .@extends from the end)
                        $pathTo = substr($path, 0, strrpos($path, '.@extends'));

                        /**
                         * Get the extends data (what we're extending from)
                         */
                        $extendsData = $config->get($extendsPath);

                        if (!is_array($extendsData)) {
                            throw new \InvalidArgumentException(sprintf(
                                'The path "%s" does not point to a valid configuration array.',
                                $extendsPath
                            ));
                        }

                        // Get current data at the target path (what we're extending)
                        $currentData = $config->get($pathTo);
                        if (!is_array($currentData)) {
                            $currentData = [];
                        }

                        /**
                         * Start with extends data (base), merge current data on top
                         * This way current values override base values
                         * Note: RecursiveApi::merge modifies the first argument by reference
                         */
                        $mergedData = $extendsData; // Start with a copy of extends data
                        RecursiveApi::merge(
                            $mergedData,
                            $currentData,
                            RecursiveApi::MERGE_OVERWRITE
                        );

                        /**
                         * Set the merged data back to the config
                         */
                        $config->set($pathTo, $mergedData);
                    }
                )
            );
            
            //let the parser know that the value has been removed
            return ParserInterface::ABANDONED_NODE;
        }

        return $next($value, $path, $subjectData);
    }
    
}
