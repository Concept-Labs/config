<?php

use Concept\Config\Factory\DefaultStorageFactory;
use Concept\Config\Factory\DefaultResourceFactory;
use Concept\Config\Factory\DefaultParserFactory;
use Concept\Config\Storage\StorageInterface;
use Concept\Config\Resource\ResourceInterface;
use Concept\Config\Parser\ParserInterface;
use Concept\Config\Config;

describe('Factory Classes', function () {
    
    describe('DefaultStorageFactory', function () {
        
        it('creates a storage instance', function () {
            $factory = new DefaultStorageFactory();
            $storage = $factory->create(['key' => 'value']);
            
            expect($storage)->toBeInstanceOf(StorageInterface::class)
                ->and($storage->get('key'))->toBe('value');
        });
        
        it('creates storage with empty data', function () {
            $factory = new DefaultStorageFactory();
            $storage = $factory->create();
            
            expect($storage)->toBeInstanceOf(StorageInterface::class)
                ->and($storage->toArray())->toBe([]);
        });
        
        it('creates independent storage instances', function () {
            $factory = new DefaultStorageFactory();
            $storage1 = $factory->create(['key' => 'value1']);
            $storage2 = $factory->create(['key' => 'value2']);
            
            expect($storage1->get('key'))->toBe('value1')
                ->and($storage2->get('key'))->toBe('value2');
        });
    });
    
    describe('DefaultResourceFactory', function () {
        
        it('creates a resource instance', function () {
            $factory = new DefaultResourceFactory();
            $resource = $factory->create();
            
            expect($resource)->toBeInstanceOf(ResourceInterface::class);
        });
        
        it('creates resource with custom adapter manager', function () {
            $factory = new DefaultResourceFactory();
            $adapterManager = new \Concept\Config\Resource\AdapterManager();
            $adapterManager->registerAdapter(\Concept\Config\Resource\Adapter\JsonAdapter::class);
            
            $resource = $factory->create($adapterManager);
            
            expect($resource)->toBeInstanceOf(ResourceInterface::class);
        });
        
        it('creates independent resource instances', function () {
            $factory = new DefaultResourceFactory();
            $resource1 = $factory->create();
            $resource2 = $factory->create();
            
            expect($resource1)->toBeInstanceOf(ResourceInterface::class)
                ->and($resource2)->toBeInstanceOf(ResourceInterface::class)
                ->and($resource1)->not->toBe($resource2);
        });
    });
    
    describe('DefaultParserFactory', function () {
        
        it('creates a parser instance without config', function () {
            $factory = new DefaultParserFactory();
            $parser = $factory->create();
            
            expect($parser)->toBeInstanceOf(ParserInterface::class);
        });
        
        it('creates a parser instance with config', function () {
            $config = new Config();
            $factory = new DefaultParserFactory($config);
            $parser = $factory->create();
            
            expect($parser)->toBeInstanceOf(ParserInterface::class);
        });
        
        it('allows setting config after construction', function () {
            $factory = new DefaultParserFactory();
            $config = new Config();
            $factory->setConfig($config);
            $parser = $factory->create();
            
            expect($parser)->toBeInstanceOf(ParserInterface::class);
        });
        
        it('creates independent parser instances', function () {
            $factory = new DefaultParserFactory();
            $parser1 = $factory->create();
            $parser2 = $factory->create();
            
            expect($parser1)->toBeInstanceOf(ParserInterface::class)
                ->and($parser2)->toBeInstanceOf(ParserInterface::class)
                ->and($parser1)->not->toBe($parser2);
        });
    });
    
    describe('Config with Factories', function () {
        
        it('accepts custom storage factory', function () {
            $storageFactory = new DefaultStorageFactory();
            $config = new Config(
                data: ['key' => 'value'],
                storageFactory: $storageFactory
            );
            
            expect($config->get('key'))->toBe('value');
        });
        
        it('accepts custom resource factory', function () {
            $resourceFactory = new DefaultResourceFactory();
            $config = new Config(
                resourceFactory: $resourceFactory
            );
            
            expect($config->getResource())->toBeInstanceOf(ResourceInterface::class);
        });
        
        it('accepts custom parser factory', function () {
            $parserFactory = new DefaultParserFactory();
            $config = new Config(
                parserFactory: $parserFactory
            );
            
            expect($config->getParser())->toBeInstanceOf(ParserInterface::class);
        });
        
        it('accepts all custom factories', function () {
            $storageFactory = new DefaultStorageFactory();
            $resourceFactory = new DefaultResourceFactory();
            $parserFactory = new DefaultParserFactory();
            
            $config = new Config(
                data: ['key' => 'value'],
                storageFactory: $storageFactory,
                resourceFactory: $resourceFactory,
                parserFactory: $parserFactory
            );
            
            expect($config->get('key'))->toBe('value')
                ->and($config->getResource())->toBeInstanceOf(ResourceInterface::class)
                ->and($config->getParser())->toBeInstanceOf(ParserInterface::class);
        });
        
        it('uses default factories when none provided', function () {
            $config = new Config(['key' => 'value']);
            
            expect($config->get('key'))->toBe('value')
                ->and($config->getResource())->toBeInstanceOf(ResourceInterface::class)
                ->and($config->getParser())->toBeInstanceOf(ParserInterface::class);
        });
        
        it('maintains backward compatibility', function () {
            // Old way of creating config should still work
            $config = new Config(
                data: ['app' => ['name' => 'Test']],
                context: ['env' => 'test']
            );
            
            expect($config->get('app.name'))->toBe('Test')
                ->and($config->getContext()->get('env'))->toBe('test');
        });
    });
});
