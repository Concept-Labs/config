# Adapters

Adapters handle reading and writing configuration data in different formats. They provide a consistent interface for working with various file types.

## Overview

The adapter system allows Concept\Config to support multiple configuration formats. Each adapter is responsible for:

- Reading files in a specific format
- Writing files in that format
- Encoding data to string format
- Decoding string data to arrays

## Adapter Interface

```php
namespace Concept\Config\Resource;

interface AdapterInterface
{
    /**
     * Check if adapter can handle the URI
     */
    public static function supports(string $uri): bool;
    
    /**
     * Read data from URI
     */
    public function read(string $uri): array;
    
    /**
     * Write data to target
     */
    public function write(string $target, array $data): static;
    
    /**
     * Encode array to string
     */
    public function encode(array $data): string;
    
    /**
     * Decode string to array
     */
    public function decode(string $data): array;
}
```

## Built-in Adapters

### JsonAdapter

**Location**: `src/Resource/Adapter/JsonAdapter.php`  
**Supports**: `.json` files

Handles JSON configuration files.

#### Features

**Reading**:
- Glob pattern support for loading multiple files
- Priority-based merging
- JSON validation with detailed error messages

**Writing**:
- Pretty-printed JSON output
- UTF-8 encoding

#### Usage

```php
use Concept\Config\Resource\Adapter\JsonAdapter;

$adapter = new JsonAdapter();

// Read
$data = $adapter->read('config.json');

// Read multiple files with glob
$data = $adapter->read('config/*.json');

// Write
$adapter->write('output.json', ['key' => 'value']);

// Encode/Decode
$json = $adapter->encode(['key' => 'value']);
$array = $adapter->decode('{"key":"value"}');
```

#### Priority Support

JSON files can include a `priority` field for controlling merge order:

```json
// config/base.json (loaded first)
{
  "priority": 0,
  "app": {
    "name": "MyApp"
  }
}

// config/override.json (loaded last)
{
  "priority": 100,
  "app": {
    "debug": true
  }
}
```

Files with higher priority are merged last, allowing them to override earlier values.

#### Example

`config/app.json`:
```json
{
  "app": {
    "name": "MyApplication",
    "version": "2.0.0",
    "debug": false
  },
  "database": {
    "default": "mysql"
  }
}
```

```php
$adapter = new JsonAdapter();
$data = $adapter->read('config/app.json');
// Returns: ['app' => ['name' => 'MyApplication', ...], ...]
```

### PhpAdapter

**Location**: `src/Resource/Adapter/PhpAdapter.php`  
**Supports**: `.php` files

Handles PHP configuration files that return arrays.

#### Features

**Reading**:
- Executes PHP files using `require`
- Validates return type is array
- Inherits PHP's full expressiveness

**Writing**:
- Uses `var_export()` for array serialization
- Generates valid PHP code

#### Usage

```php
use Concept\Config\Resource\Adapter\PhpAdapter;

$adapter = new PhpAdapter();

// Read
$data = $adapter->read('config.php');

// Write
$adapter->write('output.php', ['key' => 'value']);
```

#### Example

`config/database.php`:
```php
<?php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'mydb'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
        ],
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 5432),
        ],
    ],
];
```

```php
$adapter = new PhpAdapter();
$data = $adapter->read('config/database.php');
// Returns: ['default' => 'mysql', 'connections' => [...]]
```

#### Written Output

```php
$adapter->write('output.php', ['key' => 'value']);
```

Generates:
```php
<?php
return array (
  'key' => 'value',
);
```

## Adapter Manager

**Location**: `src/Resource/AdapterManager.php`

The `AdapterManager` handles adapter registration and selection.

### Features

- Register multiple adapters
- Automatic adapter selection based on file extension
- Fallback adapter support

### Usage

```php
use Concept\Config\Resource\AdapterManager;
use Concept\Config\Resource\Adapter\JsonAdapter;
use Concept\Config\Resource\Adapter\PhpAdapter;

$manager = new AdapterManager();

// Register adapters
$manager->registerAdapter(JsonAdapter::class);
$manager->registerAdapter(PhpAdapter::class);

// Get adapter for file
$adapter = $manager->getAdapter('config.json');  // Returns JsonAdapter
$adapter = $manager->getAdapter('config.php');   // Returns PhpAdapter
```

### How It Works

1. Manager iterates through registered adapters
2. Calls `AdapterInterface::supports($uri)` on each
3. Returns first adapter that supports the URI
4. Throws exception if no adapter found

## Creating Custom Adapters

### Basic Custom Adapter

```php
use Concept\Config\Resource\AdapterInterface;

class YamlAdapter implements AdapterInterface
{
    public static function supports(string $uri): bool
    {
        $ext = pathinfo($uri, PATHINFO_EXTENSION);
        return in_array($ext, ['yaml', 'yml']);
    }
    
    public function read(string $uri): array
    {
        $content = file_get_contents($uri);
        return $this->decode($content);
    }
    
    public function write(string $target, array $data): static
    {
        file_put_contents($target, $this->encode($data));
        return $this;
    }
    
    public function encode(array $data): string
    {
        return yaml_emit($data);
    }
    
    public function decode(string $data): array
    {
        return yaml_parse($data);
    }
}
```

### Register Custom Adapter

```php
// Via AdapterManager
$manager = new AdapterManager();
$manager->registerAdapter(YamlAdapter::class);

// Via Config
$config = new Config();
$resource = $config->getResource();
$resource->getAdapterManager()->registerAdapter(YamlAdapter::class);
```

### Advanced Custom Adapter: INI Adapter

```php
class IniAdapter implements AdapterInterface
{
    public static function supports(string $uri): bool
    {
        return pathinfo($uri, PATHINFO_EXTENSION) === 'ini';
    }
    
    public function read(string $uri): array
    {
        if (!file_exists($uri)) {
            throw new \RuntimeException("File not found: $uri");
        }
        
        $content = file_get_contents($uri);
        return $this->decode($content);
    }
    
    public function write(string $target, array $data): static
    {
        file_put_contents($target, $this->encode($data));
        return $this;
    }
    
    public function encode(array $data): string
    {
        $ini = '';
        
        foreach ($data as $section => $values) {
            $ini .= "[$section]\n";
            
            if (is_array($values)) {
                foreach ($values as $key => $value) {
                    $ini .= "$key = " . $this->formatValue($value) . "\n";
                }
            }
            
            $ini .= "\n";
        }
        
        return $ini;
    }
    
    public function decode(string $data): array
    {
        return parse_ini_string($data, true, INI_SCANNER_TYPED);
    }
    
    private function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_numeric($value)) {
            return (string)$value;
        }
        return '"' . addslashes($value) . '"';
    }
}
```

### XML Adapter Example

```php
class XmlAdapter implements AdapterInterface
{
    public static function supports(string $uri): bool
    {
        return pathinfo($uri, PATHINFO_EXTENSION) === 'xml';
    }
    
    public function read(string $uri): array
    {
        $xml = simplexml_load_file($uri);
        return $this->xmlToArray($xml);
    }
    
    public function write(string $target, array $data): static
    {
        $xml = new SimpleXMLElement('<config/>');
        $this->arrayToXml($data, $xml);
        $xml->asXML($target);
        return $this;
    }
    
    public function encode(array $data): string
    {
        $xml = new SimpleXMLElement('<config/>');
        $this->arrayToXml($data, $xml);
        return $xml->asXML();
    }
    
    public function decode(string $data): array
    {
        $xml = simplexml_load_string($data);
        return $this->xmlToArray($xml);
    }
    
    private function xmlToArray(SimpleXMLElement $xml): array
    {
        $json = json_encode($xml);
        return json_decode($json, true);
    }
    
    private function arrayToXml(array $data, SimpleXMLElement &$xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }
}
```

### Remote Source Adapter

```php
class HttpAdapter implements AdapterInterface
{
    public static function supports(string $uri): bool
    {
        return str_starts_with($uri, 'http://') || 
               str_starts_with($uri, 'https://');
    }
    
    public function read(string $uri): array
    {
        $content = file_get_contents($uri);
        
        if ($content === false) {
            throw new \RuntimeException("Failed to fetch: $uri");
        }
        
        return $this->decode($content);
    }
    
    public function write(string $target, array $data): static
    {
        throw new \RuntimeException("HTTP adapter does not support writing");
    }
    
    public function encode(array $data): string
    {
        return json_encode($data);
    }
    
    public function decode(string $data): array
    {
        $decoded = json_decode($data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON: " . json_last_error_msg());
        }
        
        return $decoded;
    }
}

// Usage
$manager->registerAdapter(HttpAdapter::class);
$data = $adapter->read('https://api.example.com/config.json');
```

## Adapter Patterns

### Multi-Format Adapter

Support multiple formats in one adapter:

```php
class MultiFormatAdapter implements AdapterInterface
{
    public static function supports(string $uri): bool
    {
        $ext = pathinfo($uri, PATHINFO_EXTENSION);
        return in_array($ext, ['json', 'yaml', 'yml', 'xml']);
    }
    
    public function read(string $uri): array
    {
        $ext = pathinfo($uri, PATHINFO_EXTENSION);
        $content = file_get_contents($uri);
        
        return match($ext) {
            'json' => json_decode($content, true),
            'yaml', 'yml' => yaml_parse($content),
            'xml' => $this->xmlToArray($content),
            default => throw new \RuntimeException("Unsupported format: $ext")
        };
    }
    
    // ... implement other methods
}
```

### Cached Adapter

Add caching to expensive operations:

```php
class CachedJsonAdapter implements AdapterInterface
{
    private array $cache = [];
    
    public static function supports(string $uri): bool
    {
        return pathinfo($uri, PATHINFO_EXTENSION) === 'json';
    }
    
    public function read(string $uri): array
    {
        $cacheKey = md5($uri . filemtime($uri));
        
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        $content = file_get_contents($uri);
        $data = json_decode($content, true);
        
        $this->cache[$cacheKey] = $data;
        
        return $data;
    }
    
    // ... implement other methods
}
```

### Encrypted Adapter

Handle encrypted configuration files:

```php
class EncryptedJsonAdapter implements AdapterInterface
{
    public function __construct(
        private string $encryptionKey
    ) {}
    
    public static function supports(string $uri): bool
    {
        return pathinfo($uri, PATHINFO_EXTENSION) === 'enc.json';
    }
    
    public function read(string $uri): array
    {
        $encrypted = file_get_contents($uri);
        $decrypted = $this->decrypt($encrypted);
        return json_decode($decrypted, true);
    }
    
    public function write(string $target, array $data): static
    {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        $encrypted = $this->encrypt($json);
        file_put_contents($target, $encrypted);
        return $this;
    }
    
    private function encrypt(string $data): string
    {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt(
            $data,
            'aes-256-cbc',
            $this->encryptionKey,
            0,
            $iv
        );
        return base64_encode($iv . $encrypted);
    }
    
    private function decrypt(string $data): string
    {
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt(
            $encrypted,
            'aes-256-cbc',
            $this->encryptionKey,
            0,
            $iv
        );
    }
    
    // ... implement encode/decode
}
```

## Best Practices

### 1. Validate Input

```php
public function read(string $uri): array
{
    if (!file_exists($uri)) {
        throw new \RuntimeException("File not found: $uri");
    }
    
    $content = file_get_contents($uri);
    
    if ($content === false) {
        throw new \RuntimeException("Failed to read: $uri");
    }
    
    return $this->decode($content);
}
```

### 2. Handle Errors Gracefully

```php
public function decode(string $data): array
{
    $decoded = json_decode($data, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \RuntimeException(
            "JSON decode error: " . json_last_error_msg()
        );
    }
    
    return $decoded;
}
```

### 3. Support URI Patterns

```php
public static function supports(string $uri): bool
{
    // Support both extensions
    $ext = pathinfo($uri, PATHINFO_EXTENSION);
    return in_array($ext, ['yaml', 'yml']);
}
```

### 4. Make Adapters Stateless

```php
// Good: Stateless
class JsonAdapter implements AdapterInterface
{
    public function read(string $uri): array
    {
        // No instance state used
        return json_decode(file_get_contents($uri), true);
    }
}

// Avoid: Stateful (unless necessary)
class StatefulAdapter implements AdapterInterface
{
    private array $lastRead = [];
    
    public function read(string $uri): array
    {
        $this->lastRead = json_decode(file_get_contents($uri), true);
        return $this->lastRead;
    }
}
```

### 5. Document Supported Features

```php
/**
 * YAML Configuration Adapter
 * 
 * Supported extensions: .yaml, .yml
 * 
 * Features:
 * - Full YAML 1.2 support
 * - Handles references and anchors
 * - Preserves data types
 * 
 * Limitations:
 * - Does not support YAML tags
 * - Maximum depth: 100 levels
 * 
 * @package Concept\Config\Resource\Adapter
 */
class YamlAdapter implements AdapterInterface
{
    // ...
}
```

## Testing Adapters

### Unit Test Example

```php
use PHPUnit\Framework\TestCase;

class YamlAdapterTest extends TestCase
{
    private YamlAdapter $adapter;
    
    protected function setUp(): void
    {
        $this->adapter = new YamlAdapter();
    }
    
    public function testSupportsYamlFiles(): void
    {
        $this->assertTrue(YamlAdapter::supports('config.yaml'));
        $this->assertTrue(YamlAdapter::supports('config.yml'));
        $this->assertFalse(YamlAdapter::supports('config.json'));
    }
    
    public function testEncodeAndDecode(): void
    {
        $data = ['key' => 'value', 'nested' => ['item' => 1]];
        
        $encoded = $this->adapter->encode($data);
        $decoded = $this->adapter->decode($encoded);
        
        $this->assertEquals($data, $decoded);
    }
    
    public function testReadAndWrite(): void
    {
        $data = ['test' => 'data'];
        $tempFile = tempnam(sys_get_temp_dir(), 'yaml');
        
        $this->adapter->write($tempFile, $data);
        $read = $this->adapter->read($tempFile);
        
        $this->assertEquals($data, $read);
        
        unlink($tempFile);
    }
}
```

## Adapter Integration

### Automatic Registration

```php
// In your bootstrap or service provider
$manager = $config->getResource()->getAdapterManager();

// Register all custom adapters
$adapters = [
    YamlAdapter::class,
    IniAdapter::class,
    XmlAdapter::class,
    EncryptedJsonAdapter::class,
];

foreach ($adapters as $adapter) {
    $manager->registerAdapter($adapter);
}
```

### Conditional Registration

```php
// Only register YAML adapter if extension is available
if (extension_loaded('yaml')) {
    $manager->registerAdapter(YamlAdapter::class);
}

// Use fallback if main adapter not available
if (class_exists(YamlAdapter::class)) {
    $manager->registerAdapter(YamlAdapter::class);
} else {
    $manager->registerAdapter(JsonAdapter::class);
}
```
