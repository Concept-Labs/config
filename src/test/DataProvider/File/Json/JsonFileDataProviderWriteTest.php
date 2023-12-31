<?php

use Cl\Config\DataProvider\File\Exception\FileWriteException;
use Cl\Config\DataProvider\File\Json\JsonFileDataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers Cl\Config\DataProvider\File\Json\JsonFileDataProvider
 */
class JsonFileDataProviderWriteTest extends TestCase
{
    const WRITE_TEXT = '{
        "a": {
            "a": "a1",
            "b":"b1"
        },
        "a.a": {
            "a": "b"
        }
    }';

    const FILE = __DIR__.'/test_config.json';

    public function testWriteSuccess()
    {
        $text = self::WRITE_TEXT;
        /** @var JsonFileDataProvider $fileDataProvider */
        $fileDataProvider = new JsonFileDataProvider(self::FILE);
        $result = $fileDataProvider->write($text, false);
        $this->assertTrue($result);
    }
    
    public function testWriteMockSuccess()
    {
        $text = self::WRITE_TEXT;
        /** @var JsonFileDataProvider $fileDataProvider */
        $fileDataProvider = $this->getMockedFileDataProvider(true, true, strlen($text));
        $result = $fileDataProvider->write($text, false);
        $this->assertTrue($result);
    }

    public function testWriteFlockFailureWithException()
    {
        $text = self::WRITE_TEXT;
        $this->expectException(FileWriteException::class);
        /** @var JsonFileDataProvider $fileDataProvider */
        $fileDataProvider = $this->getMockedFileDataProvider(false, true, strlen($text));
        $fileDataProvider->write($text, true);
    }

    public function testWriteFlockFailureWithoutException()
    {
        $text = self::WRITE_TEXT;
        /** @var JsonFileDataProvider $fileDataProvider */
        $fileDataProvider = $this->getMockedFileDataProvider(false, true, strlen($text));
        $result = $fileDataProvider->write($text, false);
        $this->assertFalse($result);
    }

    public function testWriteFtruncateFailureWithException()
    {
        $text = self::WRITE_TEXT;
        $this->expectException(FileWriteException::class);
        /** @var JsonFileDataProvider $fileDataProvider */
        $fileDataProvider = $this->getMockedFileDataProvider(true, false, strlen($text));
        $fileDataProvider->write($text, true);
    }

    public function testWriteFtruncateFailureWithoutException()
    {
        $text = self::WRITE_TEXT;
        /** @var JsonFileDataProvider $fileDataProvider */
        $fileDataProvider = $this->getMockedFileDataProvider(true, false, strlen($text));
        $result = $fileDataProvider->write($text, false);
        $this->assertFalse($result);
    }

    public function testWriteFwriteFailureWithException()
    {
        $text = self::WRITE_TEXT;
        $this->expectException(FileWriteException::class);
        /** @var JsonFileDataProvider $fileDataProvider */
        $fileDataProvider = $this->getMockedFileDataProvider(true, true, false);
        $fileDataProvider->write($text, true);
    }

    public function testWriteFwriteFailureWithoutException()
    {
        $text = 'Test content';
        /** @var JsonFileDataProvider $fileDataProvider */
        $fileDataProvider = $this->getMockedFileDataProvider(true, true, false);
        $result = $fileDataProvider->write($text, false);
        $this->assertFalse($result);
    }

    // Helper method to create a mocked FileDataProvider
    private function getMockedFileDataProvider($flockReturnValue, $ftruncateReturnValue, $fwriteReturnValue,)
    {
        $fileDataProvider = $this->getMockBuilder(JsonFileDataProvider::class)
            ->setConstructorArgs([self::FILE])
            ->onlyMethods(['flock', 'ftruncate', 'fwrite'])
            //->addMethods(['getPathname']) // Add any additional methods that you need to mock
            ->getMock();

        $fileDataProvider->method('flock')->willReturn($flockReturnValue);
        $fileDataProvider->method('ftruncate')->willReturn($ftruncateReturnValue);
        $fileDataProvider->method('fwrite')->willReturn($fwriteReturnValue);

        return $fileDataProvider;
    }
}