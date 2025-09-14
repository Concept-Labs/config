<?php

namespace Concept\Config;

class StaticFactory
{

    /**
     * Create a new config instance
     * 
     * @param array $data Initial data for the config
     * @param array $context Initial context for the config
     * @param bool $parse Whether to parse the config data
     * 
     * @return ConfigInterface
     */
    public static function create(array $data = [], array $context = [], bool $parse = false): ConfigInterface
    {
        $config = new Config([], $context);

        if (!empty($data)) {
            $config->load($data, $parse);
        }

        return $config;
    }

    /**
     * Create a new config instance from a file
     * 
     * @param string $source The source file
     * @param array $context Initial context for the config
     * @param bool $parse Whether to parse the config data
     * 
     * @return ConfigInterface
     */
    public static function fromFile(string $source, array $context = [], bool $parse = false): ConfigInterface
    {
        return static::create([], $context)->load($source, $parse);
    }

    /**
     * Create a new config instance from multiple files
     * 
     * @param array $sources The source files
     * @param array $context Initial context for the config
     * @param bool $parse Whether to parse the config data
     * 
     * @return ConfigInterface
     */
    public static function fromFiles(array $sources, array $context = [], bool $parse = false): ConfigInterface
    {
        $config = static::create([], $context);

        foreach ($sources as $source) {
            $config->import($source, $parse);
        }

        return $config;
    }

    /**
     * Create a new config instance from a glob pattern
     * 
     * @param string $pattern The glob pattern
     * @param array $context Initial context for the config
     * @param bool $parse Whether to parse the config data
     * 
     * @return ConfigInterface
     */
    public static function fromGlob(string $pattern, array $context = [], bool $parse = false): ConfigInterface
    {
        return static::fromFiles(glob($pattern), $context, $parse);
    }

    /**
     * Create a new config instance from multiple sources
     * 
     * @param string|array $sources The source files
     * @param array $context Initial context for the config
     * @param string $target The target file
     * 
     * @return ConfigInterface
     */
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