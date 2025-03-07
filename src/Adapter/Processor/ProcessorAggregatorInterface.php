<?php
namespace Concept\Config\Adapter\Processor;

interface ProcessorAggregatorInterface
{
    public function addProcessor(string $processor, ?int $priority = null): void;
    public function process(array &$data): void;
}