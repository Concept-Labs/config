<?php
namespace Concept\Config\Adapter\Export;

interface ExportAdapterInterface
{
    public static function export(array $data, string $path): void;
}