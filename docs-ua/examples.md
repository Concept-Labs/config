# Приклади

Реальні приклади використання Concept\Config.

## Зміст

1. [Базова конфігурація додатка](#базова-конфігурація-додатка)
2. [Використання Facade](#використання-facade)
3. [Налаштування для кількох середовищ](#налаштування-для-кількох-середовищ)
4. [Конфігурація бази даних](#конфігурація-бази-даних)
5. [Інтеграція з сервісним контейнером](#інтеграція-з-сервісним-контейнером)
6. [Багатоорендний додаток](#багатоорендний-додаток)
7. [Конфігурація мікросервісів](#конфігурація-мікросервісів)
8. [Прапорці функцій](#прапорці-функцій)
9. [Компіляція конфігурації](#компіляція-конфігурації)
10. [Тестування з конфігурацією](#тестування-з-конфігурацією)

## Базова конфігурація додатка

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
    "name": "@env(DB_NAME)"
  }
}
```

### app.php

```php
use Concept\Config\StaticFactory;

$config = StaticFactory::fromFile('config/app.json', parse: true);
$config->getContext()->withEnv(getenv());

echo $config->get('app.name');
```

## Використання Facade

Facade забезпечує найпростіший спосіб створення готових до продакшену конфігурацій.

### config/app.json

```json
{
  "app": {
    "name": "@env(APP_NAME)",
    "env": "@env(APP_ENV)",
    "debug": "@env(APP_DEBUG)"
  }
}
```

### app.php

```php
use Concept\Config\Facade\Config;

$config = Config::config('config/app.json', context: [
    'ENV' => getenv()
]);

echo $config->get('app.name');
```

## Налаштування для кількох середовищ

### config/app.json (база)

```json
{
  "app": {
    "name": "MyApp",
    "timezone": "UTC"
  },
  "cache": {
    "driver": "file"
  }
}
```

### config/env/development.json

```json
{
  "app": {
    "debug": true
  },
  "cache": {
    "driver": "array"
  }
}
```

### config/env/production.json

```json
{
  "app": {
    "debug": false
  },
  "cache": {
    "driver": "redis",
    "host": "@env(REDIS_HOST)"
  }
}
```

### Використання

```php
$env = getenv('APP_ENV') ?: 'production';

$config = StaticFactory::fromFiles([
    'config/app.json',
    "config/env/$env.json"
], parse: true);
```

## Конфігурація бази даних

### config/database.php

```php
return [
    'default' => '@env(DB_CONNECTION)',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => '@env(DB_HOST)',
            'port' => '@env(DB_PORT)',
            'database' => '@env(DB_DATABASE)',
            'username' => '@env(DB_USERNAME)',
            'password' => '@env(DB_PASSWORD)'
        ],
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => '@env(PG_HOST)',
            'port' => '@env(PG_PORT)',
            'database' => '@env(PG_DATABASE)'
        ]
    ]
];
```

### Database.php

```php
class Database
{
    private Config $config;
    
    public function __construct()
    {
        $this->config = StaticFactory::fromFile(
            'config/database.php',
            parse: true
        );
        $this->config->getContext()->withEnv(getenv());
    }
    
    public function connection(?string $name = null): array
    {
        $name = $name ?? $this->config->get('default');
        return $this->config->get("connections.$name");
    }
}
```

## Інтеграція з сервісним контейнером

```php
use Psr\Container\ContainerInterface;

class ServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->set('config', function() {
            return StaticFactory::fromGlob('config/*.json', parse: true);
        });
        
        $container->set('database', function($c) {
            $dbConfig = $c->get('config')->node('database');
            return new DatabaseConnection($dbConfig);
        });
    }
}
```

## Багатоорендний додаток

### config/tenants.json

```json
{
  "tenants": {
    "acme-corp": {
      "name": "Acme Corporation",
      "database": "acme_db",
      "theme": "blue"
    },
    "beta-inc": {
      "name": "Beta Inc",
      "database": "beta_db",
      "theme": "green"
    }
  }
}
```

### TenantConfig.php

```php
class TenantConfig
{
    private Config $config;
    
    public function __construct(string $tenantId)
    {
        $this->config = new Config([
            'app' => [
                'name' => '${tenant.name}',
                'database' => '${tenant.database}',
                'theme' => '${tenant.theme}'
            ]
        ]);
        
        $tenants = StaticFactory::fromFile('config/tenants.json');
        $tenantData = $tenants->get("tenants.$tenantId");
        
        $this->config->getContext()->withSection('tenant', $tenantData);
        $this->config->getParser()->parse($this->config->dataReference());
    }
    
    public function get(string $key): mixed
    {
        return $this->config->get($key);
    }
}

// Використання
$tenant = new TenantConfig('acme-corp');
echo $tenant->get('app.name'); // "Acme Corporation"
```

## Конфігурація мікросервісів

### config/services.json

```json
{
  "services": {
    "auth": {
      "url": "@env(AUTH_SERVICE_URL)",
      "timeout": 5
    },
    "payment": {
      "url": "@env(PAYMENT_SERVICE_URL)",
      "api_key": "@env(PAYMENT_API_KEY)",
      "timeout": 10
    },
    "notification": {
      "url": "@env(NOTIFICATION_SERVICE_URL)",
      "async": true
    }
  }
}
```

## Прапорці функцій

```php
$config = new Config([
    'features' => [
        'new_ui' => '@env(FEATURE_NEW_UI)',
        'beta_api' => '@env(FEATURE_BETA_API)',
        'analytics' => true
    ]
]);

$config->getContext()->withEnv(getenv());
$config->getParser()->parse($config->dataReference());

if ($config->get('features.new_ui')) {
    // Увімкнути новий UI
}
```

## Компіляція конфігурації

### Для продакшену

```php
use Concept\Config\StaticFactory;

// Компіляція
StaticFactory::compile(
    sources: 'config/*.json',
    context: ['env' => 'production', 'ENV' => getenv()],
    target: 'compiled/config.json'
);

// Використання скомпільованої конфігурації
$config = StaticFactory::fromFile('compiled/config.json');
```

## Тестування з конфігурацією

```php
class ConfigTest extends TestCase
{
    public function testDatabaseConfig()
    {
        $config = StaticFactory::fromFile('config/database.json');
        
        $this->assertTrue($config->has('connections.mysql'));
        $this->assertEquals('mysql', $config->get('connections.mysql.driver'));
    }
    
    public function testEnvironmentVariables()
    {
        $config = new Config([
            'db_host' => '@env(DB_HOST)'
        ]);
        
        $config->getContext()->withEnv(['DB_HOST' => 'localhost']);
        $config->getParser()->parse($config->dataReference());
        
        $this->assertEquals('localhost', $config->get('db_host'));
    }
}
```

Дивіться [повну документацію](../docs/examples.md) для більше прикладів.
