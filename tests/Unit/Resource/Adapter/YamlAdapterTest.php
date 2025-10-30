<?php

use Concept\Config\Resource\Adapter\YamlAdapter;
use Concept\Config\Resource\Exception\ResourceException;

describe('YamlAdapter', function () {
    it('supports yaml files', function () {
        expect(YamlAdapter::supports('config.yaml'))->toBeTrue()
            ->and(YamlAdapter::supports('config.yml'))->toBeTrue()
            ->and(YamlAdapter::supports('config.json'))->toBeFalse();
    });

    it('writes and reads yaml files', function () {
        $adapter = new YamlAdapter();
        $data = [
            'app' => [
                'name' => 'TestApp',
                'debug' => true
            ],
            'database' => [
                'host' => 'localhost',
                'port' => 3306
            ]
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'yaml_test') . '.yaml';

        try {
            $adapter->write($tempFile, $data);
            expect(file_exists($tempFile))->toBeTrue();

            $readData = $adapter->read($tempFile);
            expect($readData)->toBe($data);
        } finally {
            @unlink($tempFile);
        }
    })->skip(
        !function_exists('yaml_parse_file') && !class_exists('\Symfony\Component\Yaml\Yaml'),
        'YAML support requires either YAML extension or symfony/yaml package'
    );

    it('encodes and decodes yaml strings', function () {
        $adapter = new YamlAdapter();
        $data = [
            'foo' => 'bar',
            'nested' => [
                'key' => 'value'
            ]
        ];

        $yaml = $adapter->encode($data);
        expect($yaml)->toBeString();

        $decoded = $adapter->decode($yaml);
        expect($decoded)->toBe($data);
    })->skip(
        !function_exists('yaml_parse') && !class_exists('\Symfony\Component\Yaml\Yaml'),
        'YAML support requires either YAML extension or symfony/yaml package'
    );

    it('throws exception when file not found', function () {
        $adapter = new YamlAdapter();
        $adapter->read('/nonexistent/file.yaml');
    })->throws(ResourceException::class, 'File not found');
});
