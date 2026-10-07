<?php

use App\Kernel;

// Guarantee session save path on Windows/WAMP environments
if (DIRECTORY_SEPARATOR === '\\') {
    $sessionDir = dirname(__DIR__) . '/var/sessions/dev';
    if (!is_dir($sessionDir)) {
        @mkdir($sessionDir, 0777, true);
    }
    if (is_dir($sessionDir) && is_writable($sessionDir)) {
        @ini_set('session.save_path', $sessionDir);
    }
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
