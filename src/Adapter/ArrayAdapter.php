<?php
namespace Concept\Config\Adapter;

use Concept\Config\Config;
use Concept\Config\ConfigInterface;

class ArrayAdapter
{
    public static function load(array $data): ConfigInterface
    {
        return (new Config)->withData($data);
    }
}