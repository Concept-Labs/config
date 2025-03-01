<?php
namespace Concept\Config\Adapter;

interface AdapterInterface
{
    public function import(mixed $source): array;
    public function export(mixed $target): bool;
}