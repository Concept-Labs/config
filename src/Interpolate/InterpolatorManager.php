<?php
namespace Concept\Config\Interpolate;

use Concept\Config\ConfigInterface;

class InterpolatorManager
{
    private array $interpolators = [];

    public function __construct(array $interpolators)
    {
        foreach ($interpolators as $interpolator) {
            $this->add($interpolator);
        }
    }

    public function add(string $interpolator): void
    {
        $this->interpolators[] = $interpolator;
    }

    public function apply(array &$data, ?array $options = null, ?string $path = null ): void
    {
        $options = $options ?? [];
        foreach ($data as $key => &$value) {
            $options[InterpolatorInterface::OPTIONS_KEY] = $key;
            $options[InterpolatorInterface::OPTIONS_VALUE] = $value;

            if (is_array($value)) {
                $this->apply($value, $options, $path ? $path . '.' . $key : $key);
            } else {
                $value = $this->attach($value, $options, $path);
            }
        }


    }

    protected function attach(string $value, ?array $options = null, ?string $path = null): mixed
    {
        foreach ($this->interpolators as $interpolator) {
            if ($interpolator::match($value)) {
                $options = $options ?? [];
                $options[InterpolatorInterface::OPTIONS_PATH] = $path;
                return  new $interpolator($value, $options);
                //return $interpolator($value);
            }
        }

        return $value;
    }

    public function resolve(array &$data, ConfigInterface $config, ?array $options = null): void
    {
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $value = $this->resolve($value, $config, $options);
            } else {
                if ($value instanceof InterpolatorInterface) {
                    $value = $value($config, $options);
                }
            }
        }
    }    

}