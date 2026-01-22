#!/usr/bin/env php
<?php
/**
 * PrestaShop Module Translation Generator
 *
 * Scans a PrestaShop module for translatable strings and generates/updates
 * translation files with correct MD5 hash keys.
 *
 * Usage:
 *   php generate_translations.php [options]
 *
 * Options:
 *   --module=NAME    Module technical name (auto-detected if not specified)
 *   --lang=CODE      Language code to generate (default: fr)
 *   --dry-run        Show what would be done without writing files
 *   --verbose        Show detailed output (each file scanned)
 *   --stats-only     Show only final statistics
 *   --help           Show this help message
 *
 * Examples:
 *   php generate_translations.php
 *   php generate_translations.php --lang=es --verbose
 *   php generate_translations.php --module=mymodule --dry-run
 *
 * @author    Publiko
 * @license   MIT
 */

// ============================================
// CONFIGURATION
// ============================================

class TranslationGenerator
{
    /** @var string Module technical name */
    private $moduleName;

    /** @var string Module base path */
    private $modulePath;

    /** @var string Language code */
    private $langCode = 'fr';

    /** @var bool Dry run mode */
    private $dryRun = false;

    /** @var bool Verbose output */
    private $verbose = false;

    /** @var bool Stats only output */
    private $statsOnly = false;

    /** @var array Found translatable strings [key => string] */
    private $foundStrings = [];

    /** @var array Existing translations [key => translation] */
    private $existingTranslations = [];

    /** @var array Statistics */
    private $stats = [
        'files_scanned' => 0,
        'strings_found' => 0,
        'new_strings' => 0,
        'updated_strings' => 0,
        'removed_strings' => 0,
        'kept_strings' => 0,
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->modulePath = $this->detectModulePath();
    }

    /**
     * Run the generator
     */
    public function run(array $args): int
    {
        // Parse arguments
        if (!$this->parseArguments($args)) {
            return 1;
        }

        // Detect module name if not specified
        if (!$this->moduleName) {
            $this->moduleName = $this->detectModuleName();
            if (!$this->moduleName) {
                $this->error("Could not detect module name. Use --module=NAME option.");
                return 1;
            }
        }

        $this->info("Module: {$this->moduleName}");
        $this->info("Path: {$this->modulePath}");
        $this->info("Language: {$this->langCode}");
        $this->info("");

        // Scan all files for translatable strings
        $this->scanModule();

        // Load existing translations if file exists
        $this->loadExistingTranslations();

        // Merge and generate output
        $output = $this->generateOutput();

        // Write or display result
        if ($this->dryRun) {
            $this->info("=== DRY RUN - Would generate: ===");
            echo $output;
        } else {
            $this->writeTranslationFile($output);
        }

        // Show statistics
        $this->showStats();

        return 0;
    }

    /**
     * Parse command line arguments
     */
    private function parseArguments(array $args): bool
    {
        array_shift($args); // Remove script name

        foreach ($args as $arg) {
            if ($arg === '--help' || $arg === '-h') {
                $this->showHelp();
                return false;
            }

            if ($arg === '--dry-run') {
                $this->dryRun = true;
                continue;
            }

            if ($arg === '--verbose' || $arg === '-v') {
                $this->verbose = true;
                continue;
            }

            if ($arg === '--stats-only' || $arg === '-s') {
                $this->statsOnly = true;
                continue;
            }

            if (strpos($arg, '--module=') === 0) {
                $this->moduleName = substr($arg, 9);
                continue;
            }

            if (strpos($arg, '--lang=') === 0) {
                $this->langCode = substr($arg, 7);
                continue;
            }

            $this->error("Unknown option: $arg");
            return false;
        }

        return true;
    }

    /**
     * Show help message
     */
    private function showHelp(): void
    {
        echo <<<HELP
PrestaShop Module Translation Generator

Scans a PrestaShop module for translatable strings and generates/updates
translation files with correct MD5 hash keys.

Usage:
  php generate_translations.php [options]

Options:
  --module=NAME    Module technical name (auto-detected if not specified)
  --lang=CODE      Language code to generate (default: fr)
  --dry-run        Show what would be done without writing files
  --verbose, -v    Show detailed output
  --stats-only, -s Show only final statistics (no logs)
  --help, -h       Show this help message

Examples:
  php generate_translations.php
  php generate_translations.php --lang=es --verbose
  php generate_translations.php --module=mymodule --dry-run

The script will:
  1. Scan all PHP and TPL files in the module
  2. Extract translatable strings (\$this->l('...') and {l s='...' mod='...'})
  3. Generate correct MD5 hash keys for PrestaShop
  4. Preserve existing translations when updating
  5. Mark removed strings as comments (for review)

HELP;
    }

    /**
     * Detect module path (current directory or parent)
     */
    private function detectModulePath(): string
    {
        $cwd = getcwd();

        // Check if we're in the module root (has a .php file with same name as directory)
        $dirName = basename($cwd);
        if (file_exists($cwd . '/' . $dirName . '.php')) {
            return $cwd;
        }

        // Check if we're in a subdirectory of the module
        $parent = dirname($cwd);
        $parentName = basename($parent);
        if (file_exists($parent . '/' . $parentName . '.php')) {
            return $parent;
        }

        // Fallback to current directory
        return $cwd;
    }

    /**
     * Detect module name from main PHP file
     */
    private function detectModuleName(): ?string
    {
        // Look for main module file
        $files = glob($this->modulePath . '/*.php');
        foreach ($files as $file) {
            $filename = basename($file, '.php');
            $content = file_get_contents($file);

            // Check if this is a module class file
            if (preg_match('/class\s+' . preg_quote(ucfirst($filename), '/') . '\s+extends\s+Module/i', $content)) {
                return $filename;
            }

            // Alternative: check for $this->name assignment
            if (preg_match('/\$this->name\s*=\s*[\'"]([a-z0-9_]+)[\'"]/i', $content, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Scan the entire module for translatable strings
     */
    private function scanModule(): void
    {
        $this->info("Scanning module files...");

        // Scan PHP files
        $this->scanDirectory($this->modulePath, '*.php', [$this, 'extractPhpStrings']);

        // Scan TPL files
        $this->scanDirectory($this->modulePath, '*.tpl', [$this, 'extractTplStrings']);

        $this->info("Found {$this->stats['strings_found']} translatable strings in {$this->stats['files_scanned']} files");
    }

    /**
     * Recursively scan a directory for files matching pattern
     */
    private function scanDirectory(string $dir, string $pattern, callable $extractor): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            // Skip vendor, node_modules, etc.
            $path = $file->getPathname();
            if (preg_match('#/(vendor|node_modules|\.git)/#', $path)) {
                continue;
            }

            // Check pattern
            if (!fnmatch($pattern, $file->getFilename())) {
                continue;
            }

            $this->stats['files_scanned']++;
            $this->verbose("Scanning: " . $this->getRelativePath($path));

            $content = file_get_contents($path);
            $extractor($content, $path);
        }
    }

    /**
     * Extract translatable strings from PHP files
     * Matches: $this->l('string') and $this->l("string")
     */
    private function extractPhpStrings(string $content, string $filePath): void
    {
        // Match $this->l('...') or $this->l("...")
        // Also match $this->trans('...') for Symfony-style translations
        $patterns = [
            '/\$this->l\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            '/\$this->trans\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*\[\s*\]\s*,\s*[\'"]Modules\.' . preg_quote(ucfirst($this->moduleName), '/') . '/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $string) {
                    $this->addString($string, $filePath);
                }
            }
        }
    }

    /**
     * Extract translatable strings from TPL files
     * Matches: {l s='string' mod='modulename'} and {l s="string" mod="modulename"}
     */
    private function extractTplStrings(string $content, string $filePath): void
    {
        // Match {l s='...' mod='modulename'} or {l s='...' d='Modules.Modulename...'}
        $patterns = [
            '/\{l\s+s=[\'"]([^\'"]+)[\'"]\s+mod=[\'"]' . preg_quote($this->moduleName, '/') . '[\'"]\s*\}/',
            '/\{l\s+s=[\'"]([^\'"]+)[\'"]\s+d=[\'"]Modules\.' . preg_quote(ucfirst($this->moduleName), '/') . '/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $string) {
                    $this->addString($string, $filePath);
                }
            }
        }

        // Also match with mod before s: {l mod='...' s='...'}
        $pattern = '/\{l\s+mod=[\'"]' . preg_quote($this->moduleName, '/') . '[\'"]\s+s=[\'"]([^\'"]+)[\'"]\s*\}/';
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[1] as $string) {
                $this->addString($string, $filePath);
            }
        }

        // Match with sprintf: {l s='...' sprintf=[...] mod='...'}
        $pattern = '/\{l\s+s=[\'"]([^\'"]+)[\'"]\s+sprintf=\[[^\]]*\]\s+mod=[\'"]' . preg_quote($this->moduleName, '/') . '[\'"]\s*\}/';
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[1] as $string) {
                $this->addString($string, $filePath);
            }
        }
    }

    /**
     * Add a found string to the collection
     */
    private function addString(string $string, string $filePath): void
    {
        $key = $this->makeKey($filePath, $string);

        if (!isset($this->foundStrings[$key])) {
            $this->foundStrings[$key] = [
                'string' => $string,
                'file' => $this->getRelativePath($filePath),
            ];
            $this->stats['strings_found']++;
        }
    }

    /**
     * Generate PrestaShop translation key
     * Format: <{modulename}prestashop>filename_md5hash
     */
    private function makeKey(string $filePath, string $string): string
    {
        $filename = basename($filePath);
        $filename = preg_replace('/\.(php|tpl)$/i', '', $filename);
        $filename = strtolower($filename);

        return '<{' . $this->moduleName . '}prestashop>' . $filename . '_' . md5($string);
    }

    /**
     * Load existing translations from file
     */
    private function loadExistingTranslations(): void
    {
        $filePath = $this->getTranslationFilePath();

        if (!file_exists($filePath)) {
            $this->verbose("No existing translation file found");
            return;
        }

        $this->verbose("Loading existing translations from: " . basename($filePath));

        // Parse the PHP file to extract $_MODULE array
        $content = file_get_contents($filePath);

        // Match $_MODULE['key'] = 'value';
        if (preg_match_all('/\$_MODULE\[[\'"]([^\'"]+)[\'"]\]\s*=\s*[\'"](.*)[\'"]\s*;/', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = $match[1];
                $value = $match[2];
                // Unescape the value
                $value = str_replace(["\\'", '\\"'], ["'", '"'], $value);
                $this->existingTranslations[$key] = $value;
            }
        }

        $this->verbose("Loaded " . count($this->existingTranslations) . " existing translations");
    }

    /**
     * Generate the translation file content
     */
    private function generateOutput(): string
    {
        $output = "<?php\n\n";
        $output .= "global \$_MODULE;\n";
        $output .= "\$_MODULE = [];\n\n";

        // Sort keys for consistent output
        ksort($this->foundStrings);

        // Track which existing translations were used
        $usedKeys = [];

        // Generate entries for found strings
        foreach ($this->foundStrings as $key => $data) {
            $string = $data['string'];
            $file = $data['file'];

            // Check if we have an existing translation
            if (isset($this->existingTranslations[$key])) {
                $translation = $this->existingTranslations[$key];
                $usedKeys[$key] = true;

                if ($translation === $string) {
                    // Not yet translated (same as source)
                    $this->stats['kept_strings']++;
                } else {
                    // Has a translation
                    $this->stats['kept_strings']++;
                }
            } else {
                // New string - use source as default
                $translation = $string;
                $this->stats['new_strings']++;
            }

            // Escape for PHP string
            $escapedTranslation = str_replace("'", "\\'", $translation);

            $output .= "// Source: {$file}\n";
            $output .= "\$_MODULE['{$key}'] = '{$escapedTranslation}';\n";
        }

        // Check for removed strings (in existing but not in found)
        $removedStrings = [];
        foreach ($this->existingTranslations as $key => $translation) {
            if (!isset($usedKeys[$key])) {
                $removedStrings[$key] = $translation;
                $this->stats['removed_strings']++;
            }
        }

        // Add removed strings as comments for review
        if (!empty($removedStrings)) {
            $output .= "\n// ============================================\n";
            $output .= "// REMOVED STRINGS (review and delete if obsolete)\n";
            $output .= "// ============================================\n";

            foreach ($removedStrings as $key => $translation) {
                $escapedTranslation = str_replace("'", "\\'", $translation);
                $output .= "// \$_MODULE['{$key}'] = '{$escapedTranslation}';\n";
            }
        }

        return $output;
    }

    /**
     * Write the translation file
     */
    private function writeTranslationFile(string $content): void
    {
        $filePath = $this->getTranslationFilePath();
        $dir = dirname($filePath);

        // Create translations directory if needed
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            $this->info("Created directory: translations/");

            // Create index.php for security
            $indexContent = "<?php\nheader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');\n";
            $indexContent .= "header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');\n";
            $indexContent .= "header('Cache-Control: no-store, no-cache, must-revalidate');\n";
            $indexContent .= "header('Cache-Control: post-check=0, pre-check=0', false);\n";
            $indexContent .= "header('Pragma: no-cache');\n";
            $indexContent .= "header('Location: ../');\nexit;\n";
            file_put_contents($dir . '/index.php', $indexContent);
        }

        file_put_contents($filePath, $content);
        $this->info("Written: " . $this->getRelativePath($filePath));
    }

    /**
     * Get the translation file path
     */
    private function getTranslationFilePath(): string
    {
        return $this->modulePath . '/translations/' . $this->langCode . '.php';
    }

    /**
     * Get path relative to module root
     */
    private function getRelativePath(string $path): string
    {
        return str_replace($this->modulePath . '/', '', $path);
    }

    /**
     * Show statistics (always displayed, even with --stats-only)
     */
    private function showStats(): void
    {
        echo "\n";
        echo "=== Statistics ===\n";
        echo "Files scanned:    {$this->stats['files_scanned']}\n";
        echo "Strings found:    {$this->stats['strings_found']}\n";
        echo "New strings:      {$this->stats['new_strings']}\n";
        echo "Kept strings:     {$this->stats['kept_strings']}\n";
        echo "Removed strings:  {$this->stats['removed_strings']}\n";
    }

    /**
     * Output info message
     */
    private function info(string $message): void
    {
        if (!$this->statsOnly) {
            echo $message . "\n";
        }
    }

    /**
     * Output verbose message
     */
    private function verbose(string $message): void
    {
        if ($this->verbose) {
            echo "  " . $message . "\n";
        }
    }

    /**
     * Output error message
     */
    private function error(string $message): void
    {
        fwrite(STDERR, "ERROR: " . $message . "\n");
    }
}

// ============================================
// MAIN
// ============================================

$generator = new TranslationGenerator();
exit($generator->run($argv));
