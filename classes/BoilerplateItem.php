<?php
/**
 * BoilerplateItem - ObjectModel
 *
 * INSTRUCTIONS:
 * 1. Rename this file and class according to your entity
 * 2. Adapt properties and definition
 *
 * @author    Publiko
 * @copyright Publiko
 * @license   Commercial
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class BoilerplateItem extends ObjectModel
{
    /** @var int */
    public $id;

    /** @var string[] Multilang name */
    public $name;

    /** @var string[] Multilang description */
    public $description;

    /** @var bool Active status */
    public $active = true;

    /** @var int Position */
    public $position = 0;

    /** @var string Creation date */
    public $date_add;

    /** @var string Update date */
    public $date_upd;

    /**
     * ObjectModel definition
     */
    public static $definition = [
        'table' => 'boilerplate_item',
        'primary' => 'id_boilerplate_item',
        'multilang' => true,
        'multilang_shop' => true,
        'fields' => [
            // Standard fields
            'active' => [
                'type' => self::TYPE_BOOL,
                'validate' => 'isBool',
                'required' => false,
            ],
            'position' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
            ],
            'date_add' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
            ],
            'date_upd' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
            ],

            // Multilang fields
            'name' => [
                'type' => self::TYPE_STRING,
                'lang' => true,
                'validate' => 'isGenericName',
                'size' => 255,
                'required' => true,
            ],
            'description' => [
                'type' => self::TYPE_HTML,
                'lang' => true,
                'validate' => 'isCleanHtml',
            ],
        ],
    ];

    /**
     * Get all items
     *
     * @param int $idLang
     * @param int $idShop
     * @param bool $activeOnly
     * @return array
     */
    public static function getItems($idLang, $idShop = null, $activeOnly = false)
    {
        if ($idShop === null) {
            $idShop = Context::getContext()->shop->id;
        }

        $sql = new DbQuery();
        $sql->select('i.*, il.name, il.description');
        $sql->from('boilerplate_item', 'i');
        $sql->leftJoin(
            'boilerplate_item_lang',
            'il',
            'i.id_boilerplate_item = il.id_boilerplate_item AND il.id_lang = ' . (int) $idLang . ' AND il.id_shop = ' . (int) $idShop
        );

        if ($activeOnly) {
            $sql->where('i.active = 1');
        }

        $sql->orderBy('i.position ASC');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * Get next available position
     *
     * @return int
     */
    public static function getNextPosition()
    {
        $result = Db::getInstance()->getValue(
            'SELECT MAX(position) FROM `' . _DB_PREFIX_ . 'boilerplate_item`'
        );

        return (int) $result + 1;
    }

    /**
     * Update positions
     *
     * @param array $positions [id => position]
     * @return bool
     */
    public static function updatePositions(array $positions)
    {
        foreach ($positions as $id => $position) {
            Db::getInstance()->update(
                'boilerplate_item',
                ['position' => (int) $position],
                'id_boilerplate_item = ' . (int) $id
            );
        }

        return true;
    }
}
