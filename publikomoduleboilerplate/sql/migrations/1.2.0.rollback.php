<?php
/**
 * Rollback for Migration 1.2.0
 *
 * @return array SQL queries to execute
 */

return [
    "ALTER TABLE `PREFIX_boilerplate_item_lang` DROP COLUMN IF EXISTS `meta_title`",
    "ALTER TABLE `PREFIX_boilerplate_item_lang` DROP COLUMN IF EXISTS `meta_description`",
    "ALTER TABLE `PREFIX_boilerplate_item` DROP COLUMN IF EXISTS `featured`",
];
