<?php
/**
 * Publiko Module Boilerplate
 *
 * INSTRUCTIONS:
 * 1. Rename this file to: {modulename}.php (e.g., publikomymodule.php)
 * 2. Rename the class to: {Modulename} (e.g., Publikomymodule)
 * 3. Replace all occurrences of:
 *    - "moduleboilerplate" with your module name
 *    - "ModuleBoilerplate" with your class name
 *    - "BoilerplateItem" with your entity
 *
 * @author    Publiko
 * @copyright Publiko
 * @license   Commercial
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Load PSR-4 autoloader
require_once __DIR__ . '/autoload.php';

use PublikoModuleBoilerplate\Database\MigrationManager;

class Publikomoduleboilerplate extends Module
{
    /**
     * @var MigrationManager|null
     */
    private $migrationManager;

    public function __construct()
    {
        $this->name = 'publikomoduleboilerplate';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Publiko';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->l('Publiko Module Boilerplate');
        $this->description = $this->l('Your module description.');
    }

    /**
     * Get Migration Manager instance
     */
    protected function getMigrationManager(): MigrationManager
    {
        if ($this->migrationManager === null) {
            $this->migrationManager = new MigrationManager(__DIR__);
        }

        return $this->migrationManager;
    }

    /**
     * Check if running on PrestaShop 9+
     */
    public function isPs9(): bool
    {
        return version_compare(_PS_VERSION_, '9.0.0', '>=');
    }

    /**
     * Enable Symfony components for PS9+
     * Copies routes.yml.ps9 and services.yml.ps9 to active files
     */
    protected function enablePs9Components(): bool
    {
        $files = [
            'routes.yml.ps9' => 'routes.yml',
            'services.yml.ps9' => 'services.yml',
        ];

        foreach ($files as $source => $dest) {
            $sourcePath = __DIR__ . '/config/' . $source;
            $destPath = __DIR__ . '/config/' . $dest;

            if (file_exists($sourcePath) && !file_exists($destPath)) {
                copy($sourcePath, $destPath);
            }
        }

        return true;
    }

    /**
     * Restore minimal PS8 config files
     */
    protected function disablePs9Components(): bool
    {
        // Only remove routes.yml (services.yml stays minimal)
        $routesFile = __DIR__ . '/config/routes.yml';

        if (file_exists($routesFile)) {
            unlink($routesFile);
        }

        return true;
    }

    /**
     * Module installation
     */
    public function install()
    {
        // Enable PS9 Symfony components only on PrestaShop 9+
        if ($this->isPs9()) {
            $this->enablePs9Components();
        }

        $result = parent::install()
            && $this->installDb()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBackOfficeHeader')
            && $this->installAdminTab();

        if ($result) {
            // Initialize DB version on fresh install
            $this->getMigrationManager()->setCurrentVersion($this->version);
        }

        return $result;
    }

    /**
     * Module uninstallation
     */
    public function uninstall()
    {
        // Clean up migration version config
        Configuration::deleteByName('BOILERPLATE_DB_VERSION');

        // Clean up PS9 components
        $this->disablePs9Components();

        return $this->uninstallAdminTab()
            && $this->uninstallDb()
            && parent::uninstall();
    }

    /**
     * Module upgrade
     *
     * Called automatically by PrestaShop when module version changes.
     * Runs all pending database migrations.
     *
     * @param string $oldVersion Previous installed version
     * @return bool
     */
    public function upgrade($oldVersion)
    {
        $migrationManager = $this->getMigrationManager();

        // Check for pending migrations
        if (!$migrationManager->hasPendingMigrations($this->version)) {
            return true;
        }

        try {
            $results = $migrationManager->runMigrations($this->version);

            // Log successful migrations
            foreach ($results as $version => $result) {
                if ($result['success']) {
                    PrestaShopLogger::addLog(
                        sprintf('%s: Migration %s applied (%d queries)', $this->name, $version, $result['queries']),
                        1
                    );
                }
            }

            return true;
        } catch (\Exception $e) {
            PrestaShopLogger::addLog(
                sprintf('%s: Migration failed - %s', $this->name, $e->getMessage()),
                3
            );

            return false;
        }
    }

    /**
     * Install database tables
     */
    protected function installDb()
    {
        $sql = [];

        // Main table
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'boilerplate_item` (
            `id_boilerplate_item` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
            `position` INT(10) UNSIGNED NOT NULL DEFAULT 0,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_boilerplate_item`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

        // Multilang table
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'boilerplate_item_lang` (
            `id_boilerplate_item` INT(10) UNSIGNED NOT NULL,
            `id_lang` INT(10) UNSIGNED NOT NULL,
            `id_shop` INT(10) UNSIGNED NOT NULL DEFAULT 1,
            `name` VARCHAR(255) NOT NULL,
            `description` TEXT,
            PRIMARY KEY (`id_boilerplate_item`, `id_lang`, `id_shop`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

        // Multishop table (optional)
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'boilerplate_item_shop` (
            `id_boilerplate_item` INT(10) UNSIGNED NOT NULL,
            `id_shop` INT(10) UNSIGNED NOT NULL,
            PRIMARY KEY (`id_boilerplate_item`, `id_shop`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Uninstall database tables
     */
    protected function uninstallDb()
    {
        $sql = [];
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'boilerplate_item_shop`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'boilerplate_item_lang`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'boilerplate_item`';

        foreach ($sql as $query) {
            Db::getInstance()->execute($query);
        }

        return true;
    }

    /**
     * Install admin tab
     */
    protected function installAdminTab()
    {
        // Create or get the "Publiko" parent tab
        $idParent = $this->getOrCreatePublikoParentTab();

        // Create child tab
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminBoilerplateItems';
        $tab->module = $this->name;
        $tab->id_parent = $idParent;
        $tab->icon = 'description';

        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[$lang['id_lang']] = 'Boilerplate Items';
        }

        return $tab->add();
    }

    /**
     * Create or get the "Publiko" parent tab
     * Placed in CONFIGURE section, last position
     */
    protected function getOrCreatePublikoParentTab()
    {
        $className = 'AdminPublikoParent';

        // Find CONFIGURE section (same parent as AdminAdvancedParameters)
        $advancedTab = new Tab((int) Tab::getIdFromClassName('AdminAdvancedParameters'));
        $idConfigureSection = $advancedTab->id_parent ?: -1;

        // Check if tab already exists
        $idTab = (int) Tab::getIdFromClassName($className);

        if ($idTab) {
            // Update existing tab to ensure correct placement
            $tab = new Tab($idTab);
            if ($tab->id_parent != $idConfigureSection) {
                $tab->id_parent = $idConfigureSection;
                $tab->position = 999;
                $tab->update();
            }
            return $idTab;
        }

        // Create "Publiko" parent tab in CONFIGURE section
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $className;
        $tab->module = '';
        $tab->id_parent = $idConfigureSection;
        $tab->icon = 'data_object';
        $tab->position = 999;

        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[$lang['id_lang']] = 'Publiko';
        }

        if ($tab->add()) {
            return $tab->id;
        }

        // Fallback to Modules menu if creation fails
        return (int) Tab::getIdFromClassName('AdminParentModulesSf');
    }

    /**
     * Uninstall admin tab
     */
    protected function uninstallAdminTab()
    {
        // Delete child tab
        $idTab = (int) Tab::getIdFromClassName('AdminBoilerplateItems');

        if ($idTab) {
            $tab = new Tab($idTab);
            $tab->delete();
        }

        // Remove parent if empty
        $this->removePublikoParentTabIfEmpty();

        return true;
    }

    /**
     * Remove Publiko parent tab if it has no children
     */
    protected function removePublikoParentTabIfEmpty()
    {
        $idParent = (int) Tab::getIdFromClassName('AdminPublikoParent');

        if (!$idParent) {
            return;
        }

        $children = Tab::getTabs(Context::getContext()->language->id, $idParent);

        if (empty($children)) {
            $tab = new Tab($idParent);
            $tab->delete();
        }
    }

    /**
     * Hook displayHeader - Front Office
     */
    public function hookDisplayHeader()
    {
        // Add CSS/JS in front office
        // $this->context->controller->addCSS($this->_path . 'views/css/front.css');
        // $this->context->controller->addJS($this->_path . 'views/js/front.js');
    }

    /**
     * Hook displayBackOfficeHeader - Back Office
     */
    public function hookDisplayBackOfficeHeader()
    {
        // Add CSS/JS in admin
        // $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
        // $this->context->controller->addJS($this->_path . 'views/js/admin.js');
    }

    /**
     * Module configuration page
     */
    public function getContent()
    {
        // Option 1: Redirect to admin controller
        $adminFolder = basename(_PS_ADMIN_DIR_);
        $token = Tools::getAdminTokenLite('AdminBoilerplateItems');
        $url = _PS_BASE_URL_ . __PS_BASE_URI__ . $adminFolder
            . '/index.php?controller=AdminBoilerplateItems&token=' . $token;

        Tools::redirectAdmin($url);

        return '';

        // Option 2: Simple configuration form
        // return $this->renderConfigForm();
    }

    /**
     * Configuration form (optional)
     */
    protected function renderConfigForm()
    {
        $output = '';

        // Form processing
        if (Tools::isSubmit('submitBoilerplateConfig')) {
            Configuration::updateValue('BOILERPLATE_OPTION_1', Tools::getValue('BOILERPLATE_OPTION_1'));
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        // Form builder
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->table = $this->table;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?: 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitBoilerplateConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => [
                'BOILERPLATE_OPTION_1' => Configuration::get('BOILERPLATE_OPTION_1'),
            ],
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Option 1'),
                        'name' => 'BOILERPLATE_OPTION_1',
                        'desc' => $this->l('Description of option 1'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        return $output . $helper->generateForm([$form]);
    }
}
