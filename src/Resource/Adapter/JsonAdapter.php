<?php
namespace Concept\Config\Resource\Adapter;

use Concept\Config\Resource\AdapterInterface;

class JsonAdapter implements AdapterInterface
{

    /**
     * {@inheritDoc}
     */
    public static function supports(string $uri): bool
    {
        return pathinfo($uri, PATHINFO_EXTENSION) === 'json';
    }

    /**
     * {@inheritDoc}
     */
    public function read(string $uri): array
    {
        return $this->decode(file_get_contents($uri));
    }

    /**
     * {@inheritDoc}
     */
    public function write(string $target, array $data): static
    {

        file_put_contents($target, $this->encode($data));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function encode(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * {@inheritDoc}
     */
    public function decode(string $data): array
    {
        return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
    }
}