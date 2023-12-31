<?php

use PHPUnit\Framework\TestCase;
use Cl\Cache\InMemory\InMemoryCacheItemPool;
use Cl\Config\DataProvider\ConfigDataProviderInterface;
use Cl\Config\DataProvider\File\Exception\InvalidArgumentException;
use Cl\Config\DataProvider\File\Json\JsonFileDataProvider;

use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertIsArray;
use function PHPUnit\Framework\assertIsString;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

/**
 * @covers Cl\Config\DataProvider\File\Json\JsonFileDataProvider
 */
class JsonFileDataProviderTest extends TestCase
{

    private $_provider;

    public function setUp():void
    {
        $this->_provider = new JsonFileDataProvider(__DIR__.'/test_config.json');
        assertNotNull($this->_provider);
    }
    
    public function testWrongInstance():void
    {
        $this->expectException(InvalidArgumentException::class);
        $provider = new JsonFileDataProvider(__DIR__.'/not_exists_file', 'r');
        assertNull($provider);
    }

    public function testInstance():void
    {
        assertNotNull($this->_provider);        
        assertInstanceOf(ConfigDataProviderInterface::class, $this->_provider);
    }


    public function testToArrayAndToRaw()
    {
        $data = $this->_provider->toArray();
        assertIsArray($data);
        assertIsString($this->_provider->toRaw($data));
    }

    public function testInMemoryCache()
    {
        $cacheItemPool = new InMemoryCacheItemPool();
        assertInstanceOf(\Psr\Cache\CacheItemPoolInterface::class, $cacheItemPool);
        $this->_provider->setCacheItemPool($cacheItemPool);
        $data = $this->_provider->toArray();
        $this->_provider->toCache($data, 'cache_key');
        $cacheData = $this->_provider->fromCache('cache_key');
        assertSame($data, $cacheData);
    }

    public function testReadWrite()
    {
        // $content = $this->_provider->read();
        // assertIsString($content);

        // $origData = $this->_provider->toArray();
        // $newData = array_merge($origData, ['added'=>'data']);
        // $newContent = $this->_provider->toRaw($newData);
        // assertIsString($newContent);

        // $result = $this->_provider->write($newContent, true);
        // assertTrue($result);

        // $newReadData = $this->_provider->toArray();
        // assertSame($newReadData, $newData);
    }

   
}