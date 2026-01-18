<?php
/**
 * AdminBoilerplateItemsController - Admin CRUD Controller
 *
 * INSTRUCTIONS:
 * 1. Rename this file: Admin{YourEntity}Controller.php
 * 2. Rename the class: Admin{YourEntity}Controller
 * 3. Adapt form fields and list columns
 *
 * @author    Publiko
 * @copyright Publiko
 * @license   Commercial
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'publikomoduleboilerplate/classes/BoilerplateItem.php';

class AdminBoilerplateItemsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'boilerplate_item';
        $this->className = 'BoilerplateItem';
        $this->identifier = 'id_boilerplate_item';
        $this->lang = true;
        $this->position_identifier = 'position';
        $this->_defaultOrderBy = 'position';
        $this->_defaultOrderWay = 'ASC';

        parent::__construct();

        // Bulk actions
        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash',
            ],
            'enableSelection' => [
                'text' => $this->l('Enable selection'),
                'icon' => 'icon-power-off text-success',
            ],
            'disableSelection' => [
                'text' => $this->l('Disable selection'),
                'icon' => 'icon-power-off text-danger',
            ],
        ];

        // List columns
        $this->fields_list = [
            'id_boilerplate_item' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name' => [
                'title' => $this->l('Name'),
                'filter_key' => 'il!name',
            ],
            'active' => [
                'title' => $this->l('Active'),
                'active' => 'status',
                'type' => 'bool',
                'class' => 'fixed-width-sm',
                'align' => 'center',
            ],
            'position' => [
                'title' => $this->l('Position'),
                'filter_key' => 'a!position',
                'position' => 'position',
                'class' => 'fixed-width-md',
                'align' => 'center',
            ],
        ];

        // Add buttons
        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    /**
     * Content initialization
     */
    public function initContent()
    {
        $this->_select = 'il.name';
        $this->_join = 'LEFT JOIN `' . _DB_PREFIX_ . 'boilerplate_item_lang` il
                        ON (a.id_boilerplate_item = il.id_boilerplate_item
                        AND il.id_lang = ' . (int) $this->context->language->id . '
                        AND il.id_shop = ' . (int) $this->context->shop->id . ')';

        parent::initContent();
    }

    /**
     * Add/Edit form
     */
    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Item'),
                'icon' => 'icon-cog',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Name'),
                    'name' => 'name',
                    'lang' => true,
                    'required' => true,
                    'hint' => $this->l('Name of the item'),
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->l('Description'),
                    'name' => 'description',
                    'lang' => true,
                    'autoload_rte' => true,
                    'hint' => $this->l('Description of the item'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Active'),
                    'name' => 'active',
                    'required' => true,
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        return parent::renderForm();
    }

    /**
     * Process after add
     */
    public function processAdd()
    {
        $_POST['position'] = BoilerplateItem::getNextPosition();

        return parent::processAdd();
    }

    /**
     * Update positions (drag & drop)
     */
    public function ajaxProcessUpdatePositions()
    {
        $way = (int) Tools::getValue('way');
        $idItem = (int) Tools::getValue('id');
        $positions = Tools::getValue('boilerplate_item');

        if (is_array($positions)) {
            foreach ($positions as $position => $value) {
                $pos = explode('_', $value);
                if (isset($pos[2])) {
                    BoilerplateItem::updatePositions([(int) $pos[2] => (int) $position]);
                }
            }
        }

        die(json_encode(['success' => true]));
    }
}
