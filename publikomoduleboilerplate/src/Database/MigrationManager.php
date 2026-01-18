<?php
/**
 * Database Migration Manager
 *
 * Handles database schema upgrades between module versions.
 * Ensures safe updates without data loss for existing installations.
 *
 * USAGE:
 * 1. Create migration files in sql/migrations/ folder
 * 2. Name format: {version}.php (e.g., 1.1.0.php, 1.2.0.php)
 * 3. Each migration returns array of SQL queries
 * 4. Call runMigrations() in module's install/upgrade hook
 *
 * @author    Publiko
 * @copyright Publiko
 * @license   Commercial
 */

declare(strict_types=1);

namespace PublikoModuleBoilerplate\Database;

use Configuration;
use Db;

class MigrationManager
{
    /**
     * Configuration key for storing current DB version
     */
    private const VERSION_CONFIG_KEY = 'BOILERPLATE_DB_VERSION';

    /**
     * Module root path
     */
    private string $modulePath;

    /**
     * Path to migrations folder
     */
    private string $migrationsPath;

    public function __construct(string $modulePath)
    {
        $this->modulePath = $modulePath;
        $this->migrationsPath = $modulePath . '/sql/migrations';
    }

    /**
     * Get current installed DB version
     */
    public function getCurrentVersion(): string
    {
        return Configuration::get(self::VERSION_CONFIG_KEY) ?: '1.0.0';
    }

    /**
     * Set current DB version
     */
    public function setCurrentVersion(string $version): bool
    {
        return Configuration::updateValue(self::VERSION_CONFIG_KEY, $version);
    }

    /**
     * Run all pending migrations
     *
     * @param string $targetVersion Target module version
     * @return array Results of each migration
     * @throws \Exception on migration failure
     */
    public function runMigrations(string $targetVersion): array
    {
        $currentVersion = $this->getCurrentVersion();
        $results = [];

        // Get all available migrations
        $migrations = $this->getAvailableMigrations();

        foreach ($migrations as $migrationVersion => $migrationFile) {
            // Skip migrations already applied
            if (version_compare($migrationVersion, $currentVersion, '<=')) {
                continue;
            }

            // Skip migrations beyond target version
            if (version_compare($migrationVersion, $targetVersion, '>')) {
                continue;
            }

            // Execute migration
            $result = $this->executeMigration($migrationVersion, $migrationFile);
            $results[$migrationVersion] = $result;

            if (!$result['success']) {
                throw new \Exception(
                    sprintf('Migration %s failed: %s', $migrationVersion, $result['error'])
                );
            }

            // Update version after successful migration
            $this->setCurrentVersion($migrationVersion);
        }

        // Set final target version
        $this->setCurrentVersion($targetVersion);

        return $results;
    }

    /**
     * Get list of available migrations sorted by version
     *
     * @return array [version => filepath]
     */
    public function getAvailableMigrations(): array
    {
        $migrations = [];

        if (!is_dir($this->migrationsPath)) {
            return $migrations;
        }

        $files = glob($this->migrationsPath . '/*.php');

        foreach ($files as $file) {
            $filename = basename($file, '.php');

            // Validate version format (X.Y.Z)
            if (preg_match('/^\d+\.\d+\.\d+$/', $filename)) {
                $migrations[$filename] = $file;
            }
        }

        // Sort by version
        uksort($migrations, 'version_compare');

        return $migrations;
    }

    /**
     * Execute a single migration
     *
     * @return array ['success' => bool, 'queries' => int, 'error' => string|null]
     */
    private function executeMigration(string $version, string $file): array
    {
        $result = [
            'success' => true,
            'queries' => 0,
            'error' => null,
        ];

        // Include migration file - should return array of queries
        $queries = include $file;

        if (!is_array($queries)) {
            $result['success'] = false;
            $result['error'] = 'Migration file must return an array of SQL queries';
            return $result;
        }

        $db = Db::getInstance();

        foreach ($queries as $query) {
            // Replace prefix placeholder
            $query = str_replace('PREFIX_', _DB_PREFIX_, $query);

            try {
                if (!$db->execute($query)) {
                    $result['success'] = false;
                    $result['error'] = $db->getMsgError();
                    return $result;
                }
                $result['queries']++;
            } catch (\Exception $e) {
                $result['success'] = false;
                $result['error'] = $e->getMessage();
                return $result;
            }
        }

        return $result;
    }

    /**
     * Check if migrations are pending
     *
     * @param string $targetVersion Module target version
     * @return bool
     */
    public function hasPendingMigrations(string $targetVersion): bool
    {
        $currentVersion = $this->getCurrentVersion();

        if (version_compare($currentVersion, $targetVersion, '>=')) {
            return false;
        }

        $migrations = $this->getAvailableMigrations();

        foreach (array_keys($migrations) as $migrationVersion) {
            if (version_compare($migrationVersion, $currentVersion, '>') &&
                version_compare($migrationVersion, $targetVersion, '<=')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get list of pending migrations
     *
     * @param string $targetVersion Module target version
     * @return array
     */
    public function getPendingMigrations(string $targetVersion): array
    {
        $currentVersion = $this->getCurrentVersion();
        $pending = [];

        $migrations = $this->getAvailableMigrations();

        foreach ($migrations as $migrationVersion => $file) {
            if (version_compare($migrationVersion, $currentVersion, '>') &&
                version_compare($migrationVersion, $targetVersion, '<=')) {
                $pending[$migrationVersion] = $file;
            }
        }

        return $pending;
    }

    /**
     * Rollback to a specific version (if rollback files exist)
     *
     * Migration rollback files should be named: {version}.rollback.php
     *
     * @param string $targetVersion Version to rollback to
     * @return array Results
     */
    public function rollbackTo(string $targetVersion): array
    {
        $currentVersion = $this->getCurrentVersion();
        $results = [];

        if (version_compare($targetVersion, $currentVersion, '>=')) {
            return $results; // Nothing to rollback
        }

        $migrations = $this->getAvailableMigrations();

        // Reverse order for rollback
        $migrations = array_reverse($migrations, true);

        foreach ($migrations as $migrationVersion => $migrationFile) {
            // Skip versions at or below target
            if (version_compare($migrationVersion, $targetVersion, '<=')) {
                continue;
            }

            // Skip versions above current
            if (version_compare($migrationVersion, $currentVersion, '>')) {
                continue;
            }

            // Look for rollback file
            $rollbackFile = str_replace('.php', '.rollback.php', $migrationFile);

            if (!file_exists($rollbackFile)) {
                $results[$migrationVersion] = [
                    'success' => false,
                    'error' => 'Rollback file not found',
                ];
                continue;
            }

            // Execute rollback
            $result = $this->executeMigration($migrationVersion, $rollbackFile);
            $results[$migrationVersion] = $result;

            if (!$result['success']) {
                throw new \Exception(
                    sprintf('Rollback %s failed: %s', $migrationVersion, $result['error'])
                );
            }
        }

        // Update to target version
        $this->setCurrentVersion($targetVersion);

        return $results;
    }
}
