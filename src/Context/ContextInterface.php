<?php

namespace Concept\Config\Context;

use Concept\Config\PathAccess\PathAccessInterface;

interface ContextInterface extends PathAccessInterface
{
    const CONTEXT_BASE = 'BASE';
    const CONTEXT_APPID = 'APPID';
    const CONTEXT_ENV = 'ENV';
    const CONTEXT_VENDOR = 'VENDOR';

}