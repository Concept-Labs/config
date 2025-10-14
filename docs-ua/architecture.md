# Архітектура

Розуміння архітектури Concept\Config допоможе вам максимально використати її можливості та ефективно розширити її.

## Огляд системи

Concept\Config побудований на модульній архітектурі на основі плагінів з чітким розділенням обов'язків:

```
┌─────────────────────────────────────────────────┐
│                  Config                         │
│  (Головна точка входу та оркестратор)           │
└────────┬────────────────────────────────────────┘
         │
         ├─────► Storage (DotArray)
         │       └─ Управління даними з точковою нотацією
         │
         ├─────► Context
         │       └─ Вирішення змінних під час виконання
         │
         ├─────► Resource
         │       ├─ Обробка операцій введення/виведення
         │       └─► AdapterManager
         │           └─► Адаптери (JSON, PHP тощо)
         │
         └─────► Parser
                 ├─ Обробка директив та змінних
                 └─► Плагіни (Env, Reference, Import тощо)
```

## Основні компоненти

### 1. Config (`Config`)

**Розташування**: `src/Config.php`

Головний клас конфігурації, який оркеструє всі компоненти.

**Відповідальності**:
- Надання публічного API для доступу до конфігурації
- Координація між сховищем, парсером та ресурсом
- Управління життєвим циклом конфігурації (завантаження, імпорт, експорт)

**Ключові методи**:
```php
interface ConfigInterface
{
    public function get(string $path, mixed $default = null): mixed;
    public function set(string $path, mixed $value): static;
    public function has(string $path): bool;
    public function load(string|array|ConfigInterface $source, bool $parse = false): static;
    public function import(string|array|ConfigInterface $source, bool $parse = false): static;
    public function export(string $target): static;
    public function node(string $path, bool $copy = true): static;
}
```

### 2. Storage (`Storage`)

**Розташування**: `src/Storage/Storage.php`

Розширює `DotArray` з `concept-labs/arrays` для надання доступу до даних конфігурації через точкову нотацію.

**Відповідальності**:
- Зберігання даних конфігурації
- Надання доступу через точкову нотацію
- Підтримка вкладених операцій

**Можливості**:
- Доступ до масиву через інтерфейс `ArrayAccess`
- Маніпуляція вкладеними шляхами
- Семантика посилань проти копій

### 3. Context (`Context`)

**Розташування**: `src/Context/Context.php`

Управляє контекстом під час виконання для вирішення змінних.

**Відповідальності**:
- Зберігання контекстних змінних (оточення, користувацькі значення)
- Надання доступу до контекстних даних під час парсингу
- Підтримка секцій (ENV, користувацькі секції)

**Приклад**:
```php
$context = new Context();
$context->withEnv(getenv());
$context->withSection('custom', ['key' => 'value']);
```

### 4. Resource (`Resource`)

**Розташування**: `src/Resource/Resource.php`

Обробляє всі операції введення/виведення для файлів конфігурації.

**Відповідальності**:
- Читання конфігурації з файлів/джерел
- Запис конфігурації у файли
- Управління стеком джерел (для виявлення циклічних посилань)
- Координація з адаптерами

**Ключові можливості**:
- Вибір адаптера на основі розширення файлу
- Виявлення циклічних посилань
- Вирішення відносних шляхів
- Підтримка фрагментів (наприклад, `file.json#path.to.data`)

### 5. Parser (`Parser`)

**Розташування**: `src/Parser/Parser.php`

Обробляє дані конфігурації через систему плагінів.

**Відповідальності**:
- Виконання плагінів в порядку пріоритету
- Побудова стеку middleware
- Обробка попередньої та пост-обробки
- Управління чергою лінивого вирішення

**Виконання плагіна**:
```php
// Плагіни виконуються в порядку пріоритету (більше число = вищий пріоритет)
$parser->registerPlugin(EnvPlugin::class, 999);
$parser->registerPlugin(ReferencePlugin::class, 998);
$parser->registerPlugin(ImportPlugin::class, 997);
```

## Система плагінів

### Архітектура плагінів

Плагіни реалізують `PluginInterface` і виконуються як middleware:

```php
interface PluginInterface
{
    public function __invoke(
        mixed $value,
        string $path,
        array &$subjectData,
        callable $next
    ): mixed;
}
```

### Вбудовані плагіни

#### 1. **EnvPlugin** (Пріоритет: 999)
- **Розташування**: `src/Parser/Plugin/Expression/EnvPlugin.php`
- **Шаблон**: `@env(VARIABLE_NAME)`
- **Призначення**: Вирішення змінних оточення

#### 2. **ReferencePlugin** (Пріоритет: 998)
- **Розташування**: `src/Parser/Plugin/Expression/ReferencePlugin.php`
- **Шаблон**: `@path.to.value`
- **Призначення**: Вирішення посилань на інші значення конфігурації

#### 3. **ImportPlugin** (Пріоритет: 997)
- **Розташування**: `src/Parser/Plugin/Directive/ImportPlugin.php`
- **Шаблон**: `"@import": "file.json"` або `"@import": ["file1.json", "file2.json"]`
- **Призначення**: Імпорт та об'єднання зовнішніх файлів конфігурації

#### 4. **ContextPlugin** (Пріоритет: 998)
- **Розташування**: `src/Parser/Plugin/ContextPlugin.php`
- **Шаблон**: Вирішення значень на основі контексту
- **Призначення**: Вирішення значень з контексту

#### 5. **CommentPlugin** (Пріоритет: 996)
- **Розташування**: `src/Parser/Plugin/Directive/CommentPlugin.php`
- **Шаблон**: `"@comment": "text"`
- **Призначення**: Видалення директив коментарів з конфігурації

### Потік виконання плагіна

```
1. Виклик Parser.parse()
   │
   ├─> 2. preProcess() - Побудова стеку middleware
   │
   ├─> 3. Обробка кожного значення через ланцюг плагінів
   │   │
   │   ├─> Плагін 1 (найвищий пріоритет)
   │   │   └─> викликає next()
   │   │
   │   ├─> Плагін 2
   │   │   └─> викликає next()
   │   │
   │   └─> Плагін N (найнижчий пріоритет)
   │       └─> повертає значення
   │
   └─> 4. postProcess() - Виконання відкладених операцій
```

## Система адаптерів

### Архітектура адаптерів

Адаптери обробляють читання та запис конкретних форматів файлів.

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

### Вбудовані адаптери

#### 1. **JsonAdapter**
- **Розташування**: `src/Resource/Adapter/JsonAdapter.php`
- **Підтримує**: `.json` файли
- **Можливості**: 
  - Підтримка glob-шаблонів
  - Об'єднання на основі пріоритетів
  - Гарне форматування

#### 2. **PhpAdapter**
- **Розташування**: `src/Resource/Adapter/PhpAdapter.php`
- **Підтримує**: `.php` файли
- **Можливості**:
  - Повертає масив з PHP файлу
  - Використовує `var_export()` для запису

### Менеджер адаптерів

```php
class AdapterManager
{
    public function registerAdapter(string $adapterClass): void;
    public function getAdapter(string $uri): AdapterInterface;
}
```

## Factory Pattern

### Статична фабрика

```php
use Concept\Config\StaticFactory;

// Створення з масиву
$config = StaticFactory::create(['key' => 'value']);

// З файлу
$config = StaticFactory::fromFile('config.json');

// З кількох файлів
$config = StaticFactory::fromFiles(['file1.json', 'file2.json']);

// З glob-шаблону
$config = StaticFactory::fromGlob('config/*.json');

// Компіляція
StaticFactory::compile('config/*.json', ['env' => 'prod'], 'compiled.json');
```

### Фабрика-будівельник

```php
use Concept\Config\Factory;

$config = (new Factory())
    ->withFile('config/app.json')
    ->withContext(['env' => 'production'])
    ->withPlugin(MyPlugin::class, 100)
    ->create();
```

### Порівняння фабрик

| Функція | StaticFactory | Facade | Factory |
|---------|--------------|--------|---------|
| Простота | ✅ Висока | ✅ Висока | ⚠️ Середня |
| Плагіни за замовчуванням | ❌ Ні | ✅ Так | ❌ Ні |
| Користувацькі плагіни | ❌ Ні | ❌ Ні | ✅ Так |
| Кілька джерел | ✅ Glob | ✅ Так | ✅ Так |
| Підтримка контексту | ✅ Так | ✅ Так | ✅ Так |
| Перевизначення | ✅ Так | ❌ Ні | ✅ Так |
| Найкраще для | Швидке налаштування | Прості конфігурації | Розширене керування |

## Потік даних

### Завантаження конфігурації

```
1. Config::load('config.json', parse: true)
   │
   ├─> 2. Resource::read()
   │   │
   │   ├─> 3. AdapterManager::getAdapter('config.json')
   │   │   └─> Повертає JsonAdapter
   │   │
   │   ├─> 4. JsonAdapter::read('config.json')
   │   │   └─> Повертає дані масиву
   │   │
   │   └─> 5. Parser::parse($data) [якщо parse=true]
   │       │
   │       └─> 6. Виконання ланцюга плагінів
   │           └─> Вирішення змінних, імпортів тощо
   │
   └─> 7. Storage::hydrate($data)
       └─> Зберігання в DotArray
```

### Імпорт конфігурації

```
1. Config::import('additional.json', parse: true)
   │
   ├─> 2. Resource::read() у тимчасовий масив
   │
   ├─> 3. Parser::parse() [якщо parse=true]
   │
   └─> 4. Storage::replace()
       └─> Об'єднання з рекурсивною стратегією
```

### Експорт конфігурації

```
1. Config::export('output.json')
   │
   ├─> 2. Storage::toArray()
   │   └─> Отримання даних конфігурації
   │
   └─> 3. Resource::write('output.json', $data)
       │
       ├─> 4. AdapterManager::getAdapter('output.json')
       │   └─> Повертає JsonAdapter
       │
       └─> 5. JsonAdapter::write('output.json', $data)
           └─> Кодування та запис у файл
```

## Точки розширення

### Користувацькі плагіни

Створіть користувацькі плагіни, реалізуючи `PluginInterface` або розширюючи `AbstractPlugin`:

```php
use Concept\Config\Parser\Plugin\AbstractPlugin;

class MyPlugin extends AbstractPlugin
{
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        // Ваша користувацька логіка
        if (is_string($value) && str_starts_with($value, '@custom(')) {
            // Обробка користувацької директиви
            $value = $this->processCustom($value);
        }
        
        return $next($value, $path, $subjectData);
    }
}

// Реєстрація
$config->getParser()->registerPlugin(MyPlugin::class, priority: 100);
```

### Користувацькі адаптери

Створіть користувацькі адаптери, реалізуючи `AdapterInterface`:

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
        return yaml_parse_file($uri);
    }
    
    public function write(string $target, array $data): static
    {
        file_put_contents($target, yaml_emit($data));
        return $this;
    }
    
    // ... реалізуйте encode/decode
}

// Реєстрація
$config->getResource()->getAdapterManager()->registerAdapter(YamlAdapter::class);
```

### Користувацьке сховище

Розширте сховище для користувацької обробки даних:

```php
use Concept\Config\Storage\Storage;

class CachedStorage extends Storage
{
    private $cache;
    
    public function get(string $path, mixed $default = null): mixed
    {
        return $this->cache->remember($path, function() use ($path, $default) {
            return parent::get($path, $default);
        });
    }
}
```

## Принципи дизайну

### 1. Розділення обов'язків
- Кожен компонент має одну, чітко визначену відповідальність
- Чіткі інтерфейси між компонентами

### 2. Принцип відкритості/закритості
- Відкритий для розширення через плагіни та адаптери
- Закритий для модифікації основної функціональності

### 3. Впровадження залежностей
- Залежності впроваджуються через конструктори
- Тестовані та макетовані компоненти

### 4. Ліниве обчислення
- Парсинг тільки за потребою
- Вирішення посилань на вимогу

### 5. Опції незмінності
- Підтримка як семантики копій, так і посилань
- Вибір на основі випадку використання

## Міркування щодо продуктивності

### Стратегія парсингу

```php
// Негайний парсинг (жадібний)
$config->load('config.json', parse: true);

// Парсинг пізніше (лінивий)
$config->load('config.json', parse: false);
$config->getParser()->parse($config->dataReference());
```

### Запобігання циклічним посиланням

Компонент Resource підтримує стек джерел для виявлення та запобігання циклічним імпортам:

```php
// У Resource::read()
if ($this->hasSource($source)) {
    throw new InvalidArgumentException('Виявлено циклічне посилання');
}
```

### Пріоритет плагіна

Плагіни з вищим пріоритетом виконуються першими. Використовуйте це для керування порядком виконання:

```php
// Змінні оточення розв'язуються першими
$parser->registerPlugin(EnvPlugin::class, 999);

// Потім посилання
$parser->registerPlugin(ReferencePlugin::class, 998);

// Нарешті імпорти
$parser->registerPlugin(ImportPlugin::class, 997);
```

## Безпека потоків

Бібліотека розроблена для однопоткових PHP середовищ. Для багатопоткових сценаріїв:
- Створіть окремі екземпляри Config для кожного потоку
- Використовуйте незмінні копії (`node($path, copy: true)`)
- Уникайте спільного стану в користувацьких плагінах
