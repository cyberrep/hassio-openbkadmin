<?php

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if (null !== $error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log(sprintf('[OpenBKAdmin PHP Fatal] %s in %s:%d', $error['message'] ?? 'Unknown fatal error', $error['file'] ?? 'unknown', $error['line'] ?? 0));
    }
});

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!function_exists('curl_init')) {
    echo 'ERROR: PHP cURL is missing.';
    echo 'Please enable PHP cURL extension and restart web-server.';
    exit;
}
if (!class_exists('ZipArchive')) {
    echo 'ERROR: PHP Zip is missing.';
    echo 'Please enable PHP Zip extension and restart web-server.';
    exit;
}

$subdir = dirname($_SERVER['PHP_SELF']).'/';
$subdir = str_replace('\\', '/', $subdir);
$subdir = '//' == $subdir ? '/' : $subdir;
if ($baseurl_from_env = getenv('TASMO_BASEURL')) {
    $subdir = $baseurl_from_env;
}

define('_BASEURL_', $subdir);
define('_APPROOT_', dirname(dirname(__FILE__)).'/');
define('_TMPDIR_', getenv('TASMO_TMPDIR') ?: _APPROOT_.'tmp/');
define('_RESOURCESURL_', _BASEURL_.'resources/');
define('_INCLUDESDIR_', _APPROOT_.'includes/');
define('_HELPERSDIR_', _APPROOT_.'helpers/');
define('_RESOURCESDIR_', _APPROOT_.'resources/');
define('_LIBSDIR_', _APPROOT_.'libs/');
define('_PAGESDIR_', _APPROOT_.'pages/');
define('_DATADIR_', getenv('TASMO_DATADIR') ?: _APPROOT_.'data/');
define('_LANGDIR_', _APPROOT_.'lang/');
define('_CSVFILE_', _DATADIR_.'devices.csv');

session_save_path(_TMPDIR_.'sessions');
session_name('TASMO_SESSION');
session_start();

global $loggedin, $docker;
$loggedin = false;
$docker = false;

require_once _APPROOT_.'vendor/autoload.php';
require_once _HELPERSDIR_.'openbeken_discovery_v051.php';

use Selective\Container\Container;
use OpenBKAdmin\Config;
use OpenBKAdmin\Helper\EnvironmentHelper;
use OpenBKAdmin\Helper\JsonLanguageHelper;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

/**
 * Build a validated runtime copy of every language file.
 * A malformed translation must never make the whole application unusable.
 * Also repairs the malformed DEVICE_CONFIG marker introduced in older files.
 */
function prepareSafeLanguages(string $sourceRoot, string $tmpRoot): string
{
    $safeRoot = rtrim($tmpRoot, '/').'/safe-lang/';
    if (!is_dir($safeRoot)) {
        @mkdir($safeRoot, 0775, true);
    }

    $englishSource = $sourceRoot.'en/lang.ini';
    $english = @file_get_contents($englishSource) ?: '';
    $repair = static function (string $content): string {
        // Repair section marker accidentally appended to ERROR_NOT_OPENBEKEN.
        $content = preg_replace(
            '/^(ERROR_NOT_OPENBEKEN\s*=\s*"[^"]*")?\s*\[DEVICE_CONFIG\]"?\s*$/m',
            '$1'.PHP_EOL.'[DEVICE_CONFIG]',
            $content
        ) ?? $content;
        $content = preg_replace(
            '/^(ERROR_NOT_OPENBEKEN\s*=\s*"[^"]*\.)"?\[DEVICE_CONFIG\]"?\s*$/m',
            '$1"'.PHP_EOL.'[DEVICE_CONFIG]',
            $content
        ) ?? $content;
        return $content;
    };
    $english = $repair($english);

    foreach (glob($sourceRoot.'*', GLOB_ONLYDIR) ?: [] as $dir) {
        $lang = basename($dir);
        $src = $dir.'/lang.ini';
        if (!is_file($src)) {
            continue;
        }
        $content = $repair((string) @file_get_contents($src));
        $valid = false;
        if ('' !== $content) {
            $old = error_reporting(0);
            $parsed = parse_ini_string($content, true, INI_SCANNER_RAW);
            error_reporting($old);
            $valid = is_array($parsed);
        }
        if (!$valid) {
            error_log('[OpenBKAdmin i18n] Invalid language '.$lang.'; using English fallback for this session.');
            $content = $english;
        }
        $targetDir = $safeRoot.$lang;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }
        @file_put_contents($targetDir.'/lang.ini', $content);
    }
    return $safeRoot;
}

/** @var Container $container */
$container = require _APPROOT_.'includes/container.php';
$debug = isset($_SERVER['TASMO_DEBUG']);
if ($debug) {
    $whoops = new Run();
    $whoops->pushHandler(new PrettyPageHandler());
    $whoops->register();
}
if (file_exists(_APPROOT_.'.dockerenv')) {
    $docker = true;
}

$Config = $container->get(Config::class);
$i18n = $container->get(i18n::class);
$i18n->setCachePath(_TMPDIR_.'cache/i18n/');
$safeLangDir = prepareSafeLanguages(_LANGDIR_, _TMPDIR_);
$i18n->setFilePath($safeLangDir.'{LANGUAGE}/lang.ini');
$i18n->setFallbackLang('en');
$i18n->setPrefix('__L');
$i18n->setSectionSeparator('_');
$i18n->setMergeFallback(true);
$i18n->init();

$lang = $i18n->getAppliedLang();
$langHelper = new JsonLanguageHelper($lang, $safeLangDir."{$lang}/lang.ini", 'en', $safeLangDir.'en/lang.ini', _TMPDIR_.'cache/i18n/');
$langHelper->dumpJson();

if ((isset($_SESSION['login']) && '1' == $_SESSION['login']) || '0' == $Config->read('login') || EnvironmentHelper::isEnabled('NO_AUTH')) {
    $loggedin = true;
}

function __(string $string, ?string $category = null, ?array $args = null)
{
    $cat = '';
    if (isset($category) && !empty($category)) {
        $cat = $category.'_';
    }
    return __L($cat.$string, $args);
}
