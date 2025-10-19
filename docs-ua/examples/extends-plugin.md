# Приклад ExtendsPlugin

Детальний приклад використання ExtendsPlugin для розширення конфігурацій.

## Огляд

ExtendsPlugin дозволяє розширити конфігурацію з базового вузла, подібно до наслідування в об'єктно-орієнтованому програмуванні.

## Базове використання

### Проста конфігурація

```php
$config = new Config([
    'base' => [
        'debug' => false,
        'timezone' => 'UTC',
        'cache' => [
            'driver' => 'file'
        ]
    ],
    'development' => [
        '@extends' => 'base',
        'debug' => true,
        'cache' => [
            'driver' => 'array'
        ]
    ]
]);

$config->getParser()->parse($config->dataReference());

// $config->get('development') містить:
// [
//     'debug' => true,           // Перевизначено
//     'timezone' => 'UTC',       // Успадковано
//     'cache' => [
//         'driver' => 'array'    // Перевизначено
//     ]
// ]
```

## Складні приклади

### Середовища додатка

```php
$config = new Config([
    'app' => [
        'base' => [
            'name' => 'MyApp',
            'debug' => false,
            'timezone' => 'UTC',
            'locale' => 'en',
            'features' => [
                'billing' => false,
                'analytics' => true
            ]
        ],
        'development' => [
            '@extends' => 'app.base',
            'debug' => true,
            'features' => [
                'billing' => true
            ]
        ],
        'production' => [
            '@extends' => 'app.base',
            'features' => [
                'billing' => true,
                'analytics' => true
            ]
        ]
    ]
]);

$config->getParser()->parse($config->dataReference());
```

### Профілі бази даних

```php
$config = new Config([
    'database' => [
        'base' => [
            'driver' => 'mysql',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'options' => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ]
        ],
        'local' => [
            '@extends' => 'database.base',
            'host' => 'localhost',
            'database' => 'myapp_local',
            'username' => 'root',
            'password' => ''
        ],
        'production' => [
            '@extends' => 'database.base',
            'host' => '@env(DB_HOST)',
            'database' => '@env(DB_DATABASE)',
            'username' => '@env(DB_USERNAME)',
            'password' => '@env(DB_PASSWORD)',
            'options' => [
                \PDO::ATTR_PERSISTENT => true
            ]
        ]
    ]
]);
```

### Конфігурації сервісів

```php
$config = new Config([
    'services' => [
        'base_service' => [
            'timeout' => 30,
            'retries' => 3,
            'circuit_breaker' => [
                'threshold' => 5,
                'timeout' => 60
            ]
        ],
        'auth_service' => [
            '@extends' => 'services.base_service',
            'url' => '@env(AUTH_SERVICE_URL)',
            'timeout' => 10  // Перевизначити
        ],
        'payment_service' => [
            '@extends' => 'services.base_service',
            'url' => '@env(PAYMENT_SERVICE_URL)',
            'timeout' => 60,  // Довший таймаут
            'retries' => 5    // Більше спроб
        ]
    ]
]);
```

## Форвардні посилання

ExtendsPlugin підтримує форвардні посилання за допомогою лінивого вирішення:

```php
$config = new Config([
    'child' => [
        '@extends' => 'base',  // Посилається на дані, що завантажуються пізніше
        'extra' => 'value'
    ],
    'base' => [
        'config' => 'data'
    ]
]);

$config->getParser()->parse($config->dataReference());

// Працює коректно, навіть якщо 'base' визначено після 'child'
```

## Вкладені розширення

```php
$config = new Config([
    'level1' => [
        'a' => 1,
        'b' => 2
    ],
    'level2' => [
        '@extends' => 'level1',
        'b' => 20,
        'c' => 30
    ],
    'level3' => [
        '@extends' => 'level2',
        'c' => 300,
        'd' => 400
    ]
]);

$config->getParser()->parse($config->dataReference());

// $config->get('level3') містить:
// [
//     'a' => 1,     // З level1
//     'b' => 20,    // З level2
//     'c' => 300,   // Перевизначено в level3
//     'd' => 400    // Додано в level3
// ]
```

## Використання з Import

```php
// config/base.json
{
  "logger": {
    "channels": {
      "default": {
        "level" => "warning",
        "handler" => "file"
      }
    }
  }
}

// config/app.json
{
  "@import": "base.json",
  "logger": {
    "channels": {
      "custom": {
        "@extends": "logger.channels.default",
        "level": "debug"
      }
    }
  }
}
```

## Найкращі практики

### 1. Створюйте повторно використовувані бази

```php
// Добре: Повторно використовувана базова конфігурація
'base_api' => [
    'timeout' => 30,
    'retries' => 3,
    'headers' => [
        'Content-Type' => 'application/json'
    ]
]
```

### 2. Використовуйте зрозумілі імена

```php
// Добре: Зрозумілі назви
'@extends' => 'environments.production_base'

// Погано: Незрозумілі назви
'@extends' => 'cfg.prod.b'
```

### 3. Документуйте структуру наслідування

```php
/**
 * Структура наслідування:
 * - base -> Загальні налаштування
 *   - development -> Налаштування розробки
 *   - production -> Налаштування продакшену
 *     - production_eu -> Продакшен для ЄС
 *     - production_us -> Продакшен для США
 */
```

### 4. Уникайте циклічних розширень

```php
// Погано: Циклічне посилання
[
    'a' => ['@extends' => 'b'],
    'b' => ['@extends' => 'a']  // Викличе помилку
]
```

### 5. Обмежуйте глибину вкладеності

```php
// Добре: 2-3 рівні
base -> environment -> specific

// Погано: Занадто багато рівнів
base -> template -> environment -> region -> tenant -> specific
```

## Обробка помилок

```php
try {
    $config = new Config([
        'child' => [
            '@extends' => 'nonexistent'  // Помилка
        ]
    ]);
    
    $config->getParser()->parse($config->dataReference());
} catch (\InvalidArgumentException $e) {
    echo "Помилка розширення: " . $e->getMessage();
}
```

## Інтеграція з іншими плагінами

```php
$config = new Config([
    'base' => [
        'url' => '@env(BASE_URL)',
        'timeout' => 30
    ],
    'api' => [
        '@extends' => 'base',
        'endpoint' => '#{base.url}/api',  // Посилання на успадковане значення
        'key' => '@env(API_KEY)'
    ]
]);

$config->getContext()->withEnv(getenv());
$config->getParser()->parse($config->dataReference());
```

Дивіться [документацію по плагінам](../plugins.md) для більше інформації про ExtendsPlugin.
