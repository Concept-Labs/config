# Context & Variables

The Context system provides runtime variable resolution for your configuration, enabling dynamic configuration based on environment, user input, or other runtime values.

## Overview

Context is a key-value store that configuration values can reference during parsing. It enables:

- Environment variable resolution
- Dynamic value injection
- Runtime configuration
- Multi-tenant configurations
- Feature flags based on runtime state

## Context Basics

### Creating Context

```php
use Concept\Config\Context\Context;

// Empty context
$context = new Context();

// With initial data
$context = new Context([
    'environment' => 'production',
    'region' => 'us-east-1',
    'tenant' => 'acme-corp'
]);
```

### Adding to Config

```php
use Concept\Config\Config;

// During construction
$config = new Config(
    data: ['key' => 'value'],
    context: ['env' => 'production']
);

// After construction
$config->withContext([
    'environment' => 'staging',
    'debug' => true
]);
```

## Environment Variables

### Adding Environment Variables

```php
$context = new Context();

// Add all environment variables
$context->withEnv(getenv());

// Or specific variables
$context->withEnv([
    'DB_HOST' => getenv('DB_HOST'),
    'DB_PORT' => getenv('DB_PORT'),
]);
```

### Using in Configuration

```php
$config = new Config([
    'database' => [
        'host' => '@env(DB_HOST)',
        'port' => '@env(DB_PORT)',
        'user' => '@env(DB_USER)',
        'password' => '@env(DB_PASSWORD)',
    ]
]);

// Add environment context
$config->getContext()->withEnv(getenv());

// Parse to resolve
$config->getParser()->parse($config->dataReference());

echo $config->get('database.host'); // Value from DB_HOST env var
```

## Custom Sections

Organize context data into logical sections:

```php
$context = $config->getContext();

// Add database section
$context->withSection('database', [
    'default' => 'mysql',
    'connections' => ['mysql', 'pgsql']
]);

// Add cache section
$context->withSection('cache', [
    'driver' => 'redis',
    'prefix' => 'app_'
]);

// Add custom application section
$context->withSection('app', [
    'version' => '2.0.0',
    'features' => ['billing', 'analytics']
]);
```

### Accessing Sections

```php
// Get entire section
$dbSection = $context->get('database');

// Get specific value in section
$cacheDriver = $context->get('cache.driver');

// Check if section exists
if ($context->has('app')) {
    // Section exists
}
```

## Variable Resolution

### Environment Variables

**Syntax**: `@env(VARIABLE_NAME)`

```json
{
  "api": {
    "key": "@env(API_KEY)",
    "secret": "@env(API_SECRET)",
    "endpoint": "@env(API_ENDPOINT)"
  }
}
```

**Resolution Order**:
1. `$_ENV['VARIABLE_NAME']`
2. `getenv('VARIABLE_NAME')`
3. `context.ENV.VARIABLE_NAME`
4. Original value (unchanged)

### Config References

**Syntax**: `@path.to.value`

```json
{
  "paths": {
    "root": "/var/www/app",
    "storage": "@paths.root/storage",
    "cache": "@paths.storage/cache",
    "logs": "@paths.storage/logs"
  }
}
```

### Context References

**Syntax**: `${path.to.context.value}`

Context variables are resolved using the **ContextPlugin**, which allows you to reference values stored in the configuration context. The syntax uses `${...}` with the path to the context value.

```php
$config = new Config(
    data: [
        'app' => [
            'name' => 'MyApp',
            'environment' => '${env}',
            'region' => '${region}',
            'tenant' => '${tenant}',
            'database' => 'app_${tenant}_${env}'
        ]
    ],
    context: [
        'env' => 'production',
        'region' => 'us-west-2',
        'tenant' => 'acme-corp'
    ]
);

$config->getParser()->parse($config->dataReference());

echo $config->get('app.environment'); // 'production'
echo $config->get('app.database');    // 'app_acme-corp_production'
```

**Key Features**:
- Multiple variables can be used in a single value
- Variables can be combined with static text
- Supports nested context paths (e.g., `${user.profile.name}`)

## Practical Examples

### Multi-Environment Configuration

```php
// Set up environment-based configuration
$env = getenv('APP_ENV') ?: 'production';

$config = new Config(
    data: [
        'app' => [
            'name' => 'MyApp',
            'env' => '@env(APP_ENV)',
            'debug' => '@env(APP_DEBUG)'
        ],
        'database' => [
            'host' => '@env(DB_HOST)',
            'port' => '@env(DB_PORT)',
            'database' => '@env(DB_DATABASE)'
        ],
        'cache' => [
            'driver' => $env === 'production' ? 'redis' : 'array'
        ]
    ],
    context: [
        'ENV' => getenv()
    ]
);

$config->getParser()->parse($config->dataReference());
```

### Multi-Tenant Configuration

```php
class TenantConfig
{
    private Config $config;
    
    public function __construct(string $tenantId)
    {
        // Load base configuration
        $this->config = new Config([
            'app' => [
                'name' => '${tenant.name}',
                'logo' => '${tenant.logo}',
                'theme' => '${tenant.theme}'
            ],
            'database' => [
                'name' => 'tenant_${tenant.id}'
            ]
        ]);
        
        // Load tenant-specific context
        $tenantData = $this->loadTenantData($tenantId);
        
        $this->config->getContext()->withSection('tenant', $tenantData);
        $this->config->getParser()->parse($this->config->dataReference());
    }
    
    private function loadTenantData(string $tenantId): array
    {
        // Load from database, API, etc.
        return [
            'id' => $tenantId,
            'name' => 'Acme Corporation',
            'logo' => 'https://cdn.example.com/logos/acme.png',
            'theme' => 'dark'
        ];
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config->get($key, $default);
    }
}

// Usage
$tenantConfig = new TenantConfig('acme-corp');
echo $tenantConfig->get('app.name'); // "Acme Corporation"
```

### Feature Flags with Context

```php
class FeatureFlags
{
    private Config $config;
    
    public function __construct(array $userContext)
    {
        $this->config = new Config([
            'features' => [
                'new_ui' => [
                    'enabled' => '@env(FEATURE_NEW_UI)',
                    'rollout_percentage' => 50
                ],
                'beta_api' => [
                    'enabled' => '${user.beta_tester}',
                    'version' => 'v2'
                ],
                'premium_features' => [
                    'enabled' => '${user.subscription_tier}'
                ]
            ]
        ]);
        
        $this->config->withContext([
            'ENV' => getenv(),
            'user' => $userContext
        ]);
        
        $this->config->getParser()->parse($this->config->dataReference());
    }
    
    public function isEnabled(string $feature): bool
    {
        return (bool) $this->config->get("features.$feature.enabled", false);
    }
}

// Usage
$flags = new FeatureFlags([
    'beta_tester' => true,
    'subscription_tier' => 'premium'
]);

if ($flags->isEnabled('beta_api')) {
    // Use beta API
}
```

### Dynamic Path Resolution

```php
$config = new Config([
    'paths' => [
        'root' => '@env(APP_ROOT)',
        'public' => '@paths.root/public',
        'storage' => '@paths.root/storage',
        'cache' => '@paths.storage/cache',
        'logs' => '@paths.storage/logs',
        'uploads' => '@paths.public/uploads',
        'temp' => '@env(TEMP_DIR)'
    ]
]);

$config->getContext()->withEnv(getenv());
$config->getParser()->parse($config->dataReference());

// All paths are now resolved
$cachePath = $config->get('paths.cache');
// e.g., "/var/www/app/storage/cache"
```

### Service Configuration with Context

```php
$config = new Config([
    'services' => [
        'payment' => [
            'provider' => '@env(PAYMENT_PROVIDER)',
            'api_key' => '@env(PAYMENT_API_KEY)',
            'webhook_secret' => '@env(PAYMENT_WEBHOOK_SECRET)',
            'mode' => '${app.mode}'
        ],
        'email' => [
            'driver' => '@env(MAIL_DRIVER)',
            'host' => '@env(MAIL_HOST)',
            'from' => [
                'address' => '@env(MAIL_FROM_ADDRESS)',
                'name' => '${app.name}'
            ]
        ],
        'storage' => [
            'default' => '${storage.default_disk}',
            'disks' => [
                's3' => [
                    'key' => '@env(AWS_ACCESS_KEY_ID)',
                    'secret' => '@env(AWS_SECRET_ACCESS_KEY)',
                    'region' => '${app.region}',
                    'bucket' => '${storage.s3_bucket}'
                ]
            ]
        ]
    ]
]);

$config->withContext([
    'ENV' => getenv(),
    'app' => [
        'name' => 'MyApp',
        'mode' => 'live',
        'region' => 'us-east-1'
    ],
    'storage' => [
        'default_disk' => 's3',
        's3_bucket' => 'myapp-uploads'
    ]
]);

$config->getParser()->parse($config->dataReference());
```

## Advanced Patterns

### Computed Context Values

```php
class ComputedContext
{
    public static function create(): array
    {
        return [
            'timestamp' => time(),
            'date' => date('Y-m-d'),
            'datetime' => date('Y-m-d H:i:s'),
            'hostname' => gethostname(),
            'pid' => getmypid(),
            'user' => get_current_user(),
            'timezone' => date_default_timezone_get(),
            'php_version' => PHP_VERSION,
        ];
    }
}

$config->withContext([
    'runtime' => ComputedContext::create()
]);
```

### Conditional Context

```php
$isDevelopment = getenv('APP_ENV') === 'development';

$config->withContext([
    'app' => [
        'debug' => $isDevelopment,
        'log_level' => $isDevelopment ? 'debug' : 'error',
        'cache_enabled' => !$isDevelopment
    ]
]);
```

### Nested Context Resolution

```php
$config = new Config([
    'database' => [
        'dsn' => 'mysql:host=@env(DB_HOST);port=@env(DB_PORT);dbname=@env(DB_NAME)',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    ],
    'url' => 'https://@env(APP_DOMAIN)/${app.path}'
]);

$config->withContext([
    'ENV' => getenv(),
    'app' => [
        'path' => 'api/v1'
    ]
]);

$config->getParser()->parse($config->dataReference());
```

### Context Inheritance

```php
class BaseConfig
{
    protected Config $config;
    
    protected function getBaseContext(): array
    {
        return [
            'app' => [
                'name' => 'MyApp',
                'version' => '1.0.0',
            ],
            'ENV' => getenv()
        ];
    }
}

class ApiConfig extends BaseConfig
{
    public function __construct()
    {
        $this->config = new Config([
            'api' => [
                'name' => '${app.name} API',
                'version' => '${app.version}',
                'base_url' => '@env(API_BASE_URL)'
            ]
        ]);
        
        $context = array_merge(
            $this->getBaseContext(),
            [
                'api' => [
                    'timeout' => 30,
                    'retries' => 3
                ]
            ]
        );
        
        $this->config->withContext($context);
        $this->config->getParser()->parse($this->config->dataReference());
    }
}
```

## Context Best Practices

### 1. Namespace Your Context

```php
// Good: Organized in sections
$context->withSection('app', $appData);
$context->withSection('user', $userData);
$context->withSection('request', $requestData);

// Avoid: Flat structure
$context = new Context([
    'app_name' => '...',
    'app_version' => '...',
    'user_id' => '...',
    'user_role' => '...'
]);
```

### 2. Validate Context Values

```php
class ValidatedContext
{
    public static function create(array $data): array
    {
        if (!isset($data['environment'])) {
            throw new \InvalidArgumentException('environment is required');
        }
        
        if (!in_array($data['environment'], ['dev', 'staging', 'production'])) {
            throw new \InvalidArgumentException('Invalid environment');
        }
        
        return $data;
    }
}

$config->withContext(ValidatedContext::create($userInput));
```

### 3. Use Type-Safe Context Access

```php
class TypedContext
{
    public function __construct(private Config $config) {}
    
    public function getEnvironment(): string
    {
        return $this->config->getContext()->get('app.environment') ?? 'production';
    }
    
    public function getRegion(): string
    {
        return $this->config->getContext()->get('app.region') ?? 'us-east-1';
    }
    
    public function isDebugEnabled(): bool
    {
        return (bool) $this->config->getContext()->get('app.debug', false);
    }
}
```

### 4. Document Context Requirements

```php
/**
 * Configuration class
 * 
 * Required context:
 * - ENV.DB_HOST: Database hostname
 * - ENV.DB_PORT: Database port
 * - ENV.DB_NAME: Database name
 * - app.environment: Application environment (dev, staging, production)
 * - app.region: AWS region
 * 
 * Optional context:
 * - app.debug: Enable debug mode (default: false)
 * - app.log_level: Logging level (default: 'error')
 */
class AppConfig
{
    // ...
}
```

### 5. Provide Defaults

```php
$config = new Config([
    'cache' => [
        'driver' => '@env(CACHE_DRIVER)',
        'ttl' => '${cache.ttl}',
        'prefix' => '${cache.prefix}'
    ]
]);

$config->withContext([
    'ENV' => getenv(),
    'cache' => [
        'ttl' => 3600,      // Default 1 hour
        'prefix' => 'app_'  // Default prefix
    ]
]);
```

### 6. Immutable Context for Safety

```php
// Create context once
$context = new Context([
    'app' => ['env' => 'production']
]);

// Pass to config
$config->withContext($context->toArray());

// Context in config is now independent
// Changes to $context won't affect config
```

## Debugging Context

### Print Context

```php
$context = $config->getContext();
print_r($context->toArray());
```

### Debug Plugin

```php
class ContextDebugPlugin extends AbstractPlugin
{
    public function __invoke($value, $path, &$data, $next): mixed
    {
        if (is_string($value) && str_contains($value, '@')) {
            error_log("Resolving at $path: $value");
            error_log("Available context: " . json_encode($this->getConfig()->getContext()->toArray()));
        }
        return $next($value, $path, $data);
    }
}

$config->getParser()->registerPlugin(ContextDebugPlugin::class, priority: 10000);
```

### Context Validation

```php
class ContextValidator
{
    public static function validate(ContextInterface $context, array $required): void
    {
        $missing = [];
        
        foreach ($required as $key) {
            if (!$context->has($key)) {
                $missing[] = $key;
            }
        }
        
        if (!empty($missing)) {
            throw new \RuntimeException(
                "Missing required context: " . implode(', ', $missing)
            );
        }
    }
}

// Usage
ContextValidator::validate($config->getContext(), [
    'ENV.DB_HOST',
    'ENV.DB_PORT',
    'app.environment',
    'app.region'
]);
```
