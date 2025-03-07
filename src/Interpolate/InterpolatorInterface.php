<?php
namespace Concept\Config\Interpolate;

use Concept\Config\ConfigInterface;

interface InterpolatorInterface
{
    const OPTIONS_EXPRESSION = '#expression';
    const OPTIONS_EXPRESSION_ARGUMETS = '#arguments';

    const OPTIONS_KEY = '#key';
    const OPTIONS_PATH = '#path';
    const OPTIONS_VALUE = '#value';
    const OPTIONS_SOURCE = '#source';

    public static function create(string $value, ?array $options = null): static;
    public function __invoke(ConfigInterface $config);
    public static function match(mixed $value): bool;
}