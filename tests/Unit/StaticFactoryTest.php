<?php

namespace Concept\Config\Test\Unit;

use Concept\Config\ConfigInterface;
use Concept\Config\StaticFactory;
use PHPUnit\Framework\TestCase;

class StaticFactoryTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturesDir = __DIR__ . '/../Fixtures';
        
        // Create fixtures directory if it doesn't exist
        if (!is_dir($this->fixturesDir)) {
            mkdir($this->fixturesDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up test files
        $testFiles = glob($this->fixturesDir . '/*.json');
        foreach ($testFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function testCreateWithEmptyData(): void
    {
        $config = StaticFactory::create();

        $this->assertInstanceOf(ConfigInterface::class, $config);
        $this->assertEquals([], $config->toArray());
    }

    public function testCreateWithData(): void
    {
        $data = ['app' => ['name' => 'TestApp']];
        $config = StaticFactory::create($data);

        $this->assertEquals($data, $config->toArray());
    }

    public function testCreateWithContext(): void
    {
        $context = ['env' => 'test'];
        $config = StaticFactory::create([], $context);

        $contextArray = $config->getContext()->toArray();
        $this->assertArrayHasKey('env', $contextArray);
        $this->assertEquals('test', $contextArray['env']);
    }

    public function testCreateWithParse(): void
    {
        $data = ['app' => ['name' => 'TestApp']];
        $config = StaticFactory::create($data, [], parse: true);

        $this->assertInstanceOf(ConfigInterface::class, $config);
        $this->assertEquals($data, $config->toArray());
    }

    public function testFromFileLoadsJsonFile(): void
    {
        $testFile = $this->fixturesDir . '/test.json';
        $data = ['database' => ['host' => 'localhost', 'port' => 3306]];
        file_put_contents($testFile, json_encode($data));

        $config = StaticFactory::fromFile($testFile);

        $this->assertInstanceOf(ConfigInterface::class, $config);
        $this->assertEquals($data, $config->toArray());
    }

    public function testFromFileWithContext(): void
    {
        $testFile = $this->fixturesDir . '/test-context.json';
        $data = ['app' => ['name' => 'TestApp']];
        file_put_contents($testFile, json_encode($data));
        
        $context = ['env' => 'production'];
        $config = StaticFactory::fromFile($testFile, $context);

        $contextArray = $config->getContext()->toArray();
        $this->assertEquals('production', $contextArray['env']);
    }

    public function testFromFilesLoadsMultipleFiles(): void
    {
        $file1 = $this->fixturesDir . '/config1.json';
        $file2 = $this->fixturesDir . '/config2.json';
        
        file_put_contents($file1, json_encode(['app' => ['name' => 'TestApp']]));
        file_put_contents($file2, json_encode(['database' => ['host' => 'localhost']]));

        $config = StaticFactory::fromFiles([$file1, $file2]);

        $this->assertEquals('TestApp', $config->get('app.name'));
        $this->assertEquals('localhost', $config->get('database.host'));
    }

    public function testFromFilesMergesConfigurations(): void
    {
        $file1 = $this->fixturesDir . '/base.json';
        $file2 = $this->fixturesDir . '/override.json';
        
        file_put_contents($file1, json_encode(['app' => ['name' => 'Base', 'version' => '1.0']]));
        file_put_contents($file2, json_encode(['app' => ['version' => '2.0'], 'new' => 'value']));

        $config = StaticFactory::fromFiles([$file1, $file2]);

        $this->assertEquals('Base', $config->get('app.name'));
        $this->assertEquals('2.0', $config->get('app.version'));
        $this->assertEquals('value', $config->get('new'));
    }

    public function testFromGlobLoadsMatchingFiles(): void
    {
        file_put_contents($this->fixturesDir . '/config1.json', json_encode(['key1' => 'value1']));
        file_put_contents($this->fixturesDir . '/config2.json', json_encode(['key2' => 'value2']));
        file_put_contents($this->fixturesDir . '/other.txt', 'not a json');

        $pattern = $this->fixturesDir . '/*.json';
        $config = StaticFactory::fromGlob($pattern);

        $this->assertTrue($config->has('key1'));
        $this->assertTrue($config->has('key2'));
    }

    public function testFromGlobWithContext(): void
    {
        file_put_contents($this->fixturesDir . '/test.json', json_encode(['key' => 'value']));
        
        $pattern = $this->fixturesDir . '/*.json';
        $context = ['env' => 'test'];
        $config = StaticFactory::fromGlob($pattern, $context);

        $contextArray = $config->getContext()->toArray();
        $this->assertEquals('test', $contextArray['env']);
    }

    public function testCompileCreatesConfigAndExportsToFile(): void
    {
        $source1 = $this->fixturesDir . '/source1.json';
        $source2 = $this->fixturesDir . '/source2.json';
        $target = $this->fixturesDir . '/compiled.json';
        
        file_put_contents($source1, json_encode(['app' => ['name' => 'TestApp']]));
        file_put_contents($source2, json_encode(['database' => ['host' => 'localhost']]));

        $config = StaticFactory::compile([$source1, $source2], [], $target);

        $this->assertInstanceOf(ConfigInterface::class, $config);
        $this->assertFileExists($target);
        
        $compiled = json_decode(file_get_contents($target), true);
        $this->assertEquals('TestApp', $compiled['app']['name']);
        $this->assertEquals('localhost', $compiled['database']['host']);
    }

    public function testCompileWithGlobPattern(): void
    {
        file_put_contents($this->fixturesDir . '/app1.json', json_encode(['key1' => 'value1']));
        file_put_contents($this->fixturesDir . '/app2.json', json_encode(['key2' => 'value2']));
        
        $pattern = $this->fixturesDir . '/app*.json';
        $target = $this->fixturesDir . '/compiled-glob.json';

        $config = StaticFactory::compile($pattern, [], $target);

        $this->assertFileExists($target);
        $compiled = json_decode(file_get_contents($target), true);
        $this->assertArrayHasKey('key1', $compiled);
        $this->assertArrayHasKey('key2', $compiled);
    }

    public function testCompileWithContext(): void
    {
        $source = $this->fixturesDir . '/source-ctx.json';
        $target = $this->fixturesDir . '/compiled-ctx.json';
        
        file_put_contents($source, json_encode(['app' => ['name' => 'TestApp']]));
        
        $context = ['env' => 'production'];
        $config = StaticFactory::compile($source, $context, $target);

        $contextArray = $config->getContext()->toArray();
        $this->assertEquals('production', $contextArray['env']);
    }
}
