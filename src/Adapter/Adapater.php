<?php
namespace Concept\Config\Adapter;

use Concept\Config\ConfigInterface;
use Concept\Config\Exception\InvalidArgumentException;

class Adapater implements AdapterInterface
{
    /**
     * {@inheritDoc}
     * 
     * @throws InvalidArgumentException
     */
    public static function load(mixed $source): array
    {
        return (static::matchAdapter($source))::load($source);
    }

    /**
     * Match the adapter to the source
     * 
     * @param mixed $source
     * @return string
     * 
     * @throws InvalidArgumentException
     */
    protected static function matchAdapter(mixed $source): string
    {
        return match(true) {
            $source instanceof ConfigInterface => ConfigAdapter::class,
            is_array($source) => ArrayAdapter::class,
            JsonAdapter::isJson($source) => JsonAdapter::class,
            is_string($source) && is_file($source) => static::matchFileAdapter($source),
            default => new InvalidArgumentException('Invalid config source'),
        };

    }

    /**
     * Match the file adapter to the source
     * 
     * @param string $source
     * @return string
     * 
     * @throws InvalidArgumentException
     */
    protected static function matchFileAdapter(string $source): string
    {
        return match(pathinfo($source, PATHINFO_EXTENSION)) {
            'json' => JsonFileAdapter::class,
            'php' => PhpFileAdapter::class,
            /**
             * @todo Implement other file types
             */
            // 'xml' => XmlFileAdapter::class,
            // 'yaml' => YamlFileAdapter::class,
            // 'ini' => IniFileAdapter::class,
            default => throw new InvalidArgumentException('Unsupported file type:'.' '.pathinfo($source, PATHINFO_EXTENSION))
        };
    }
   
}
        