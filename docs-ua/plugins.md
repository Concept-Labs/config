# Система плагінів

Система плагінів надає розширюваний спосіб обробки та трансформації даних конфігурації під час парсингу.

## Огляд

Плагіни обробляють дані конфігурації за допомогою middleware-архітектури. Вони виконуються в порядку пріоритету, що дозволяє точно контролювати обробку.

## Вбудовані плагіни

### EnvPlugin (Пріоритет: 999)

Вирішує змінні оточення.

**Синтаксис**: `@env(VARIABLE_NAME)`

```php
$config = new Config([
    'database' => [
        'host' => '@env(DB_HOST)',
        'port' => '@env(DB_PORT)'
    ]
]);
```

### ReferencePlugin (Пріоритет: 995)

Вирішує посилання на інші значення конфігурації.

**Синтаксис**: `@path.to.value` або `#{path.to.value}`

```php
$config = new Config([
    'api' => [
        'base_url' => 'https://api.example.com',
        'endpoints' => [
            'users' => '@api.base_url/users',
            'posts' => '@api.base_url/posts'
        ]
    ]
]);
```

### ImportPlugin (Пріоритет: 996)

Імпортує та об'єднує зовнішні файли конфігурації.

**Синтаксис**: `{"@import": "file.json"}` або `{"@import": ["file1.json", "file2.json"]}`

```json
{
  "@import": [
    "config/database.json",
    "config/cache.json"
  ],
  "app": {
    "name": "MyApp"
  }
}
```

### ExtendsPlugin (Пріоритет: 997)

Розширює конфігурацію з базового вузла.

**Синтаксис**: `{"@extends": "path.to.base"}`

```php
$config = new Config([
    'base' => [
        'debug' => false,
        'timezone' => 'UTC'
    ],
    'development' => [
        '@extends' => 'base',
        'debug' => true
    ]
]);
```

### IncludePlugin (Пріоритет: 997)

Включає вміст файлу в конфігурацію.

**Синтаксис**: `"@include(file.json)"`

```php
$config = new Config([
    'secrets' => '@include(secrets.json)',
    'config' => '@include(config/app.json)'
]);
```

## Створення користувацьких плагінів

### Реалізація PluginInterface

```php
use Concept\Config\Parser\Plugin\PluginInterface;

class MyCustomPlugin implements PluginInterface
{
    public function __invoke(
        mixed $value,
        string $path,
        array &$subjectData,
        callable $next
    ): mixed {
        // Ваша логіка обробки
        if (is_string($value) && str_starts_with($value, '@custom(')) {
            $value = $this->processCustomDirective($value);
        }
        
        // Передати наступному плагіну в ланцюзі
        return $next($value, $path, $subjectData);
    }
    
    private function processCustomDirective(string $value): mixed
    {
        // Обробити користувацьку директиву
        return $processedValue;
    }
}
```

### Реєстрація плагіна

```php
$config = new Config([...]);
$parser = $config->getParser();

// Реєстрація з пріоритетом
$parser->registerPlugin(MyCustomPlugin::class, priority: 100);

// Або з екземпляром
$parser->registerPlugin(new MyCustomPlugin(), priority: 100);
```

## Пріоритети плагінів

Плагіни виконуються від вищого до нижчого пріоритету:

| Плагін | Пріоритет | Призначення |
|--------|----------|-------------|
| EnvPlugin | 999 | Змінні оточення |
| ContextPlugin | 998 | Контекстні змінні |
| ExtendsPlugin | 997 | Розширення конфігурацій |
| IncludePlugin | 997 | Включення файлів |
| ImportPlugin | 996 | Імпорт файлів |
| ReferencePlugin | 995 | Посилання |
| ConfigValuePlugin | 994 | Значення конфігурації |

## Найкращі практики

### 1. Зберігайте плагіни сфокусованими

```php
// Добре: Один плагін = одна відповідальність
class EnvPlugin // Тільки обробка змінних оточення
class ReferencePlugin // Тільки обробка посилань
```

### 2. Використовуйте відповідні пріоритети

```php
// Вищий пріоритет для базових операцій
$parser->registerPlugin(EnvPlugin::class, 999);

// Нижчий пріоритет для трансформацій
$parser->registerPlugin(CustomTransformPlugin::class, 500);
```

### 3. Завжди викликайте next()

```php
public function __invoke($value, $path, &$data, callable $next): mixed
{
    // Ваша обробка
    $processed = $this->process($value);
    
    // ОБОВ'ЯЗКОВО викликати next()
    return $next($processed, $path, $data);
}
```

### 4. Обробляйте крайні випадки

```php
public function __invoke($value, $path, &$data, callable $next): mixed
{
    // Перевіряти тип
    if (!is_string($value)) {
        return $next($value, $path, $data);
    }
    
    // Перевіряти формат
    if (!$this->matches($value)) {
        return $next($value, $path, $data);
    }
    
    // Обробляти
    $processed = $this->process($value);
    return $next($processed, $path, $data);
}
```

### 5. Документуйте ваші плагіни

```php
/**
 * CustomPlugin - Обробляє директиви @custom(...)
 * 
 * Синтаксис: @custom(key)
 * Приклад: @custom(api_key)
 * 
 * Вирішує значення з користувацького джерела.
 */
class CustomPlugin implements PluginInterface
{
    // ...
}
```

## Приклади плагінів

### Плагін перетворення

```php
class UpperCasePlugin implements PluginInterface
{
    public function __invoke($value, $path, &$data, callable $next): mixed
    {
        if (is_string($value) && str_starts_with($value, '@upper(')) {
            preg_match('/@upper\((.*)\)/', $value, $matches);
            $value = strtoupper($matches[1]);
        }
        
        return $next($value, $path, $data);
    }
}
```

### Плагін валідації

```php
class ValidatePlugin implements PluginInterface
{
    public function __invoke($value, $path, &$data, callable $next): mixed
    {
        // Валідувати перед обробкою
        if ($path === 'database.port' && !is_int($value)) {
            throw new \InvalidArgumentException('Port must be integer');
        }
        
        return $next($value, $path, $data);
    }
}
```

Дивіться [повну документацію](../docs/plugins.md) для детальніших прикладів.
