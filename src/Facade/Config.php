<?php
namespace Concept\Config\Facade;

use Concept\Config\ConfigInterface;
use Concept\Config\Config as ConfigService;
use Concept\Config\Context\ContextInterface;

class Config
{

    public static function create(): ConfigInterface
    {
        return (new ConfigService());
    }

    public static function from(mixed ...$source): ConfigInterface
    {
        $service =  static::create();
        foreach ($source as $config) {
            $service->import($config);
        }

        return $service;
    }

    public static function withContext(ContextInterface $context): ConfigInterface
    {
        return static::create()->withContext($context);
    }
}