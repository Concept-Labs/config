<?php

use Concept\Config\Resource\Adapter\JsonAdapter;
use Concept\Config\Resource\Adapter\PhpAdapter;

beforeEach(function () {
    $this->fixturesDir = __DIR__ . '/../../Fixtures';
    
    if (!is_dir($this->fixturesDir)) {
        mkdir($this->fixturesDir, 0777, true);
    }
});

afterEach(function () {
    $jsonFiles = glob($this->fixturesDir . '/*.json');
    $phpFiles = glob($this->fixturesDir . '/*.php');
    
    foreach (array_merge($jsonFiles, $phpFiles) as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});

describe('JsonAdapter', function () {
    beforeEach(function () {
        $this->adapter = new JsonAdapter();
    });

    it('supports json files', function () {
        expect(JsonAdapter::supports('config.json'))->toBeTrue()
            ->and(JsonAdapter::supports('/path/to/file.json'))->toBeTrue();
    });

    it('does not support other extensions', function () {
        expect(JsonAdapter::supports('config.php'))->toBeFalse()
            ->and(JsonAdapter::supports('config.yml'))->toBeFalse();
    });

    it('encodes data to json', function () {
        $data = ['key' => 'value'];
        $encoded = $this->adapter->encode($data);

        expect($encoded)->toBeString()
            ->and(json_decode($encoded, true))->toBe($data);
    });

    it('decodes json string', function () {
        $jsonString = '{"key":"value"}';
        $decoded = $this->adapter->decode($jsonString);

        expect($decoded)->toBe(['key' => 'value']);
    });

    it('reads json file', function () {
        $testFile = $this->fixturesDir . '/pest-read.json';
        $data = ['app' => ['name' => 'TestApp']];
        file_put_contents($testFile, json_encode($data));

        $result = $this->adapter->read($testFile);

        expect($result)->toBe($data);
    });

    it('writes json file', function () {
        $testFile = $this->fixturesDir . '/pest-write.json';
        $data = ['key' => 'value'];

        $this->adapter->write($testFile, $data);

        expect(file_exists($testFile))->toBeTrue();
        
        $content = file_get_contents($testFile);
        expect(json_decode($content, true))->toBe($data);
    });

    it('performs round-trip read and write', function () {
        $testFile = $this->fixturesDir . '/pest-roundtrip.json';
        $data = ['app' => ['name' => 'TestApp', 'version' => '1.0']];

        $this->adapter->write($testFile, $data);
        $result = $this->adapter->read($testFile);

        expect($result)->toBe($data);
    });

    it('throws exception for invalid json', function () {
        expect(fn() => $this->adapter->decode('invalid json {'))
            ->toThrow(\JsonException::class);
    });
});

describe('PhpAdapter', function () {
    beforeEach(function () {
        $this->adapter = new PhpAdapter();
    });

    it('supports php files', function () {
        expect(PhpAdapter::supports('config.php'))->toBeTrue()
            ->and(PhpAdapter::supports('/path/to/file.php'))->toBeTrue();
    });

    it('does not support other extensions', function () {
        expect(PhpAdapter::supports('config.json'))->toBeFalse()
            ->and(PhpAdapter::supports('config.yml'))->toBeFalse();
    });

    it('encodes data to php array string', function () {
        $data = ['key' => 'value'];
        $encoded = $this->adapter->encode($data);

        expect($encoded)->toBeString()
            ->and($encoded)->toContain('array');
    });

    it('reads php file', function () {
        $testFile = $this->fixturesDir . '/pest-read.php';
        $data = ['app' => ['name' => 'TestApp']];
        file_put_contents($testFile, '<?php return ' . var_export($data, true) . ';');

        $result = $this->adapter->read($testFile);

        expect($result)->toBe($data);
    });

    it('writes php file', function () {
        $testFile = $this->fixturesDir . '/pest-write.php';
        $data = ['key' => 'value'];

        $this->adapter->write($testFile, $data);

        expect(file_exists($testFile))->toBeTrue();
        
        $content = file_get_contents($testFile);
        expect($content)->toStartWith('<?php')
            ->and($content)->toContain('return');
    });

    it('performs round-trip read and write', function () {
        $testFile = $this->fixturesDir . '/pest-roundtrip.php';
        $data = [
            'app' => [
                'name' => 'TestApp',
                'version' => '1.0'
            ]
        ];

        $this->adapter->write($testFile, $data);
        $result = $this->adapter->read($testFile);

        expect($result)->toBe($data);
    });

    it('throws exception when decode is called', function () {
        expect(fn() => $this->adapter->decode('any string'))
            ->toThrow(\RuntimeException::class, 'Use read method instead');
    });

    it('throws exception for non-array return', function () {
        $testFile = $this->fixturesDir . '/pest-invalid.php';
        file_put_contents($testFile, '<?php return "not an array";');

        expect(fn() => $this->adapter->read($testFile))
            ->toThrow(\Concept\Config\Resource\Exception\InvalidArgumentException::class);
    });
});
