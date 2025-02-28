<?php
namespace Concept\Config\Adapter;

use Concept\Config\Exception\InvalidArgumentException;

class ArrayAdapter implements AdapterInterface
{
    public static function load(mixed $source): array
    {
        if (!is_array($source)) {
            throw new InvalidArgumentException('Invalid config source provided. Source is not an array');
        }
        return $source;
    }
}