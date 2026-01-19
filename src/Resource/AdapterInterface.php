<?php

namespace Concept\Config\Resource;

/**
 * Interface for configuration file adapters
 * 
 * Adapters handle reading and writing configuration data in specific file
 * formats (JSON, PHP, YAML, etc.). Each adapter is responsible for:
 * - Detecting if it can handle a given URI/file
 * - Encoding data to the format's string representation
 * - Decoding the format's string representation to arrays
 * - Reading from and writing to files
 */
interface AdapterInterface
{
    /**
     * Check if the adapter can handle a URI
     *
     * Determines whether this adapter supports the given URI based on
     * file extension, protocol, or other criteria. This is used by the
     * AdapterManager to select the appropriate adapter.
     *
     * @param string $uri The URI to check (file path, URL, etc.)
     * 
     * @return bool True if this adapter can handle the URI
     */
    public static function supports(string $uri): bool;

    /**
     * Decode string data to array
     *
     * Parses the string representation of the configuration format
     * and returns it as a PHP array.
     *
     * @param string $data The encoded string data
     * 
     * @return array The decoded configuration array
     * @throws \Concept\Config\Resource\Exception\ResourceException If decoding fails
     */
    public function decode(string $data): array;

    /**
     * Encode array data to string
     *
     * Converts a PHP array to the string representation of the
     * configuration format (e.g., JSON string, PHP code, etc.).
     *
     * @param array $data The configuration data to encode
     * 
     * @return string The encoded string representation
     * @throws \Concept\Config\Resource\Exception\ResourceException If encoding fails
     */
    public function encode(array $data): string;

    /**
     * Read configuration from a URI
     *
     * Reads the file or resource at the given URI, decodes it,
     * and returns the configuration as an array.
     *
     * @param string $uri The URI to read from (file path, URL, etc.)
     * 
     * @return array The configuration data
     * @throws \Concept\Config\Resource\Exception\ResourceException If reading fails
     */
    public function read(string $uri): array;

    /**
     * Write configuration to a target
     *
     * Encodes the configuration data and writes it to the specified
     * target file or resource.
     *
     * @param string $target The target file path or URI
     * @param array $data The configuration data to write
     * 
     * @return static The adapter instance for method chaining
     * @throws \Concept\Config\Resource\Exception\ResourceException If writing fails
     */
    public function write(string $target, array $data): static;
}
