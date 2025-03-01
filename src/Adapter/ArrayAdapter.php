<?php
namespace Concept\Config\Adapter;

use Concept\Config\Adapter\Exception\InvalidArgumentException;

class ArrayAdapter extends AbstractAdapter
{
    /**
     * {@inheritDoc}
     * 
     * @throws InvalidArgumentException
     */
    public function import(mixed $source): array
    {
        if (!is_array($source)) {
            throw new InvalidArgumentException('Invalid config source provided. Source is not an array');
        }

        return $this->getConfig()->asArrayCopy($source);
    }

    /**
     * {@inheritDoc}
     * 
     * @throws InvalidArgumentException
     */
    public function export(mixed $target): bool
    {
        throw new InvalidArgumentException(
            "Cannot export to an array. Use a asArray() or asArrayCopy() method to get the array"
        );
    }

}