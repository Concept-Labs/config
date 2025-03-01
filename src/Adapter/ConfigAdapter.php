<?php
namespace Concept\Config\Adapter;

use Concept\Config\Adapter\Exception\InvalidArgumentException;
use Concept\Config\ConfigInterface;

class ConfigAdapter extends AbstractAdapter
{
    /**
     * {@inheritDoc}
     * 
     * @throws InvalidArgumentException
     */
    public function import(mixed $source): array
    {
        if (!$source instanceof ConfigInterface) {
            throw new InvalidArgumentException('Invalid config source provided. Source is not a file');
        }

        return $source->asArray();
    }

    /**
     * {@inheritDoc}
     * 
     * @throws InvalidArgumentException
     */
    public function export(mixed $target): bool
    {
        if (!$target instanceof ConfigInterface) {
            throw new InvalidArgumentException('Invalid config target provided. Target is not a file');
        }

        $data = $this->getConfig()->asArrayCopy();

        $target->hydrate($data);

        return true;
    }

}