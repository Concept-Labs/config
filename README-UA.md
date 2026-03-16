
# Concept\Config

Потужна, гнучка та розширювана бібліотека управління конфігурацією для PHP 8.2+.

PHP >= 8.2 | Ліцензія: MIT | Частина екосистеми [Concept Labs](https://github.com/Concept-Labs)

## Особливості

- **Доступ через точкову нотацію**: Доступ до вкладених значень конфігурації за допомогою інтуїтивної точкової нотації
- **Підтримка багатьох форматів**: JSON, PHP масиви та розширювані до YAML, INI тощо
- **Система плагінів**: Розширювана архітектура плагінів для користувацької обробки
- **Інтерполяція змінних**: Підтримка змінних оточення, посилань та контекстних значень
- **Система Import/Include**: Модульна конфігурація з імпортом файлів
- **Управління контекстом**: Гнучкий контекст для вирішення змінних під час виконання
- **Ліниве вирішення**: Ефективна ліниве обчислення значень конфігурації
- **Шаблон Factory**: Множинні методи фабрики для різних випадків використання
- **Інтерфейс Facade**: Спрощене створення конфігурації з попередньо налаштованими плагінами
- **Типобезпека**: Повні підказки типів PHP 8.2+ та сувора типізація

## Встановлення

Встановіть через Composer:

```bash
composer require concept-labs/config
```

## Швидкий старт

### Базове використання

```php
use Concept\Config\Config;

// Створення екземпляра конфігурації з вбудованими даними
$config = new Config([
    'app' => [
        'name' => 'MyApp',
        'debug' => true,
        'version' => '1.0.0'
    ],
    'database' => [
        'host' => 'localhost',
        'port' => 3306
    ]
]);

// Доступ до значень через точкову нотацію
echo $config->get('app.name');        // "MyApp"
echo $config->get('database.host');   // "localhost"

// Встановлення значень
$config->set('app.version', '2.0.0');

// Перевірка існування ключа
if ($config->has('app.debug')) {
    // ...
}
```

### Завантаження з файлів

```php
use Concept\Config\Facade\Config;

// Використання Facade для повнофункціональної конфігурації з усіма попередньо налаштованими плагінами
$config = Config::config('config/app.json', context: [
    'ENV' => getenv()
]);

// Або використання прямого класу
use Concept\Config\Config;

// Завантаження з JSON файлу
$config = new Config();
$config->load('config/app.json', parse: true);

// Або використання статичної фабрики
use Concept\Config\StaticFactory;

$config = StaticFactory::fromFile('config/app.json', parse: true);
```

### Використання контексту та змінних

```php
// Створення конфігурації з підтримкою змінних оточення
$config = new Config([
    'database' => [
        'host' => '@env(DB_HOST)',
        'user' => '@env(DB_USER)',
        'password' => '@env(DB_PASSWORD)'
    ]
], context: [
    'ENV' => getenv()
]);

// Парсинг для вирішення змінних
$config->getParser()->parse($config->dataReference());
```

## Документація

Вичерпна документація доступна в директорії [docs-ua](./docs-ua):

- [Початок роботи](./docs-ua/getting-started.md) - Встановлення та базове використання
- [Архітектура](./docs-ua/architecture.md) - Архітектура системи та дизайн
- [Посібник з конфігурації](./docs-ua/configuration.md) - Методи та опції конфігурації
- [Система плагінів](./docs-ua/plugins.md) - Розуміння та створення плагінів
- [Адаптери](./docs-ua/adapters.md) - Адаптери форматів файлів
- [Контекст та змінні](./docs-ua/context.md) - Вирішення змінних та управління контекстом
- [Довідник API](./docs-ua/api-reference.md) - Повна документація API
- [Приклади](./docs-ua/examples.md) - Практичні приклади та випадки використання
- [Розширені теми](./docs-ua/advanced.md) - Користувацькі плагіни, адаптери та фабрики

## Ключові концепції

### Точкова нотація

Доступ до вкладених значень конфігурації за допомогою знайомої точкової нотації:

```php
$config->get('database.connection.host');
$config->set('cache.drivers.redis.port', 6379);
```

### Система плагінів

Розширення функціональності через плагіни:

```php
// Змінні оточення
'@env(DB_HOST)'

// Посилання на конфігурацію
'@database.host'

// Користувацькі плагіни
$config->getParser()->registerPlugin(MyCustomPlugin::class, priority: 100);
```

### Фабрики

Кілька способів створення конфігурацій:

```php
// Facade - Спрощений інтерфейс з попередньо налаштованими плагінами
use Concept\Config\Facade\Config;

$config = Config::config('config/*.json', context: [
    'env' => 'production',
    'ENV' => getenv()
]);

// Статична фабрика
use Concept\Config\StaticFactory;

$config = StaticFactory::create(['key' => 'value']);
$config = StaticFactory::fromFile('config.json');
$config = StaticFactory::fromGlob('config/*.json');

// Фабрика-будівельник
use Concept\Config\Factory;

$config = (new Factory())
    ->withFile('config/app.json')
    ->withFile('config/database.json')
    ->withContext(['env' => 'production'])
    ->create();
```

### Інтерфейс Facade

Клас `Concept\Config\Facade\Config` надає спрощений, упереджений спосіб створення конфігурацій з усіма необхідними плагінами, попередньо налаштованими та готовими до використання.

```php
use Concept\Config\Facade\Config;

// Створення конфігурації з файлу(ів) з усіма увімкненими плагінами
$config = Config::config(
    source: 'config/*.json',           // Шлях до файлу або glob-шаблон
    context: ['env' => 'production'],  // Додаткові контекстні змінні
    overrides: ['debug' => false]      // Додаткові перевизначення конфігурації
);

// Facade автоматично налаштовує ці плагіни в порядку пріоритету:
// - EnvPlugin (999): @env(VAR_NAME) - Змінні оточення
// - ContextPlugin (998): ${context.key} - Контекстні значення  
// - IncludePlugin (997): @include(file) - Включення зовнішніх файлів
// - ImportPlugin (996): @import директива - Імпорт та об'єднання конфігурацій
// - ReferencePlugin (995): @path.to.value - Внутрішні посилання
// - ConfigValuePlugin (994): Вирішення значень специфічних для конфігурації
```

**Коли використовувати Facade:**
- Вам потрібне швидке, повнофункціональне налаштування конфігурації
- Вам потрібні змінні оточення, посилання, імпорти та включення
- Ви віддаєте перевагу конвенції над конфігурацією
- Ви починаєте новий проект і хочете розумні значення за замовчуванням

**Коли використовувати інші фабрики:**
- `StaticFactory`: Прості випадки використання без плагінів
- `Factory`: Користувацька конфігурація плагінів та розширене керування

## Розширені можливості

### Імпорт конфігурацій

```php
// Імпорт з іншого файлу
$config->import('additional-config.json', parse: true);

// Імпорт до конкретного шляху
$config->importTo('database-config.json', 'database', parse: true);
```

### Вузли конфігурації

```php
// Отримання підконфігурації як нового екземпляра Config
$dbConfig = $config->node('database');
echo $dbConfig->get('host'); // Прямий доступ без префіксу 'database.'
```

### Експорт

Експорт конфігурації у файли з автоматичним визначенням формату на основі розширення файлу:

```php
// Експорт у JSON (автоматично визначається з розширення .json)
$config->export('output/config.json');

// Експорт у PHP масив (автоматично визначається з розширення .php)
$config->export('output/config.php');
```

Формат автоматично визначається системою адаптерів Resource на основі розширення файлу.

## Приклади

### Багатосередовищна конфігурація

```json
{
  "app": {
    "name": "MyApp",
    "env": "@env(APP_ENV)",
    "debug": "@env(APP_DEBUG)"
  },
  "database": {
    "host": "@env(DB_HOST)",
    "port": "@env(DB_PORT)",
    "name": "@env(DB_NAME)"
  }
}
```

### Конфігурація з посиланнями

```json
{
  "paths": {
    "root": "/var/www",
    "public": "@paths.root/public",
    "storage": "@paths.root/storage"
  }
}
```

### Імпорт кількох файлів

```json
{
  "@import": [
    "config/database.json",
    "config/cache.json",
    "config/services.json"
  ]
}
```

## Розробка

### Вимоги

- PHP 8.2 або вище
- Composer

### Залежності

- `concept-labs/arrays` - Утиліти для роботи з масивами

### Запуск тестів

Цей пакет включає комплексне покриття тестами з використанням як PHPUnit, так і Pest:

```bash
# Запуск всіх тестів
composer test

# Запуск тільки юніт-тестів
composer test:unit

# Запуск тільки функціональних тестів
composer test:feature

# Запуск тестів з покриттям
composer test:coverage
```

**Статистика тестів:**
- 143 тести, що охоплюють всю функціональність
- 259 тверджень
- Стилі тестів PHPUnit та Pest
- Юніт та інтеграційні тести

Дивіться [tests/README.md](tests/README.md) для детальної документації з тестування.

## Ліцензія

Цей проект ліцензовано за ліцензією MIT - дивіться файл [LICENSE](LICENSE) для деталей.

## Внесок

Ми вітаємо внески! Будь ласка, не соромтеся надсилати Pull Request.

## Підтримка

- **Проблеми**: [GitHub Issues](https://github.com/Concept-Labs/config/issues)
- **Документація**: [Повна документація](./docs-ua)

## Автори

Розроблено та підтримується [Concept Labs](https://github.com/Concept-Labs).

---

Зроблено з турботою командою Concept Labs
