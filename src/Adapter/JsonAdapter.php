<?php
namespace Concept\Config\Adapter;

use Concept\Config\Adapter\Exception\InvalidArgumentException;

class JsonAdapter extends AbstractAdapter
{
    /**
     * {@inheritDoc}
     * 
     * @throws InvalidArgumentException
     */
    public function import(mixed $source): array
    {
        return $this->readJson($source);
        //$this->getConfig()->hydrate($data);
    }

    /**
     * {@inheritDoc}
     * 
     * @throws InvalidArgumentException
     */
    public function export(mixed $path): bool
    {
        if (!is_string($path)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid config target provided. Target (%s) is not a file',
                    $path
                )
            );
        }

        $this->ensureDirectoryExists($path);

        $data = $this->getConfig()->asArray();

        try {
         $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException(
                sprintf(
                    "Unable to encode data to JSON: %s",
                    $e->getMessage()
                )
            );
        }

        try {
            file_put_contents($path, $json);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException(
                sprintf(
                    "Unable to write to file: %s (%s) ",
                    $path,
                    $e->getMessage()
                )
            );
        }

        return true;
    }

    protected function readJson(string $path): array
    {
        try {
            if (!is_string($path) || !is_file($path)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid config source provided. Source (%s) is not a file',
                        $path
                    )
                );
            }

            $json = file_get_contents($path);
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            return $data;

        } catch (\Throwable $e) {
            throw new InvalidArgumentException(
                sprintf(
                    "Unable to load file: %s (%s) ",
                    $path,
                    $e->getMessage()
                )
            );
        }

        
    }
}