<?php
namespace Concept\Config\Adapter\Export;


class JsonExportAdapter implements ExportAdapterInterface
{
    /**
     * {@inheritDoc}
     */
    public static function export(array $data, string $path): void
    {
        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        file_put_contents($path, $content);
    }
}