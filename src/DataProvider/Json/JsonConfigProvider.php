<?php

namespace Cl\Config\DataProvider\Json;

use Cl\Config\DataProvider\ConfigDataProviderInterface;

/**
 * Configuration provider from JSON file.
 */
class JsonConfigProvider implements ConfigDataProviderInterface
{
    /**
     * Constructor.
     *
     * @param string $filePath Path to the JSON file.
     */
    public function __construct(private string $filePath)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function load(): array
    {
        return json_decode(file_get_contents($this->getFilePath()), true) ?: [];
    }

    /**
     * {@inheritDoc}
     */
    public function save(array $data): bool
    {
        return file_put_contents($this->getFilePath(), json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }
    
    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;
        return $this;
    }
}