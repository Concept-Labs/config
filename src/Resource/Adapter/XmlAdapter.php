<?php

declare(strict_types=1);

namespace Concept\Config\Resource\Adapter;

use Concept\Config\Resource\AdapterInterface;
use Concept\Config\Resource\Exception\InvalidArgumentException;
use Concept\Config\Resource\Exception\ResourceException;
use SimpleXMLElement;

/**
 * XML Adapter
 * 
 * Handles XML file reading and writing.
 * Converts XML to associative arrays and vice versa.
 *
 * @package Concept\Config\Resource\Adapter
 */
class XmlAdapter implements AdapterInterface
{
    /**
     * {@inheritDoc}
     */
    public static function supports(string $path): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return $extension === 'xml';
    }

    /**
     * {@inheritDoc}
     */
    public function read(string $path): array
    {
        if (!file_exists($path)) {
            throw new ResourceException("File not found: $path");
        }

        $content = file_get_contents($path);
        
        if ($content === false) {
            throw new ResourceException("Failed to read file: $path");
        }

        return $this->decode($content);
    }

    /**
     * {@inheritDoc}
     */
    public function write(string $path, array $data): static
    {
        $xml = $this->encode($data);
        
        if (file_put_contents($path, $xml) === false) {
            throw new ResourceException("Failed to write file: $path");
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function decode(string $content): array
    {
        libxml_use_internal_errors(true);
        
        try {
            $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
            
            if ($xml === false) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                
                $errorMessages = array_map(
                    fn($error) => $error->message,
                    $errors
                );
                
                throw new InvalidArgumentException(
                    "Invalid XML: " . implode(', ', $errorMessages)
                );
            }
            
            return $this->xmlToArray($xml);
            
        } finally {
            libxml_use_internal_errors(false);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function encode(array $data): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><config/>');
        $this->arrayToXml($data, $xml);
        
        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        
        return $dom->saveXML();
    }

    /**
     * Convert SimpleXMLElement to array
     *
     * @param SimpleXMLElement $xml
     * @return array
     */
    protected function xmlToArray(SimpleXMLElement $xml): array
    {
        $json = json_encode($xml);
        $array = json_decode($json, true);
        
        return is_array($array) ? $array : [];
    }

    /**
     * Convert array to XML
     *
     * @param array $data
     * @param SimpleXMLElement $xml
     * @return void
     */
    protected function arrayToXml(array $data, SimpleXMLElement &$xml): void
    {
        foreach ($data as $key => $value) {
            // Handle numeric keys
            if (is_numeric($key)) {
                $key = "item{$key}";
            }
            
            // Sanitize key for XML
            $key = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)$key);
            
            if (is_array($value)) {
                // Check if it's a sequential array
                if (array_keys($value) === range(0, count($value) - 1)) {
                    // Sequential array - create multiple child elements
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $subnode = $xml->addChild($key);
                            $this->arrayToXml($item, $subnode);
                        } else {
                            $xml->addChild($key, htmlspecialchars((string)$item, ENT_XML1));
                        }
                    }
                } else {
                    // Associative array - create child element and recurse
                    $subnode = $xml->addChild($key);
                    $this->arrayToXml($value, $subnode);
                }
            } else {
                // Scalar value
                $xml->addChild($key, htmlspecialchars((string)$value, ENT_XML1));
            }
        }
    }
}
