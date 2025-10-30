<?php
namespace Concept\Config\Parser\Plugin\Directive;

use Concept\Config\Parser\Plugin\AbstractPlugin;

/**
 * Class ExtendsPlugin
 *
 * This plugin is responsible for marking @extends directives for later processing.
 * The actual merging happens in Config::processExtendsDirectives() after all
 * parsing and initial resolution is complete.
 * 
 * Syntax:
 *  "@extends": "path.to.base.node"
 * 
 * The node containing @extends will inherit all properties from the base node,
 * with its own properties taking precedence (overwriting base properties).
 * 
 * This approach allows for:
 * - Forward references (extending a node defined later)
 * - Extending nodes that contain Resolvable values
 * - Extending nodes from imported/included files
 *
 * @package ConceptLabs\Config\Parser\Plugin\Directive
 */
class ExtendsPlugin extends AbstractPlugin
{
    /**
     * {@inheritDoc}
     * 
     * The ExtendsPlugin doesn't modify values during parsing.
     * It simply passes through, allowing @extends directives to remain
     * in the data until Config::processExtendsDirectives() handles them.
     */
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        // Pass through to next plugin - no processing needed during parsing
        // The @extends directive will be processed later by Config::processExtendsDirectives()
        return $next($value, $path, $subjectData);
    }
}
