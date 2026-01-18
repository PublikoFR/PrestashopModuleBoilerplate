<?php
/**
 * Front Controller - Display
 *
 * INSTRUCTIONS:
 * 1. Rename this file according to your needs
 * 2. Adapt class name: {Modulename}{ControllerName}ModuleFrontController
 * 3. Configure $auth, $authRedirection properties as needed
 *
 * @author    Publiko
 * @copyright Publiko
 * @license   Commercial
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class PublikomoduleboilerplateDisplayModuleFrontController extends ModuleFrontController
{
    /**
     * Authentication required (true = logged customer required)
     */
    public $auth = false;

    /**
     * Redirect page if not authenticated
     */
    public $authRedirection = 'authentication';

    /**
     * Controller initialization
     */
    public function init()
    {
        parent::init();

        // Example: get a parameter
        // $id = (int) Tools::getValue('id');
    }

    /**
     * Define meta tags
     */
    public function getTemplateVarPage()
    {
        $page = parent::getTemplateVarPage();

        $page['meta']['title'] = $this->module->l('Page Title');
        $page['meta']['description'] = $this->module->l('Page description for SEO');
        $page['body_classes']['page-module-boilerplate'] = true;

        return $page;
    }

    /**
     * Content initialization
     */
    public function initContent()
    {
        parent::initContent();

        // Get data
        // $items = BoilerplateItem::getItems($this->context->language->id, $this->context->shop->id, true);

        // Assign variables to template
        $this->context->smarty->assign([
            'module_name' => $this->module->name,
            'page_title' => $this->module->l('Boilerplate Page'),
            // 'items' => $items,
        ]);

        // Set template
        $this->setTemplate('module:publikomoduleboilerplate/views/templates/front/display.tpl');
    }

    /**
     * Breadcrumb
     */
    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        $breadcrumb['links'][] = [
            'title' => $this->module->l('Boilerplate'),
            'url' => '',
        ];

        return $breadcrumb;
    }

    /**
     * POST data processing (optional)
     */
    public function postProcess()
    {
        parent::postProcess();

        if (Tools::isSubmit('submitBoilerplateForm')) {
            // Form processing
            // $value = Tools::getValue('field_name');
        }
    }
}
