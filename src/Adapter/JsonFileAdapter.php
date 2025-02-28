<?php
namespace Concept\Config\Adapter;

use Concept\Config\Exception\InvalidArgumentException;

class JsonFileAdapter implements AdapterInterface
{
    /**
     * {@inheritDoc}
     * 
     * @throws InvalidArgumentException
     */ 
    public static function load(mixed $source): array
    {
        if (!is_file($source)) {
            throw new InvalidArgumentException('Invalid config source provided. Source is not a file');
        }

        try {
            $json = file_get_contents($source);
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        } catch (\Throwable $e) {
            throw new InvalidArgumentException(
                sprintf(
                    "Unable to load file: %s (%s) ",
                    $source,
                    $e->getMessage()
                )
            );
        }

        return $data;
    }
}