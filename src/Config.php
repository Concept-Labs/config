<?php
namespace Cl\Config;

use Cl\Config\DataProvider\ConfigDataProviderInterface;
use Cl\Config\Exception\UnableToLoadConfigException;
use Cl\Iterator\ArrayPathIterator\ArrayPathIterator;



/**
 * Class for configuration management.
 */
class Config extends ArrayPathIterator implements ConfigInterface
{    
    protected array $configDataProviders;

    /**
     * Constructor
     * 
     * Config data may be array or \ArrayAccess + \Traversable for the tree access
     * or final value used for get()
     * 
     * @param mixed|null                         $configData          Configuration data. 
     * @param ConfigDataProviderInterface[]|null $configDataProviders Data providers
     */
    public function __construct(
        mixed $configData,
        array $configDataProviders = [],
        protected string $path = '',
        protected Config|null $parent = null
    ) {
        //init properties
        array_reduce(
            $configDataProviders, 
            fn(ConfigDataProviderInterface $configDataProvider) => $this->addProvider($configDataProvider)
        );
        
        
        parent::__construct($configData);
    }

    /**
     * {@inheritDoc}
     */
    public function load(): bool
    {
        $data = $this->getArrayCopy();
        $errors = [];
        foreach ($this->getProviders() as $provider) {
            try {
                $data = array_merge_recursive($provider->load());
            } catch (\Throwable $e) {
                $errors[] = $provider->getFilePath();
            }
        }
        if (count($errors)) {
            throw new UnableToLoadConfigException(sprintf("Unable to load config(s) %s ", implode(', ', $errors)));
        }
        $this->setStorageArray($data);
        return true;
    }
    
    public function addProvider(ConfigDataProviderInterface $configDataProvider): static
    {
        $this->configDataProviders[] = $configDataProvider;

        return $this;
    }

    protected function getProviders(): array
    {
        return $this->configDataProviders;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $path): bool
    {
        return $this->offsetExists($path);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $path, mixed $default = null): mixed
    {
        return $this->offsetGet($path) ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $path, mixed $value): static
    {
        $this->offsetSet($path, $value);
        return $this;
    }
    
    /**
     * {@inheritDoc}
     */
    public function remove(string $path): static
    {
        $this->offsetUnset($path);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        return $this->getArrayCopy();
    }
}