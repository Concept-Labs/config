<?php

namespace Concept\Config\Resource;

interface AdapterInterface
{
    /**
     * Check if adapter can handle the uri.
     *
     * @param string $uri
     * 
     * @return bool
     */
    public static function supports(string $uri): bool;

    /**
     * Decode the data.
     *
     * @param string $data
     * 
     * @return array
     */
    public function decode(string $data): array;

    /**
     * Encode the data.
     *
     * @param array $data
     * 
     * @return string
     */
    public function encode(array $data): string; //Stream?

    /**
     * Read the data from the uri.
     *
     * @param string $uri
     * 
     * @return array
     */
    public function read(string $uri): array;

    /**
     * Write the data to the target.
     *
     * @param string $target
     * @param array $data
     * 
     * @return static
     */
    public function write(string $target, array $data): static;
}