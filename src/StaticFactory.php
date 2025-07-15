<?php

namespace Concept\Config;

class StaticFactory
{

    public static function create(array $data = [], array $context = [], bool $parse = false): ConfigInterface
    {
        $config = new Config([], $context);

        if (!empty($data)) {
            $config->load($data, $parse);
        }

        return $config;
    }

    public static function fromFile(string $source, array $context = [], bool $parse = false): ConfigInterface
    {
        return static::create([], $context)->load($source, $parse);
    }

    public static function fromFiles(array $sources, array $context = [], bool $parse = false): ConfigInterface
    {
        $config = static::create([], $context);

        foreach ($sources as $source) {
            $config->import($source, $parse);
        }

        return $config;
    }

    public static function fromGlob(string $pattern, array $context = [], bool $parse = false): ConfigInterface
    {
        return static::fromFiles(glob($pattern), $context, $parse);
    }

    public static function compile(string|array $sources, array $context , string $target): ConfigInterface
    {
        $config = static::create([], $context);

        $sources = is_string($sources) ? [$sources] : $sources;

        foreach ($sources as $source) {
            if (is_string($source)) {
                $config->import(self::fromGlob($source, $context, true));
            } elseif ($source instanceof ConfigInterface || is_array($source)) {
                $config->import($source);
            }
        }

        $config->export($target);

        return $config;
    }

}