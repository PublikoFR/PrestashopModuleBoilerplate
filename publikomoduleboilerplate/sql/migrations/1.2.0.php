<?php
/**
 * Migration 1.2.0
 *
 * Example migration: Add 'meta_title' and 'meta_description' to lang table
 *
 * This demonstrates adding multilingual fields in an update.
 *
 * @return array SQL queries to execute
 */

return [
    // Add SEO fields to lang table
    "ALTER TABLE `PREFIX_boilerplate_item_lang`
     ADD COLUMN IF NOT EXISTS `meta_title` VARCHAR(255) DEFAULT NULL",

    "ALTER TABLE `PREFIX_boilerplate_item_lang`
     ADD COLUMN IF NOT EXISTS `meta_description` VARCHAR(512) DEFAULT NULL",

    // Add 'featured' flag to main table
    "ALTER TABLE `PREFIX_boilerplate_item`
     ADD COLUMN IF NOT EXISTS `featured` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
     AFTER `active`",
];
