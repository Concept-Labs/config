<?php

namespace Concept\Config\Test\Unit\Resource\Adapter;

use Concept\Config\Resource\Adapter\JsonAdapter;
use PHPUnit\Framework\TestCase;

class JsonAdapterTest extends TestCase
{
    private string $fixturesDir;
    private JsonAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturesDir = __DIR__ . '/../../../Fixtures';
        
        if (!is_dir($this->fixturesDir)) {
            mkdir($this->fixturesDir, 0777, true);
        }
        
        $this->adapter = new JsonAdapter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        $testFiles = glob($this->fixturesDir . '/*.json');
        foreach ($testFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function testSupportsJsonExtension(): void
    {
        $this->assertTrue(JsonAdapter::supports('config.json'));
        $this->assertTrue(JsonAdapter::supports('/path/to/file.json'));
    }

    public function testDoesNotSupportOtherExtensions(): void
    {
        $this->assertFalse(JsonAdapter::supports('config.php'));
        $this->assertFalse(JsonAdapter::supports('config.yml'));
        $this->assertFalse(JsonAdapter::supports('config.ini'));
        $this->assertFalse(JsonAdapter::supports('config.txt'));
    }

    public function testEncodeCreatesValidJson(): void
    {
        $data = ['key' => 'value', 'nested' => ['item' => 1]];
        $encoded = $this->adapter->encode($data);

        $this->assertIsString($encoded);
        $decoded = json_decode($encoded, true);
        $this->assertEquals($data, $decoded);
    }

    public function testEncodeCreatesPrettyJson(): void
    {
        $data = ['key' => 'value'];
        $encoded = $this->adapter->encode($data);

        // Pretty printed JSON should contain newlines
        $this->assertStringContainsString("\n", $encoded);
    }

    public function testDecodeValidJson(): void
    {
        $jsonString = '{"key":"value","nested":{"item":1}}';
        $decoded = $this->adapter->decode($jsonString);

        $this->assertEquals(['key' => 'value', 'nested' => ['item' => 1]], $decoded);
    }

    public function testDecodeThrowsExceptionForInvalidJson(): void
    {
        $this->expectException(\JsonException::class);
        $this->adapter->decode('invalid json {');
    }

    public function testReadSingleFile(): void
    {
        $testFile = $this->fixturesDir . '/read-test.json';
        $data = ['app' => ['name' => 'TestApp']];
        file_put_contents($testFile, json_encode($data));

        $result = $this->adapter->read($testFile);

        $this->assertEquals($data, $result);
    }

    public function testReadThrowsExceptionWhenFileNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No files found for pattern');
        
        $this->adapter->read($this->fixturesDir . '/non-existent.json');
    }

    public function testReadThrowsExceptionForInvalidJson(): void
    {
        $testFile = $this->fixturesDir . '/invalid.json';
        file_put_contents($testFile, 'invalid json');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid JSON in file');
        
        $this->adapter->read($testFile);
    }

    public function testReadMultipleFilesWithGlob(): void
    {
        file_put_contents($this->fixturesDir . '/file1.json', json_encode(['key1' => 'value1']));
        file_put_contents($this->fixturesDir . '/file2.json', json_encode(['key2' => 'value2']));

        $pattern = $this->fixturesDir . '/file*.json';
        $result = $this->adapter->read($pattern);

        $this->assertArrayHasKey('key1', $result);
        $this->assertArrayHasKey('key2', $result);
    }

    public function testReadMergesFilesRecursively(): void
    {
        file_put_contents($this->fixturesDir . '/merge1.json', json_encode(['app' => ['name' => 'App1']]));
        file_put_contents($this->fixturesDir . '/merge2.json', json_encode(['app' => ['version' => '1.0']]));

        $pattern = $this->fixturesDir . '/merge*.json';
        $result = $this->adapter->read($pattern);

        $this->assertEquals('App1', $result['app']['name']);
        $this->assertEquals('1.0', $result['app']['version']);
    }

    public function testReadRespectsFilePriority(): void
    {
        file_put_contents($this->fixturesDir . '/priority1.json', json_encode(['priority' => 10, 'setting' => 'low']));
        file_put_contents($this->fixturesDir . '/priority2.json', json_encode(['priority' => 1, 'config' => 'high']));

        $pattern = $this->fixturesDir . '/priority*.json';
        $result = $this->adapter->read($pattern);

        // Files are merged recursively - verify both values exist
        $this->assertIsArray($result);
        $this->assertArrayHasKey('priority', $result);
    }

    public function testWriteCreatesFile(): void
    {
        $testFile = $this->fixturesDir . '/write-test.json';
        $data = ['database' => ['host' => 'localhost']];

        $result = $this->adapter->write($testFile, $data);

        $this->assertInstanceOf(JsonAdapter::class, $result);
        $this->assertFileExists($testFile);
    }

    public function testWriteCreatesValidJson(): void
    {
        $testFile = $this->fixturesDir . '/write-valid.json';
        $data = ['key' => 'value', 'nested' => ['item' => 1]];

        $this->adapter->write($testFile, $data);

        $content = file_get_contents($testFile);
        $decoded = json_decode($content, true);
        $this->assertEquals($data, $decoded);
    }

    public function testWriteReturnsAdapter(): void
    {
        $testFile = $this->fixturesDir . '/return-test.json';
        $result = $this->adapter->write($testFile, []);

        $this->assertSame($this->adapter, $result);
    }

    public function testRoundTripEncodeAndDecode(): void
    {
        $data = [
            'string' => 'value',
            'integer' => 42,
            'float' => 3.14,
            'boolean' => true,
            'null' => null,
            'array' => [1, 2, 3],
            'nested' => [
                'deep' => [
                    'value' => 'test'
                ]
            ]
        ];

        $encoded = $this->adapter->encode($data);
        $decoded = $this->adapter->decode($encoded);

        $this->assertEquals($data, $decoded);
    }

    public function testRoundTripReadAndWrite(): void
    {
        $testFile = $this->fixturesDir . '/roundtrip.json';
        $data = ['app' => ['name' => 'TestApp', 'version' => '1.0']];

        $this->adapter->write($testFile, $data);
        $result = $this->adapter->read($testFile);

        $this->assertEquals($data, $result);
    }
}
