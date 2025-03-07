<?php

namespace Concept\Config\Adapter;

use Concept\Config\Adapter\Exception\InvalidArgumentException;
use Concept\Config\ConfigInterface;

abstract class AbstractAdapter implements AdapterInterface
{
    public function __construct(private ConfigInterface $config) {}

    protected function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    protected function ensureDirectoryExists(string $path): void
    {
        try {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        } catch (\Throwable $e) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unable to create directory: %s (%s)',
                    $dir,
                    $e->getMessage()
                )
            );
        }
    }
}
