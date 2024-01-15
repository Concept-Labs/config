<?php

use Cl\Config\Config;
use Cl\Config\ConfigInterface;
use Cl\Config\DataProvider\ConfigDataProviderInterface;
use Cl\Config\DataProvider\File\Json\JsonFileDataProvider;
use Cl\Config\Exception\InvalidPathException;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertInstanceOf;

/**
 * @covers Cl\Container\\config\config
 */
class ClConfigTest extends TestCase
{
    protected Config $config;
    public function configDataProvider()
    {
        
    }

    
    public function setUp():void
    {
        $data = [
            'a' => ['b' => 'value'],
            'a.a' => ['b' => ["c.c" => 'value']],
            'childInstance' => ['a','b'],
            
        ];
        $this->config =  new Config($data);
    }


    public function testLoad()
    {
        $this->config->addProvider(new JsonFileDataProvider(__DIR__.'/config.json'));
        $this->assertIsArray($this->config->getProviders());
        foreach ($this->config->getProviders() as $provider) {
            assertInstanceOf(ConfigDataProviderInterface::class, $provider);
        }
        $this->config->load();

        $this->assertIsArray($this->config->all());
        $this->assertGreaterThan(0, $this->config->all());
        
        $a = $this->config->get('a');
        $this->isInstanceOf(ConfigInterface::class, $a);
        $this->assertSame($a->all(), ["a"=>"a1", "b"=>"b1"]);

        $b = $this->config->get('a.b');
        $this->assertSame('b1', $b);
    }
    /**
     * Test the getter
     *
     * @return void
     */
    public function testGetByKey()
    {
        $result = $this->config['a'];
        $this->assertSame(['b' => 'value'], $result->all());

        $result = $this->config['a'];
        $this->assertSame(['b' => 'value'], $result->all());
    }

    /**
     * Test the getter using path string
     *
     * @return void
     */
    public function testGetByPath()
    {
        $result = $this->config->get('a.b');
        $this->assertSame('value', $result);

        $result = $this->config['a.b'];
        $this->assertSame('value', $result);
        
        $result = $this->config->get('a');
        $this->assertInstanceOf(ConfigInterface::class, $result);
    }

    /**
     * Test the getter using path string
     *
     * @return void
     */
    public function testGetByPathReturnInstance()
    {
        $result = $this->config->get('"a.a"');
        $this->assertInstanceOf(ConfigInterface::class, $result);
    }

    /**
     * Test the getter using path string
     *
     * @return void
     */
    public function testgetByPathSplitter()
    {
        $result = $this->config->get('"a.a".b');
        $this->assertInstanceOf(ConfigInterface::class, $result);
        $this->assertSame(['c.c'=>'value'], (array)$result);
    }

    /**
     * Test the getter with invalid path string
     *
     * @return void
     */
    public function testGetInvalidPath()
    {
        $this->expectException(InvalidPathException::class);

        $this->config->get('invalid.path');
    }

    /**
     * Test child instance
     *
     * @return void
     */
    public function testNewChildInstance()
    {
        $data = ['a' => ['b' => ['c','d'=>'e']]];
        $iterator = new Config($data);
        /** @var Config $child */
        $child = $iterator['a.b'];

        $this->assertInstanceOf(Config::class, $child);
        $this->assertSame(['c','d'=>'e'], $child->all());
        $this->assertSame('a.b', $child->getPath());
        $this->assertSame($iterator, $child->getParent());
        $this->assertSame(Config::PATH_DEFAULT_SEPARATOR, $child->getSeparator());
        $this->assertSame(0, $child->getFlags());
    }
}