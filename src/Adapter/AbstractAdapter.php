<?php
namespace Concept\Config\Adapter;

use Concept\Config\ConfigInterface;

abstract class AbstractAdapter implements AdapterInterface
{
    public function __construct(private ConfigInterface $config)
    {
        
    }

    protected function getConfig(): ConfigInterface
    {
        return $this->config;
    }
}