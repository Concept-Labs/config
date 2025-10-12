<?php

use Concept\Config\Config;
use Concept\Config\Parser\Plugin\Directive\ExtendsPlugin;

describe('ExtendsPlugin', function () {
    it('extends a configuration node with additional properties', function () {
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

        expect($config->get('fooze'))
            ->toBe([
                'acme' => 'lorem',
                'bar' => 'some',
                'bar2' => 'another'
            ])
            ->and($config->get('fooze.acme'))->toBe('lorem')
            ->and($config->get('fooze.bar'))->toBe('some')
            ->and($config->get('fooze.bar2'))->toBe('another');
    });

    it('removes the @extends directive after processing', function () {
        $config = new Config([
            'base' => [
                'setting1' => 'value1',
                'setting2' => 'value2'
            ],
            'extended' => [
                '@extends' => 'base',
                'setting3' => 'value3'
            ]
        ]);

        $config->getParser()->registerPlugin(ExtendsPlugin::class, 997);
        $config->getParser()->parse($config->dataReference());

        expect($config->get('extended'))
            ->not->toHaveKey('@extends')
            ->and($config->has('extended.@extends'))->toBe(false);
    });

    it('preserves existing properties in the extending node', function () {
        $config = new Config([
            'base' => [
                'prop1' => 'base_value1',
                'prop2' => 'base_value2'
            ],
            'child' => [
                '@extends' => 'base',
                'prop1' => 'child_value1',
                'prop3' => 'child_value3'
            ]
        ]);

        $config->getParser()->registerPlugin(ExtendsPlugin::class, 997);
        $config->getParser()->parse($config->dataReference());

        expect($config->get('child.prop1'))->toBe('child_value1') // Preserved
            ->and($config->get('child.prop2'))->toBe('base_value2') // Extended
            ->and($config->get('child.prop3'))->toBe('child_value3'); // Own property
    });

    it('extends nested configuration paths', function () {
        $config = new Config([
            'app' => [
                'defaults' => [
                    'timeout' => 30,
                    'retries' => 3,
                    'logging' => [
                        'level' => 'info',
                        'format' => 'json'
                    ]
                ]
            ],
            'services' => [
                'api' => [
                    '@extends' => 'app.defaults',
                    'endpoint' => 'https://api.example.com'
                ]
            ]
        ]);

        $config->getParser()->registerPlugin(ExtendsPlugin::class, 997);
        $config->getParser()->parse($config->dataReference());

        expect($config->get('services.api.timeout'))->toBe(30)
            ->and($config->get('services.api.retries'))->toBe(3)
            ->and($config->get('services.api.logging'))->toBe([
                'level' => 'info',
                'format' => 'json'
            ])
            ->and($config->get('services.api.endpoint'))->toBe('https://api.example.com');
    });

    it('handles forward references with lazy resolution', function () {
        $config = new Config([
            'child' => [
                '@extends' => 'base',
                'extra' => 'value'
            ],
            'base' => [
                'prop1' => 'value1',
                'prop2' => 'value2'
            ]
        ]);

        $config->getParser()->registerPlugin(ExtendsPlugin::class, 997);
        $config->getParser()->parse($config->dataReference());

        expect($config->get('child.prop1'))->toBe('value1')
            ->and($config->get('child.prop2'))->toBe('value2')
            ->and($config->get('child.extra'))->toBe('value');
    });

    it('extends with deeply nested properties', function () {
        $config = new Config([
            'database' => [
                'connection' => [
                    'host' => 'localhost',
                    'port' => 3306,
                    'options' => [
                        'charset' => 'utf8mb4',
                        'collation' => 'utf8mb4_unicode_ci'
                    ]
                ]
            ],
            'production_db' => [
                '@extends' => 'database.connection',
                'host' => 'prod.example.com',
                'options' => [
                    'ssl' => true
                ]
            ]
        ]);

        $config->getParser()->registerPlugin(ExtendsPlugin::class, 997);
        $config->getParser()->parse($config->dataReference());

        expect($config->get('production_db.host'))->toBe('prod.example.com')
            ->and($config->get('production_db.port'))->toBe(3306)
            ->and($config->get('production_db.options.ssl'))->toBe(true)
            ->and($config->get('production_db.options.charset'))->toBe('utf8mb4')
            ->and($config->get('production_db.options.collation'))->toBe('utf8mb4_unicode_ci');
    });

    it('throws exception when extending non-existent path', function () {
        $config = new Config([
            'child' => [
                '@extends' => 'nonexistent.path',
                'prop' => 'value'
            ]
        ]);

        $config->getParser()->registerPlugin(ExtendsPlugin::class, 997);
        
        expect(fn() => $config->getParser()->parse($config->dataReference()))
            ->toThrow(InvalidArgumentException::class);
    });

    it('throws exception when extending non-array value', function () {
        $config = new Config([
            'scalar' => 'just a string',
            'child' => [
                '@extends' => 'scalar',
                'prop' => 'value'
            ]
        ]);

        $config->getParser()->registerPlugin(ExtendsPlugin::class, 997);
        
        expect(fn() => $config->getParser()->parse($config->dataReference()))
            ->toThrow(InvalidArgumentException::class);
    });

    it('handles multiple nodes extending the same base', function () {
        $config = new Config([
            'base' => [
                'shared1' => 'value1',
                'shared2' => 'value2'
            ],
            'child1' => [
                '@extends' => 'base',
                'unique1' => 'child1_value'
            ],
            'child2' => [
                '@extends' => 'base',
                'unique2' => 'child2_value'
            ]
        ]);

        $config->getParser()->registerPlugin(ExtendsPlugin::class, 997);
        $config->getParser()->parse($config->dataReference());

        expect($config->get('child1.shared1'))->toBe('value1')
            ->and($config->get('child1.shared2'))->toBe('value2')
            ->and($config->get('child1.unique1'))->toBe('child1_value')
            ->and($config->get('child2.shared1'))->toBe('value1')
            ->and($config->get('child2.shared2'))->toBe('value2')
            ->and($config->get('child2.unique2'))->toBe('child2_value');
    });

    it('extends with empty base object', function () {
        $config = new Config([
            'base' => [],
            'child' => [
                '@extends' => 'base',
                'prop' => 'value'
            ]
        ]);

        $config->getParser()->registerPlugin(ExtendsPlugin::class, 997);
        $config->getParser()->parse($config->dataReference());

        expect($config->get('child'))
            ->toBe(['prop' => 'value']);
    });

    it('preserves original base node unchanged', function () {
        $config = new Config([
            'base' => [
                'prop1' => 'value1',
                'prop2' => 'value2'
            ],
            'child' => [
                '@extends' => 'base',
                'prop3' => 'value3'
            ]
        ]);

        $config->getParser()->registerPlugin(ExtendsPlugin::class, 997);
        $config->getParser()->parse($config->dataReference());

        // Verify base is unchanged
        expect($config->get('base'))
            ->toBe([
                'prop1' => 'value1',
                'prop2' => 'value2'
            ])
            ->and($config->get('base'))->not->toHaveKey('prop3');
    });
});
