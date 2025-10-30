<?php

declare(strict_types=1);

namespace Concept\Config\Resource\Adapter;

use Concept\Config\Resource\AdapterInterface;
use Concept\Config\Resource\Exception\InvalidArgumentException;
use Concept\Config\Resource\Exception\ResourceException;

/**
 * YAML Adapter
 * 
 * Handles YAML file reading and writing.
 * Requires the symfony/yaml component.
 *
 * @package Concept\Config\Resource\Adapter
 */
class YamlAdapter implements AdapterInterface
{
    /**
     * {@inheritDoc}
     */
    public static function supports(string $path): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($extension, ['yml', 'yaml'], true);
    }

    /**
     * {@inheritDoc}
     */
    public function read(string $path): array
    {
        if (!file_exists($path)) {
            throw new ResourceException("File not found: $path");
        }

        if (!function_exists('yaml_parse_file')) {
            // Fallback to symfony/yaml if available
            if (class_exists('\Symfony\Component\Yaml\Yaml')) {
                $content = file_get_contents($path);
                $data = \Symfony\Component\Yaml\Yaml::parse($content);
            } else {
                throw new ResourceException(
                    'YAML support requires either the YAML PHP extension or symfony/yaml component. ' .
                    'Install with: composer require symfony/yaml'
                );
            }
        } else {
            $data = yaml_parse_file($path);
        }

        if (!is_array($data)) {
            throw new InvalidArgumentException("YAML file must contain an array: $path");
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function write(string $path, array $data): static
    {
        if (!function_exists('yaml_emit_file')) {
            // Fallback to symfony/yaml if available
            if (class_exists('\Symfony\Component\Yaml\Yaml')) {
                $yaml = \Symfony\Component\Yaml\Yaml::dump($data, 4, 2);
                file_put_contents($path, $yaml);
            } else {
                throw new ResourceException(
                    'YAML support requires either the YAML PHP extension or symfony/yaml component. ' .
                    'Install with: composer require symfony/yaml'
                );
            }
        } else {
            yaml_emit_file($path, $data);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function decode(string $content): array
    {
        if (!function_exists('yaml_parse')) {
            if (class_exists('\Symfony\Component\Yaml\Yaml')) {
                $data = \Symfony\Component\Yaml\Yaml::parse($content);
            } else {
                throw new ResourceException(
                    'YAML support requires either the YAML PHP extension or symfony/yaml component'
                );
            }
        } else {
            $data = yaml_parse($content);
        }

        if (!is_array($data)) {
            throw new InvalidArgumentException("YAML content must be an array");
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function encode(array $data): string
    {
        if (!function_exists('yaml_emit')) {
            if (class_exists('\Symfony\Component\Yaml\Yaml')) {
                return \Symfony\Component\Yaml\Yaml::dump($data, 4, 2);
            } else {
                throw new ResourceException(
                    'YAML support requires either the YAML PHP extension or symfony/yaml component'
                );
            }
        } else {
            return yaml_emit($data);
        }
    }
}
