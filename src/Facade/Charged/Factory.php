<?php
namespace Concept\Config\Facade\Charged;

use Concept\Config\Factory as ConfigFactory;
use Concept\Config\Parser\Plugin\ContextPlugin;
use Concept\Config\Parser\Plugin\ReferenceNodePlugin;
use Concept\Config\Parser\Plugin\ReferenceValuePlugin;
use Concept\Config\Parser\Plugin\IncludePlugin;
use Concept\Config\Parser\Plugin\Expression\EnvPlugin;
use Concept\Config\Parser\Plugin\Directive\ImportPlugin;
use Concept\Config\Parser\Plugin\Directive\CommentPlugin;
use Concept\Config\Parser\Plugin\Directive\ExtendsPlugin;

class Factory
{
    /**
     * Create the configuration factory with common plugins pre-registered
     * So developer doesn't have to remember to add them each time
     * 
     * @param string $source File path or glob pattern
     * @param array $context Context variables to be used in the configuration
     * @param array $overrides Configuration overrides
     * 
     * @return ConfigFactory
     */
    //public static function factory(array|string $source, array $context = [], array $overrides = []): ConfigInterface
    public static function factory(string $source, array $context = [], array $overrides = []): ConfigFactory
    {
        return (new ConfigFactory())
                ->withGlob($source)
                ->withContext($context)
                ->withOverrides($overrides)
                ->withPlugin(CommentPlugin::class, 1000)
                ->withPlugin(ContextPlugin::class, 1000)
                ->withPlugin(EnvPlugin::class, 1000)
                ->withPlugin(ReferenceValuePlugin::class, 1000)
                ->withPlugin(ReferenceNodePlugin::class, 1000)
                ->withPlugin(IncludePlugin::class, 1000)
                ->withPlugin(ImportPlugin::class, 1000)
                ->withPlugin(ExtendsPlugin::class, 1000)
            ;

        //return $config;
        
    }
}