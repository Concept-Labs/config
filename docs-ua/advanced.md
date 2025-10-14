# Розширені теми

Розширені техніки та шаблони для Concept\Config.

## Зміст

1. [Користувацькі фабрики](#користувацькі-фабрики)
2. [Розширена розробка плагінів](#розширена-розробка-плагінів)
3. [Користувацькі адаптери](#користувацькі-адаптери)
4. [Оптимізація продуктивності](#оптимізація-продуктивності)
5. [Міркування безпеки](#міркування-безпеки)
6. [Розширення ядра](#розширення-ядра)
7. [Шаблони інтеграції](#шаблони-інтеграції)
8. [Обробка помилок](#обробка-помилок)

## Користувацькі фабрики

### Спеціалізована фабрика

```php
class CustomConfigFactory
{
    public static function forEnvironment(string $env): ConfigInterface
    {
        return (new Factory())
            ->withFile('config/base.json')
            ->withFile("config/env/$env.json")
            ->withContext(['env' => $env, 'ENV' => getenv()])
            ->withParsing(true)
            ->create();
    }
    
    public static function forTenant(string $tenantId): ConfigInterface
    {
        $config = new Config([
            'tenant' => [
                'id' => $tenantId,
                'config' => "@include(tenants/$tenantId.json)"
            ]
        ]);
        
        $config->getParser()->parse($config->dataReference());
        return $config;
    }
}

// Використання
$config = CustomConfigFactory::forEnvironment('production');
$tenantConfig = CustomConfigFactory::forTenant('acme-corp');
```

### Фабрика з валідацією

```php
class ValidatedConfigFactory
{
    private array $schema;
    
    public function __construct(array $schema)
    {
        $this->schema = $schema;
    }
    
    public function create(array $data): ConfigInterface
    {
        $this->validate($data);
        
        $config = new Config($data);
        $config->getParser()->registerPlugin(
            new ValidationPlugin($this->schema),
            priority: 1000
        );
        
        return $config;
    }
    
    private function validate(array $data): void
    {
        // Валідація відповідно до схеми
        foreach ($this->schema as $key => $rules) {
            if (!isset($data[$key]) && $rules['required']) {
                throw new \RuntimeException("Missing required key: $key");
            }
        }
    }
}
```

## Розширена розробка плагінів

### Плагін з станом

```php
class StatefulPlugin implements PluginInterface
{
    private array $processed = [];
    
    public function __invoke($value, $path, &$data, callable $next): mixed
    {
        if (in_array($path, $this->processed)) {
            return $next($value, $path, $data);
        }
        
        $this->processed[] = $path;
        $value = $this->process($value);
        
        return $next($value, $path, $data);
    }
}
```

### Асинхронний плагін

```php
class AsyncPlugin implements PluginInterface
{
    private array $promises = [];
    
    public function __invoke($value, $path, &$data, callable $next): mixed
    {
        if ($this->shouldDefer($value)) {
            $this->promises[$path] = function() use ($value) {
                return $this->resolveAsync($value);
            };
            
            return $next($value, $path, $data);
        }
        
        return $next($value, $path, $data);
    }
    
    public function resolveAll(): void
    {
        foreach ($this->promises as $path => $promise) {
            $this->promises[$path] = $promise();
        }
    }
}
```

### Плагін з кешуванням

```php
class CachedPlugin implements PluginInterface
{
    private array $cache = [];
    
    public function __invoke($value, $path, &$data, callable $next): mixed
    {
        $cacheKey = md5(serialize($value));
        
        if (isset($this->cache[$cacheKey])) {
            return $next($this->cache[$cacheKey], $path, $data);
        }
        
        $processed = $this->process($value);
        $this->cache[$cacheKey] = $processed;
        
        return $next($processed, $path, $data);
    }
}
```

## Користувацькі адаптери

### HTTP Adapter

```php
class HttpAdapter implements AdapterInterface
{
    public static function supports(string $uri): bool
    {
        return str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://');
    }
    
    public function read(string $uri): array
    {
        $response = file_get_contents($uri);
        return json_decode($response, true);
    }
    
    public function write(string $target, array $data): static
    {
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data)
            ]
        ];
        
        $context = stream_context_create($options);
        file_get_contents($target, false, $context);
        
        return $this;
    }
}
```

### Database Adapter

```php
class DatabaseAdapter implements AdapterInterface
{
    private \PDO $pdo;
    
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    public static function supports(string $uri): bool
    {
        return str_starts_with($uri, 'db://');
    }
    
    public function read(string $uri): array
    {
        $table = parse_url($uri, PHP_URL_HOST);
        $stmt = $this->pdo->prepare("SELECT config FROM $table WHERE id = 1");
        $stmt->execute();
        
        $json = $stmt->fetchColumn();
        return json_decode($json, true);
    }
}
```

## Оптимізація продуктивності

### Кешування конфігурації

```php
class CachedConfig implements ConfigInterface
{
    private ConfigInterface $config;
    private CacheInterface $cache;
    private string $cacheKey;
    
    public function get(string $path, mixed $default = null): mixed
    {
        $key = $this->cacheKey . ':' . $path;
        
        return $this->cache->remember($key, function() use ($path, $default) {
            return $this->config->get($path, $default);
        });
    }
}
```

### Ліниве завантаження

```php
class LazyConfig implements ConfigInterface
{
    private ?ConfigInterface $config = null;
    private string $configFile;
    
    private function ensureLoaded(): void
    {
        if ($this->config === null) {
            $this->config = StaticFactory::fromFile($this->configFile);
        }
    }
    
    public function get(string $path, mixed $default = null): mixed
    {
        $this->ensureLoaded();
        return $this->config->get($path, $default);
    }
}
```

### Попередня компіляція

```php
// build.php - Скрипт для збірки
StaticFactory::compile(
    sources: [
        'config/app.json',
        'config/database.json',
        'config/services.json'
    ],
    context: [
        'env' => 'production',
        'ENV' => getenv()
    ],
    target: 'dist/config.json'
);

// app.php - Продакшен
$config = StaticFactory::fromFile('dist/config.json');
```

## Міркування безпеки

### Шифрування конфігурації

```php
class EncryptedConfig implements ConfigInterface
{
    private ConfigInterface $config;
    private string $encryptionKey;
    
    public function get(string $path, mixed $default = null): mixed
    {
        $value = $this->config->get($path, $default);
        
        if ($this->isEncrypted($value)) {
            return $this->decrypt($value);
        }
        
        return $value;
    }
    
    private function decrypt(string $encrypted): string
    {
        return openssl_decrypt(
            base64_decode($encrypted),
            'AES-256-CBC',
            $this->encryptionKey
        );
    }
}
```

### Валідація введення

```php
class SecureConfig implements ConfigInterface
{
    private ConfigInterface $config;
    
    public function set(string $path, mixed $value): static
    {
        $this->validatePath($path);
        $this->validateValue($value);
        
        $this->config->set($path, $value);
        return $this;
    }
    
    private function validatePath(string $path): void
    {
        if (preg_match('/[^a-zA-Z0-9._-]/', $path)) {
            throw new \InvalidArgumentException('Invalid path');
        }
    }
}
```

## Розширення ядра

### Користувацьке сховище

```php
class RedisStorage extends Storage
{
    private \Redis $redis;
    
    public function get(string $path, mixed $default = null): mixed
    {
        $value = $this->redis->get("config:$path");
        return $value !== false ? unserialize($value) : $default;
    }
    
    public function set(string $path, mixed $value): static
    {
        $this->redis->set("config:$path", serialize($value));
        return $this;
    }
}
```

### Користувацький парсер

```php
class CustomParser extends Parser
{
    protected function preProcess(array &$data): void
    {
        parent::preProcess($data);
        // Додаткова попередня обробка
    }
    
    protected function postProcess(array &$data): void
    {
        // Користувацька пост-обробка
        parent::postProcess($data);
    }
}
```

## Шаблони інтеграції

### Інтеграція з Laravel

```php
// config/app.php (Laravel)
return [
    'providers' => [
        ConceptConfigServiceProvider::class,
    ],
];

// ConceptConfigServiceProvider.php
class ConceptConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('concept.config', function() {
            return StaticFactory::fromGlob(
                config_path('concept/*.json'),
                parse: true
            );
        });
    }
}
```

### Інтеграція з Symfony

```php
// services.yaml
services:
    concept.config:
        class: Concept\Config\ConfigInterface
        factory: ['Concept\Config\StaticFactory', 'fromGlob']
        arguments:
            - '%kernel.project_dir%/config/concept/*.json'
            - true
```

## Обробка помилок

### Користувацькі винятки

```php
class ConfigNotFoundException extends ConfigException
{
    public static function forPath(string $path): self
    {
        return new self("Configuration not found: $path");
    }
}

class InvalidConfigException extends ConfigException
{
    public static function withErrors(array $errors): self
    {
        $message = "Invalid configuration:\n" . implode("\n", $errors);
        return new self($message);
    }
}
```

### Обробка помилок

```php
class ErrorHandlingConfig implements ConfigInterface
{
    private ConfigInterface $config;
    private LoggerInterface $logger;
    
    public function get(string $path, mixed $default = null): mixed
    {
        try {
            return $this->config->get($path, $default);
        } catch (\Exception $e) {
            $this->logger->error("Config error", [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            
            return $default;
        }
    }
}
```

Дивіться [повну документацію](../docs/advanced.md) для більше розширених тем.
