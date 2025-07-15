<?php
namespace Concept\Config\Resource;

interface ResourceInterface
{
    /**
     * Read the data
     * 
     * @param array $data        The target data
     * @param mixed $source      The source
     * @param bool  $withParser  Whether to use the parser
     * 
     * @return static
     */
    public function read(array &$data, string $source, bool $withParser = true): static;

    /**
     * Write the data
     * 
     * @param mixed $target    The target
     * @param array  $data      The data
     * 
     * @return static
     */
    public function write(mixed $target, array $data): static;
}