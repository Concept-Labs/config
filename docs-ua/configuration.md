# Посібник з конфігурації

Комплексний посібник з налаштування та використання Concept\Config у ваших додатках.

## Створення конфігурацій

### Пряме створення екземпляра

```php
use Concept\Config\Config;

// З даними масиву
$config = new Config([
    'app' => ['name' => 'MyApp'],
    'debug' => true
]);

// З контекстом
$config = new Config(
    data: ['key' => 'value'],
    context: ['env' => 'production']
);
```

### Використання статичної фабрики

```php
use Concept\Config\StaticFactory;

// З масиву
$config = StaticFactory::create(['key' => 'value']);

// З файлу
$config = StaticFactory::fromFile('config.json');

// З кількох файлів
$config = StaticFactory::fromFiles([
    'config/app.json',
    'config/database.json'
]);

// З glob-шаблону
$config = StaticFactory::fromGlob('config/*.json');
```

### Використання фабрики-будівельника

```php
use Concept\Config\Factory;

$config = (new Factory())
    ->withFile('config/app.json')
    ->withGlob('config/modules/*.json')
    ->withArray(['override' => 'value'])
    ->withContext(['env' => 'staging'])
    ->create();
```

## Читання конфігурації

### Отримання значень

```php
// Просте отримання
$value = $config->get('key');

// Вкладене отримання з точковою нотацією
$value = $config->get('database.connection.host');

// Зі значенням за замовчуванням
$value = $config->get('missing.key', 'default');

// За посиланням (для модифікації)
$ref = &$config->get('some.path');
$ref = 'new value'; // Модифікує конфігурацію
```

### Перевірка існування

```php
if ($config->has('database.host')) {
    // Ключ існує
}

// Перевірка вкладених ключів
if ($config->has('app.features.billing.enabled')) {
    // Вкладений ключ існує
}
```

### Ітерація конфігурації

```php
// Config реалізує IteratorAggregate
foreach ($config as $key => $value) {
    echo "$key = $value\n";
}

// Конвертація в масив
$array = $config->toArray();
```

## Запис конфігурації

### Встановлення значень

```php
// Встановлення простого значення
$config->set('key', 'value');

// Встановлення вкладеного значення
$config->set('database.host', 'localhost');

// Встановлення складної структури
$config->set('services.cache', [
    'driver' => 'redis',
    'host' => '127.0.0.1',
    'port' => 6379
]);
```

### Використання DotArray

Доступ до базового сховища для розширених операцій:

```php
$storage = $config->dotArray();

// Об'єднання даних
$storage->merge(['new' => 'data'], 'path.to.merge');

// Заповнення (не перезаписує існуючі)
$storage->fill(['defaults' => 'here'], 'path');

// Заміна
$storage->replace(['completely' => 'new'], 'path');
```

## Завантаження конфігурації

### З файлів

```php
// Завантаження та заміна всіх даних
$config->load('config/app.json');

// Завантаження з парсингом
$config->load('config/app.json', parse: true);

// Завантаження з PHP файлу
$config->load('config/app.php');
```

### З масивів

```php
// Завантаження даних масиву
$config->load([
    'app' => ['name' => 'MyApp'],
    'debug' => true
]);
```

### З іншої конфігурації

```php
$otherConfig = new Config(['data' => 'here']);
$config->load($otherConfig);
```

## Імпорт конфігурації

Імпорт об'єднує дані з існуючою конфігурацією:

```php
// Базовий імпорт
$config->import('additional.json');

// Імпорт з парсингом
$config->import('additional.json', parse: true);

// Імпорт до конкретного шляху
$config->importTo('modules/auth.json', 'modules.auth', parse: true);
```

### Стратегії імпорту

Імпорт використовує рекурсивне об'єднання за замовчуванням:

```php
// Оригінальна конфігурація
$config = new Config([
    'app' => ['name' => 'MyApp', 'debug' => false]
]);

// Імпорт
$config->import([
    'app' => ['debug' => true, 'version' => '2.0']
]);

// Результат:
// [
//     'app' => [
//         'name' => 'MyApp',      // збережено
//         'debug' => true,         // перезаписано
//         'version' => '2.0'       // додано
//     ]
// ]
```

## Експорт конфігурації

Метод `export()` дозволяє зберегти вашу конфігурацію у файл. Формат виводу автоматично визначається на основі розширення файлу за допомогою системи адаптерів **Resource**.

### У файли

```php
// Експорт у JSON (використовує JsonAdapter)
$config->export('output/config.json');

// Експорт у PHP (використовує PhpAdapter)
$config->export('output/config.php');

// Експорт парсеної конфігурації для продакшену
$config = StaticFactory::fromGlob('config/*.json', parse: true);
$config->export('compiled/config.json');
```

### Автоматичне визначення формату

Метод `export()` використовує компонент **Resource** з зареєстрованими адаптерами для визначення формату виводу:

- **`.json`** - Використовує `JsonAdapter` для запису JSON з гарним форматуванням
- **`.php`** - Використовує `PhpAdapter` для запису PHP масиву (використовуючи `var_export`)

Адаптер автоматично вибирається на основі розширення файлу через `AdapterManager::getAdapter()`.

### Як це працює

1. `Config::export($target)` викликає `Resource::write($target, $data)`
2. `Resource::write()` використовує `AdapterManager::getAdapter($target)` для знаходження відповідного адаптера
3. Адаптер перевіряє, чи підтримує він файл за допомогою `Adapter::supports($uri)` (перевіряє розширення файлу)
4. Вибраний адаптер записує дані у відповідному форматі

### Користувацькі формати

Ви можете додати підтримку додаткових форматів, зареєструвавши користувацькі адаптери:

```php
$resource = $config->getResource();
$adapterManager = $resource->getAdapterManager();
$adapterManager->registerAdapter(YamlAdapter::class);

// Тепер ви можете експортувати в YAML
$config->export('output/config.yaml');
```

### Отримання як масиву

```php
// Отримання всієї конфігурації
$data = $config->toArray();

// Отримання за посиланням (будьте обережні!)
$ref = &$config->dataReference();
```

## Вузли конфігурації

Вузли створюють ізольовані підконфігурації:

```php
$config = new Config([
    'database' => [
        'default' => 'mysql',
        'connections' => [
            'mysql' => ['host' => 'localhost'],
            'pgsql' => ['host' => 'postgres.local']
        ]
    ]
]);

// Отримання вузла бази даних
$dbConfig = $config->node('database');
echo $dbConfig->get('default'); // 'mysql'
echo $dbConfig->get('connections.mysql.host'); // 'localhost'
```

### Копія проти посилання

```php
// Копія (за замовчуванням) - незалежна від батьківської
$copy = $config->node('database', copy: true);
$copy->set('default', 'pgsql');
echo $config->get('database.default'); // Залишається 'mysql'

// Посилання - зміни впливають на батьківську
$ref = $config->node('database', copy: false);
$ref->set('default', 'pgsql');
echo $config->get('database.default'); // Тепер 'pgsql'
```

## Контекст

### Встановлення контексту

```php
// Заміна контексту
$config->withContext(['env' => 'staging']);

// Отримання контексту
$context = $config->getContext();

// Додавання змінних оточення
$context->withEnv(getenv());

// Додавання користувацької секції
$context->withSection('app', ['version' => '2.0']);
```

### Доступ до контексту

```php
$context = $config->getContext();

// Отримання всього контексту
$data = $context->toArray();

// Отримання секції
$env = $context->get('ENV');
```

## Парсинг

### Парсинг даних

```php
// Парсинг поточних даних
$config->getParser()->parse($config->dataReference());

// Парсинг при завантаженні
$config->load('config.json', parse: true);
```

### Управління контекстом

```php
// Парсинг з користувацьким контекстом
$parser = $config->getParser();
$parser->setContext($customContext);
$parser->parse($config->dataReference());
```

## Операції з даними

### Об'єднання

```php
$config = new Config(['a' => 1, 'b' => 2]);
$config->dotArray()->merge(['b' => 3, 'c' => 4]);
// Результат: ['a' => 1, 'b' => 3, 'c' => 4]
```

### Заповнення

```php
$config = new Config(['a' => 1]);
$config->dotArray()->fill(['a' => 2, 'b' => 3]);
// Результат: ['a' => 1, 'b' => 3] (існуюче 'a' не перезаписано)
```

### Заміна

```php
$config = new Config(['a' => 1, 'b' => 2]);
$config->dotArray()->replace(['c' => 3]);
// Результат: ['c' => 3] (повна заміна)
```

### Гідратація

```php
$config = new Config();
$config->hydrate(['new' => 'data']);
// Заміняє всі дані на ['new' => 'data']
```

### Очищення

Очищення конфігурації до початкового порожнього стану:

```php
$config->reset();
// Всі дані, контекст, парсер та ресурс очищені
```

### Клонування

Створення глибокої копії конфігурації:

```php
$config = new Config(['data' => 'here']);
$clone = clone $config;

$clone->set('data', 'modified');
// Оригінальний $config не змінено
```

## Управління ресурсами

### Отримання ресурсу

Доступ до менеджера ресурсів для розширених операцій:

```php
$resource = $config->getResource();

// Пряме читання
$data = [];
$resource->read($data, 'config.json', withParser: true);

// Прямий запис
$resource->write('output.json', ['data' => 'here']);
```

### Управління адаптерами

```php
$resource = $config->getResource();
$adapterManager = $resource->getAdapterManager();

// Реєстрація користувацького адаптера
$adapterManager->registerAdapter(CustomAdapter::class);

// Отримання адаптера для файлу
$adapter = $adapterManager->getAdapter('config.yaml');
```

## Управління парсером

### Отримання парсера

```php
$parser = $config->getParser();
```

### Реєстрація плагінів

```php
// Реєстрація з пріоритетом
$parser->registerPlugin(MyPlugin::class, priority: 100);

// Реєстрація callable
$parser->registerPlugin(
    function($value, $path, &$data, $next) {
        // Користувацька логіка
        return $next($value, $path, $data);
    },
    priority: 50
);
```

### Отримання плагіна

```php
$plugin = $parser->getPlugin(EnvPlugin::class);
```

## Компіляція

Компіляція кількох джерел в один файл конфігурації:

```php
use Concept\Config\StaticFactory;

StaticFactory::compile(
    sources: [
        'config/base.json',
        'config/overrides.json',
        'config/local.json'
    ],
    context: ['env' => 'production'],
    target: 'compiled/config.json'
);
```

Це корисно для:
- Оптимізації розгортання
- Зменшення операцій введення/виведення файлів
- Створення дистрибутивних пакетів

## Шаблони конфігурації

### Багаторівнева конфігурація

```php
$config = (new Factory())
    ->withFile('config/defaults.json')     // Базові значення за замовчуванням
    ->withFile('config/app.json')          // Конфігурація додатка
    ->withFile('config/env/prod.json')     // Специфічна для середовища
    ->withFile('config/local.json')        // Локальні перевизначення
    ->create();
```

### Прапорці функцій

```php
$config = new Config([
    'features' => [
        'new_ui' => '@env(FEATURE_NEW_UI)',
        'beta_api' => '@env(FEATURE_BETA_API)',
        'analytics' => true
    ]
]);

$config->load($config->toArray(), parse: true);

if ($config->get('features.new_ui')) {
    // Увімкнути новий UI
}
```

### Конфігурація сервісів

```php
$config = new Config([
    'services' => [
        'database' => [
            'class' => 'Database\\Connection',
            'config' => '@database'
        ],
        'cache' => [
            'class' => 'Cache\\Redis',
            'config' => '@cache'
        ]
    ],
    'database' => [
        'host' => '@env(DB_HOST)',
        'port' => '@env(DB_PORT)'
    ],
    'cache' => [
        'host' => '@env(REDIS_HOST)',
        'port' => 6379
    ]
]);
```

### Багатоорендна конфігурація

```php
$tenant = 'acme-corp';

$config = (new Factory())
    ->withFile('config/base.json')
    ->withFile("config/tenants/$tenant.json")
    ->withContext(['tenant' => $tenant])
    ->create();
```

## Найкращі практики

### 1. Розділення обов'язків

```php
// Добре: Окремі файли для різних обов'язків
$config = StaticFactory::fromFiles([
    'config/app.json',
    'config/database.json',
    'config/cache.json',
    'config/services.json'
]);
```

### 2. Використання змінних оточення

```php
// Добре: Чутливі дані з оточення
{
    "database": {
        "host": "@env(DB_HOST)",
        "password": "@env(DB_PASSWORD)"
    }
}
```

### 3. Значення за замовчуванням

```php
// Добре: Надавати розумні значення за замовчуванням
$timeout = $config->get('api.timeout', 30);
$retries = $config->get('api.retries', 3);
```

### 4. Валідація конфігурації

```php
// Добре: Валідація при завантаженні
$config->load('config.json', parse: true);

$required = ['app.name', 'database.host'];
foreach ($required as $key) {
    if (!$config->has($key)) {
        throw new \RuntimeException("Відсутнє: $key");
    }
}
```

### 5. Перевірка типів

```php
// Добре: Перевіряти типи
$debug = $config->get('app.debug');
if (!is_bool($debug)) {
    throw new \TypeError("app.debug має бути boolean");
}
```

### 6. Незмінність де можливо

```php
// Добре: Використовувати копії для незалежних конфігурацій
$apiConfig = $config->node('api', copy: true);
// Зміни в $apiConfig не вплинуть на $config
```

### 7. Кешування скомпільованих конфігурацій

```php
// Добре: Компіляція для продакшену
if ($env === 'production') {
    if (!file_exists('cache/config.json')) {
        StaticFactory::compile(
            'config/*.json',
            ['env' => 'production'],
            'cache/config.json'
        );
    }
    $config = StaticFactory::fromFile('cache/config.json');
}
```
