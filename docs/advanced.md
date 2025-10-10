# Advanced Topics

Advanced techniques and patterns for using Concept\Config.

## Table of Contents

1. [Custom Factories](#custom-factories)
2. [Advanced Plugin Development](#advanced-plugin-development)
3. [Custom Adapters](#custom-adapters)
4. [Performance Optimization](#performance-optimization)
5. [Security Considerations](#security-considerations)
6. [Extending the Core](#extending-the-core)
7. [Integration Patterns](#integration-patterns)
8. [Error Handling](#error-handling)

## Custom Factories

### Domain-Specific Factory

Create factories tailored to your application domain:

```php
<?php

class ApplicationConfigFactory extends Factory
{
    private string $environment;
    
    public function __construct(string $environment = 'production')
    {
        parent::__construct(Config::class);
        $this->environment = $environment;
    }
    
    public function create(): ConfigInterface
    {
        $this
            ->withFile('config/app.json')
            ->withFile("config/env/{$this->environment}.json")
            ->withContext($this->buildContext())
            ->withDefaultPlugins()
            ->withCustomPlugins();
        
        return parent::create();
    }
    
    private function buildContext(): array
    {
        return [
            'ENV' => getenv(),
            'app' => [
                'environment' => $this->environment,
                'hostname' => gethostname(),
                'root_path' => dirname(__DIR__),
            ],
            'runtime' => [
                'php_version' => PHP_VERSION,
                'timestamp' => time(),
            ]
        ];
    }
    
    private function withDefaultPlugins(): self
    {
        return $this
            ->withPlugin(EnvPlugin::class, 999)
            ->withPlugin(ReferencePlugin::class, 998)
            ->withPlugin(ImportPlugin::class, 997);
    }
    
    private function withCustomPlugins(): self
    {
        // Add custom application plugins
        return $this
            ->withPlugin(SecretsManagerPlugin::class, 1000)
            ->withPlugin(FeatureFlagPlugin::class, 500);
    }
}

// Usage
$factory = new ApplicationConfigFactory('staging');
$config = $factory->create();
```

### Cached Factory

Factory with built-in caching:

```php
<?php

class CachedConfigFactory
{
    private string $cacheDir;
    private int $ttl;
    
    public function __construct(string $cacheDir = 'cache', int $ttl = 3600)
    {
        $this->cacheDir = $cacheDir;
        $this->ttl = $ttl;
    }
    
    public function create(array $sources, array $context = []): ConfigInterface
    {
        $cacheKey = $this->getCacheKey($sources, $context);
        $cacheFile = "{$this->cacheDir}/{$cacheKey}.json";
        
        // Check cache
        if ($this->isCacheValid($cacheFile)) {
            return StaticFactory::fromFile($cacheFile);
        }
        
        // Build config
        $config = (new Factory())
            ->withFiles($sources)
            ->withContext($context)
            ->withParsing(true)
            ->create();
        
        // Cache it
        $this->cache($config, $cacheFile);
        
        return $config;
    }
    
    private function getCacheKey(array $sources, array $context): string
    {
        $data = [
            'sources' => $sources,
            'context' => $context,
            'filemtimes' => array_map('filemtime', $sources)
        ];
        
        return md5(json_encode($data));
    }
    
    private function isCacheValid(string $cacheFile): bool
    {
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        $age = time() - filemtime($cacheFile);
        return $age < $this->ttl;
    }
    
    private function cache(ConfigInterface $config, string $cacheFile): void
    {
        $config->export($cacheFile);
    }
}

// Usage
$factory = new CachedConfigFactory('storage/cache', 3600);
$config = $factory->create([
    'config/app.json',
    'config/database.json'
]);
```

## Advanced Plugin Development

### Stateful Plugin with Lifecycle

```php
<?php

class DatabaseConfigPlugin extends AbstractPlugin
{
    private PDO $pdo;
    private array $cache = [];
    
    public function __construct(ConfigInterface $config, PDO $pdo)
    {
        parent::__construct($config);
        $this->pdo = $pdo;
    }
    
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        if (is_string($value) && preg_match('/@db\((.*?)\)/', $value, $matches)) {
            $query = $matches[1];
            $value = $this->fetchFromDatabase($query);
        }
        
        return $next($value, $path, $subjectData);
    }
    
    private function fetchFromDatabase(string $query): mixed
    {
        // Check cache
        if (isset($this->cache[$query])) {
            return $this->cache[$query];
        }
        
        // Fetch from database
        $stmt = $this->pdo->prepare("SELECT value FROM config WHERE key = ?");
        $stmt->execute([$query]);
        $result = $stmt->fetchColumn();
        
        // Cache result
        $this->cache[$query] = $result;
        
        return $result;
    }
}
```

### Async Plugin

```php
<?php

class AsyncImportPlugin extends AbstractPlugin
{
    private array $promises = [];
    
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        if (is_string($value) && str_starts_with($value, '@async-import(')) {
            preg_match('/@async-import\((.*?)\)/', $value, $matches);
            $url = $matches[1];
            
            // Create async promise
            $this->promises[] = $this->fetchAsync($url)->then(
                function($data) use ($path, &$subjectData) {
                    RecursiveDotApi::set($subjectData, $path, $data);
                }
            );
            
            // Return placeholder
            return null;
        }
        
        return $next($value, $path, $subjectData);
    }
    
    private function fetchAsync(string $url): Promise
    {
        // Use async HTTP client
        return (new AsyncHttpClient())->get($url);
    }
    
    public function wait(): void
    {
        // Wait for all promises to resolve
        Promise::all($this->promises)->wait();
    }
}
```

### Plugin Chain Builder

```php
<?php

class PluginChainBuilder
{
    private array $plugins = [];
    
    public function add(PluginInterface|callable $plugin, int $priority = 0): self
    {
        $this->plugins[] = ['plugin' => $plugin, 'priority' => $priority];
        return $this;
    }
    
    public function addConditional(
        callable $condition,
        PluginInterface|callable $plugin,
        int $priority = 0
    ): self {
        if ($condition()) {
            $this->add($plugin, $priority);
        }
        return $this;
    }
    
    public function addSequence(array $plugins, int $basePriority = 0): self
    {
        foreach ($plugins as $i => $plugin) {
            $this->add($plugin, $basePriority - $i);
        }
        return $this;
    }
    
    public function build(ParserInterface $parser): void
    {
        foreach ($this->plugins as $item) {
            $parser->registerPlugin($item['plugin'], $item['priority']);
        }
    }
}

// Usage
$builder = new PluginChainBuilder();

$builder
    ->add(EnvPlugin::class, 999)
    ->add(ReferencePlugin::class, 998)
    ->addConditional(
        fn() => extension_loaded('yaml'),
        YamlImportPlugin::class,
        997
    )
    ->addSequence([
        ValidationPlugin::class,
        TransformationPlugin::class,
        CachePlugin::class,
    ], 500)
    ->build($config->getParser());
```

## Custom Adapters

### Database Adapter

```php
<?php

class DatabaseAdapter implements AdapterInterface
{
    private PDO $pdo;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    public static function supports(string $uri): bool
    {
        return str_starts_with($uri, 'db://');
    }
    
    public function read(string $uri): array
    {
        // Parse URI: db://table_name
        $table = substr($uri, 5);
        
        $stmt = $this->pdo->query(
            "SELECT config_key, config_value FROM {$table}"
        );
        
        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            RecursiveDotApi::set(
                $data,
                $row['config_key'],
                json_decode($row['config_value'], true)
            );
        }
        
        return $data;
    }
    
    public function write(string $target, array $data): static
    {
        $table = substr($target, 5);
        
        // Clear existing
        $this->pdo->exec("DELETE FROM {$table}");
        
        // Insert new
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$table} (config_key, config_value) VALUES (?, ?)"
        );
        
        RecursiveDotApi::each($data, function($value, $path) use ($stmt) {
            $stmt->execute([$path, json_encode($value)]);
        });
        
        return $this;
    }
    
    public function encode(array $data): string
    {
        return json_encode($data);
    }
    
    public function decode(string $data): array
    {
        return json_decode($data, true);
    }
}
```

### Redis Adapter

```php
<?php

class RedisAdapter implements AdapterInterface
{
    private Redis $redis;
    
    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }
    
    public static function supports(string $uri): bool
    {
        return str_starts_with($uri, 'redis://');
    }
    
    public function read(string $uri): array
    {
        // Parse URI: redis://key_prefix
        $prefix = substr($uri, 8);
        
        $keys = $this->redis->keys("{$prefix}*");
        $data = [];
        
        foreach ($keys as $key) {
            $path = str_replace($prefix, '', $key);
            $value = $this->redis->get($key);
            RecursiveDotApi::set($data, $path, json_decode($value, true));
        }
        
        return $data;
    }
    
    public function write(string $target, array $data): static
    {
        $prefix = substr($target, 8);
        
        RecursiveDotApi::each($data, function($value, $path) use ($prefix) {
            $key = $prefix . $path;
            $this->redis->set($key, json_encode($value));
        });
        
        return $this;
    }
    
    public function encode(array $data): string
    {
        return json_encode($data);
    }
    
    public function decode(string $data): array
    {
        return json_decode($data, true);
    }
}
```

### Versioned Adapter

```php
<?php

class VersionedJsonAdapter implements AdapterInterface
{
    private string $storageDir;
    
    public function __construct(string $storageDir)
    {
        $this->storageDir = $storageDir;
    }
    
    public static function supports(string $uri): bool
    {
        return pathinfo($uri, PATHINFO_EXTENSION) === 'verjson';
    }
    
    public function read(string $uri): array
    {
        $versions = $this->getVersions($uri);
        
        if (empty($versions)) {
            throw new \RuntimeException("No versions found for: $uri");
        }
        
        // Get latest version
        $latestVersion = max($versions);
        $versionFile = $this->getVersionFile($uri, $latestVersion);
        
        return json_decode(file_get_contents($versionFile), true);
    }
    
    public function write(string $target, array $data): static
    {
        $versions = $this->getVersions($target);
        $newVersion = empty($versions) ? 1 : max($versions) + 1;
        
        $versionFile = $this->getVersionFile($target, $newVersion);
        
        // Write new version
        file_put_contents($versionFile, json_encode($data, JSON_PRETTY_PRINT));
        
        // Keep only last 10 versions
        $this->pruneVersions($target, 10);
        
        return $this;
    }
    
    private function getVersions(string $uri): array
    {
        $pattern = $this->storageDir . '/' . basename($uri, '.verjson') . '.v*.json';
        $files = glob($pattern);
        
        return array_map(function($file) {
            preg_match('/\.v(\d+)\.json$/', $file, $matches);
            return (int)$matches[1];
        }, $files);
    }
    
    private function getVersionFile(string $uri, int $version): string
    {
        $basename = basename($uri, '.verjson');
        return "{$this->storageDir}/{$basename}.v{$version}.json";
    }
    
    private function pruneVersions(string $uri, int $keep): void
    {
        $versions = $this->getVersions($uri);
        rsort($versions);
        
        foreach (array_slice($versions, $keep) as $version) {
            unlink($this->getVersionFile($uri, $version));
        }
    }
    
    public function encode(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }
    
    public function decode(string $data): array
    {
        return json_decode($data, true);
    }
}
```

## Performance Optimization

### Lazy Loading Config

```php
<?php

class LazyConfig implements ConfigInterface
{
    private ?Config $config = null;
    private array $sources;
    private array $context;
    
    public function __construct(array $sources, array $context = [])
    {
        $this->sources = $sources;
        $this->context = $context;
    }
    
    private function getConfig(): Config
    {
        if ($this->config === null) {
            $this->config = StaticFactory::fromFiles(
                $this->sources,
                $this->context,
                parse: true
            );
        }
        
        return $this->config;
    }
    
    public function get(string $path, mixed $default = null): mixed
    {
        return $this->getConfig()->get($path, $default);
    }
    
    // Implement other methods...
}
```

### Config Cache Warmer

```php
<?php

class ConfigCacheWarmer
{
    public function warm(array $environments = ['production', 'staging']): void
    {
        foreach ($environments as $env) {
            $this->warmEnvironment($env);
        }
    }
    
    private function warmEnvironment(string $env): void
    {
        $config = (new Factory())
            ->withGlob('config/*.json')
            ->withFile("config/env/{$env}.json")
            ->withContext(['env' => $env])
            ->withParsing(true)
            ->create();
        
        $cacheFile = "cache/config.{$env}.json";
        $config->export($cacheFile);
        
        echo "Warmed cache for {$env}: {$cacheFile}\n";
    }
}

// Usage
$warmer = new ConfigCacheWarmer();
$warmer->warm(['production', 'staging', 'development']);
```

### Selective Parsing

```php
<?php

class SelectiveParser
{
    public static function parseOnly(Config $config, array $paths): void
    {
        $data = &$config->dataReference();
        
        foreach ($paths as $path) {
            $value = RecursiveDotApi::get($data, $path);
            
            if (is_string($value)) {
                $parsed = self::parseValue($value, $config);
                RecursiveDotApi::set($data, $path, $parsed);
            }
        }
    }
    
    private static function parseValue(string $value, Config $config): mixed
    {
        // Parse only environment variables
        if (preg_match('/@env\((.*?)\)/', $value, $matches)) {
            return getenv($matches[1]) ?: $value;
        }
        
        // Parse only references
        if (preg_match('/^@(.*)$/', $value, $matches)) {
            return $config->get($matches[1]) ?: $value;
        }
        
        return $value;
    }
}

// Usage - only parse specific paths
SelectiveParser::parseOnly($config, [
    'database.host',
    'database.port',
    'cache.driver'
]);
```

## Security Considerations

### Encrypted Configuration

```php
<?php

class SecureConfig
{
    private Config $config;
    private string $encryptionKey;
    
    public function __construct(string $encryptionKey)
    {
        $this->encryptionKey = $encryptionKey;
        $this->config = new Config();
    }
    
    public function loadEncrypted(string $file): self
    {
        $encrypted = file_get_contents($file);
        $decrypted = $this->decrypt($encrypted);
        $data = json_decode($decrypted, true);
        
        $this->config->hydrate($data);
        
        return $this;
    }
    
    public function saveEncrypted(string $file): self
    {
        $data = $this->config->toArray();
        $json = json_encode($data);
        $encrypted = $this->encrypt($json);
        
        file_put_contents($file, $encrypted);
        
        return $this;
    }
    
    private function encrypt(string $data): string
    {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt(
            $data,
            'aes-256-gcm',
            $this->encryptionKey,
            0,
            $iv,
            $tag
        );
        
        return base64_encode($iv . $tag . $encrypted);
    }
    
    private function decrypt(string $data): string
    {
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $tag = substr($data, 16, 16);
        $encrypted = substr($data, 32);
        
        return openssl_decrypt(
            $encrypted,
            'aes-256-gcm',
            $this->encryptionKey,
            0,
            $iv,
            $tag
        );
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config->get($key, $default);
    }
}
```

### Secret Masking

```php
<?php

class MaskedConfig
{
    private Config $config;
    private array $secretPaths = [];
    
    public function __construct(Config $config, array $secretPaths = [])
    {
        $this->config = $config;
        $this->secretPaths = $secretPaths;
    }
    
    public function toArray(bool $maskSecrets = true): array
    {
        $data = $this->config->toArray();
        
        if ($maskSecrets) {
            foreach ($this->secretPaths as $path) {
                if (RecursiveDotApi::has($data, $path)) {
                    RecursiveDotApi::set($data, $path, '***MASKED***');
                }
            }
        }
        
        return $data;
    }
    
    public function export(string $file, bool $maskSecrets = true): void
    {
        $data = $this->toArray($maskSecrets);
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }
}

// Usage
$maskedConfig = new MaskedConfig($config, [
    'database.password',
    'api.secret_key',
    'encryption.key'
]);

// Export without secrets
$maskedConfig->export('public-config.json', maskSecrets: true);
```

## Extending the Core

### Custom Config Class

```php
<?php

class ApplicationConfig extends Config
{
    private CacheInterface $cache;
    
    public function __construct(
        array $data = [],
        array $context = [],
        CacheInterface $cache = null
    ) {
        parent::__construct($data, $context);
        $this->cache = $cache ?? new ArrayCache();
    }
    
    public function get(string $path, mixed $default = null): mixed
    {
        $cacheKey = "config.{$path}";
        
        return $this->cache->remember($cacheKey, function() use ($path, $default) {
            return parent::get($path, $default);
        });
    }
    
    public function set(string $path, mixed $value): static
    {
        parent::set($path, $value);
        
        // Invalidate cache
        $this->cache->forget("config.{$path}");
        
        return $this;
    }
}
```

### Observable Config

```php
<?php

class ObservableConfig extends Config
{
    private array $listeners = [];
    
    public function set(string $path, mixed $value): static
    {
        $oldValue = $this->get($path);
        
        parent::set($path, $value);
        
        $this->notify($path, $oldValue, $value);
        
        return $this;
    }
    
    public function onChange(string $path, callable $listener): void
    {
        $this->listeners[$path][] = $listener;
    }
    
    private function notify(string $path, mixed $oldValue, mixed $newValue): void
    {
        if (isset($this->listeners[$path])) {
            foreach ($this->listeners[$path] as $listener) {
                $listener($path, $oldValue, $newValue);
            }
        }
        
        // Notify wildcard listeners
        if (isset($this->listeners['*'])) {
            foreach ($this->listeners['*'] as $listener) {
                $listener($path, $oldValue, $newValue);
            }
        }
    }
}

// Usage
$config = new ObservableConfig();

$config->onChange('database.host', function($path, $old, $new) {
    echo "Database host changed from {$old} to {$new}\n";
    // Reconnect to database
});

$config->set('database.host', 'newhost.com');
```

## Integration Patterns

### Dependency Injection

```php
<?php

// Service Provider
class ConfigServiceProvider
{
    public function register(Container $container): void
    {
        $container->singleton(ConfigInterface::class, function() {
            return StaticFactory::fromGlob('config/*.json', parse: true);
        });
    }
}

// Usage in services
class UserService
{
    public function __construct(
        private ConfigInterface $config,
        private PDO $database
    ) {}
    
    public function getMaxUsers(): int
    {
        return $this->config->get('users.max_count', 1000);
    }
}
```

### Event Sourcing

```php
<?php

class EventSourcedConfig
{
    private Config $config;
    private array $events = [];
    
    public function applyEvent(ConfigEvent $event): void
    {
        $this->events[] = $event;
        
        match($event->type) {
            'set' => $this->config->set($event->path, $event->value),
            'import' => $this->config->import($event->source),
            'reset' => $this->config->reset(),
        };
    }
    
    public function replay(array $events): void
    {
        foreach ($events as $event) {
            $this->applyEvent($event);
        }
    }
    
    public function getEvents(): array
    {
        return $this->events;
    }
}
```

## Error Handling

### Graceful Degradation

```php
<?php

class ResilientConfig
{
    private Config $config;
    private array $fallbacks = [];
    
    public function get(string $path, mixed $default = null): mixed
    {
        try {
            return $this->config->get($path, $default);
        } catch (\Throwable $e) {
            // Log error
            error_log("Config error for {$path}: " . $e->getMessage());
            
            // Try fallback
            if (isset($this->fallbacks[$path])) {
                return $this->fallbacks[$path];
            }
            
            return $default;
        }
    }
    
    public function setFallback(string $path, mixed $value): void
    {
        $this->fallbacks[$path] = $value;
    }
}
```
