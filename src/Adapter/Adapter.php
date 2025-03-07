<?php
namespace Concept\Config\Adapter;

use Concept\Config\Adapter\Facade\AggregateProcessor;
use Concept\Config\Adapter\Processor\ProcessorAggregatorInterface;
use Concept\Config\ConfigInterface;
use Concept\Config\Exception\InvalidArgumentException;
use Concept\Config\Interpolate\ContextInterpolator;
use Concept\Config\Interpolate\ExpressionInterpolator;
use Concept\Config\Interpolate\InterpolatorInterface;
use Concept\Config\Interpolate\InterpolatorManager;

class Adapter extends AbstractAdapter
{
    private $inteprolator;

    public function __construct(private ConfigInterface $config) {
        parent::__construct($config);

        $this->inteprolator = new InterpolatorManager(
            [
                ContextInterpolator::class,
                ExpressionInterpolator::class
            ]
        );
    }

    /**
     * Adapter cache
     * 
     * @var array<string, AdapterInterface>
     */
    private static array $adapters = [];
    private ?ProcessorAggregatorInterface $processor = null;

    public function getProcessor(): ProcessorAggregatorInterface
    {
        if ($this->processor === null) {
            $this->processor = AggregateProcessor::withConfig($this->getConfig());
        }

        return $this->processor;
    }

    /**
     * {@inheritDoc}
     */
    public function import(mixed $source, bool $compile = true): array
    {
        $data = $this->getAdapter($source)->import($source);

        $this->inteprolator->apply($data, [
            InterpolatorInterface::OPTIONS_SOURCE => $source,
        ]);
        
        if ($compile) {
            //$this->getProcessor()->process($data, $source);
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function export(mixed $target): bool
    {
        $this->getAdapter($target)->export($target);

        return true;
    }

    protected function  createAdapter(string $adapter): AdapterInterface
    {
        return new $adapter($this->getConfig());
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

        return $this->createAdapter($adapterClass);

        /**
         @todo Cache the adapter: pass config to adapter
         */
        if (!isset(static::$adapters[$adapterClass])) {
            static::$adapters[$adapterClass] = $this->createAdapter($adapterClass);
        }

        return static::$adapters[$adapterClass];
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
            is_string($target_or_source) && preg_match('/composer\.json$/', $target_or_source) => ComposerAdapter::class,
            /**
             * File $target_or_source
             */
            is_string($target_or_source)  => 

                match(pathinfo($target_or_source, PATHINFO_EXTENSION)) {
                    'json' => JsonAdapter::class,
                    'php' => PhpAdapter::class,
                    // 'xml' => XmlFileAdapter::class,
                    // 'yaml' => YamlFileAdapter::class,
                    // 'ini' => IniFileAdapter::class,
                    default => throw new InvalidArgumentException(
                        'Unsupported file type:'.' '.pathinfo($target_or_source, PATHINFO_EXTENSION)
                        )
                },

            //static::matchFileAdapter($source),
            default => new InvalidArgumentException('Invalid config source'),
        };

    }

   
   
}
        