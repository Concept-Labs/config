<?php

namespace Cl\Config\DataProvider\File\Json;

use Cl\Adapter\String\Csv\CsvAdapter;
use Cl\Config\DataProvider\File\Csv\Exception\CsvException;
use Cl\Config\DataProvider\File\FileDataProviderAbstract;

use Throwable;

/**
 * Configuration provider from CSV file.
 */
class CsvFileDataProvider extends FileDataProviderAbstract
{
    

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $cacheKey = $this->getCacheKey();
        $array = $this->fromCache($cacheKey);
        if (is_array($array)) {
            return $array;
        }
        
        try {
            $content = $this->read();
            $array = CsvAdapter::toArray(csvString: $content, headers: true);
        } catch (Throwable $e) {
            throw new CsvException(
                sprintf('Unable to convert array to CSV for file "%s" with error: "%s"', $this->getPathname(), $e->getMessage()),
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
            $content = CsvAdapter::toCsv(data: $data);
        } catch(Throwable $e) {
            throw new CsvException(
                sprintf('Unable to convert array to CSV for file "%s" with error: "%s"', $this->getPathname(), $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
        return $content; 
    }

}