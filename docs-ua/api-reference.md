# Довідник API

Повна документація API для Concept\Config.

## ConfigInterface

Головний інтерфейс конфігурації.

**Простір імен**: `Concept\Config`

### Методи

#### `get(string $path, mixed $default = null): mixed`

Отримати значення за шляхом.

```php
$value = $config->get('database.host');
$value = $config->get('missing.key', 'default');
```

#### `set(string $path, mixed $value): static`

Встановити значення за шляхом.

```php
$config->set('database.host', 'localhost');
$config->set('app.debug', true);
```

#### `has(string $path): bool`

Перевірити існування ключа.

```php
if ($config->has('database.host')) {
    // Ключ існує
}
```

#### `load(string|array|ConfigInterface $source, bool $parse = false): static`

Завантажити конфігурацію з джерела.

```php
$config->load('config.json');
$config->load('config.json', parse: true);
$config->load(['key' => 'value']);
```

#### `import(string|array|ConfigInterface $source, bool $parse = false): static`

Імпортувати та об'єднати конфігурацію.

```php
$config->import('additional.json');
$config->import('additional.json', parse: true);
```

#### `export(string $target): static`

Експортувати конфігурацію у файл.

```php
$config->export('output.json');
$config->export('output.php');
```

#### `node(string $path, bool $copy = true): static`

Отримати підконфігурацію як новий екземпляр.

```php
$dbConfig = $config->node('database');
$dbRef = $config->node('database', copy: false);
```

#### `toArray(): array`

Конвертувати в масив.

```php
$array = $config->toArray();
```

#### `getContext(): ContextInterface`

Отримати контекст.

```php
$context = $config->getContext();
```

#### `withContext(ContextInterface|array $context): static`

Встановити новий контекст.

```php
$config->withContext(['env' => 'production']);
```

#### `getParser(): ParserInterface`

Отримати парсер.

```php
$parser = $config->getParser();
```

#### `getResource(): ResourceInterface`

Отримати ресурс.

```php
$resource = $config->getResource();
```

## StaticFactory

Статичні методи фабрики для створення конфігурацій.

**Простір імен**: `Concept\Config`

### Методи

#### `create(array $data = [], array $context = []): ConfigInterface`

Створити з масиву.

```php
$config = StaticFactory::create(['key' => 'value']);
```

#### `fromFile(string $file, bool $parse = false): ConfigInterface`

Створити з файлу.

```php
$config = StaticFactory::fromFile('config.json');
$config = StaticFactory::fromFile('config.json', parse: true);
```

#### `fromFiles(array $files, bool $parse = false): ConfigInterface`

Створити з кількох файлів.

```php
$config = StaticFactory::fromFiles([
    'config/app.json',
    'config/database.json'
]);
```

#### `fromGlob(string $pattern, bool $parse = false): ConfigInterface`

Створити з glob-шаблону.

```php
$config = StaticFactory::fromGlob('config/*.json');
```

#### `compile(string|array $sources, array $context, string $target): void`

Скомпілювати конфігурацію.

```php
StaticFactory::compile(
    'config/*.json',
    ['env' => 'production'],
    'compiled/config.json'
);
```

## Facade\Config

Спрощений інтерфейс з попередньо налаштованими плагінами.

**Простір імен**: `Concept\Config\Facade`

### Методи

#### `config(string $source, array $context = [], array $overrides = []): ConfigInterface`

Створити повнофункціональну конфігурацію.

```php
use Concept\Config\Facade\Config;

$config = Config::config(
    source: 'config/*.json',
    context: ['ENV' => getenv()],
    overrides: ['debug' => false]
);
```

## Factory

Фабрика-будівельник для конфігурацій.

**Простір імен**: `Concept\Config`

### Методи

#### `reset(): static`

Очистити фабрику.

#### `create(): ConfigInterface`

Створити конфігурацію.

#### `withFile(string $file): static`

Додати файл.

```php
$config = (new Factory())
    ->withFile('config/app.json')
    ->create();
```

#### `withGlob(string $pattern): static`

Додати glob-шаблон.

```php
$config = (new Factory())
    ->withGlob('config/*.json')
    ->create();
```

#### `withArray(array $data): static`

Додати масив даних.

```php
$config = (new Factory())
    ->withArray(['key' => 'value'])
    ->create();
```

#### `withContext(ContextInterface|array $context): static`

Встановити контекст.

```php
$config = (new Factory())
    ->withContext(['env' => 'production'])
    ->create();
```

#### `withPlugin(PluginInterface|callable $plugin, int $priority = 0): static`

Додати плагін.

```php
$config = (new Factory())
    ->withPlugin(MyPlugin::class, 100)
    ->create();
```

#### `withParsing(bool $parse): static`

Увімкнути/вимкнути парсинг.

```php
$config = (new Factory())
    ->withParsing(true)
    ->create();
```

## ParserInterface

Інтерфейс парсера.

**Простір імен**: `Concept\Config\Parser`

### Методи

#### `parse(array &$data): void`

Парсити дані.

```php
$parser->parse($dataReference);
```

#### `registerPlugin(PluginInterface|callable|string $plugin, int $priority = 0): static`

Зареєструвати плагін.

```php
$parser->registerPlugin(MyPlugin::class, 100);
```

## PluginInterface

Інтерфейс плагіна.

**Простір імен**: `Concept\Config\Parser\Plugin`

### Методи

#### `__invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed`

Обробити значення.

```php
public function __invoke($value, $path, &$data, callable $next): mixed
{
    // Ваша обробка
    return $next($value, $path, $data);
}
```

## ContextInterface

Інтерфейс контексту.

**Простір імен**: `Concept\Config\Context`

### Методи

#### `withEnv(array $env): static`

Додати змінні оточення.

```php
$context->withEnv(getenv());
```

#### `withSection(string $section, array $data): static`

Додати секцію контексту.

```php
$context->withSection('app', ['name' => 'MyApp']);
```

## AdapterInterface

Інтерфейс адаптера.

**Простір імен**: `Concept\Config\Resource`

### Методи

#### `supports(string $uri): bool`

Перевірити підтримку URI.

```php
if (JsonAdapter::supports('config.json')) {
    // Підтримується
}
```

#### `read(string $uri): array`

Прочитати з URI.

```php
$data = $adapter->read('config.json');
```

#### `write(string $target, array $data): static`

Записати в файл.

```php
$adapter->write('output.json', $data);
```

## Класи винятків

### ConfigException

Базовий виняток конфігурації.

```php
throw new ConfigException('Configuration error');
```

### InvalidArgumentException

Неправильний аргумент.

```php
throw new InvalidArgumentException('Invalid path');
```

### RuntimeException

Помилка під час виконання.

```php
throw new RuntimeException('Failed to load config');
```

Дивіться повну документацію для детальнішої інформації.
