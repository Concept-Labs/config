<?php
namespace Concept\Config\Adapter\Facade;

use Concept\Config\Adapter\Processor\ProcessorAggregator;
use Concept\Config\Adapter\Processor\ProcessorAggregatorInterface;
use Concept\Config\ConfigInterface;

class AggregateProcessor
{
    public static function withConfig(ConfigInterface $config): ProcessorAggregatorInterface
    {
        return new ProcessorAggregator($config);
    }
}