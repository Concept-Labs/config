<?php

namespace Concept\Config\Adapter\Export;

use Concept\Config\Exception\InvalidArgumentException;

class ExportAdapter implements ExportAdapterInterface
{
/**
     * {@inheritDoc}
     * 
     */
    public static function export(array $data, string $path): void
    {

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        if (!is_writable(dirname($path))) {
            throw new \RuntimeException('Directory is not writable: '.dirname($path));
        }

        (static::matchAdapter($path))::export($data, $path);
    }


    /**
     * Match the adapter to the source
     * 
     * @param string $path
     * @return string
     * 
     * @throws InvalidArgumentException
     */
    protected static function matchAdapter(string $path): string
    {
        return match(pathinfo($path, PATHINFO_EXTENSION)) {
            'json' => JsonExportAdapter::class,
            'php' => PhpExportAdapter::class,
            /**
             * @todo Implement other file types
             */
            // 'xml' => XmlFileAdapter::class,
            // 'yaml' => YamlFileAdapter::class,
            // 'ini' => IniFileAdapter::class,
            default => throw new InvalidArgumentException('Unsupported file type:'.' '.pathinfo($path, PATHINFO_EXTENSION))
        };
    }

}