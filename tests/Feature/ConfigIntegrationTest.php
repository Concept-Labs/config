<?php

namespace Concept\Config\Test\Feature;

use Concept\Config\Config;
use Concept\Config\StaticFactory;
use Concept\Config\Factory;
use PHPUnit\Framework\TestCase;

class ConfigIntegrationTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturesDir = __DIR__ . '/../Fixtures/integration';
        
        if (!is_dir($this->fixturesDir)) {
            mkdir($this->fixturesDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        $this->cleanupFixtures();
    }

    private function cleanupFixtures(): void
    {
        if (is_dir($this->fixturesDir)) {
            $files = glob($this->fixturesDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->fixturesDir);
        }
    }

    public function testBasicConfigurationWorkflow(): void
    {
        // Create a config
        $config = new Config([
            'app' => [
                'name' => 'MyApp',
                'version' => '1.0.0'
            ]
        ]);

        // Verify initial state
        $this->assertEquals('MyApp', $config->get('app.name'));
        $this->assertEquals('1.0.0', $config->get('app.version'));

        // Update values
        $config->set('app.version', '2.0.0');
        $config->set('app.debug', true);

        // Verify updates
        $this->assertEquals('2.0.0', $config->get('app.version'));
        $this->assertTrue($config->get('app.debug'));
    }

    public function testLoadImportExportWorkflow(): void
    {
        $baseFile = $this->fixturesDir . '/base.json';
        $overrideFile = $this->fixturesDir . '/override.json';
        $exportFile = $this->fixturesDir . '/export.json';

        // Create base configuration
        file_put_contents($baseFile, json_encode([
            'app' => [
                'name' => 'MyApp',
                'version' => '1.0.0',
                'debug' => false
            ]
        ]));

        // Create override configuration
        file_put_contents($overrideFile, json_encode([
            'app' => [
                'version' => '2.0.0',
                'debug' => true
            ],
            'database' => [
                'host' => 'localhost'
            ]
        ]));

        // Load base config
        $config = StaticFactory::fromFile($baseFile);

        // Import overrides
        $config->import($overrideFile);

        // Verify merged configuration
        $this->assertEquals('MyApp', $config->get('app.name'));
        $this->assertEquals('2.0.0', $config->get('app.version'));
        $this->assertTrue($config->get('app.debug'));
        $this->assertEquals('localhost', $config->get('database.host'));

        // Export configuration
        $config->export($exportFile);

        // Verify exported file
        $this->assertFileExists($exportFile);
        $exported = json_decode(file_get_contents($exportFile), true);
        $this->assertEquals('MyApp', $exported['app']['name']);
        $this->assertEquals('2.0.0', $exported['app']['version']);
        $this->assertEquals('localhost', $exported['database']['host']);
    }

    public function testFactoryBuildPattern(): void
    {
        $file1 = $this->fixturesDir . '/config1.json';
        $file2 = $this->fixturesDir . '/config2.json';

        file_put_contents($file1, json_encode(['service1' => ['enabled' => true]]));
        file_put_contents($file2, json_encode(['service2' => ['enabled' => true]]));

        // Use factory pattern
        $config = (new Factory())
            ->withContext(['env' => 'production'])
            ->withFile($file1)
            ->withFile($file2)
            ->withOverrides(['app' => ['name' => 'BuiltApp']])
            ->create();

        // Verify all configurations merged
        $this->assertTrue($config->get('service1.enabled'));
        $this->assertTrue($config->get('service2.enabled'));
        $this->assertEquals('BuiltApp', $config->get('app.name'));
        
        $contextArray = $config->getContext()->toArray();
        $this->assertEquals('production', $contextArray['env']);
    }

    public function testNodeIsolation(): void
    {
        $config = new Config([
            'services' => [
                'database' => [
                    'host' => 'localhost',
                    'port' => 3306,
                    'credentials' => [
                        'user' => 'root',
                        'password' => 'secret'
                    ]
                ],
                'cache' => [
                    'driver' => 'redis',
                    'host' => 'localhost'
                ]
            ]
        ]);

        // Get database node
        $dbConfig = $config->node('services.database');

        // Verify node isolation
        $this->assertEquals('localhost', $dbConfig->get('host'));
        $this->assertEquals('root', $dbConfig->get('credentials.user'));

        // Modify node
        $dbConfig->set('host', '127.0.0.1');

        // Original should be unchanged (copy mode)
        $this->assertEquals('localhost', $config->get('services.database.host'));
    }

    public function testMultipleFormatSupport(): void
    {
        $jsonFile = $this->fixturesDir . '/config.json';
        $phpFile = $this->fixturesDir . '/config.php';

        $data = [
            'app' => ['name' => 'TestApp'],
            'version' => '1.0'
        ];

        // Write to JSON
        file_put_contents($jsonFile, json_encode($data));

        // Load from JSON
        $jsonConfig = StaticFactory::fromFile($jsonFile);
        $this->assertEquals($data, $jsonConfig->toArray());

        // Export to PHP
        $jsonConfig->export($phpFile);

        // Load from PHP
        $phpConfig = StaticFactory::fromFile($phpFile);
        $this->assertEquals($data, $phpConfig->toArray());
    }

    public function testConfigCloneAndPrototype(): void
    {
        $original = new Config([
            'database' => ['host' => 'localhost']
        ]);

        // Test clone
        $cloned = clone $original;
        $cloned->set('database.host', 'remote.host');

        $this->assertEquals('localhost', $original->get('database.host'));
        $this->assertEquals('remote.host', $cloned->get('database.host'));

        // Test prototype
        $prototype = $original->prototype();
        $this->assertEquals([], $prototype->toArray());
        
        $prototype->set('new', 'value');
        $this->assertFalse($original->has('new'));
    }

    public function testIterationAndArrayAccess(): void
    {
        $data = [
            'service1' => ['enabled' => true],
            'service2' => ['enabled' => false],
            'service3' => ['enabled' => true]
        ];

        $config = new Config($data);

        // Test iteration
        $iterated = [];
        foreach ($config as $key => $value) {
            $iterated[$key] = $value;
        }

        $this->assertEquals($data, $iterated);

        // Test conversion
        $this->assertEquals($data, $config->toArray());
    }
}
