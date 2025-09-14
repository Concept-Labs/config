<?php
namespace Concept\Config\Parser;

use Concept\Config\ConfigInterface;
use Concept\Config\Parser\Plugin\ConfigValuePlugin;
use Concept\Config\Parser\Plugin\ContextPlugin;
use Concept\Config\Parser\Plugin\Directive\CommentPlugin;
use Concept\Config\Parser\Plugin\Directive\ImportPlugin;
use Concept\Config\Parser\Plugin\Expression\EnvPlugin;
use Concept\Config\Parser\Plugin\Expression\ReferencePlugin;
use Concept\Config\Parser\Plugin\IncludePlugin;
use Concept\Singularity\Config\Plugin\ComposerPlugin;

class ParserFactory
{
    /**
     * Create a new parser instance
     *
     * @param ConfigInterface $config
     * @return ParserInterface
     */
    public static function create(ConfigInterface $config): ParserInterface
    {
        return (new Parser($config))
                // ->registerPlugin(CommentPlugin::class, 996)
                // ->registerPlugin(EnvPlugin::class, 999)
                // ->registerPlugin(ContextPlugin::class, 998)
                // ->registerPlugin(IncludePlugin::class, 997)
                // ->registerPlugin(ImportPlugin::class, 996)
                // ->registerPlugin(ReferencePlugin::class, 995)
                // ->registerPlugin(ConfigValuePlugin::class, 994)
                // ->registerPlugin(ComposerPlugin::class, 993)
                
            ;
    }
}