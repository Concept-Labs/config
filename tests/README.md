# Tests

This directory contains comprehensive test coverage for the Concept\Config library using both PHPUnit and Pest testing frameworks.

## Test Structure

```
tests/
├── Unit/               # Unit tests for individual components
│   ├── ConfigTest.php
│   ├── ConfigPestTest.php
│   ├── FactoryTest.php
│   ├── StaticFactoryTest.php
│   ├── FactoriesPestTest.php
│   ├── AdaptersPestTest.php
│   └── Resource/
│       └── Adapter/
│           ├── JsonAdapterTest.php
│           └── PhpAdapterTest.php
├── Feature/            # Integration/feature tests
│   └── ConfigIntegrationTest.php
├── Fixtures/           # Test fixtures (gitignored)
└── Pest.php           # Pest configuration

```

## Running Tests

### Run All Tests (PHPUnit + Pest)

```bash
./vendor/bin/pest
```

### Run Only PHPUnit Tests

PHPUnit tests are named with `*Test.php` pattern (excluding `*PestTest.php`):

```bash
# Note: Pest intercepts PHPUnit, so run Pest instead
./vendor/bin/pest
```

### Run Specific Test Suites

```bash
# Run only unit tests
./vendor/bin/pest tests/Unit

# Run only feature tests
./vendor/bin/pest tests/Feature

# Run specific test file
./vendor/bin/pest tests/Unit/ConfigTest.php
```

### Run with Coverage

```bash
./vendor/bin/pest --coverage
```

## Test Coverage

The test suite covers:

### Core Functionality (30 PHPUnit tests + 18 Pest tests)
- **Config class**: Construction, getters, setters, dot notation access
- **Data manipulation**: load, import, export, hydrate
- **Context management**: withContext, getContext
- **Node operations**: node creation and isolation
- **Utility methods**: reset, prototype, clone, iteration

### Factory Patterns (33 PHPUnit tests + 8 Pest tests)
- **StaticFactory**: create, fromFile, fromFiles, fromGlob, compile
- **Builder Factory**: fluent API, plugin registration, context configuration

### Resource Adapters (31 PHPUnit tests + 16 Pest tests)
- **JsonAdapter**: encode/decode, read/write, glob patterns, priority handling
- **PhpAdapter**: encode (var_export), read (require), write, special character handling

### Integration Tests (7 Feature tests)
- End-to-end workflows
- Multiple format support
- Factory build patterns
- Node isolation
- Configuration export/import

### Total Test Count

- **143 tests** in total
- **259 assertions**
- **PHPUnit-style tests**: 94 test methods
- **Pest-style tests**: 42 test methods
- **Feature/Integration tests**: 7 tests

## Writing Tests

### PHPUnit Style

```php
public function testConfigCreation(): void
{
    $config = new Config(['key' => 'value']);
    
    $this->assertInstanceOf(ConfigInterface::class, $config);
    $this->assertEquals('value', $config->get('key'));
}
```

### Pest Style

```php
it('creates config with data', function () {
    $config = new Config(['key' => 'value']);
    
    expect($config)->toBeInstanceOf(ConfigInterface::class)
        ->and($config->get('key'))->toBe('value');
});
```

## Test Fixtures

Test fixtures are created in `tests/Fixtures/` during test execution and are automatically cleaned up after each test. The fixtures directory is gitignored to prevent committing test data.

## Continuous Integration

These tests can be integrated into CI/CD pipelines:

```yaml
# Example GitHub Actions workflow
- name: Run Tests
  run: ./vendor/bin/pest --coverage
```

## Requirements

- PHP 8.2+
- PHPUnit 10.0+
- Pest 2.0+

## Contributing

When adding new functionality:

1. Write tests in both PHPUnit and Pest styles when appropriate
2. Ensure all tests pass before submitting
3. Aim for high code coverage
4. Add integration tests for complex workflows
