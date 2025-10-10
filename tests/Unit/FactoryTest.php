<?php

namespace Concept\Config\Test\Unit;

use Concept\Config\Config;
use Concept\Config\ConfigInterface;
use Concept\Config\Context\Context;
use Concept\Config\Context\ContextInterface;
use Concept\Config\Factory;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturesDir = __DIR__ . '/../Fixtures';
        
        if (!is_dir($this->fixturesDir)) {
            mkdir($this->fixturesDir, 0777, true);
        }
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

    public function testConstructorWithoutClass(): void
    {
        $factory = new Factory();
        $config = $factory->create();

        $this->assertInstanceOf(Config::class, $config);
    }

    public function testConstructorWithConfigClass(): void
    {
        $factory = new Factory(Config::class);
        $config = $factory->create();

        $this->assertInstanceOf(Config::class, $config);
    }

    public function testConstructorThrowsExceptionForInvalidClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Factory(\stdClass::class);
    }

    public function testCreateReturnsConfigInterface(): void
    {
        $factory = new Factory();
        $config = $factory->create();

        $this->assertInstanceOf(ConfigInterface::class, $config);
    }

    public function testWithContextSetsContext(): void
    {
        $factory = new Factory();
        $factory->withContext(['env' => 'test']);
        $config = $factory->create();

        $contextArray = $config->getContext()->toArray();
        $this->assertEquals('test', $contextArray['env']);
    }

    public function testWithContextAcceptsContextInterface(): void
    {
        $factory = new Factory();
        $context = new Context(['key' => 'value']);
        $factory->withContext($context);
        $config = $factory->create();

        $contextArray = $config->getContext()->toArray();
        $this->assertEquals('value', $contextArray['key']);
    }

    public function testWithFileAddsSource(): void
    {
        $testFile = $this->fixturesDir . '/factory-test.json';
        file_put_contents($testFile, json_encode(['app' => ['name' => 'TestApp']]));

        $factory = new Factory();
        $factory->withFile($testFile);
        $config = $factory->create();

        $this->assertEquals('TestApp', $config->get('app.name'));
    }

    public function testWithFileReturnsFactory(): void
    {
        $factory = new Factory();
        $result = $factory->withFile('test.json');

        $this->assertInstanceOf(Factory::class, $result);
        $this->assertSame($factory, $result);
    }

    public function testWithGlobAddsMultipleSources(): void
    {
        file_put_contents($this->fixturesDir . '/glob1.json', json_encode(['key1' => 'value1']));
        file_put_contents($this->fixturesDir . '/glob2.json', json_encode(['key2' => 'value2']));

        $factory = new Factory();
        $factory->withGlob($this->fixturesDir . '/glob*.json');
        $config = $factory->create();

        $this->assertTrue($config->has('key1'));
        $this->assertTrue($config->has('key2'));
    }

    public function testWithOverridesAddsOverrides(): void
    {
        $testFile = $this->fixturesDir . '/override-test.json';
        file_put_contents($testFile, json_encode(['app' => ['name' => 'Original', 'version' => '1.0']]));

        $factory = new Factory();
        $factory->withFile($testFile)
                ->withOverrides(['app' => ['name' => 'Overridden']]);
        $config = $factory->create();

        $this->assertEquals('Overridden', $config->get('app.name'));
        $this->assertEquals('1.0', $config->get('app.version'));
    }

    public function testWithPluginRegistersPlugin(): void
    {
        $factory = new Factory();
        $plugin = function ($value, $path, &$data, $next) {
            return $next($value, $path, $data);
        };

        $result = $factory->withPlugin($plugin, priority: 100);

        $this->assertInstanceOf(Factory::class, $result);
    }

    public function testWithParseEnablesParsing(): void
    {
        $factory = new Factory();
        $result = $factory->withParse(true);

        $this->assertInstanceOf(Factory::class, $result);
        $this->assertSame($factory, $result);
    }

    public function testWithParseDisablesParsing(): void
    {
        $factory = new Factory();
        $result = $factory->withParse(false);

        $this->assertInstanceOf(Factory::class, $result);
    }

    public function testResetClearsFactoryState(): void
    {
        $factory = new Factory();
        $factory->withContext(['env' => 'test'])
                ->withOverrides(['key' => 'value']);
        
        $factory->reset();
        $config = $factory->create();

        $this->assertEquals([], $config->toArray());
    }

    public function testResetReturnsFactory(): void
    {
        $factory = new Factory();
        $result = $factory->reset();

        $this->assertInstanceOf(Factory::class, $result);
        $this->assertSame($factory, $result);
    }

    public function testExportCreatesAndExportsConfig(): void
    {
        $sourceFile = $this->fixturesDir . '/export-source.json';
        $targetFile = $this->fixturesDir . '/export-target.json';
        
        file_put_contents($sourceFile, json_encode(['app' => ['name' => 'ExportTest']]));

        $factory = new Factory();
        $factory->withFile($sourceFile)->export($targetFile);

        $this->assertFileExists($targetFile);
        $exported = json_decode(file_get_contents($targetFile), true);
        $this->assertEquals('ExportTest', $exported['app']['name']);
    }

    public function testFromArrayCreatesConfigWithData(): void
    {
        $data = ['key' => 'value'];
        $factory = new Factory();
        $config = $factory->fromArray($data);

        $this->assertInstanceOf(ConfigInterface::class, $config);
        $this->assertEquals($data, $config->toArray());
    }

    public function testGetContextReturnsContextInterface(): void
    {
        $factory = new Factory();
        $context = $factory->getContext();

        $this->assertInstanceOf(ContextInterface::class, $context);
    }

    public function testChainableMethodCalls(): void
    {
        $testFile = $this->fixturesDir . '/chain-test.json';
        file_put_contents($testFile, json_encode(['base' => 'value']));

        $factory = new Factory();
        $config = $factory
            ->withContext(['env' => 'test'])
            ->withFile($testFile)
            ->withOverrides(['override' => 'value'])
            ->withParse(true)
            ->create();

        $this->assertInstanceOf(ConfigInterface::class, $config);
        $this->assertEquals('value', $config->get('base'));
        $this->assertEquals('value', $config->get('override'));
        
        $contextArray = $config->getContext()->toArray();
        $this->assertEquals('test', $contextArray['env']);
    }

    public function testMultipleFilesImportedInOrder(): void
    {
        $file1 = $this->fixturesDir . '/order1.json';
        $file2 = $this->fixturesDir . '/order2.json';
        
        file_put_contents($file1, json_encode(['key' => 'first', 'unique1' => 'value1']));
        file_put_contents($file2, json_encode(['key' => 'second', 'unique2' => 'value2']));

        $factory = new Factory();
        $config = $factory
            ->withFile($file1)
            ->withFile($file2)
            ->create();

        $this->assertEquals('second', $config->get('key'));
        $this->assertEquals('value1', $config->get('unique1'));
        $this->assertEquals('value2', $config->get('unique2'));
    }
}
