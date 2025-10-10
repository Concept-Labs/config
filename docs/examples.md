# Examples

Practical examples showing how to use Concept\Config in real-world scenarios.

## Table of Contents

1. [Basic Application Configuration](#basic-application-configuration)
2. [Using the Facade](#using-the-facade)
3. [Multi-Environment Setup](#multi-environment-setup)
4. [Database Configuration](#database-configuration)
5. [Service Container Integration](#service-container-integration)
6. [Multi-Tenant Application](#multi-tenant-application)
7. [Microservices Configuration](#microservices-configuration)
8. [Feature Flags](#feature-flags)
9. [Configuration Compilation](#configuration-compilation)
10. [Dynamic Configuration](#dynamic-configuration)
11. [Testing with Configuration](#testing-with-configuration)

## Basic Application Configuration

### config/app.json

```json
{
  "app": {
    "name": "MyApplication",
    "version": "1.0.0",
    "debug": "@env(APP_DEBUG)",
    "timezone": "UTC",
    "locale": "en"
  },
  "paths": {
    "root": "${APP_ROOT}",
    "storage": "${APP_ROOT}/storage",
    "cache": "${APP_ROOT}/storage/cache",
    "logs": "${APP_ROOT}/storage/logs"
  }
}
```

### bootstrap.php

```php
<?php
require_once 'vendor/autoload.php';

use Concept\Config\StaticFactory;

// Load configuration
$config = StaticFactory::fromFile('config/app.json', parse: true);

// Use configuration
define('APP_NAME', $config->get('app.name'));
define('APP_VERSION', $config->get('app.version'));
define('APP_DEBUG', $config->get('app.debug', false));

// Set timezone
date_default_timezone_set($config->get('app.timezone'));
```

## Using the Facade

The Facade provides the simplest way to create production-ready configurations with all features enabled.

### config/app.json

```json
{
  "app": {
    "name": "@env(APP_NAME)",
    "env": "@env(APP_ENV)",
    "debug": "@env(APP_DEBUG)",
    "url": "@env(APP_URL)"
  },
  "database": {
    "host": "@env(DB_HOST)",
    "port": "@env(DB_PORT)",
    "name": "@env(DB_NAME)",
    "user": "@env(DB_USER)",
    "password": "@env(DB_PASSWORD)"
  },
  "paths": {
    "root": "@env(APP_ROOT)",
    "public": "@env(APP_ROOT)/public",
    "storage": "@env(APP_ROOT)/storage",
    "cache": "@env(APP_ROOT)/storage/cache"
  }
}
```

### app.php

```php
<?php
require_once 'vendor/autoload.php';

use Concept\Config\Facade\Config;

// Load configuration with Facade - all plugins pre-configured
$config = Config::config(
    source: 'config/app.json',
    context: [
        'ENV' => getenv()  // Make environment variables available
    ]
);

// All variables are automatically resolved
echo $config->get('app.name');          // From APP_NAME env variable
echo $config->get('database.host');     // From DB_HOST env variable
echo $config->get('paths.cache');       // Resolved from @paths.root reference
```

### Multiple Files with Facade

```php
use Concept\Config\Facade\Config;

// Load all config files
$config = Config::config(
    source: 'config/*.json',
    context: [
        'env' => 'production',
        'region' => 'us-east-1',
        'ENV' => getenv()
    ]
);
```

### Facade with Overrides

```php
use Concept\Config\Facade\Config;

// Load config with runtime overrides
$config = Config::config(
    source: 'config/app.json',
    context: ['ENV' => getenv()],
    overrides: [
        'app.debug' => true,           // Force debug mode
        'cache.enabled' => false,      // Disable cache for testing
        'mail.driver' => 'log'         // Use log driver for emails
    ]
);
```

## Multi-Environment Setup

### config/app.json (base)

```json
{
  "app": {
    "name": "MyApp",
    "debug": false,
    "log_level": "error"
  },
  "@import": "env/@env(APP_ENV).json"
}
```

### config/env/development.json

```json
{
  "app": {
    "debug": true,
    "log_level": "debug"
  },
  "cache": {
    "driver": "array"
  },
  "database": {
    "host": "localhost"
  }
}
```

### config/env/production.json

```json
{
  "app": {
    "debug": false,
    "log_level": "error"
  },
  "cache": {
    "driver": "redis",
    "connection": "default"
  },
  "database": {
    "host": "@env(DB_HOST)"
  }
}
```

### Usage

```php
// Set environment
putenv('APP_ENV=development');

$config = StaticFactory::fromFile('config/app.json', parse: true);

if ($config->get('app.debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}
```

## Database Configuration

### config/database.php

```php
<?php
return [
    'default' => env('DB_CONNECTION', 'mysql'),
    
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'mydb'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
        
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 5432),
            'database' => env('DB_DATABASE', 'mydb'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'schema' => 'public',
        ],
    ],
];
```

### Database.php

```php
<?php

class Database
{
    private static ?PDO $connection = null;
    
    public static function connect(Config $config): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }
        
        $default = $config->get('database.default');
        $connConfig = $config->get("database.connections.$default");
        
        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s',
            $connConfig['driver'],
            $connConfig['host'],
            $connConfig['port'],
            $connConfig['database']
        );
        
        self::$connection = new PDO(
            $dsn,
            $connConfig['username'],
            $connConfig['password'],
            $connConfig['options'] ?? []
        );
        
        return self::$connection;
    }
}

// Usage
$config = StaticFactory::fromFile('config/database.php');
$pdo = Database::connect($config);
```

## Multi-Tenant Application

### TenantConfig.php

```php
<?php

class TenantConfig
{
    private Config $config;
    private string $tenantId;
    
    public function __construct(string $tenantId)
    {
        $this->tenantId = $tenantId;
        $this->loadConfig();
    }
    
    private function loadConfig(): void
    {
        // Load base configuration
        $this->config = StaticFactory::fromFiles([
            'config/app.json',
            'config/database.json',
        ]);
        
        // Load tenant-specific configuration
        $tenantConfigFile = "config/tenants/{$this->tenantId}.json";
        
        if (file_exists($tenantConfigFile)) {
            $this->config->import($tenantConfigFile, parse: true);
        }
        
        // Add tenant context
        $this->config->withContext([
            'tenant' => [
                'id' => $this->tenantId,
                'name' => $this->getTenantName(),
                'subdomain' => $this->getTenantSubdomain(),
            ],
            'ENV' => getenv()
        ]);
        
        // Parse with context
        $this->config->getParser()->parse($this->config->dataReference());
    }
    
    private function getTenantName(): string
    {
        // Load from database or cache
        return "Tenant {$this->tenantId}";
    }
    
    private function getTenantSubdomain(): string
    {
        // Load from database or cache
        return strtolower($this->tenantId);
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config->get($key, $default);
    }
    
    public function getDatabaseName(): string
    {
        return "tenant_{$this->tenantId}";
    }
}

// Usage
$tenantId = $_SERVER['HTTP_X_TENANT_ID'] ?? 'default';
$config = new TenantConfig($tenantId);

echo $config->get('app.name'); // Tenant-specific app name
```

## Microservices Configuration

### config/services.json

```json
{
  "services": {
    "auth": {
      "url": "@env(AUTH_SERVICE_URL)",
      "timeout": 5,
      "retries": 3,
      "circuit_breaker": {
        "threshold": 5,
        "timeout": 60
      }
    },
    "payment": {
      "url": "@env(PAYMENT_SERVICE_URL)",
      "api_key": "@env(PAYMENT_API_KEY)",
      "timeout": 10
    },
    "notification": {
      "url": "@env(NOTIFICATION_SERVICE_URL)",
      "async": true,
      "queue": "notifications"
    }
  },
  
  "discovery": {
    "enabled": true,
    "consul": {
      "host": "@env(CONSUL_HOST)",
      "port": 8500
    }
  }
}
```



## Configuration Compilation

### export (best way)
```php
$config->export('compiled.json'); //export parsed to single json file
// OR f.e.
//$config->export('compiled.php'); //auto resolution of target format
```

### compile.php

```php
<?php
require_once 'vendor/autoload.php';

use Concept\Config\StaticFactory;

// Compile all configuration files into one
StaticFactory::compile(
    sources: [
        'config/app.json',
        'config/database.json',
        'config/cache.json',
        'config/services.json',
        'config/features.json',
    ],
    context: [
        'env' => 'production',
        'ENV' => getenv()
    ],
    target: 'compiled/config.json'
);

echo "Configuration compiled successfully!\n";
```

### Usage in production

```php
<?php

// In production, use compiled config
if (file_exists('compiled/config.json')) {
    $config = StaticFactory::fromFile('compiled/config.json');
} else {
    // Fallback to loading all files
    $config = StaticFactory::fromGlob('config/*.json', parse: true);
}
```

## Dynamic Configuration

### DynamicConfig.php

```php
<?php

class DynamicConfig
{
    private Config $config;
    
    public function __construct()
    {
        $this->config = new Config();
        $this->loadFromEnvironment();
        $this->loadFromFiles();
        $this->loadFromDatabase();
        $this->loadFromRemote();
    }
    
    private function loadFromEnvironment(): void
    {
        $this->config->import([
            'env' => getenv('APP_ENV') ?: 'production',
            'debug' => getenv('APP_DEBUG') === 'true',
        ]);
    }
    
    private function loadFromFiles(): void
    {
        $configFiles = glob('config/*.json');
        
        foreach ($configFiles as $file) {
            $this->config->import($file, parse: true);
        }
    }
    
    private function loadFromDatabase(): void
    {
        try {
            $pdo = new PDO(/* ... */);
            $stmt = $pdo->query("SELECT key, value FROM config");
            
            $dbConfig = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $dbConfig[$row['key']] = json_decode($row['value'], true);
            }
            
            $this->config->import($dbConfig);
        } catch (\Exception $e) {
            // Log error, continue with file-based config
        }
    }
    
    private function loadFromRemote(): void
    {
        try {
            $url = getenv('REMOTE_CONFIG_URL');
            
            if ($url) {
                $json = file_get_contents($url);
                $remoteConfig = json_decode($json, true);
                
                $this->config->import($remoteConfig, parse: true);
            }
        } catch (\Exception $e) {
            // Log error, continue without remote config
        }
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config->get($key, $default);
    }
    
    public function refresh(): void
    {
        $this->config->reset();
        $this->__construct();
    }
}
```

## Testing with Configuration

### TestCase.php

```php
<?php

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Config $config;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Load test configuration
        $this->config = StaticFactory::fromFiles([
            'config/app.json',
            'tests/config/test.json',
        ], parse: true);
        
        // Override with test values
        $this->config->import([
            'database' => [
                'default' => 'sqlite',
                'connections' => [
                    'sqlite' => [
                        'driver' => 'sqlite',
                        'database' => ':memory:',
                    ]
                ]
            ],
            'cache' => [
                'driver' => 'array'
            ],
            'mail' => [
                'driver' => 'array'
            ]
        ]);
    }
    
    protected function tearDown(): void
    {
        $this->config->reset();
        parent::tearDown();
    }
}
```

### UserServiceTest.php

```php
<?php

class UserServiceTest extends TestCase
{
    public function testUserCreation(): void
    {
        $userService = new UserService($this->config);
        
        $user = $userService->create([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
    }
    
    public function testWithCustomConfig(): void
    {
        // Override config for specific test
        $this->config->set('users.verification_required', false);
        
        $userService = new UserService($this->config);
        $user = $userService->create(['email' => 'test@example.com']);
        
        $this->assertTrue($user->isVerified());
    }
}
```
