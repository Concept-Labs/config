<?php
namespace Concept\Config\Resource;

use Concept\Config\Contract\ParserProviderInterface;

/**
 * Interface for resource handlers
 * 
 * Resources are responsible for reading and writing configuration data
 * from various sources. They work with adapters to handle different
 * file formats and coordinate with parsers for data processing.
 */
interface ResourceInterface
{
    /**
     * Read data from a source
     * 
     * Reads configuration data from the specified source using the appropriate
     * adapter. Optionally processes the data through the parser if available.
     * 
     * @param array &$data The target data array to populate
     * @param string|array $source The source to read from (file path, URL, or array)
     * @param bool $withParser Whether to parse the data after reading
     * 
     * @return static
     */
    public function read(array &$data, string|array $source, bool $withParser = true): static;

    /**
     * Write data to a target
     * 
     * Writes configuration data to the specified target using the appropriate
     * adapter based on the target's format/extension.
     * 
     * @param mixed $target The target to write to (file path, stream, etc.)
     * @param array $data The data to write
     * 
     * @return static
     */
    public function write(mixed $target, array $data): static;

    /**
     * Set the parser provider
     * 
     * Allows the resource to access a parser when needed for parsing
     * configuration data during read operations.
     * 
     * @param ParserProviderInterface $parserProvider The parser provider
     * 
     * @return static
     */
    public function setParserProvider(ParserProviderInterface $parserProvider): static;
}
