<?php
namespace Concept\Config\Adapter\Processor;

use Concept\Config\Adapter\Exception\InvalidArgumentException;
use Concept\Config\ConfigInterface;

class ProcessorAggregator implements ProcessorAggregatorInterface
{
    private array $processors = [];

    private $stack = null;

    public function __construct(
        //private readonly mixed $source, 
        private ConfigInterface $config
    )
    {
        $this->addProcessor(ContextProcessor::class);
        $this->addProcessor(ExpressionProcessor::class);
        //$this->addProcessor(new ExpressionProcessor());
    }

    public function addProcessor(string $processor, ?int $priority = null): void
    {
        if (!class_exists($processor) || !is_subclass_of($processor, ProcessorInterface::class)) {
            throw new InvalidArgumentException(
                sprintf('Processor must be a valid class name that implements %s. "%s" Given', ProcessorInterface::class, $processor )
            );
        }
        $this->processors[$priority ?? $processor::getPriority() ?? 0][] = $processor;
        $this->stack = null;
    }


    public function process(array &$data, mixed $source = null): void
    {
        if ($source !== null) {    
            /**
             @todo: more correct way to handle this?
            */
            $currentSource = $this->getConfig()->get('___current_source');
            $this->getConfig()->set('___current_source', $source);
        }

        $this->processData($data);

        
        if ($source !== null) {
            /**
             @todo: more correct way to handle this?
            */
            if ($currentSource === null) {
                $this->getConfig()->unset('___current_source');
            } else {
                $this->getConfig()->set('___current_source', $currentSource);
            }
        }
    }

    protected function processData(array &$data): void
    {
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $this->processData($value);
            } else {
                $this->invokeProcessors($key, $value);
            }
        }
    }

    private function invokeProcessors(string $key, mixed &$value): void
    {
        $this->getProcessorMiddlewareStack()($key, $value, $this->config);
    }
    

    protected function getProcessorMiddlewareStack(): callable
    {
        if ($this->stack !== null) {
            return $this->stack;
        }

        $next = fn(string $key, mixed &$value, ConfigInterface $config) => $value;

        foreach ($this->processors as $priority => $processors) {
            foreach ($processors as $processor) {
                $next = $this->wrapProcessor($processor, $next);
            }
        }

        return $this->stack = $next;
    }

    protected function wrapProcessor(string $processor, callable $next): callable
    {
        return function (string $key, mixed &$value, ConfigInterface $config) use ($processor, $next) {
            return $processor::process($key, $value, $config, $next);
        };
    }

    protected function getConfig(): ConfigInterface
    {
        return $this->config;
    }
}