<?php
namespace Cl\Config;

use Cl\Config\Exception\UnableToLoadConfigException;
use Cl\Config\DataProvider\ConfigDataProviderInterface;
use Cl\Iterator\ArrayPathIterator\ArrayPathIterator;



/**
 * Class for configuration management.
 */
class Config extends ArrayPathIterator implements ConfigInterface
{    
    use ConfigTrait;
    use ConigDataProviderTrait;


    /**
     * {@inheritDoc}
     */
    public function load(): bool
    {
        $data = $this->all();
        $errors = [];
        /** @var ConfigDataProviderInterface $provider */
        foreach ($this->getProviders() as $provider) {
            try {
                $data = array_merge_recursive($provider->toArray());
            } catch (\Throwable $e) {
                
                throw new UnableToLoadConfigException(
                    sprintf("Unable to load config(s) %s ", $e->getMessage()),
                    $e->getCode(), 
                    $e
                );
            }
        }
        if (count($errors)) {
            
        }
        $this->setStorageArray($data);
        return true;
    }
}