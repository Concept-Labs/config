<?php
namespace Concept\Config\Adapter;

class JsonAdapter implements AdapterInterface
{
    /**
     * Check if the source is a JSON string
     * 
     * @param string $source
     * 
     * @return bool
     */
    public static function isJson(string $source): bool
    {
        return is_string($source) && strpos($source, '{') === 0;
        /**
         * @todo: or use json_decode()?
         * will be slower but more reliable
         */
        json_decode($source);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * {@inheritDoc}
     * @throws \JsonException
     */
    public static function load(mixed $source): array
    {
        return json_decode($source, true, 512, JSON_THROW_ON_ERROR);
    }

}