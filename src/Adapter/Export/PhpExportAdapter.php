<?php
namespace Concept\Config\Adapter\Export;


class PhpExportAdapter implements ExportAdapterInterface
{
    /**
     * {@inheritDoc}
     */
    public static function export(array $data, string $path): void
    {
        $content = "<?php\n\nreturn ".var_export($data, true).";\n";
        file_put_contents($path, $content);
    }
}