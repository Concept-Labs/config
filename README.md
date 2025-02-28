# Concept Labs Simple Config
**Documentation is in progress**

Welcome to `Concept-Labs/config` — a flexible and efficient configuration management library for PHP. This package is designed as part of the `Singularity` framework but can be used as a standalone component.

## Key Features
- **Path-Based Access**: Access configuration data using dot-separated paths (e.g., `path.to.key`).
- **Plugin System**: Extensible plugin architecture for processing configuration values (e.g., `${var}` substitution).
- **Compilation**: Optimize performance by compiling configurations into JSON for fast loading.
- **Caching**: Integration with PSR-16 (`SimpleCache`) for caching processed values.
- **Context Support**: Dynamic variable substitution using a context (e.g., `${HOME}`).

## Installation
Install the package via Composer:

```bash
composer require concept-labs/config
```
**Requires PHP >= 8.0.
**Usage**
- Initialization
```php
use Concept\Config\Config;
$config = new Config([
    'context' => ['root' => '/app'],
    'path' => [
        'to' => '${root}/var/.cache'
    ]
]);
echo $config->get('path.to'); // Outputs: '/app/var/.cache'
```
- Adding Plugins
Plugins allow customization of configuration value processing:
```php
use Concept\Config\Plugin\VarPlugin;

$config->addPlugin(new VarPlugin());
$config->set('user', '${root}/users');
echo $config->get('user'); // Outputs: '/app/users'
```
 - Loading Configuration
Load configuration from various sources (file, array, JSON string):
```php
// Load from a JSON file
$config->load('/path/to/config.json');
// Load and merge with existing data
$config->load(['new' => 'data'], true);
// Compile and export for performance
$config->export('/path/to/compiled.json');
// Load compiled configuration
$config->load('/path/to/compiled.json', false); // Replace existing data
```
- Checking Key Existence
```php
if ($config->has('path')) {
    echo "Key 'path' exists!";
}
//Note: The has() method checks keys in memory ($data or $compiledData) and does not read file contents.
```

**Performance Optimization**
- JSON Format: Configurations are exported to JSON using json_encode, which is faster and more compact than var_export + include.
- Single Plugin Chain: PluginManager builds the processing chain once and reuses it for efficiency.
- Caching: Leverage PSR-16 caching to store processed values.
- 
**Contributing**
- Clone the repository:
```bash
git clone https://github.com/Concept-Labs/config.git
```
- Install dependencies:
```bash
composer install
```

- Submit a Pull Request with your changes.
  
**License**
This project is licensed under the MIT License.

**Acknowledgements**
Developed as part of the Singularity framework by the Concept Labs team.
