<?php

use Concept\Config\Config;
use Concept\Config\Parser\Plugin\ReferenceNodePlugin;
use Concept\Config\Parser\Plugin\ReferenceValuePlugin;
use Concept\Config\Parser\Plugin\ContextPlugin;
use Concept\Config\Parser\Plugin\Expression\EnvPlugin;

describe('ReferenceNodePlugin', function () {
    it('replaces entire node with #reference syntax', function () {
        $config = new Config([
            'database' => [
                'host' => 'localhost',
                'port' => 3306,
                'credentials' => [
                    'user' => 'admin',
                    'password' => 'secret'
                ]
            ],
            'services' => [
                'api' => [
                    'connection' => '#database.credentials'
                ]
            ]
        ]);

        $config->getParser()->registerPlugin(ReferenceNodePlugin::class, 998);
        $config->getParser()->parse($config->dataReference());

        expect($config->get('services.api.connection'))
            ->toBe([
                'user' => 'admin',
                'password' => 'secret'
            ]);
    });

    it('supports default values with #reference|default syntax', function () {
        $config = new Config([
            'fallback' => '#missing.path|default_value'
        ]);

        $config->getParser()->registerPlugin(ReferenceNodePlugin::class, 998);
        $config->getParser()->parse($config->dataReference());

        expect($config->get('fallback'))->toContain('default_value');
    });

    it('references scalar values', function () {
        $config = new Config([
            'paths' => [
                'root' => '/var/www/app',
                'storage' => '/var/www/app/storage'
            ],
            'backup' => [
                'location' => '#paths.storage'
            ]
        ]);

        $config->getParser()->registerPlugin(ReferenceNodePlugin::class, 998);
        $config->getParser()->parse($config->dataReference());

        expect($config->get('backup.location'))->toBe('/var/www/app/storage');
    });
});

describe('ReferenceValuePlugin', function () {
    it('interpolates single value with #{...} syntax', function () {
        $config = new Config([
            'server' => [
                'host' => 'localhost',
                'port' => 8080
            ],
            'api' => [
                'singleRef' => '#{server.port}'
            ]
        ]);

        $config->getParser()->registerPlugin(ReferenceValuePlugin::class, 998);
        $config->getParser()->parse($config->dataReference());

        // When interpolated, numeric values become strings in string context
        expect($config->get('api.singleRef'))->toBe('8080');
    });

    it('supports default values with #{path|default} syntax', function () {
        $config = new Config([
            'greeting' => 'Hello #{user.name|Guest}!'
        ]);

        $config->getParser()->registerPlugin(ReferenceValuePlugin::class, 998);
        $config->getParser()->parse($config->dataReference());

        expect($config->get('greeting'))->toBe('Hello Guest!');
    });

    it('handles multiple interpolations in same string', function () {
        $config = new Config([
            'app' => [
                'name' => 'MyApp',
                'version' => '1.0.0'
            ],
            'display' => [
                'title' => '#{app.name} v#{app.version}'
            ]
        ]);

        $config->getParser()->registerPlugin(ReferenceValuePlugin::class, 998);
        $config->getParser()->parse($config->dataReference());

        expect($config->get('display.title'))->toBe('MyApp v1.0.0');
    });

    it('combines interpolation with static text', function () {
        $config = new Config([
            'paths' => [
                'root' => '/var/www'
            ],
            'locations' => [
                'public' => '#{paths.root}/public',
                'cache' => '#{paths.root}/storage/cache'
            ]
        ]);

        $config->getParser()->registerPlugin(ReferenceValuePlugin::class, 998);
        $config->getParser()->parse($config->dataReference());

        expect($config->get('locations.public'))->toBe('/var/www/public')
            ->and($config->get('locations.cache'))->toBe('/var/www/storage/cache');
    });
    
    it('interpolates values with #{...} syntax for URL construction', function () {
        $config = new Config([
            'server' => [
                'host' => 'localhost',
                'port' => 8080
            ],
            'api' => [
                'url' => 'http://#{server.host}:#{server.port}/api'
            ]
        ]);

        $config->getParser()->registerPlugin(ReferenceValuePlugin::class, 998);
        $config->getParser()->parse($config->dataReference());

        expect($config->get('api.url'))->toBe('http://localhost:8080/api');
    });
});

describe('ContextPlugin with defaults', function () {
    it('supports default values with ${var|default} syntax', function () {
        $config = new Config(
            data: [
                'app' => [
                    'mode' => '${mode|development}',
                    'debug' => '${debug|false}'
                ]
            ],
            context: []
        );

        $config->getParser()->registerPlugin(ContextPlugin::class, 998);
        $config->getParser()->parse($config->dataReference());

        expect($config->get('app.mode'))->toBe('development')
            ->and($config->get('app.debug'))->toBe('false');
    });

    it('uses context value when available', function () {
        $config = new Config(
            data: [
                'app' => [
                    'mode' => '${mode|development}'
                ]
            ],
            context: [
                'mode' => 'production'
            ]
        );

        $config->getParser()->registerPlugin(ContextPlugin::class, 998);
        $config->getParser()->parse($config->dataReference());

        expect($config->get('app.mode'))->toBe('production');
    });

    it('resolves multiple context variables in same value', function () {
        $config = new Config(
            data: [
                'app' => [
                    'database' => 'app_${tenant}_${env}'
                ]
            ],
            context: [
                'tenant' => 'acme',
                'env' => 'production'
            ]
        );

        $config->getParser()->registerPlugin(ContextPlugin::class, 998);
        $config->getParser()->parse($config->dataReference());

        expect($config->get('app.database'))->toBe('app_acme_production');
    });

    it('combines defaults with existing context variables', function () {
        $config = new Config(
            data: [
                'app' => [
                    'name' => '${appName|DefaultApp}',
                    'env' => '${environment}'
                ]
            ],
            context: [
                'environment' => 'staging'
            ]
        );

        $config->getParser()->registerPlugin(ContextPlugin::class, 998);
        $config->getParser()->parse($config->dataReference());

        expect($config->get('app.name'))->toBe('DefaultApp')
            ->and($config->get('app.env'))->toBe('staging');
    });
});

describe('Plugin integration', function () {
    it('combines environment, context, and reference plugins', function () {
        putenv('API_KEY=secret123');
        
        $config = new Config(
            data: [
                'api' => [
                    'host' => '${apiHost}',
                    'key' => '@env(API_KEY)',
                    'url' => 'https://#{api.host}/api'
                ],
                'services' => [
                    'external' => '#api'
                ]
            ],
            context: [
                'apiHost' => 'api.example.com'
            ]
        );

        $config->getParser()->registerPlugin(EnvPlugin::class, 999);
        $config->getParser()->registerPlugin(ContextPlugin::class, 998);
        $config->getParser()->registerPlugin(ReferenceValuePlugin::class, 997);
        $config->getParser()->registerPlugin(ReferenceNodePlugin::class, 996);
        $config->getParser()->parse($config->dataReference());

        expect($config->get('api.url'))->toBe('https://api.example.com/api')
            ->and($config->get('api.key'))->toBe('secret123')
            ->and($config->get('services.external'))->toBeArray()
            ->and($config->get('services.external.host'))->toBe('api.example.com');
    });
    
    it('combines environment, context, and node reference plugins', function () {
        putenv('API_KEY=secret456');
        
        $config = new Config(
            data: [
                'api' => [
                    'host' => '${apiHost}',
                    'key' => '@env(API_KEY)'
                ],
                'services' => [
                    'external' => '#api'
                ]
            ],
            context: [
                'apiHost' => 'api.example.com'
            ]
        );

        $config->getParser()->registerPlugin(EnvPlugin::class, 999);
        $config->getParser()->registerPlugin(ContextPlugin::class, 998);
        $config->getParser()->registerPlugin(ReferenceNodePlugin::class, 996);
        $config->getParser()->parse($config->dataReference());

        expect($config->get('api.key'))->toBe('secret456')
            ->and($config->get('services.external'))->toBeArray()
            ->and($config->get('services.external.host'))->toBe('api.example.com');
    });
});
