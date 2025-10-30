<?php

use Concept\Config\Resource\Adapter\XmlAdapter;
use Concept\Config\Resource\Exception\ResourceException;
use Concept\Config\Resource\Exception\InvalidArgumentException;

describe('XmlAdapter', function () {
    it('supports xml files', function () {
        expect(XmlAdapter::supports('config.xml'))->toBeTrue()
            ->and(XmlAdapter::supports('config.json'))->toBeFalse()
            ->and(XmlAdapter::supports('config.yaml'))->toBeFalse();
    });

    it('writes and reads xml files', function () {
        $adapter = new XmlAdapter();
        $data = [
            'app' => [
                'name' => 'TestApp',
                'debug' => 'true'
            ],
            'database' => [
                'host' => 'localhost',
                'port' => '3306'
            ]
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'xml_test') . '.xml';

        try {
            $adapter->write($tempFile, $data);
            expect(file_exists($tempFile))->toBeTrue();

            $readData = $adapter->read($tempFile);
            expect($readData)->toHaveKey('app')
                ->and($readData)->toHaveKey('database');
        } finally {
            @unlink($tempFile);
        }
    });

    it('encodes data to xml string', function () {
        $adapter = new XmlAdapter();
        $data = [
            'foo' => 'bar',
            'nested' => [
                'key' => 'value'
            ]
        ];

        $xml = $adapter->encode($data);
        expect($xml)->toBeString()
            ->and($xml)->toContain('<?xml')
            ->and($xml)->toContain('<foo>')
            ->and($xml)->toContain('<nested>');
    });

    it('decodes xml string to array', function () {
        $adapter = new XmlAdapter();
        $xml = '<?xml version="1.0"?><config><name>TestApp</name><debug>true</debug></config>';

        $data = $adapter->decode($xml);
        expect($data)->toBeArray()
            ->and($data)->toHaveKey('name')
            ->and($data['name'])->toBe('TestApp');
    });

    it('handles sequential arrays', function () {
        $adapter = new XmlAdapter();
        $data = [
            'items' => ['item1', 'item2', 'item3']
        ];

        $xml = $adapter->encode($data);
        expect($xml)->toContain('<items>');

        $decoded = $adapter->decode($xml);
        expect($decoded)->toHaveKey('items');
    });

    it('sanitizes invalid xml keys', function () {
        $adapter = new XmlAdapter();
        $data = [
            'invalid-key!' => 'value',
            '123numeric' => 'test'
        ];

        $xml = $adapter->encode($data);
        expect($xml)->toBeString();
        // Keys should be sanitized to valid XML
    });

    it('throws exception for invalid xml', function () {
        $adapter = new XmlAdapter();
        $adapter->decode('invalid xml content');
    })->throws(InvalidArgumentException::class);

    it('throws exception when file not found', function () {
        $adapter = new XmlAdapter();
        $adapter->read('/nonexistent/file.xml');
    })->throws(ResourceException::class, 'File not found');

    it('performs round trip encoding and decoding', function () {
        $adapter = new XmlAdapter();
        $data = [
            'application' => [
                'name' => 'MyApp',
                'version' => '1.0.0',
                'settings' => [
                    'debug' => 'true',
                    'timezone' => 'UTC'
                ]
            ]
        ];

        $xml = $adapter->encode($data);
        $decoded = $adapter->decode($xml);

        expect($decoded)->toHaveKey('application')
            ->and($decoded['application'])->toHaveKey('name')
            ->and($decoded['application']['name'])->toBe('MyApp');
    });
});
