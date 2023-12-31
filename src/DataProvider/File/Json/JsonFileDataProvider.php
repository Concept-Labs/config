<?php

namespace Cl\Config\DataProvider\File\Json;

use Cl\Config\DataProvider\File\Exception\FileReadException;
use Cl\Config\DataProvider\File\FileDataProviderAbstract;
use Cl\Config\DataProvider\File\Json\Exception\JsonEncodeException;
use Exception;
use Throwable;

use Cl\Config\DataProvider\File\Json\Exception\JsonDecodeException;



/**
 * Configuration provider from JSON file.
 */
class JsonFileDataProvider extends FileDataProviderAbstract
{
    

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $cacheKey = $this->getCacheKey();
        if (is_array($array = $this->fromCache($cacheKey))) {
            return $array;
        }
        if (!$content = $this->read()) {
            throw new FileReadException(sprintf('Error reading file "%s"', $this->getPathname()));
        }
        $array = $this->fromJson($content);

        $this->toCache($array, $cacheKey);

        return $array;
    }

    /**
     * {@inheritDoc}
     */
    public function toRaw(array $data): string
    {
        return $this->toJson($data);
    }

    /**
     * Converts the JSON to the array
     *
     * @param string $json 
     * 
     * @return array
     * @throws JsonDecodeException
     */
    public function fromJson(string $json, bool|null $associative = true, int|null $depth = 512, int $flags = JSON_THROW_ON_ERROR): array
    {
        $array = [];
        try {
            $array = json_decode($json, associative: $associative, depth: $depth, flags: $flags);
            if (empty($array)) {
                throw new Exception("Decoded array is empty");
            }
        } catch (Throwable $e) {
            throw new JsonDecodeException(
                sprintf('Unable to decode JSON in file "%s" with error: "%s"', $this->getPathname(), $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
        
        return $array;
    }

    /**
     * Encode the data to the JSON
     *
     * @param mixed    $data 
     * @param int|null $flags 
     * @param int|null $depth 
     * 
     * @return string
     * @throws JsonEncodeException
     */
    public function toJson(mixed $data, int|null $flags = JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR, int|null $depth = 512): string
    {
        $json = '';
        try {
            $json = json_encode($data, flags: $flags, depth: $depth);
            if (!strlen($json)) {
                throw new Exception("Encoded JSON is empty");
            }
        } catch (Throwable $e) {
            throw new JsonEncodeException(
                sprintf('Unable to encode to JSON for file "%s" with error: "%s"', $this->getPathname(), $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
        
        return $json;
    }

}