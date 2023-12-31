<?php

namespace Cl\Config\DataProvider;

use Cl\Config\DataProvider\Exception\DataProviderException;

/**
 * Interface for configuration providers
 */
interface ConfigDataProviderInterface
{
    /**
     * Converts a provider`s raw data to an array for the config usage
     *
     * @return array
     * @throws DataProviderException
     */
    function toArray(): array;

    /**
     * Converts the data to a raw data for a provider`s save usage
     * 
     * @param array $data 
     *
     * @return mixed
     * @throws DataProviderException
     */
    function toRaw(array $data): mixed;

}