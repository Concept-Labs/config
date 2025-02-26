<?php
namespace Concept\Config\Adapter;

use Concept\Config\Config;
use Concept\Config\ConfigInterface;
use Concept\Config\Exception\InvalidArgumentException;

class JsonAdapter 
{
    public static function load(string $path): ConfigInterface
    {
        try {
            $json = file_get_contents($path);
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException(
                sprintf(
                    "Unable to load file: %s (%s) ",
                    $path,
                    $e->getMessage()
                )
            );
        }

        return ArrayAdapter::load($data);
    }
}