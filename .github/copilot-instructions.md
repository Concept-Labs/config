# Copilot Instructions — Concept‑Labs Repositories (PHP)

> **Мета:** допомогти GitHub Copilot / AI‑асистентам генерувати коректний, узгоджений і тестований код для проєктів екосистеми **Concept‑Labs** (Singularity DI, Config, Arrays, DBAL/DBC, HTTP, HTTP‑Message, Event Dispatcher, Phtmal, Simple‑Http тощо).  
> **Мова документації:** українська. **Код (класи/методи/інтерфейси):** англійською.  
> **Цільова версія PHP:** 8.1+ (переважно 8.2/8.3).  
> **Ліцензія:** Apache‑2.0.

---

## 1) Що Copilot має знати про проєкт

- Екосистема побудована з окремих Composer‑пакетів під організацією **Concept‑Labs**.
- Базові принципи: **PSR‑12**, **PSR‑4**, типізація скрізь, `declare(strict_types=1);`, **інтерфейси на публічних точках**, **конструкторна DI**, **жодного Service Locator** в доменному коді.
- **Singularity DI** — наш PSR‑11 контейнер із контекстною резолюцією, плагінами та контрактами життєвого циклу.
- **Config** — конфігурації (JSON/масив), автозбір через `extra.concept`/`concept.json`, вузол `singularity.*`.
- **Arrays** — DotArray / RecursiveApi / DotQuery (маніпуляції масивами, dot‑нотація, DQL‑запити).
- **DBAL / DBC** — SQL‑builder, драйвери (PDO), менеджери DML/DDL.
- **HTTP & HTTP‑Message** — PSR‑7/17/15, Router, Middleware.
- **Event Dispatcher** — PSR‑14.
- **Phtmal** — fluent‑builder HTML дерев, мінімальний рендер.

> Copilot має **не вигадувати** сторонні API/класи; використовувати **існуючі неймспейси** і стилі з цих пакетів.

---

## 2) Стиль коду та базові правила

- На початку кожного PHP‑файлу: `<?php declare(strict_types=1);`  
- **PSR‑12**: форматування, імена, імпорти.  
- **Інтерфейси** для сервісів; **класи розширювані**: віддаємо перевагу `protected` замість `private` для точок розширення, якщо це не шкодить інкапсуляції.  
- **Final** — тільки якщо об’єктивно потрібно заборонити спадкування.  
- Властивості **типізовані**; `readonly` там, де це доречно.  
- Ніяких прихованих синглтонов; життєвий цикл керує **DI**.  
- Коментарі/README/док‑блоки — **українською**, назви класів/методів — **англійською**.

---

## 3) Правила для Singularity DI (контейнер)

- Контейнер реалізує **PSR‑11** (`get()`, `has()`) та розширення `create()` для **нового екземпляра**.
- Перевага за **constructor injection**. Не тягнути контейнер у доменний код (антипатерн Service Locator).
- Контекстні прив’язки через конфіг: `singularity.preference`, `singularity.namespace`, `singularity.package`.
- Плагіни через **Plugin Manager**, можна вмикати/вимикати глобально або на рівні сервісу.
- Контракти життєвого циклу:
  - `SharedInterface` (+ `Shared\WeakInterface` для WeakReference);
  - `PrototypeInterface` (повинен мати `prototype()`);
  - Ініціалізація: `InjectableInterface` (методи з `#[Injector]`), `AutoConfigureInterface`.

### 3.1) Мінімальні приклади

**Конфіг (concept.json або PHP‑масив):**
```json
{
  "singularity": {
    "preference": {
      "App\\Contract\\FooInterface": { "class": "App\\Service\\FooService" },
      "App\\Contract\\BarInterface": { "class": "App\\Service\\BarService", "shared": true }
    },
    "namespace": {
      "App\\FeatureA\\": {
        "preference": {
          "App\\Contract\\CarInterface": { "class": "App\\Service\\BMW" }
        }
      },
      "App\\FeatureB\\": {
        "preference": {
          "App\\Contract\\CarInterface": { "class": "App\\Service\\Audi" }
        }
      }
    }
  }
}
```

**Клас із ін’єкцією та атрибутом Injector:**
```php
namespace App\Service;

use Concept\Singularity\Plugin\Attribute\Injector;
use Concept\Singularity\Contract\Initialization\InjectableInterface;

final class FooService implements InjectableInterface
{
    public function __construct(private BarService $bar) {}

    #[Injector]
    public function init(BazService $baz): void
    {
        // post‑construct injection
    }
}
```

**Shared/Prototype:**
```php
use Concept\Singularity\Contract\Lifecycle\SharedInterface;
use Concept\Singularity\Contract\Lifecycle\PrototypeInterface;

final class CachePool implements SharedInterface { /* singleton in registry */ }

final class TokenGenerator implements PrototypeInterface
{
    public function prototype(): self
    {
        return new self(); // or clone with cleared state
    }
}
```

---

## 4) Іменування, неймспейси, автозавантаження

- PSR‑4; корінь за `composer.json`.  
- Неймспейси відображають домен/модуль (`Concept\DBAL\...`, `Concept\DBC\...`, `Concept\Http\...`).  
- Нові публічні API спершу як **інтерфейси** в пакеті‑контракті, далі — імплементації.

---

## 5) Винятки та помилки

- Окремий простір `...\\Exception`.  
- Базовий інтерфейс `...\\Exception\\*Interface` + конкретні винятки: `RuntimeException`, `InvalidArgumentException`, `ServiceNotFoundException` тощо.  
- Повідомлення — інформативні, з ідентифікаторами сервісів/шляхами резолюції там, де доречно.

---

## 6) Конфігурації (Concept‑Labs/Config)

- Консолідуємо конфіги пакетів через `extra.concept.include` / `concept.json`.  
- Вузли `singularity.preference/namespace/package`, `reference`, `settings.plugin-manager.plugins`.  
- Не хардкодити значення в коді, якщо вони можуть жити у конфігурації.

**Приклад preference + плагін:**
```json
"singularity": {
  "preference": {
    "App\\Contract\\EmailSenderInterface": {
      "class": "App\\Infra\\SmtpEmailSender",
      "plugins": {
        "App\\Di\\Plugin\\LoggingPlugin": { "priority": 10 },
        "App\\Di\\Plugin\\LegacyPlugin": false
      }
    }
  }
}
```

---

## 7) Тестування (PHPUnit)

- Тести зберігати у `tests/` (Unit/Integration).  
- Іменування: `*Test.php`, один SUT на файл; data‑providers; міні‑фікстури.  
- Моки: через PHPUnit/Mockery; у складних конструкторах — фабрики‑білдери для тестів.  
- Покриття core‑пакетів — високе (70–90%+), але без “мікроassertів”, фокус на гілках і інваріантах.

**Шаблон тесту:**
```php
<?php declare(strict_types=1);

namespace Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use App\Service\FooService;
use App\Service\BarService;

final class FooServiceTest extends TestCase
{
    public function test_it_initializes_with_bar(): void
    {
        $sut = new FooService(new BarService());
        $this->assertTrue(method_exists($sut, 'init'));
    }
}
```

---

## 8) Документація та PHPDoc

- Повні PHPDoc‑блоки для **публічних** методів/класів, включно з описом побічних ефектів.  
- Приклад анотацій: `@template`, `@psalm-type` там, де складні масиви (особливо в Arrays/Config).  
- README‑приклади мають бути **реально виконувані** (runnable).

---

## 9) Коміти, PR та рецензії

- **Conventional Commits**: `feat:`, `fix:`, `refactor:`, `perf:`, `docs:`, `test:`, `chore:`, `build:`.  
- Гілки: `feature/<scope>`, `fix/<scope>`, `refactor/<scope>`.  
- PR‑шаблон має містити: контекст, рішення, скріни/логи, чек‑лист (тести, дока, BC).  
- Перед злиттям: `phpstan` (level high), `phpcs` (PSR‑12), тести зелені, README оновлено, BC‑зміни описані.

---

## 10) Поведінка Copilot (як генерувати пропозиції)

**Роби:**
- Пропонуй **інтерфейс + реалізацію** та мінімальні тести.
- Вставляй `declare(strict_types=1);` автоматично.
- Підбирай **існуючі неймспейси** з проєкту.
- Генеруй конфіг‑уривки для `singularity.preference` коли додаєш новий сервіс.
- Додавай атрибути `#[Injector]` тільки якщо клас реалізує `InjectableInterface`.
- Пропонуй фабрики (`ServiceFactory`) замість прямих викликів контейнера в бізнес‑коді.
- Пояснюй складні ділянки у коментарях (укр.).

**Не роби:**
- Не вигадуй API, яких немає в пакунках Concept‑Labs.
- Не тягни контейнер у доменні класи.
- Не використовуй `private` там, де потрібна розширюваність; надай `protected` hook‑методи.
- Не змінюй існуючі публічні сигнатури без явного маркування **BC‑break**.

---

## 11) Патерни та антипатерни

**Перевага:**
- Constructor Injection, Factories, Strategy/Adapter, Null‑Object (за потреби), Value Objects.
- Ледача ініціалізація — через **плагіни/проксі** контейнера, а не вручну в домені.

**Уникати:**
- Service Locator у домені, приховані синглтони, глобальний стан, фасади, надмірний static.

---

## 12) Швидкі шаблони

**Клас сервісу:**
```php
<?php declare(strict_types=1);

namespace App\Service;

final class SampleService
{
    public function __construct(
        private readonly DependencyA $a,
        private readonly DependencyB $b,
    ) {}

    public function doWork(): Result
    {
        // ...
    }
}
```

**Інтерфейс + реалізація:**
```php
namespace App\Contract;

interface SluggerInterface { public function slugify(string $s): string; }

namespace App\Infra;

use App\Contract\SluggerInterface;

final class AsciiSlugger implements SluggerInterface
{
    public function slugify(string $s): string { /* ... */ }
}
```

**Фабрика (Singularity):**
```php
namespace App\Factory;

use Concept\Singularity\Factory\ServiceFactory;

final class SluggerFactory extends ServiceFactory
{
    public function create(array $args = []): object
    {
        return $this->createService(\App\Infra\AsciiSlugger::class, $args);
    }
}
```

**Конфіг (preference):**
```json
"singularity": {
  "preference": {
    "App\\Contract\\SluggerInterface": { "class": "App\\Infra\\AsciiSlugger" }
  }
}
```

**PHPUnit:**
```php
final class AsciiSluggerTest extends TestCase
{
    /** @dataProvider samples */
    public function test_slugify(string $in, string $out): void
    {
        $this->assertSame($out, (new AsciiSlugger())->slugify($in));
    }

    public static function samples(): iterable
    {
        yield ['Hello World', 'hello-world'];
    }
}
```

---

## 13) Приклад робочого флоу (Copilot)

1. Користувач додає контракт `PaymentGatewayInterface`.  
2. Copilot пропонує реалізацію `StripePaymentGateway`, тест `StripePaymentGatewayTest`.  
3. Додає `singularity.preference` маппінг на реалізацію.  
4. Пропонує фабрику `PaymentGatewayFactory` для ін’єкції у контролери.  
5. Оновлює README з прикладом використання та FAQ.  
6. Створює PR із `feat(payment): add Stripe gateway` + чек‑лист.

---

## 14) Чек‑лист для кожного згенерованого файлу

- [ ] `declare(strict_types=1);`  
- [ ] Імпорти впорядковані, типи скрізь  
- [ ] Жодних неузгоджених `private`, якщо очікується розширення  
- [ ] Тести покривають позитивні/негативні гілки  
- [ ] Оновлено конфіг/README, якщо з’явився публічний API  
- [ ] Винятки з інформативними меседжами  
- [ ] Відсутній прихований доступ до контейнера з домену

---

## 15) Приклади підказок (prompt) для Copilot

- “Створи `FooInterface` і `FooService`, додай preference у `singularity` та тест із data‑provider.”  
- “Згенеруй `ServiceFactory` для `BarService` і приклад використання без Service Locator.”  
- “Перепиши клас під DI: конструкторні залежності, жодних статиків, додай PHPDoc.”  
- “Додай приклад `#[Injector]` для пост‑ініціалізації у класі `BazService`.”  
- “Побудуй `phpunit.xml.dist` і 2 тести для `DotQuery::dql()` з edge‑кейсами.”

---

**Коротко:** дотримуйся PSR, пиши код розширювано, керуй життєвим циклом через Singularity, не використовуй Service Locator, покривай тестами, оновлюй конфіг і документацію.  
Copilot має допомагати системно: **контракт → реалізація → тести → конфіг → фабрика → README → PR**.
