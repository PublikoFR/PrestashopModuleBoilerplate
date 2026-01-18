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

**⚠️ TOUJOURS créer une migration lors de modifications BDD une fois que le module a été rendu publi (dépassant la version 1.0.0) !**

- **OBLIGATOIRE** : Créer un fichier de migration dans `sql/migrations/` pour TOUTE modification de schéma :
  - Ajout d'une nouvelle table
  - Ajout/modification/suppression d'une colonne
  - Ajout/modification d'un index
- **Format** : `sql/migrations/X.X.X.php` (version du module)
- **Contenu** : Tableau de requêtes SQL avec `PREFIX_` remplacé automatiquement
- **Raison** : Les utilisateurs ayant déjà installé le module n'auront PAS les nouvelles tables/colonnes sans migration !

```php
// sql/migrations/1.1.0.php
return [
    'CREATE TABLE IF NOT EXISTS `PREFIX_mymodule_newtable` (...)',
    'ALTER TABLE `PREFIX_mymodule_data` ADD COLUMN `new_field` VARCHAR(255) AFTER `existing_field`',
];
```
- **OPTIMIZATION**: Create the LEAST number of tables possible
  - Prefer one table with more columns rather than several small tables
  - Use `_lang` tables only if necessary for multilingual
  - Group related data in the same table when logical
- **JOINS**: Use foreign keys (`id_product`, `id_customer`, etc.) to link to PrestaShop tables

### Table naming convention (Publiko modules)

**Préfixe obligatoire** : `pko_` (pour Publiko) après le préfixe PrestaShop.

Format : `{_DB_PREFIX_}pko_{nomtable}`

```
✓ CORRECT (modules Publiko):
`ps_pko_siretverif_data`
`ps_pko_siretverif_data_lang`
`ps_pko_mymodule_config`

✗ INCORRECT:
`ps_product` (table native modifiée)
`ps_mymodule_data` (manque préfixe pko_)
`ps_pko_config` + `ps_pko_settings` (2 tables au lieu d'une)
```

### Utilisation en PHP

```php
// Définir le préfixe Publiko
const PKO_PREFIX = 'pko_';

// Création de table
$sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::PKO_PREFIX . 'mymodule_data` (...)';

// Requête SELECT
$result = Db::getInstance()->getValue(
    'SELECT * FROM `' . _DB_PREFIX_ . 'pko_mymodule_data` WHERE id = ' . (int)$id
);
```

### Installation
```php
// Préfixe Publiko pour les tables
const PKO_PREFIX = 'pko_';

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
    // Format: ps_pko_modulename_tablename
    $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.self::PKO_PREFIX.'mymodule_data` (
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
    $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.self::PKO_PREFIX.'mymodule_data_lang` (
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
    $sql = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.self::PKO_PREFIX.'mymodule_data`';
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

## Build Script (build.sh)

A `build.sh` script must ALWAYS be created and kept up to date with the module structure:

```bash
#!/bin/bash

# Module name
MODULE_NAME="mymodule"
VERSION=$(grep "this->version" ${MODULE_NAME}.php | cut -d"'" -f4)
ZIP_NAME="${MODULE_NAME}_v${VERSION}.zip"

# Cleanup
rm -f ${ZIP_NAME}

# List of files and folders to include
# IMPORTANT: Update this list if the structure changes
zip -r ${ZIP_NAME} \
    ${MODULE_NAME}.php \
    index.php \
    logo.png \
    config.xml \
    README.md \
    translations/ \
    views/ \
    controllers/ \
    classes/ \
    sql/ \
    upgrade/ \
    -x "*.git*" "*.DS_Store" "*build.sh" "*Claude.md" "*.zip"

echo "✓ Module packaged: ${ZIP_NAME}"
ls -lh ${ZIP_NAME}
```

### Using the build script
```bash
# Make the script executable (one time only)
chmod +x build.sh
```

```bash
# Generate the module ZIP
./build.sh
```

**IMPORTANT**: If files/folders are added or removed in the module, update the list in the `build.sh` script.

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

## Resources

- PrestaShop 8 Documentation: https://devdocs.prestashop-project.org/8/
- Module Documentation: https://devdocs.prestashop-project.org/8/modules/
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
