<?php

namespace Concept\Config\Facade;

use Concept\Config\Context\Context as ContextService;
use Concept\Config\Context\ContextInterface;

/**
 @todo: move to context facades
 */
class Context
{
    public static function from(array $context): ContextInterface
    {
        return (new ContextService())->fromArray($context);
    }

    public static function captureWith(array $data)
    {
        return (new ContextService())->fromArray(
            array_merge(
                ['SERVER' =>$_SERVER],
                ['ENV' => $_ENV],
                $data
            )
        );
    }
}