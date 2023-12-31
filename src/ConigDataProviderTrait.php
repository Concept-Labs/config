<?php
namespace Cl\Config;

use Cl\Config\DataProvider\ConfigDataProviderInterface;
use Cl\Config\Exception\InvalidPathException;

trait ConigDataProviderTrait
{

    /**
     * Undocumented variable
     *
     * @var array
     */
    protected array $configDataProviders;

    /**
     * Add the Provider
     *
     * @param ConfigDataProviderInterface $configDataProvider 
     * 
     * @return ConfigInterface
     */
    public function addProvider(ConfigDataProviderInterface $configDataProvider): ConfigInterface
    {
        $this->configDataProviders[] = $configDataProvider;

        return $this;
    }

    /**
     * Add the Provider
     *
     * @param ConfigDataProviderInterface[] $configDataProviders 
     * 
     * @return ConfigInterface
     */
    public function addProviders(array $configDataProviders): ConfigInterface
    {
        foreach ($configDataProviders as $configDataProvider) {
            if (!$configDataProvider instanceof ConfigDataProviderInterface) {
                throw new InvalidPathException(sprintf("Config data provider must be instance of %s", ConfigDataProviderInterface::class));
            }
            $this->addProvider($configDataProvider);
        }
        
        return $this;
    }

    /**
     * Get the providers
     *
     * @return array
     */
    public function getProviders(): array
    {
        return $this->configDataProviders;
    }
}