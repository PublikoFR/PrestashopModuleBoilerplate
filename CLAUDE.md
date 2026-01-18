# PrestaShop 8 Module Development Guide

## General Context

This project involves developing a module for PrestaShop 8. The module must be professional, follow PrestaShop conventions, and be ready for potential commercialization.

## Documentation and Resources

**IMPORTANT**: Always use Context7 to consult the official PrestaShop 8 documentation before implementing a feature. Check best practices, naming conventions, and recommended patterns.

```bash
# Search in PrestaShop documentation
context7 search "your search query"
```

## Compatibility

- **Priority version**: PrestaShop 8.x
- **Extended compatibility**: PrestaShop 1.7 to PrestaShop 9
- Use cross-version compatible methods when possible
- Test deprecated features and provide alternatives

## Module Structure

Strictly follow the PrestaShop file structure:

```
mymodule/
├── mymodule.php           # Main module file
├── config.xml             # Configuration (optional)
├── index.php              # Directory protection
├── logo.png              # Logo 57x57px
├── translations/         # Translation files
│   ├── fr.php
│   ├── en.php
│   └── index.php
├── views/
│   ├── templates/
│   │   ├── admin/        # Back office templates
│   │   ├── hook/         # Front office hook templates
│   │   └── index.php
│   ├── css/
│   ├── js/
│   └── img/
├── controllers/
│   ├── admin/
│   ├── front/
│   └── index.php
├── classes/
│   └── index.php
├── sql/
│   ├── install.php
│   ├── uninstall.php
│   └── index.php
├── upgrade/
│   └── index.php
├── build.sh              # Build script
└── README.md

```

## Agents
- Always check the availables agens to and hire them as much as possible to do parallels tasks

## Overrides

### ⚠️ Avoid overrides as much as POSSIBLE

**General rule**: Never use overrides except in EXTREME cases where no other solution exists.

### Why avoid overrides?

- **Conflicts**: Two modules cannot override the same class/method
- **Maintenance**: Complicate PrestaShop updates
- **Performance**: Impact performance
- **Instability**: Can cause bugs difficult to diagnose
- **Marketplace**: Often rejected for validation on PrestaShop Addons

### Alternatives to overrides

```php
// ✗ BAD: Override
class Product extends ProductCore {
    // Modifying the Product class
}

// ✓ GOOD: Use hooks
public function hookActionProductSave($params) {
    // Logic executed when saving the product
}

// ✓ GOOD: Use dispatchers
public function hookActionDispatcher($params) {
    // Intercept specific actions
}

// ✓ GOOD: Create your own classes
class MyModuleProductHelper {
    public static function customLogic($id_product) {
        // Custom logic
    }
}
```

### When an override MAY be acceptable

Only if **ALL** these conditions are met:
1. No hook allows achieving the result
2. No viable alternative (dispatcher, service, etc.)
3. The modification is minimal and well documented
4. The client/project is informed of the risks
5. Internal use only (not for marketplace)

### If override is necessary

```php
// Always inherit from Core class
class Product extends ProductCore {
    // ALWAYS call parent
    public function add($autodate = true, $null_values = false)
    {
        // Logic BEFORE

        $result = parent::add($autodate, $null_values);

        // Logic AFTER

        return $result;
    }
}
```

**Mandatory documentation**:
- Comment explaining WHY the override is necessary
- Alternatives explored and why they don't work
- Tested PrestaShop version
- Known risks

## PrestaShop Coding Conventions

### Naming
- **Main class**: Module name in CamelCase (e.g., `MyModule`)
- **Technical name**: All lowercase, no spaces (e.g., `mymodule`)
- **Classes**: PascalCase
- **Methods**: camelCase
- **Constants**: UPPER_SNAKE_CASE
- **Variables**: snake_case for PrestaShop, camelCase acceptable

### Code Standards
- Follow PSR-2 for PHP code style
- Indentation: 4 spaces
- Braces on new line for classes and methods
- Always type parameters and returns (PHP 7.2+)
- Use namespaces for PS 8+

### Security
- **Always** escape outputs: `Tools::safeOutput()`, `{$var|escape:'html':'UTF-8'}`
- Validate inputs: `Validate::isInt()`, `Validate::isEmail()`, etc.
- Use tokens for forms: `Tools::getAdminTokenLite()`
- SQL protection: use `Db::getInstance()->escape()` or prepared queries
- Add `index.php` in every folder: `header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');exit;`

## Multilingual Support

The module MUST be fully multilingual:

### Translations
```php
// In PHP code
$this->l('Text to translate');

// In Smarty templates
{l s='Text to translate' mod='mymodule'}
```

### Multilingual database fields
```php
// Use _lang in table names for translated fields
CREATE TABLE IF NOT EXISTS `PREFIX_mymodule_item` (
  `id_item` int(11) NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_item`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_mymodule_item_lang` (
  `id_item` int(11) NOT NULL,
  `id_lang` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  PRIMARY KEY (`id_item`, `id_lang`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;
```

### Multilingual configuration
```php
// Save multilingual configs
Configuration::updateValue('MY_CONFIG', $values, true); // true = html

// Get by language
Configuration::get('MY_CONFIG', $id_lang);
```

## Hooks

Declare all hooks used in `install()`:

```php
public function install()
{
    return parent::install()
        && $this->registerHook('displayHeader')
        && $this->registerHook('displayBackOfficeHeader')
        && $this->registerHook('actionObjectAddAfter');
}

public function hookDisplayHeader($params)
{
    $this->context->controller->addCSS($this->_path.'views/css/front.css');
    $this->context->controller->addJS($this->_path.'views/js/front.js');
}
```

## Configuration and Forms

Use PrestaShop helpers for forms:

```php
public function getContent()
{
    $output = '';

    if (Tools::isSubmit('submit'.$this->name)) {
        $output .= $this->postProcess();
    }

    return $output.$this->renderForm();
}

protected function renderForm()
{
    $helper = new HelperForm();
    // Helper configuration...
    return $helper->generateForm(array($fields_form));
}
```

## Database

### CRITICAL Rules

**⚠️ NEVER modify PrestaShop native tables!**

- **ABSOLUTE PROHIBITION**: Never do `ALTER TABLE` on an existing PrestaShop table
- **ALWAYS** create your own tables prefixed by the module name
- **OPTIMIZATION**: Create the LEAST number of tables possible
  - Prefer one table with more columns rather than several small tables
  - Use `_lang` tables only if necessary for multilingual
  - Group related data in the same table when logical
- **JOINS**: Use foreign keys (`id_product`, `id_customer`, etc.) to link to PrestaShop tables

### Table naming examples

```
✓ CORRECT:
`ps_mymodule_config`
`ps_mymodule_data`
`ps_mymodule_data_lang`

✗ INCORRECT:
`ps_product` (modifying native table)
`ps_mymodule_config`
`ps_mymodule_config_parameters`
`ps_mymodule_settings`
→ These 3 tables could be just one!
```

### Installation
```php
public function install()
{
    return parent::install()
        && $this->installDB()
        && $this->registerHook('hookName');
}

private function installDB()
{
    $sql = array();

    // Create the LEAST number of tables possible
    // Group related data in the same table
    $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mymodule_data` (
        `id_data` int(11) NOT NULL AUTO_INCREMENT,
        `id_product` int(11) DEFAULT NULL,
        `id_customer` int(11) DEFAULT NULL,
        `config_param1` varchar(255),
        `config_param2` text,
        `active` tinyint(1) NOT NULL DEFAULT 1,
        `date_add` datetime NOT NULL,
        `date_upd` datetime NOT NULL,
        PRIMARY KEY (`id_data`),
        KEY `id_product` (`id_product`),
        KEY `id_customer` (`id_customer`)
    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

    // _lang table only if necessary
    $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mymodule_data_lang` (
        `id_data` int(11) NOT NULL,
        `id_lang` int(11) NOT NULL,
        `name` varchar(255) NOT NULL,
        `description` text,
        PRIMARY KEY (`id_data`, `id_lang`)
    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

    foreach ($sql as $query) {
        if (!Db::getInstance()->execute($query)) {
            return false;
        }
    }
    return true;
}
```

### Uninstallation
```php
public function uninstall()
{
    return $this->uninstallDB()
        && parent::uninstall();
}

private function uninstallDB()
{
    $sql = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'mymodule_table`';
    return Db::getInstance()->execute($sql);
}
```

## Smarty Templates

### Front Office
```smarty
{* views/templates/hook/myhook.tpl *}
<div class="mymodule-container">
    <h2>{l s='My title' mod='mymodule'}</h2>
    <p>{$my_variable|escape:'html':'UTF-8'}</p>
</div>
```

### Back Office
```smarty
{* views/templates/admin/configure.tpl *}
<div class="panel">
    <div class="panel-heading">{l s='Configuration' mod='mymodule'}</div>
    <div class="panel-body">
        {$form}
    </div>
</div>
```

## Install Script (install.sh)

- The `install.sh` script handles module installation, synchronization, and building. It provides an interactive menu with arrow key navigation and CLI options.
- When you init a project from this boilerplate, simply rename the variables in .env.install

### Configuration for a New Module

When creating a new module from this boilerplate, update these variables at the top of `install.sh`:

```bash
# =============================================================================
# Configuration - À MODIFIER pour chaque nouveau module
# =============================================================================
PRESTASHOP_PATH="/path/to/your/prestashop"    # Path to local PrestaShop
DOCKER_CONTAINER="your_container_name"         # Docker container name
MODULE_NAME="yourmodulename"                   # Module technical name (no spaces)
```

### Interactive Menu

Run `./install.sh` without arguments to open the interactive menu:

### CLI Options

```bash
./install.sh --install      # Installer / Réinstaller
./install.sh --uninstall    # Désinstaller
./install.sh --reinstall    # Désinstaller puis Réinstaller
./install.sh --delete       # Supprimer
./install.sh --reset        # Supprimer puis Réinstaller
./install.sh --restore      # Restaurer un backup
./install.sh --cache        # Vider le cache
./install.sh --restart      # Restart Docker Containers
./install.sh --zip          # Build ZIP
./install.sh --help         # Show help
```

### First Time Setup

```bash
# Make the script executable (one time only)
chmod +x install.sh
```

## Testing and Validation

### Manual Tests
- [ ] Module installation/uninstallation
- [ ] Configuration saved correctly
- [ ] Hooks functional
- [ ] Multilingual (test FR + EN minimum)
- [ ] No PHP errors
- [ ] Compatible with PS 1.7, 8 and 9 (if possible)

### Code Validation
- [ ] No forgotten `var_dump` or `print_r`
- [ ] All outputs are escaped
- [ ] SQL queries are secured
- [ ] index.php present everywhere
- [ ] Complete translations
- [ ] Logo present (57x57px)

### PrestaShop Validator
Use the official validator before publication:
https://validator.prestashop.com/

## Documentation to Produce

### README.md
Include in each module:
- Module description
- Features
- Installation
- Configuration
- Compatibility
- Changelog
- Support

### Code Comments
- DocBlocks for all classes and methods
- PHP 7.2+ type annotations
- Explanations for complex logic

## PrestaShop 9 Compatibility (Symfony Architecture)

### Overview

PrestaShop 9 uses a modern Symfony-based architecture. This boilerplate supports **both** legacy (PS 1.7-8) and modern (PS 9+) approaches.

### File Structure for PS9

```
src/
├── Controller/
│   └── Admin/
│       └── BoilerplateItemController.php    # Symfony controller
├── Grid/
│   ├── Definition/
│   │   └── Factory/
│   │       └── BoilerplateItemGridDefinitionFactory.php
│   └── Query/
│       └── BoilerplateItemQueryBuilder.php
├── Form/
│   ├── BoilerplateItemType.php              # Symfony form type
│   ├── BoilerplateItemDataProvider.php      # Form data provider
│   └── BoilerplateItemDataHandler.php       # Form data handler
└── Database/
    └── MigrationManager.php                 # DB migration system
config/
├── services.yml                             # Symfony services
└── routes.yml                               # Symfony routes
```

### Services Configuration (config/services.yml)

```yaml
services:
  _defaults:
    public: true
    autowire: true
    autoconfigure: true

  # Controller
  MyModule\Controller\Admin\MyController:
    tags: ['controller.service_arguments']

  # Grid Definition
  MyModule\Grid\Definition\Factory\MyGridDefinitionFactory:
    parent: 'prestashop.core.grid.definition.factory.abstract_grid_definition'

  # Grid Query Builder
  MyModule\Grid\Query\MyQueryBuilder:
    arguments:
      - '@doctrine.dbal.default_connection'
      - '%database_prefix%'
      - '@prestashop.core.grid.query.doctrine_search_criteria_applicator'
      - '@=service("prestashop.adapter.legacy.context").getLanguage().id'
      - '@=service("prestashop.adapter.legacy.context").getContext().shop.id'
```

### Routes Configuration (config/routes.yml)

```yaml
admin_my_items_index:
  path: /my-items
  methods: [GET]
  defaults:
    _controller: 'MyModule\Controller\Admin\MyController::indexAction'
    _legacy_controller: AdminMyItems
    _legacy_link: AdminMyItems

admin_my_items_create:
  path: /my-items/new
  methods: [GET, POST]
  defaults:
    _controller: 'MyModule\Controller\Admin\MyController::createAction'
    _legacy_controller: AdminMyItems
```

### Controller with AdminSecurity (PS9+)

```php
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;

class MyController extends PrestaShopAdminController
{
    #[AdminSecurity("is_granted('read', request.get('_legacy_controller'))")]
    public function indexAction(
        Request $request,
        GridFactoryInterface $myGridFactory
    ): Response {
        $grid = $myGridFactory->getGrid(
            $this->buildFiltersFromRequest($request, 'my_grid')
        );

        return $this->render('@Modules/mymodule/views/templates/admin/ps9/index.html.twig', [
            'grid' => $this->presentGrid($grid),
        ]);
    }
}
```

### Version Detection

```php
// In main module file
public function isPs9(): bool
{
    return version_compare(_PS_VERSION_, '9.0.0', '>=');
}

// Usage
if ($this->isPs9()) {
    // Use Symfony routing
} else {
    // Use legacy controller
}
```

---

## Database Migrations

### Why Migrations?

When users purchase and install your module, they have data in the database. When you release an update with schema changes, you **MUST NOT** lose their data. The migration system handles this safely.

### Migration System Overview

```
sql/
├── install.sql                 # Initial schema (fresh install)
├── uninstall.sql               # Cleanup on uninstall
└── migrations/
    ├── 1.1.0.php               # Migration to v1.1.0
    ├── 1.1.0.rollback.php      # Rollback for v1.1.0
    ├── 1.2.0.php               # Migration to v1.2.0
    ├── 1.2.0.rollback.php      # Rollback for v1.2.0
    └── index.php               # Security file
```

### How Migrations Work

1. **Fresh Install**: Creates tables from `installDb()`, sets version to current
2. **Upgrade**: PrestaShop calls `upgrade($oldVersion)` → runs pending migrations
3. **Version Tracking**: `MODULENAME_DB_VERSION` config stores current DB version

### Creating a Migration File

**File**: `sql/migrations/{version}.php`

```php
<?php
/**
 * Migration 1.1.0
 *
 * Description of changes
 */

return [
    // Use PREFIX_ placeholder (replaced with actual DB prefix)
    "ALTER TABLE `PREFIX_mymodule_item`
     ADD COLUMN IF NOT EXISTS `slug` VARCHAR(255) DEFAULT NULL
     AFTER `position`",

    "CREATE INDEX IF NOT EXISTS `idx_slug`
     ON `PREFIX_mymodule_item` (`slug`)",
];
```

### Migration Best Practices

```php
// ✓ GOOD: Safe operations
"ALTER TABLE `PREFIX_table` ADD COLUMN IF NOT EXISTS `col` VARCHAR(255)"
"CREATE INDEX IF NOT EXISTS `idx_name` ON `PREFIX_table` (`col`)"
"ALTER TABLE `PREFIX_table` MODIFY COLUMN `col` VARCHAR(512)"

// ✗ BAD: Dangerous operations
"DROP COLUMN `col`"              // Data loss!
"TRUNCATE TABLE `PREFIX_table`"  // Data loss!
"DROP TABLE `PREFIX_table`"      // Data loss!
```

### Migration Guidelines

| Rule | Description |
|------|-------------|
| **One change per query** | Easier error handling and debugging |
| **Use IF NOT EXISTS** | Safe re-runs, idempotent operations |
| **PREFIX_ placeholder** | Replaced with actual `_DB_PREFIX_` |
| **Create rollback file** | For reversible changes |
| **Test thoroughly** | On fresh install AND upgrade scenarios |
| **Document changes** | Comments explaining why |

### Rollback Files (Optional but Recommended)

**File**: `sql/migrations/{version}.rollback.php`

```php
<?php
return [
    "DROP INDEX IF EXISTS `idx_slug` ON `PREFIX_mymodule_item`",
    "ALTER TABLE `PREFIX_mymodule_item` DROP COLUMN IF EXISTS `slug`",
];
```

### Integration in Main Module

```php
use MyModule\Database\MigrationManager;

class MyModule extends Module
{
    private $migrationManager;

    protected function getMigrationManager(): MigrationManager
    {
        if ($this->migrationManager === null) {
            $this->migrationManager = new MigrationManager(__DIR__);
        }
        return $this->migrationManager;
    }

    public function install()
    {
        $result = parent::install() && $this->installDb();

        if ($result) {
            // Set initial DB version
            $this->getMigrationManager()->setCurrentVersion($this->version);
        }

        return $result;
    }

    public function upgrade($oldVersion)
    {
        try {
            $this->getMigrationManager()->runMigrations($this->version);
            return true;
        } catch (\Exception $e) {
            PrestaShopLogger::addLog($e->getMessage(), 3);
            return false;
        }
    }

    public function uninstall()
    {
        Configuration::deleteByName('MYMODULE_DB_VERSION');
        return parent::uninstall();
    }
}
```

### Common Migration Scenarios

#### Adding a new column

```php
// Migration 1.1.0.php
return [
    "ALTER TABLE `PREFIX_mymodule_item`
     ADD COLUMN IF NOT EXISTS `new_field` VARCHAR(255) DEFAULT NULL",
];
```

#### Adding multilingual field

```php
// Migration 1.2.0.php
return [
    "ALTER TABLE `PREFIX_mymodule_item_lang`
     ADD COLUMN IF NOT EXISTS `meta_title` VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE `PREFIX_mymodule_item_lang`
     ADD COLUMN IF NOT EXISTS `meta_description` VARCHAR(512) DEFAULT NULL",
];
```

#### Adding a new table

```php
// Migration 1.3.0.php
return [
    "CREATE TABLE IF NOT EXISTS `PREFIX_mymodule_category` (
        `id_category` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_parent` INT(10) UNSIGNED DEFAULT 0,
        `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
        PRIMARY KEY (`id_category`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];
```

#### Modifying column type (safe)

```php
// Migration 1.4.0.php
return [
    // Increase VARCHAR size (safe, no data loss)
    "ALTER TABLE `PREFIX_mymodule_item`
     MODIFY COLUMN `name` VARCHAR(512) NOT NULL",
];
```

---

## Resources

- PrestaShop 8 Documentation: https://devdocs.prestashop-project.org/8/
- PrestaShop 9 Documentation: https://devdocs.prestashop-project.org/9/
- Module Documentation: https://devdocs.prestashop-project.org/8/modules/
- Grid Documentation: https://devdocs.prestashop-project.org/8/development/components/grid/
- Coding Standards: https://devdocs.prestashop-project.org/8/development/coding-standards/
- Context7: Always use to search in official documentation

## Final Checklist Before Build

- [ ] Version incremented in main file
- [ ] All translations present
- [ ] build.sh up to date with current structure
- [ ] Complete README.md
- [ ] Tests performed on PS 8
- [ ] Code validated (no errors, secured)
- [ ] Logo present
- [ ] All index.php in place
- [ ] Multilingual configuration functional
- [ ] **No PrestaShop native tables modified**
- [ ] **Minimal number of tables created**
- [ ] **No overrides (or justified and documented)**
- [ ] ZIP generated and tested

---

**Note**: This guide should be used as a reference for each PrestaShop module developed. Adapt according to the specific needs of each module, but always respect PrestaShop conventions and best practices.
