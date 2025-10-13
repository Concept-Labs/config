# @extends Plugin Examples

This document provides real-world examples of using the `@extends` plugin for configuration inheritance.

## Basic Example

The example from the problem statement:

```php
<?php
use Concept\Config\Config;
use Concept\Config\Parser\Plugin\Directive\ExtendsPlugin;

$config = new Config([
    'foo' => [
        'baz' => [
            'bar' => 'some',
            'bar2' => 'another'
        ]
    ],
    'fooze' => [
        '@extends' => 'foo.baz',
        'acme' => 'lorem'
    ]
]);

$config->getParser()->registerPlugin(ExtendsPlugin::class, 997);
$config->getParser()->parse($config->dataReference());

// Result:
// [
//     'foo' => [
//         'baz' => [
//             'bar' => 'some',
//             'bar2' => 'another'
//         ]
//     ],
//     'fooze' => [
//         'bar' => 'some',
//         'bar2' => 'another',
//         'acme' => 'lorem'
//     ]
// ]
```

## Multiple Environments Example

```php
<?php
use Concept\Config\Config;
use Concept\Config\Parser\Plugin\Directive\ExtendsPlugin;

$config = new Config([
    'app' => [
        'base' => [
            'name' => 'MyApp',
            'timezone' => 'UTC',
            'locale' => 'en',
            'debug' => false,
            'cache' => [
                'ttl' => 3600
            ]
        ]
    ],
    'environments' => [
        'development' => [
            '@extends' => 'app.base',
            'debug' => true,
            'cache' => [
                'driver' => 'array',
                'ttl' => 0
            ]
        ],
        'staging' => [
            '@extends' => 'app.base',
            'cache' => [
                'driver' => 'redis',
                'prefix' => 'staging_'
            ]
        ],
        'production' => [
            '@extends' => 'app.base',
            'cache' => [
                'driver' => 'redis',
                'prefix' => 'prod_',
                'ttl' => 7200
            ]
        ]
    ]
]);

$config->getParser()->registerPlugin(ExtendsPlugin::class, 997);
$config->getParser()->parse($config->dataReference());

// Access environment-specific config
print_r($config->get('environments.development'));
// [
//     'name' => 'MyApp',
//     'timezone' => 'UTC',
//     'locale' => 'en',
//     'debug' => true,
//     'cache' => [
//         'driver' => 'array',
//         'ttl' => 0
//     ]
// ]

print_r($config->get('environments.production'));
// [
//     'name' => 'MyApp',
//     'timezone' => 'UTC',
//     'locale' => 'en',
//     'debug' => false,
//     'cache' => [
//         'driver' => 'redis',
//         'prefix' => 'prod_',
//         'ttl' => 7200
//     ]
// ]
```

## Database Connection Example

```php
<?php
use Concept\Config\Config;
use Concept\Config\Parser\Plugin\Directive\ExtendsPlugin;

$config = new Config([
    'database' => [
        'defaults' => [
            'driver' => 'mysql',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'options' => [
                'PDO::ATTR_ERRMODE' => 'PDO::ERRMODE_EXCEPTION',
                'PDO::ATTR_EMULATE_PREPARES' => false
            ]
        ],
        'connections' => [
            'primary' => [
                '@extends' => 'database.defaults',
                'host' => 'db-primary.example.com',
                'port' => 3306,
                'database' => 'app_primary',
                'username' => 'app_user',
                'password' => 'secret'
            ],
            'replica' => [
                '@extends' => 'database.defaults',
                'host' => 'db-replica.example.com',
                'port' => 3306,
                'database' => 'app_primary',
                'username' => 'app_readonly',
                'password' => 'secret'
            ]
        ]
    ]
]);

$config->getParser()->registerPlugin(ExtendsPlugin::class, 997);
$config->getParser()->parse($config->dataReference());

// All connections inherit common settings
print_r($config->get('database.connections.primary.charset')); // 'utf8mb4'
print_r($config->get('database.connections.replica.collation')); // 'utf8mb4_unicode_ci'
```

## Notes

- The `@extends` directive is removed after processing
- Properties in the extending node always take precedence over inherited properties
- The base node remains unchanged
- Supports forward references (can reference nodes defined later)
- Can be combined with other plugins like `@import`, `@env()`, and reference plugins
- Throws `InvalidArgumentException` if the extended path doesn't exist or is not an array
