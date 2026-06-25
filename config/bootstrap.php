<?php

declare(strict_types=1);

use Dotenv\Dotenv;

$root = dirname(__DIR__);
$autoloadPath = $root.'/vendor/autoload.php';

if (file_exists($autoloadPath) && ! class_exists(Dotenv::class)) {
    require_once $autoloadPath;
}

if (class_exists(Dotenv::class) && file_exists($root.'/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

$debugEnabled = filter_var(
    $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? getenv('APP_DEBUG') ?? false,
    FILTER_VALIDATE_BOOL
);

ini_set('display_errors', $debugEnabled ? '1' : '0');
error_reporting($debugEnabled ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
