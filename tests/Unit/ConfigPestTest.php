<?php

use Concept\Config\Config;
use Concept\Config\ConfigInterface;
use Concept\Config\Context\Context;

describe('Config', function () {
    it('can be instantiated with data', function () {
        $config = new Config(['app' => ['name' => 'TestApp']]);
        
        expect($config)->toBeInstanceOf(Config::class)
            ->and($config->toArray())->toBe(['app' => ['name' => 'TestApp']]);
    });

    it('can be created from array', function () {
        $config = Config::fromArray(['key' => 'value']);
        
        expect($config)->toBeInstanceOf(ConfigInterface::class)
            ->and($config->get('key'))->toBe('value');
    });

    it('retrieves values using dot notation', function () {
        $config = new Config([
            'database' => [
                'host' => 'localhost',
                'port' => 3306
            ]
        ]);
        
        expect($config->get('database.host'))->toBe('localhost')
            ->and($config->get('database.port'))->toBe(3306);
    });

    it('returns default when key does not exist', function () {
        $config = new Config(['key' => 'value']);
        
        expect($config->get('nonexistent', 'default'))->toBe('default')
            ->and($config->get('nonexistent'))->toBeNull();
    });

    it('sets values using dot notation', function () {
        $config = new Config();
        $config->set('app.name', 'MyApp');
        
        expect($config->get('app.name'))->toBe('MyApp');
    });

    it('checks if key exists', function () {
        $config = new Config(['app' => ['name' => 'TestApp']]);
        
        expect($config->has('app.name'))->toBeTrue()
            ->and($config->has('app.version'))->toBeFalse();
    });

    it('converts to array', function () {
        $data = ['app' => ['name' => 'TestApp']];
        $config = new Config($data);
        
        expect($config->toArray())->toBe($data);
    });

    it('resets configuration', function () {
        $config = new Config(['key' => 'value']);
        $config->reset();
        
        expect($config->toArray())->toBe([]);
    });

    it('creates a node for sub-configuration', function () {
        $config = new Config([
            'database' => [
                'host' => 'localhost',
                'port' => 3306
            ]
        ]);
        
        $dbConfig = $config->node('database');
        
        expect($dbConfig)->toBeInstanceOf(ConfigInterface::class)
            ->and($dbConfig->get('host'))->toBe('localhost');
    });

    it('loads configuration from array', function () {
        $config = new Config(['old' => 'value']);
        $config->load(['new' => 'value']);
        
        expect($config->has('old'))->toBeFalse()
            ->and($config->has('new'))->toBeTrue();
    });

    it('imports and merges configuration', function () {
        $config = new Config(['app' => ['name' => 'TestApp']]);
        $config->import(['app' => ['version' => '1.0']]);
        
        expect($config->get('app.name'))->toBe('TestApp')
            ->and($config->get('app.version'))->toBe('1.0');
    });

    it('supports chaining methods', function () {
        $config = new Config();
        $result = $config->set('key1', 'value1')->set('key2', 'value2');
        
        expect($result)->toBe($config)
            ->and($config->get('key1'))->toBe('value1')
            ->and($config->get('key2'))->toBe('value2');
    });

    it('hydrates with new data', function () {
        $config = new Config(['old' => 'data']);
        $config->hydrate(['new' => 'data']);
        
        expect($config->toArray())->toHaveKey('old')
            ->and($config->toArray())->toHaveKey('new');
    });

    it('replaces context', function () {
        $config = new Config();
        $config->withContext(['env' => 'test']);
        
        $contextArray = $config->getContext()->toArray();
        expect($contextArray)->toHaveKey('env')
            ->and($contextArray['env'])->toBe('test');
    });

    it('accepts context interface', function () {
        $config = new Config();
        $context = new Context(['key' => 'value']);
        $config->withContext($context);
        
        $contextArray = $config->getContext()->toArray();
        expect($contextArray['key'])->toBe('value');
    });

    it('is iterable', function () {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $config = new Config($data);
        
        expect($config->getIterator())->toBeInstanceOf(\Traversable::class);
        
        $result = [];
        foreach ($config as $key => $value) {
            $result[$key] = $value;
        }
        
        expect($result)->toBe($data);
    });

    it('clones independently', function () {
        $config = new Config(['key' => 'original']);
        $cloned = clone $config;
        
        $cloned->set('key', 'modified');
        
        expect($config->get('key'))->toBe('original')
            ->and($cloned->get('key'))->toBe('modified');
    });

    it('creates a prototype', function () {
        $config = new Config(['key' => 'value']);
        $prototype = $config->prototype();
        
        expect($prototype)->toBeInstanceOf(ConfigInterface::class)
            ->and($prototype)->not->toBe($config)
            ->and($prototype->toArray())->toBe([]);
    });
});
