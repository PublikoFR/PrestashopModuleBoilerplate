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

## Admin Tabs (Menu)

Publiko modules share a common parent tab in the admin menu ("Configure" section).

### Structure

```
Configure (section)
└── Publiko (AdminPublikoParent)
    ├── Module A (AdminPublikoModuleA)
    └── Module B (AdminPublikoModuleB)
```

### Tab Installation

```php
protected function installAdminTab(): bool
{
    $idParent = $this->getOrCreatePublikoParentTab();

    $tab = new Tab();
    $tab->active = 1;
    $tab->class_name = 'AdminPublikoMyModule';  // Must match the controller
    $tab->module = $this->name;
    $tab->id_parent = $idParent;
    $tab->icon = 'category';  // Material icon name

    foreach (Language::getLanguages(false) as $lang) {
        $tab->name[$lang['id_lang']] = 'My Module';
    }

    return $tab->add();
}
```

### Get or Create Publiko Parent Tab

```php
protected function getOrCreatePublikoParentTab(): int
{
    $className = 'AdminPublikoParent';
    $idTab = (int) Tab::getIdFromClassName($className);

    if ($idTab) {
        return $idTab;  // Already created by another Publiko module
    }

    // Find the "Configure" section
    $idConfigureSection = 0;
    $idAdvancedParams = (int) Tab::getIdFromClassName('AdminAdvancedParameters');
    if ($idAdvancedParams) {
        $advancedTab = new Tab($idAdvancedParams);
        $idConfigureSection = (int) $advancedTab->id_parent;
    }

    // Create the Publiko parent tab
    $tab = new Tab();
    $tab->active = 1;
    $tab->class_name = $className;
    $tab->module = '';  // No associated module (shared)
    $tab->id_parent = $idConfigureSection;
    $tab->icon = 'data_object';

    foreach (Language::getLanguages(false) as $lang) {
        $tab->name[$lang['id_lang']] = 'Publiko';
    }

    return $tab->add() ? $tab->id : 0;
}
```

### Uninstallation (automatic cleanup)

```php
protected function uninstallAdminTab(): bool
{
    $idTab = (int) Tab::getIdFromClassName('AdminPublikoMyModule');
    if ($idTab) {
        $tab = new Tab($idTab);
        $tab->delete();
    }

    // Remove Publiko parent if no children left
    $this->removePublikoParentTabIfEmpty();
    return true;
}

protected function removePublikoParentTabIfEmpty(): void
{
    $idParent = (int) Tab::getIdFromClassName('AdminPublikoParent');
    if (!$idParent) return;

    $children = Tab::getTabs(Context::getContext()->language->id, $idParent);
    if (empty($children)) {
        $tab = new Tab($idParent);
        $tab->delete();
    }
}
```

### Key Points

- `AdminPublikoParent` is shared between all Publiko modules
- First installed module creates the parent, last uninstalled removes it
- `$tab->module = ''` for the parent (otherwise it would be deleted with the module)
- Controller must exist: `controllers/admin/AdminPublikoMyModuleController.php`

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

## Theme Integration (CSS Variables)

Structure CSS files with variables for easy theme customization.

### Variable Declaration

All CSS custom properties MUST be declared in a single `:root` block at the beginning of your CSS file:

```css
/* modulename.css */
:root {
    /* All variables here */
    --modulename-primary: #2a9d8f;
    --modulename-text: #333333;
}

/* All styles below */
.module-element {
    color: var(--modulename-text);
}
```

### Naming Convention

Use **strict prefix + atomic naming**: `--{modulename}-{target}-{property}`

```css
:root {
    /* Main colors - no target needed */
    --modulename-primary: #2a9d8f;
    --modulename-primary-hover: #238b7e;
    --modulename-primary-light: rgba(42, 157, 143, 0.1);

    /* Targeted to specific component */
    --modulename-header-bg: #1a3c5a;
    --modulename-header-text: #ffffff;

    /* Multiple levels of specificity */
    --modulename-next-gift-bg: #f8f9fa;
    --modulename-next-gift-title: #1a3c5a;
}
```

### Variable Categories

Organize with comments:

```css
:root {
    /* ================================ */
    /* PRIMARY COLORS                   */
    /* ================================ */
    --modulename-primary: #2a9d8f;
    --modulename-primary-hover: #238b7e;

    /* ================================ */
    /* TEXT                             */
    /* ================================ */
    --modulename-text: #333333;
    --modulename-text-muted: #666666;

    /* ================================ */
    /* BACKGROUNDS & BORDERS            */
    /* ================================ */
    --modulename-bg: #ffffff;
    --modulename-border: #e9ecef;

    /* ================================ */
    /* STATUS COLORS                    */
    /* ================================ */
    --modulename-success: #27ae60;
    --modulename-success-light: #d4edda;

    /* ================================ */
    /* SPACING & EFFECTS                */
    /* ================================ */
    --modulename-radius: 8px;
    --modulename-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}
```

### Color Variants Pattern

For each main color:

```css
--modulename-primary: #2a9d8f;           /* Base color */
--modulename-primary-hover: #238b7e;     /* Darker for hover */
--modulename-primary-light: rgba(...);   /* Light background */
```

### Anti-Patterns to Avoid

```css
/* BAD: Generic names */
--primary: #2a9d8f;

/* BAD: No prefix */
--header-bg: #1a3c5a;

/* BAD: Scattered declarations */
.header { --header-bg: #1a3c5a; }

/* BAD: Hardcoded values */
.element { color: #2a9d8f; }  /* Should use var() */

/* BAD: Inconsistent naming */
--modulename-headerBackground: #1a3c5a;  /* camelCase */
```

### Best Practices

1. **Single declaration block**: All variables in one `:root` at file start
2. **Prefixed names**: Always use `--modulename-` prefix
3. **Atomic targeting**: Name describes exactly what it styles
4. **Logical grouping**: Organize by category with comments
5. **Provide variants**: Include `-hover`, `-light` when needed
6. **No magic values**: Every hardcoded color/size should be a variable

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
