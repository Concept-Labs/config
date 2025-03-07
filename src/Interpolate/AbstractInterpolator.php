<?php
namespace Concept\Config\Interpolate;

abstract class AbstractInterpolator implements InterpolatorInterface
{
    public function __construct(private mixed $value, private array $options = [])
    {
        $this->init();
    }

    public static function create(string $value, ?array $options = null): static
    {
        return new static($value, $options);
    }

    protected function init(): void
    {
    }

    protected function getValue(): string
    {
        return $this->value;
    }

    protected function getOptions(?string $option = null): mixed
    {
        return $option 
            ? ($this->options[$option] ?? null) 
            : $this->options;
    }
}