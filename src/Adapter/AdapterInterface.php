<?php
namespace Concept\Config\Adapter;

interface AdapterInterface
{
    public static function load(mixed $source): array;
}