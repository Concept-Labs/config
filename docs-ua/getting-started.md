# Початок роботи

Цей посібник допоможе вам розпочати роботу з Concept\Config, від встановлення до вашої першої конфігурації.

## Встановлення

### Вимоги

- PHP 8.2 або вище
- Composer

### Встановлення через Composer

```bash
composer require concept-labs/config
```

### Перевірка встановлення

Створіть простий тестовий файл для перевірки встановлення:

```php
<?php
require_once 'vendor/autoload.php';

use Concept\Config\Config;

$config = new Config(['test' => 'Привіт, Config!']);
echo $config->get('test'); // Вивід: Привіт, Config!
```

## Базові концепції

### Об'єкт конфігурації

Клас `Config` є основною точкою входу для роботи з конфігураціями. Він реалізує `ConfigInterface` і надає fluent API для управління даними конфігурації.

```php
use Concept\Config\Config;

$config = new Config([
    'app' => [
        'name' => 'MyApp',
        'version' => '1.0.0'
    ]
]);
```

### Точкова нотація

Доступ до вкладених значень за допомогою точкової нотації:

```php
// Отримання значень
$name = $config->get('app.name');     // "MyApp"
$version = $config->get('app.version'); // "1.0.0"

// Встановлення значень
$config->set('app.debug', true);
$config->set('database.host', 'localhost');

// Перевірка існування
if ($config->has('app.name')) {
    // Ключ існує
}
```

### Значення за замовчуванням

Надайте значення за замовчуванням при доступі до неіснуючих ключів:

```php
$timeout = $config->get('api.timeout', 30); // Повертає 30, якщо не встановлено
```

## Завантаження з файлів

### JSON файли

Створіть файл конфігурації `config/app.json`:

```json
{
  "app": {
    "name": "MyApplication",
    "version": "2.0.0",
    "debug": false
  },
  "database": {
    "host": "localhost",
    "port": 3306,
    "name": "mydb"
  }
}
```

Завантажте його у вашому коді:

```php
use Concept\Config\Config;

$config = new Config();
$config->load('config/app.json');

echo $config->get('app.name'); // "MyApplication"
```

### PHP файли

Створіть файл конфігурації `config/app.php`:

```php
<?php
return [
    'app' => [
        'name' => 'MyApplication',
        'version' => '2.0.0',
        'debug' => false
    ],
    'database' => [
        'host' => 'localhost',
        'port' => 3306
    ]
];
```

Завантажте його:

```php
$config = new Config();
$config->load('config/app.php');
```

## Використання Facade

Клас `Concept\Config\Facade\Config` є рекомендованим способом створення конфігурацій для більшості випадків використання. Він поставляється з усіма необхідними плагінами, попередньо налаштованими та готовими обробляти змінні оточення, посилання, імпорти тощо.

### Базове використання Facade

```php
use Concept\Config\Facade\Config;

// Створення з файлу з усіма увімкненими функціями
$config = Config::config('config/app.json');

// Негайний доступ до значень - змінні автоматично розв'язуються
echo $config->get('app.name');
```

### Facade з контекстом

```php
use Concept\Config\Facade\Config;

// Надання контексту для вирішення змінних
$config = Config::config(
    source: 'config/app.json',
    context: [
        'env' => 'production',
        'region' => 'us-east-1',
        'ENV' => getenv()  // Зробити всі змінні оточення доступними
    ]
);
```

### Facade з кількома файлами

```php
use Concept\Config\Facade\Config;

// Завантаження кількох файлів за допомогою glob-шаблонів
$config = Config::config('config/*.json', context: [
    'ENV' => getenv()
]);
```

### Facade з перевизначеннями

```php
use Concept\Config\Facade\Config;

// Перевизначення конкретних значень
$config = Config::config(
    source: 'config/app.json',
    context: ['ENV' => getenv()],
    overrides: [
        'app.debug' => true,
        'cache.enabled' => false
    ]
);
```

### Попередньо налаштовані плагіни

Facade автоматично включає ці плагіни:

| Плагін | Пріоритет | Призначення | Приклад |
|--------|----------|---------|---------|
| EnvPlugin | 999 | Змінні оточення | `@env(DB_HOST)` |
| ContextPlugin | 998 | Контекстні значення | `${region}` |
| IncludePlugin | 997 | Включення вмісту файлу | `@include(file.json)` |
| ImportPlugin | 996 | Імпорт та об'єднання конфігурацій | `{"@import": "db.json"}` |
| ReferencePlugin | 995 | Внутрішні посилання | `@database.host` |
| ConfigValuePlugin | 994 | Вирішення значень конфігурації | Розширений парсинг |

## Використання статичної фабрики

Клас `StaticFactory` надає зручні статичні методи для створення конфігурацій:

### Створення з масиву

```php
use Concept\Config\StaticFactory;

$config = StaticFactory::create([
    'key' => 'value'
]);
```

### Створення з файлу

```php
$config = StaticFactory::fromFile('config/app.json');
```

### Створення з кількох файлів

```php
$config = StaticFactory::fromFiles([
    'config/app.json',
    'config/database.json',
    'config/cache.json'
]);
```

### Створення за шаблоном Glob

```php
// Завантаження всіх JSON файлів у директорії config
$config = StaticFactory::fromGlob('config/*.json');
```

## Робота з контекстом

Контекст надає спосіб передачі значень під час виконання, на які можна посилатися у вашій конфігурації:

```php
$config = new Config(
    data: [
        'app' => [
            'name' => '@env(APP_NAME)',
            'url' => '@env(APP_URL)'
        ]
    ],
    context: [
        'ENV' => getenv()
    ]
);

// Парсинг для вирішення змінних оточення
$config->getParser()->parse($config->dataReference());

echo $config->get('app.name'); // Значення з змінної оточення APP_NAME
```

## Парсинг конфігурації

Парсер розв'язує змінні, включення та інші директиви у вашій конфігурації:

### Увімкнення парсингу при завантаженні

```php
// Парсинг одразу при завантаженні
$config->load('config/app.json', parse: true);
```

### Ручний парсинг

```php
// Завантаження без парсингу
$config->load('config/app.json', parse: false);

// Парсинг пізніше
$config->getParser()->parse($config->dataReference());
```

## Імпорт додаткової конфігурації

Ви можете об'єднати додаткові файли конфігурації з існуючою конфігурацією:

```php
$config = new Config(['app' => ['name' => 'MyApp']]);

// Імпорт та об'єднання
$config->import('config/additional.json', parse: true);
```

## Експорт конфігурації

Експортуйте вашу конфігурацію у файл. Формат виводу автоматично визначається на основі розширення файлу:

```php
// Експорт у JSON (формат автоматично визначається з розширення .json)
$config->export('output/config.json');

// Експорт у PHP масив (формат автоматично визначається з розширення .php)
$config->export('output/config.php');
```

## Вузли конфігурації

Створення ізольованих екземплярів конфігурації з підмножини даних:

```php
$config = new Config([
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'credentials' => [
            'username' => 'admin',
            'password' => 'secret'
        ]
    ]
]);

// Отримання вузла для конфігурації бази даних
$dbConfig = $config->node('database');

// Доступ без префіксу 'database.'
echo $dbConfig->get('host');                    // "localhost"
echo $dbConfig->get('credentials.username');    // "admin"
```

### Копія проти посилання

```php
// Копія (за замовчуванням) - зміни не впливають на оригінал
$dbConfigCopy = $config->node('database', copy: true);
$dbConfigCopy->set('host', 'remote.host');
echo $config->get('database.host'); // Залишається "localhost"

// Посилання - зміни впливають на оригінал
$dbConfigRef = $config->node('database', copy: false);
$dbConfigRef->set('host', 'remote.host');
echo $config->get('database.host'); // Тепер "remote.host"
```

## Наступні кроки

Тепер, коли ви розумієте основи, вивчіть ці теми:

- [Архітектура](./architecture.md) - Зрозумійте дизайн системи
- [Посібник з конфігурації](./configuration.md) - Вивчіть усі методи конфігурації
- [Система плагінів](./plugins.md) - Розширення за допомогою користувацьких плагінів
- [Контекст та змінні](./context.md) - Розширене вирішення змінних
- [Приклади](./examples.md) - Реальні випадки використання

## Загальні шаблони

### Конфігурація додатка

```php
// config/app.php
return [
    'name' => getenv('APP_NAME') ?: 'MyApp',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => getenv('APP_DEBUG') === 'true',
    'url' => getenv('APP_URL') ?: 'http://localhost',
];

// У вашому додатку
$config = StaticFactory::fromFile('config/app.php');
```

### Налаштування для кількох середовищ

```php
$env = getenv('APP_ENV') ?: 'production';

$config = StaticFactory::fromFiles([
    'config/app.json',              // Базова конфігурація
    "config/env/$env.json",         // Специфічна для середовища
]);
```

### Конфігурація з валідацією

```php
$config = StaticFactory::fromFile('config/app.json');

// Валідація обов'язкових ключів
$required = ['app.name', 'app.version', 'database.host'];
foreach ($required as $key) {
    if (!$config->has($key)) {
        throw new \RuntimeException("Відсутня обов'язкова конфігурація: $key");
    }
}
```
