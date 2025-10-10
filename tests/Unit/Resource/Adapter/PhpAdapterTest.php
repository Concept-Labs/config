<?php

namespace Concept\Config\Test\Unit\Resource\Adapter;

use Concept\Config\Resource\Adapter\PhpAdapter;
use Concept\Config\Resource\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PhpAdapterTest extends TestCase
{
    private string $fixturesDir;
    private PhpAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturesDir = __DIR__ . '/../../../Fixtures';
        
        if (!is_dir($this->fixturesDir)) {
            mkdir($this->fixturesDir, 0777, true);
        }
        
        $this->adapter = new PhpAdapter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        $testFiles = glob($this->fixturesDir . '/*.php');
        foreach ($testFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function testSupportsPhpExtension(): void
    {
        $this->assertTrue(PhpAdapter::supports('config.php'));
        $this->assertTrue(PhpAdapter::supports('/path/to/file.php'));
    }

    public function testDoesNotSupportOtherExtensions(): void
    {
        $this->assertFalse(PhpAdapter::supports('config.json'));
        $this->assertFalse(PhpAdapter::supports('config.yml'));
        $this->assertFalse(PhpAdapter::supports('config.ini'));
        $this->assertFalse(PhpAdapter::supports('config.txt'));
    }

    public function testEncodeCreatesValidPhpArrayString(): void
    {
        $data = ['key' => 'value', 'nested' => ['item' => 1]];
        $encoded = $this->adapter->encode($data);

        $this->assertIsString($encoded);
        $this->assertStringContainsString('array', $encoded);
    }

    public function testEncodeHandlesComplexData(): void
    {
        $data = [
            'string' => 'value',
            'integer' => 42,
            'float' => 3.14,
            'boolean' => true,
            'null' => null,
            'array' => [1, 2, 3],
            'nested' => ['deep' => ['value' => 'test']]
        ];

        $encoded = $this->adapter->encode($data);
        
        $this->assertIsString($encoded);
    }

    public function testDecodeThrowsRuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Use read method instead');
        
        $this->adapter->decode('any string');
    }

    public function testReadValidPhpFile(): void
    {
        $testFile = $this->fixturesDir . '/read-test.php';
        $data = ['app' => ['name' => 'TestApp']];
        file_put_contents($testFile, '<?php return ' . var_export($data, true) . ';');

        $result = $this->adapter->read($testFile);

        $this->assertEquals($data, $result);
    }

    public function testReadThrowsExceptionForNonArrayReturn(): void
    {
        $testFile = $this->fixturesDir . '/invalid-return.php';
        file_put_contents($testFile, '<?php return "not an array";');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected an array');
        
        $this->adapter->read($testFile);
    }

    public function testWriteCreatesFile(): void
    {
        $testFile = $this->fixturesDir . '/write-test.php';
        $data = ['database' => ['host' => 'localhost']];

        $result = $this->adapter->write($testFile, $data);

        $this->assertInstanceOf(PhpAdapter::class, $result);
        $this->assertFileExists($testFile);
    }

    public function testWriteCreatesValidPhpFile(): void
    {
        $testFile = $this->fixturesDir . '/write-valid.php';
        $data = ['key' => 'value', 'nested' => ['item' => 1]];

        $this->adapter->write($testFile, $data);

        $content = file_get_contents($testFile);
        $this->assertStringStartsWith('<?php', $content);
        $this->assertStringContainsString('return', $content);
    }

    public function testWriteReturnsAdapter(): void
    {
        $testFile = $this->fixturesDir . '/return-test.php';
        $result = $this->adapter->write($testFile, []);

        $this->assertSame($this->adapter, $result);
    }

    public function testRoundTripReadAndWrite(): void
    {
        $testFile = $this->fixturesDir . '/roundtrip.php';
        $data = [
            'app' => [
                'name' => 'TestApp',
                'version' => '1.0',
                'debug' => true
            ],
            'database' => [
                'host' => 'localhost',
                'port' => 3306
            ]
        ];

        $this->adapter->write($testFile, $data);
        $result = $this->adapter->read($testFile);

        $this->assertEquals($data, $result);
    }

    public function testWrittenFileCanBeIncluded(): void
    {
        $testFile = $this->fixturesDir . '/include-test.php';
        $data = ['key' => 'value'];

        $this->adapter->write($testFile, $data);
        
        $loaded = require $testFile;
        $this->assertEquals($data, $loaded);
    }

    public function testWriteHandlesSpecialCharacters(): void
    {
        $testFile = $this->fixturesDir . '/special-chars.php';
        $data = [
            'quotes' => "It's a test with \"quotes\"",
            'backslash' => 'Path\\to\\file',
            'newline' => "Line1\nLine2"
        ];

        $this->adapter->write($testFile, $data);
        $result = $this->adapter->read($testFile);

        $this->assertEquals($data, $result);
    }

    public function testReadHandlesNestedArrays(): void
    {
        $testFile = $this->fixturesDir . '/nested.php';
        $data = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'value' => 'deep'
                    ]
                ]
            ]
        ];

        file_put_contents($testFile, '<?php return ' . var_export($data, true) . ';');
        $result = $this->adapter->read($testFile);

        $this->assertEquals('deep', $result['level1']['level2']['level3']['value']);
    }
}
