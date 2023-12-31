<?php
namespace Cl\Config;

use Cl\Iterator\ArrayPathIterator\Exception\InvalidPathException as IteratorInvalidPathException;
use Cl\Config\Exception\InvalidPathException;

trait ConfigTrait
{
    /**
     * {@inheritDoc}
     */
    public function has(string $path): bool
    {
        try {
            return $this->offsetExists($path);
        } catch (IteratorInvalidPathException $e) {
            throw new InvalidPathException($e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $path, mixed $default = null): mixed
    {
        try {
            return $this->offsetGet($path) ?? $default;
        } catch (IteratorInvalidPathException $e) {
            throw new InvalidPathException($e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $path, mixed $value): ConfigInterface
    {
        try {
            $this->offsetSet($path, $value);
        } catch (IteratorInvalidPathException $e) {
            throw new InvalidPathException($e);
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $path): ConfigInterface
    {
        try {
            $this->offsetUnset($path);
        } catch (IteratorInvalidPathException $e) {
            throw new InvalidPathException($e);
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function all(): \Traversable|array
    {
        return $this->getArrayCopy();
    }
}