# PrestaShop Module Boilerplate

A production-ready boilerplate for PrestaShop module development, supporting versions 1.7 to 9.

## Features

- **Multi-version compatibility**: PrestaShop 1.7, 8, and 9
- **Multilingual support**: Full i18n with `_lang` tables and translation helpers
- **Multi-shop ready**: Shop-specific data and configurations
- **PS9 Symfony architecture**: Grid, Form, and Controller components (auto-enabled on PS9)
- **Database migrations**: Safe schema upgrades between module versions
- **Admin interface**: CRUD with drag-and-drop positioning, bulk actions, filters
- **Security**: Input validation, output escaping, CSRF protection
- **Build script**: One-command ZIP generation for distribution

## Requirements

- PHP 7.4+
- PrestaShop 1.7.0 - 9.x

## Installation

### From ZIP

1. Run `./build.sh` to generate the module ZIP
2. Go to PrestaShop Back Office > Modules > Module Manager
3. Click "Upload a module" and select the ZIP file

### For Development

1. Clone/copy this folder to `modules/publikomoduleboilerplate`
2. Install from Back Office or via CLI: `php bin/console prestashop:module install publikomoduleboilerplate`

## Project Structure

```
publikomoduleboilerplate/
├── publikomoduleboilerplate.php   # Main module class
├── classes/
│   └── BoilerplateItem.php        # ObjectModel entity
├── controllers/
│   ├── admin/                     # Legacy admin controller (PS 1.7-8)
│   └── front/                     # Front-office controllers
├── config/
│   ├── services.yml               # Symfony services (PS8 minimal)
│   ├── services.yml.ps9           # Full Symfony services (PS9)
│   └── routes.yml.ps9             # Symfony routes (PS9)
├── src/
│   ├── Controller/Admin/          # Symfony controller (PS9)
│   ├── Grid/                      # Grid definition & query builder
│   ├── Form/                      # Symfony form types
│   └── Database/
│       └── MigrationManager.php   # DB migration system
├── sql/
│   ├── install.sql
│   ├── uninstall.sql
│   └── migrations/                # Version upgrade scripts
├── views/
│   └── templates/
│       ├── admin/                 # Admin templates
│       ├── front/                 # Front-office templates
│       └── hook/                  # Hook templates
└── build.sh                       # Build script
```

## Usage

### Renaming the Module

1. Rename `publikomoduleboilerplate.php` to `yourmodulename.php`
2. Rename the class `Publikomoduleboilerplate` to `Yourmodulename`
3. Replace all occurrences of:
   - `publikomoduleboilerplate` → `yourmodulename`
   - `PublikoModuleBoilerplate` → `YourModuleName`
   - `BoilerplateItem` → `YourEntity`
   - `boilerplate_item` → `your_entity`

### Database Migrations

When releasing updates with schema changes:

1. Create `sql/migrations/{version}.php`:
```php
<?php
return [
    "ALTER TABLE `PREFIX_yourmodule_item`
     ADD COLUMN IF NOT EXISTS `new_field` VARCHAR(255)",
];
```

2. Optionally create `sql/migrations/{version}.rollback.php` for reversibility

3. Increment version in main module file - migrations run automatically on upgrade

### PS9 Symfony Components

On PrestaShop 9+, the module automatically enables:
- Symfony admin controller with Grid
- Form types with translatable fields
- Modern routing

Files `services.yml.ps9` and `routes.yml.ps9` are copied to active config during installation.

## Build

```bash
chmod +x build.sh
./build.sh
```

Generates `publikomoduleboilerplate.zip` ready for installation.

## Development Guidelines

See `claude.md` for comprehensive development guidelines including:
- Coding standards and conventions
- Security best practices
- Multilingual implementation
- Hook usage (avoid overrides)
- Testing checklist

## Version Compatibility

| Feature | PS 1.7 | PS 8 | PS 9 |
|---------|--------|------|------|
| Legacy Admin Controller | ✅ | ✅ | ✅ |
| Symfony Controller | ❌ | ❌ | ✅ |
| Grid Component | ❌ | ❌ | ✅ |
| Symfony Forms | ❌ | ❌ | ✅ |
| ObjectModel | ✅ | ✅ | ✅ |
| Hooks | ✅ | ✅ | ✅ |
| Multilingual | ✅ | ✅ | ✅ |

## License

Commercial - Publiko

## Author

[Publiko](https://www.publiko.fr)
