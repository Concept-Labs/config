<?php

namespace Cl\Config\DataProvider\File\Json;

use Cl\Adapter\String\Json\JsonAdapter;
use Cl\Config\DataProvider\File\FileDataProviderAbstract;
use Cl\Config\DataProvider\File\Json\Exception\JsonEncodeException;

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

        try {

            $content = $this->read();
            $array = JsonAdapter::toArray(json: $content, throwOnFail: true);

        } catch (Throwable $e) {
            throw new JsonDecodeException(
                sprintf('Unable to decode JSON from file "%s" with error: "%s"', $this->getPathname(), $e->getMessage()),
                $e->getCode(),
                $e
            );
        }

        $this->toCache($array, $cacheKey);

        return $array;
    }

    /**
     * {@inheritDoc}
     */
    public function toRaw(array $data): string
    {
        try {
            $json = JsonAdapter::toJson(data: $data, flags: JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR, throwOnFail: true);
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