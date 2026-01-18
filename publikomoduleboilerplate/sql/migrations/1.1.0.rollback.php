<?php
/**
 * Rollback for Migration 1.1.0
 *
 * Reverses the changes made in 1.1.0.php
 *
 * @return array SQL queries to execute
 */

return [
    // Remove index first
    "DROP INDEX IF EXISTS `idx_slug` ON `PREFIX_boilerplate_item`",

    // Remove column
    "ALTER TABLE `PREFIX_boilerplate_item` DROP COLUMN IF EXISTS `slug`",
];
