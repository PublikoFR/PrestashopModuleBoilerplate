# PrestaShop 8 Development Guide

## Context

Professional PrestaShop 8 module, ready for commercialization. Extended compatibility PS 1.7 to PS 9.

## Agents

Always check available agents and use them in parallel as much as possible.

## Documentation

**Always** use Context7 before implementing a feature:
```bash
context7 search "your query"
```

## Module Structure

```
mymodule/
├── mymodule.php          # Main file
├── config.xml            # Configuration (optional)
├── index.php             # Directory protection
├── logo.png              # Logo 57x57px
├── translations/         # Translations (fr.php, en.php)
├── views/
│   ├── templates/admin/  # Back-office templates
│   ├── templates/hook/   # Front-office templates
│   ├── css/, js/, img/
├── controllers/admin/, front/
├── classes/
├── sql/
│   ├── install.php
│   ├── uninstall.php
│   └── migrations/       # DB migrations
└── upgrade/
```

## Coding Conventions

### Naming
- **Main class**: CamelCase (`MyModule`)
- **Technical name**: lowercase no spaces (`mymodule`)
- **Classes**: PascalCase | **Methods**: camelCase
- **Constants**: UPPER_SNAKE_CASE | **Variables**: snake_case

### Standards
- **All code and comments in English**
- PSR-2, 4 spaces indentation
- PHP 7.2+ typing required
- Namespaces for PS 8+

### Security
- Escape outputs: `Tools::safeOutput()`, `{$var|escape:'html':'UTF-8'}`
- Validate inputs: `Validate::isInt()`, `Validate::isEmail()`
- Form tokens: `Tools::getAdminTokenLite()`
- SQL: `Db::getInstance()->escape()` or prepared queries
- `index.php` in every folder

## Database

### Critical Rules

**FORBIDDEN**: Modify native PrestaShop tables (`ALTER TABLE` on existing table)

**REQUIRED**: Create own tables with `pko_` prefix (Publiko)

### Table Naming

Format: `{_DB_PREFIX_}pko_{tablename}`

```
✓ ps_pko_mymodule_data
✓ ps_pko_mymodule_data_lang
✗ ps_product (native table)
✗ ps_mymodule_data (missing pko_)
```

### Optimization
- Minimum tables possible
- Group related data
- `_lang` tables only if multilingual needed
- Foreign keys to PS tables (`id_product`, `id_customer`)

### Migrations

**REQUIRED** after version 1.0.0 for any DB modification:

```php
// sql/migrations/1.1.0.php
return [
    'ALTER TABLE `PREFIX_pko_mymodule_data` ADD COLUMN `new_field` VARCHAR(255)',
];
```

### DB Installation

```php
const PKO_PREFIX = 'pko_';

private function installDB()
{
    $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.self::PKO_PREFIX.'mymodule_data` (
        `id_data` int(11) NOT NULL AUTO_INCREMENT,
        `active` tinyint(1) NOT NULL DEFAULT 1,
        `date_add` datetime NOT NULL,
        PRIMARY KEY (`id_data`)
    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

    return Db::getInstance()->execute($sql);
}
```

## Multilingual

```php
// PHP
$this->l('Text to translate');

// Smarty
{l s='Text to translate' mod='mymodule'}

// Multilingual config
Configuration::updateValue('MY_CONFIG', $values, true);
Configuration::get('MY_CONFIG', $id_lang);
```

## Hooks

```php
public function install()
{
    return parent::install()
        && $this->registerHook('displayHeader')
        && $this->registerHook('actionObjectAddAfter');
}

public function hookDisplayHeader($params)
{
    $this->context->controller->addCSS($this->_path.'views/css/front.css');
}
```

## Overrides

### Avoid as Much as Possible

Issues: module conflicts, difficult maintenance, Marketplace rejection.

### Alternatives
```php
// ✗ Override
class Product extends ProductCore { }

// ✓ Hooks
public function hookActionProductSave($params) { }

// ✓ Own classes
class MyModuleProductHelper { }
```

### If Unavoidable
- Always call `parent::`
- Document: why, tested alternatives, PS version, risks

## Install Script (install.sh)

Unified script for modules AND themes. Config in `.env.install`:

```bash
TYPE="module"  # or "theme"
PRESTASHOP_PATH="/path/to/prestashop"
DOCKER_CONTAINER="container_name"
MODULE_NAME="mymodule"  # or NAME for themes
```

### Commands
```bash
./install.sh              # Interactive menu
./install.sh --install    # Install
./install.sh --update-script  # Update script
./install.sh --help       # Full help
```

## Final Checklist

- [ ] Version incremented
- [ ] Translations complete
- [ ] Tests on PS 8 (+ PS 1.7/9 if possible)
- [ ] Code secured, no var_dump
- [ ] Logo 57x57px present
- [ ] index.php everywhere
- [ ] No native table modified
- [ ] No overrides (or justified)
- [ ] ZIP tested

## Resources

- [PS 8 Documentation](https://devdocs.prestashop-project.org/8/)
- [Module Documentation](https://devdocs.prestashop-project.org/8/modules/)
- [PrestaShop Validator](https://validator.prestashop.com/)
