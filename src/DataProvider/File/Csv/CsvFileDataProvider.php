<?php

namespace Cl\Config\DataProvider\File\Json;

use Cl\Config\DataProvider\File\Csv\Exception\CsvException;
use Cl\Config\DataProvider\File\Exception\FileReadException;
use Cl\Config\DataProvider\File\FileDataProviderAbstract;
use Cl\Converter\Csv\CsvConverter;
use Cl\Converter\Exception\CsvConverterException;
use Exception;
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
        if (is_array($array = $this->fromCache($cacheKey))) {
            return $array;
        }
        if (!$content = $this->read()) {
            throw new FileReadException(sprintf('Error reading file "%s"', $this->getPathname()));
        }
        $array = $this->fromCsv($content);

        $this->toCache($array, $cacheKey);

        return $array;
    }

    /**
     * {@inheritDoc}
     */
    public function toRaw(array $data): string
    {
        return $this->toCsv($data);
    }

    /**
     * Converts a CSV string to an array
     *
     * @param string $csvString The array to convert
     * @param bool   $headers   True to fetch headers and use for array keys
     * @param string $separator The CSV field separator
     * @param string $enclosure The CSV field enclosure
     * @param string $escape    The CSV escape character
     *
     * @return array
     * @throws CsvException
     */
    public function fromCsv(string $csvString, bool $headers = true, string $separator = ',', string $enclosure = '"', string $escape = "\\"): array
    {
        try {
            return CsvConverter::toArray($csvString, $headers, $separator, $enclosure, $escape);
        } catch (CsvConverterException $e) {
            throw new CsvException(
                sprintf('Unable to convert array to CSV for file "%s" with error: "%s"', $this->getPathname(), $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Converts an array to a CSV string
     *
     * @param array  $array     The array to convert
     * @param string $separator The CSV field separator
     * @param string $enclosure The CSV field enclosure
     * @param string $escape    The CSV escape character
     *
     * @return string
     * @throws CsvException
     */
    public function toCsv(array $array, string $separator = ',', string $enclosure = '"', string $escape = "\\"): string
    {
        
        try {
            $json = CsvConverter::toCsv($array, $separator, $enclosure, $escape);
        } catch (Throwable $e) {
            throw new CsvException(
                sprintf('Unable to convert array to CSV for file "%s" with error: "%s"', $this->getPathname(), $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
        
        return $json;
    }

}