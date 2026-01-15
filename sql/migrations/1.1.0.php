<?php
/**
 * Migration 1.1.0
 *
 * Example migration: Add 'slug' column to boilerplate_item table
 *
 * GUIDELINES FOR MIGRATIONS:
 * - Use PREFIX_ placeholder (replaced with actual DB prefix)
 * - Always check IF NOT EXISTS / IF EXISTS for safety
 * - Return array of SQL queries
 * - One change per query for better error handling
 * - Create corresponding .rollback.php for reversible changes
 *
 * @return array SQL queries to execute
 */

return [
    // Add new column (safe: IF NOT EXISTS pattern via procedure)
    "ALTER TABLE `PREFIX_boilerplate_item`
     ADD COLUMN IF NOT EXISTS `slug` VARCHAR(255) DEFAULT NULL
     AFTER `position`",

    // Add index for better performance
    "CREATE INDEX IF NOT EXISTS `idx_slug`
     ON `PREFIX_boilerplate_item` (`slug`)",
];
