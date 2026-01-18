# Guide Développement PrestaShop 8

## Contexte

Module PrestaShop 8 professionnel, prêt pour commercialisation. Compatibilité étendue PS 1.7 à PS 9.

## Agents

Toujours vérifier les agents disponibles et les utiliser en parallèle autant que possible.

## Documentation

**Toujours** utiliser Context7 avant d'implémenter une fonctionnalité :
```bash
context7 search "votre requête"
```

## Structure Module

```
mymodule/
├── mymodule.php          # Fichier principal
├── config.xml            # Configuration (optionnel)
├── index.php             # Protection répertoire
├── logo.png              # Logo 57x57px
├── translations/         # Traductions (fr.php, en.php)
├── views/
│   ├── templates/admin/  # Templates back-office
│   ├── templates/hook/   # Templates front-office
│   ├── css/, js/, img/
├── controllers/admin/, front/
├── classes/
├── sql/
│   ├── install.php
│   ├── uninstall.php
│   └── migrations/       # Migrations BDD
└── upgrade/
```

## Conventions de Code

### Nommage
- **Classe principale** : CamelCase (`MyModule`)
- **Nom technique** : minuscules sans espaces (`mymodule`)
- **Classes** : PascalCase | **Méthodes** : camelCase
- **Constantes** : UPPER_SNAKE_CASE | **Variables** : snake_case

### Standards
- PSR-2, indentation 4 espaces
- Typage PHP 7.2+ obligatoire
- Namespaces pour PS 8+

### Sécurité
- Échapper sorties : `Tools::safeOutput()`, `{$var|escape:'html':'UTF-8'}`
- Valider entrées : `Validate::isInt()`, `Validate::isEmail()`
- Tokens formulaires : `Tools::getAdminTokenLite()`
- SQL : `Db::getInstance()->escape()` ou requêtes préparées
- `index.php` dans chaque dossier

## Base de Données

### Règles Critiques

**INTERDIT** : Modifier les tables natives PrestaShop (`ALTER TABLE` sur table existante)

**OBLIGATOIRE** : Créer ses propres tables avec préfixe `pko_` (Publiko)

### Nommage Tables

Format : `{_DB_PREFIX_}pko_{nomtable}`

```
✓ ps_pko_mymodule_data
✓ ps_pko_mymodule_data_lang
✗ ps_product (table native)
✗ ps_mymodule_data (manque pko_)
```

### Optimisation
- Minimum de tables possible
- Regrouper données liées
- Tables `_lang` uniquement si multilingue nécessaire
- Clés étrangères vers tables PS (`id_product`, `id_customer`)

### Migrations

**OBLIGATOIRE** après version 1.0.0 pour toute modification BDD :

```php
// sql/migrations/1.1.0.php
return [
    'ALTER TABLE `PREFIX_pko_mymodule_data` ADD COLUMN `new_field` VARCHAR(255)',
];
```

### Installation BDD

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

## Multilingue

```php
// PHP
$this->l('Texte à traduire');

// Smarty
{l s='Texte à traduire' mod='mymodule'}

// Config multilingue
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

### Éviter au Maximum

Problèmes : conflits modules, maintenance difficile, rejet Marketplace.

### Alternatives
```php
// ✗ Override
class Product extends ProductCore { }

// ✓ Hooks
public function hookActionProductSave($params) { }

// ✓ Classes propres
class MyModuleProductHelper { }
```

### Si Indispensable
- Toujours appeler `parent::`
- Documenter : pourquoi, alternatives testées, version PS, risques

## Install Script (install.sh)

Script unifié pour modules ET thèmes. Config dans `.env.install` :

```bash
TYPE="module"  # ou "theme"
PRESTASHOP_PATH="/path/to/prestashop"
DOCKER_CONTAINER="container_name"
MODULE_NAME="mymodule"  # ou NAME pour thèmes
```

### Commandes
```bash
./install.sh              # Menu interactif
./install.sh --install    # Installer
./install.sh --update-script  # Mise à jour script
./install.sh --help       # Aide complète
```

## Checklist Finale

- [ ] Version incrémentée
- [ ] Traductions complètes
- [ ] Tests PS 8 (+ PS 1.7/9 si possible)
- [ ] Code sécurisé, pas de var_dump
- [ ] Logo 57x57px présent
- [ ] index.php partout
- [ ] Aucune table native modifiée
- [ ] Pas d'overrides (ou justifiés)
- [ ] ZIP testé

## Ressources

- [Documentation PS 8](https://devdocs.prestashop-project.org/8/)
- [Documentation Modules](https://devdocs.prestashop-project.org/8/modules/)
- [Validateur PrestaShop](https://validator.prestashop.com/)
