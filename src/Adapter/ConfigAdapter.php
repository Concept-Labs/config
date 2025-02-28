<?php
namespace Concept\Config\Adapter;

use Concept\Config\ConfigInterface;
use Concept\Config\Exception\InvalidArgumentException;

class ConfigAdapter implements AdapterInterface
{
    public static function load(mixed $source): array
    {
        if (!$source instanceof ConfigInterface) {
            throw new InvalidArgumentException('Invalid config source provided. Source is not a config');
        }

        return $source->asArray();

        //or reference?
        //return $source->asArrayRef();
    }

}