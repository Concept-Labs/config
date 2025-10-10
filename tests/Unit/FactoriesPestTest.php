<?php

use Concept\Config\ConfigInterface;
use Concept\Config\StaticFactory;

beforeEach(function () {
    $this->fixturesDir = __DIR__ . '/../Fixtures';
    
    if (!is_dir($this->fixturesDir)) {
        mkdir($this->fixturesDir, 0777, true);
    }
});

afterEach(function () {
    $testFiles = glob($this->fixturesDir . '/*.json');
    foreach ($testFiles as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});

describe('StaticFactory', function () {
    it('creates empty config', function () {
        $config = StaticFactory::create();
        
        expect($config)->toBeInstanceOf(ConfigInterface::class)
            ->and($config->toArray())->toBe([]);
    });

    it('creates config with data', function () {
        $data = ['app' => ['name' => 'TestApp']];
        $config = StaticFactory::create($data);
        
        expect($config->toArray())->toBe($data);
    });

    it('creates config with context', function () {
        $context = ['env' => 'test'];
        $config = StaticFactory::create([], $context);
        
        $contextArray = $config->getContext()->toArray();
        expect($contextArray)->toHaveKey('env')
            ->and($contextArray['env'])->toBe('test');
    });

    it('loads config from file', function () {
        $testFile = $this->fixturesDir . '/pest-test.json';
        $data = ['database' => ['host' => 'localhost']];
        file_put_contents($testFile, json_encode($data));

        $config = StaticFactory::fromFile($testFile);

        expect($config->toArray())->toBe($data);
    });

    it('loads config from multiple files', function () {
        $file1 = $this->fixturesDir . '/pest-config1.json';
        $file2 = $this->fixturesDir . '/pest-config2.json';
        
        file_put_contents($file1, json_encode(['app' => ['name' => 'TestApp']]));
        file_put_contents($file2, json_encode(['database' => ['host' => 'localhost']]));

        $config = StaticFactory::fromFiles([$file1, $file2]);

        expect($config->get('app.name'))->toBe('TestApp')
            ->and($config->get('database.host'))->toBe('localhost');
    });

    it('merges multiple files', function () {
        $file1 = $this->fixturesDir . '/pest-base.json';
        $file2 = $this->fixturesDir . '/pest-override.json';
        
        file_put_contents($file1, json_encode(['app' => ['name' => 'Base', 'version' => '1.0']]));
        file_put_contents($file2, json_encode(['app' => ['version' => '2.0']]));

        $config = StaticFactory::fromFiles([$file1, $file2]);

        expect($config->get('app.name'))->toBe('Base')
            ->and($config->get('app.version'))->toBe('2.0');
    });

    it('loads config from glob pattern', function () {
        file_put_contents($this->fixturesDir . '/pest1.json', json_encode(['key1' => 'value1']));
        file_put_contents($this->fixturesDir . '/pest2.json', json_encode(['key2' => 'value2']));

        $pattern = $this->fixturesDir . '/pest*.json';
        $config = StaticFactory::fromGlob($pattern);

        expect($config->has('key1'))->toBeTrue()
            ->and($config->has('key2'))->toBeTrue();
    });

    it('compiles and exports config', function () {
        $source = $this->fixturesDir . '/pest-source.json';
        $target = $this->fixturesDir . '/pest-compiled.json';
        
        file_put_contents($source, json_encode(['app' => ['name' => 'TestApp']]));

        $config = StaticFactory::compile($source, [], $target);

        expect($config)->toBeInstanceOf(ConfigInterface::class)
            ->and(file_exists($target))->toBeTrue();
        
        $compiled = json_decode(file_get_contents($target), true);
        expect($compiled['app']['name'])->toBe('TestApp');
    });
});
