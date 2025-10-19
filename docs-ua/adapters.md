# Адаптери

Адаптери обробляють читання та запис конфігураційних даних у різних форматах файлів.

## Огляд

Адаптери надають послідовний інтерфейс для роботи з різними типами файлів. Вони автоматично вибираються на основі розширення файлу.

## Інтерфейс Adapter

```php
interface AdapterInterface
{
    public static function supports(string $uri): bool;
    public function read(string $uri): array;
    public function write(string $target, array $data): static;
    public function encode(array $data): string;
    public function decode(string $data): array;
}
```

## Вбудовані адаптери

### JsonAdapter

**Підтримує**: Файли `.json`

**Можливості**:
- Підтримка glob-шаблонів
- Гарне форматування
- Валідація JSON

```php
$adapter = new JsonAdapter();
$data = $adapter->read('config.json');
$adapter->write('output.json', $data);
```

### PhpAdapter

**Підтримує**: Файли `.php`

**Можливості**:
- Повертає PHP масиви
- Використовує `var_export()` для запису
- Підтримка PHP-коду

```php
// config.php
return [
    'app' => ['name' => 'MyApp'],
    'debug' => true
];

// Використання
$adapter = new PhpAdapter();
$data = $adapter->read('config.php');
```

## Менеджер адаптерів

```php
class AdapterManager
{
    public function registerAdapter(string $adapterClass): void;
    public function getAdapter(string $uri): AdapterInterface;
}

// Використання
$manager = new AdapterManager();
$manager->registerAdapter(YamlAdapter::class);

$adapter = $manager->getAdapter('config.yaml');
```

## Створення користувацьких адаптерів

### Приклад: YAML Adapter

```php
use Concept\Config\Resource\AdapterInterface;

class YamlAdapter implements AdapterInterface
{
    public static function supports(string $uri): bool
    {
        return pathinfo($uri, PATHINFO_EXTENSION) === 'yaml';
    }
    
    public function read(string $uri): array
    {
        if (!file_exists($uri)) {
            throw new \RuntimeException("File not found: $uri");
        }
        
        $content = file_get_contents($uri);
        return yaml_parse($content);
    }
    
    public function write(string $target, array $data): static
    {
        $yaml = yaml_emit($data);
        file_put_contents($target, $yaml);
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

### Приклад: INI Adapter

```php
class IniAdapter implements AdapterInterface
{
    public static function supports(string $uri): bool
    {
        return pathinfo($uri, PATHINFO_EXTENSION) === 'ini';
    }
    
    public function read(string $uri): array
    {
        return parse_ini_file($uri, true);
    }
    
    public function write(string $target, array $data): static
    {
        $ini = $this->encode($data);
        file_put_contents($target, $ini);
        return $this;
    }
    
    public function encode(array $data): string
    {
        $ini = '';
        foreach ($data as $section => $values) {
            $ini .= "[$section]\n";
            foreach ($values as $key => $value) {
                $ini .= "$key = $value\n";
            }
        }
        return $ini;
    }
    
    public function decode(string $data): array
    {
        return parse_ini_string($data, true);
    }
}
```

## Реєстрація адаптерів

```php
$resource = $config->getResource();
$manager = $resource->getAdapterManager();

// Реєстрація користувацьких адаптерів
$manager->registerAdapter(YamlAdapter::class);
$manager->registerAdapter(IniAdapter::class);
$manager->registerAdapter(XmlAdapter::class);

// Тепер можна використовувати
$config->load('config.yaml');
$config->export('output.ini');
```

## Шаблони адаптерів

### Адаптер з кешуванням

```php
class CachedAdapter implements AdapterInterface
{
    private AdapterInterface $adapter;
    private array $cache = [];
    
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }
    
    public function read(string $uri): array
    {
        if (!isset($this->cache[$uri])) {
            $this->cache[$uri] = $this->adapter->read($uri);
        }
        return $this->cache[$uri];
    }
    
    // ... інші методи
}
```

### Адаптер з валідацією

```php
class ValidatingAdapter implements AdapterInterface
{
    private AdapterInterface $adapter;
    private array $schema;
    
    public function read(string $uri): array
    {
        $data = $this->adapter->read($uri);
        $this->validate($data);
        return $data;
    }
    
    private function validate(array $data): void
    {
        // Валідація даних відповідно до схеми
    }
}
```

## Найкращі практики

### 1. Валідація введення

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

### 2. Обробка помилок

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

### 3. Підтримка шаблонів URI

```php
public static function supports(string $uri): bool
{
    // Підтримка кількох розширень
    $extension = pathinfo($uri, PATHINFO_EXTENSION);
    return in_array($extension, ['yaml', 'yml']);
}
```

### 4. Адаптери без стану

```php
// Добре: Адаптер без стану
class JsonAdapter implements AdapterInterface
{
    public function read(string $uri): array
    {
        return json_decode(file_get_contents($uri), true);
    }
}

// Погано: Адаптер зі станом
class BadAdapter implements AdapterInterface
{
    private array $lastRead; // Уникайте збереження стану
}
```

### 5. Документуйте підтримувані можливості

```php
/**
 * YamlAdapter - Адаптер для YAML файлів
 * 
 * Підтримує:
 * - Файли .yaml та .yml
 * - Вкладені структури
 * - Масиви та об'єкти
 * 
 * Вимагає: ext-yaml
 */
class YamlAdapter implements AdapterInterface
{
    // ...
}
```

Дивіться повну документацію для додаткових прикладів та детальної інформації.
