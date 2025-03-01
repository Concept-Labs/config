<?php
namespace Concept\Config\Adapter;

use Concept\Config\ConfigInterface;
use Concept\Config\Exception\InvalidArgumentException;

class Adapter extends AbstractAdapter
{

    private array $adapterCache = [];

    protected function  createAdapter(string $adapter): AdapterInterface
    {
        return new $adapter($this->getConfig());
    }

    /**
     * {@inheritDoc}
     */
    public function import(mixed $source): array
    {
        return $this->getAdapter($source)->import($source);
    }

    /**
     * {@inheritDoc}
     */
    public function export(mixed $target): bool
    {
        $this->getAdapter($target)->export($target);

        return true;
    }

    /**
     * Get the adapter for the source
     * 
     * @param mixed $target_or_source
     * @return AdapterInterface
     */
    protected function getAdapter(mixed $target_or_source): AdapterInterface
    {
        $adapterClass = $this->matchAdapter($target_or_source);

        if (!isset($this->adapterCache[$adapterClass])) {
            $this->adapterCache[$adapterClass] = $this->createAdapter($adapterClass);
        }

        return $this->adapterCache[$adapterClass];
    }

    
    /**
     * Match the adapter to the source
     * 
     * @param mixed $target_or_source
     * @return string
     * 
     * @throws InvalidArgumentException
     */
    protected function matchAdapter(mixed $target_or_source): string
    {
        
        return match(true) {
            is_array($target_or_source) => ArrayAdapter::class,
            is_object($target_or_source) && $target_or_source instanceof ConfigInterface => ConfigAdapter::class,
            /**
             * File $target_or_source
             */
            is_string($target_or_source)  => 

                match(pathinfo($target_or_source, PATHINFO_EXTENSION)) {
                    'json' => JsonAdapter::class,
                    'php' => PhpFileAdapter::class,
                    // 'xml' => XmlFileAdapter::class,
                    // 'yaml' => YamlFileAdapter::class,
                    // 'ini' => IniFileAdapter::class,
                    default => throw new InvalidArgumentException('Unsupported file type:'.' '.pathinfo($source, PATHINFO_EXTENSION))
                },

            //static::matchFileAdapter($source),
            default => new InvalidArgumentException('Invalid config source'),
        };

    }

   
   
}
        