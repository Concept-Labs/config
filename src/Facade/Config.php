<?php
namespace Concept\Config\Facade;

use Concept\Config\ConfigInterface;
use Concept\Config\Config as ConfigClass;
use Concept\Config\Parser\Plugin\ConfigValuePlugin;
use Concept\Config\Parser\Plugin\ContextPlugin;
use Concept\Config\Parser\Plugin\Directive\ImportPlugin;
use Concept\Config\Parser\Plugin\Expression\EnvPlugin;
use Concept\Config\Parser\Plugin\Expression\ReferencePlugin;
use Concept\Config\Parser\Plugin\IncludePlugin;
use Concept\Config\Factory as ConfigFactory;

class Config
{
    /**
     * Create a new Config instance from a source
     * 
     * @param array|string $source File path or glob pattern
     * @param array $context Context variables to be used in the configuration
     * @param array $overrides Configuration overrides
     * 
     * @return ConfigInterface
     */
    public static function config(array|string $source, array $context = [], array $overrides = []): ConfigInterface
    {
        $config = (new ConfigFactory(ConfigClass::class))
                ->withGlob($source)
                ->withContext($context)
                ->withOverrides($overrides)
                //->withPlugin(ComposerPlugin::class) // register the Singularity Composer plugin

                //->withPlugin(CommentPlugin::class, 996)
                ->withPlugin(EnvPlugin::class, 999)
                ->withPlugin(ContextPlugin::class, 998)
                ->withPlugin(IncludePlugin::class, 997)
                ->withPlugin(ImportPlugin::class, 996)
                ->withPlugin(ReferencePlugin::class, 995)
                ->withPlugin(ConfigValuePlugin::class, 994)
                //->withPlugin(ExtendsPlugin::class, 993)
                ->create()
            ;

        return $config;
        
    }
}