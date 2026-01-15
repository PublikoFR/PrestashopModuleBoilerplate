<?php
/**
 * Publiko Module Boilerplate - PSR-4 Autoloader
 * This file is automatically loaded by PrestaShop
 *
 * INSTRUCTIONS:
 * 1. Rename the namespace according to your module
 * 2. Adapt the prefix if necessary
 *
 * @author    Publiko
 * @copyright Publiko
 * @license   Commercial
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Register PSR-4 autoloader for PublikoModuleBoilerplate namespace
spl_autoload_register(function ($class) {
    $prefix = 'PublikoModuleBoilerplate\\';
    $baseDir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
