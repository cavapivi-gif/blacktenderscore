<?php
namespace BlackTenders\Core;

defined('ABSPATH') || exit;

class Loader {

    public static function autoload(string $class): void {
        if (strpos($class, 'BlackTenders\\') !== 0) return;

        $relative = substr($class, strlen('BlackTenders\\'));
        $parts    = explode('\\', $relative);

        // Dernier segment = nom de classe
        $class_name = array_pop($parts);

        // CamelCase → kebab-case pour chaque segment
        $to_kebab = fn(string $s): string =>
            strtolower(preg_replace('/([A-Z])/', '-$1', lcfirst($s)));

        $folders   = array_map($to_kebab, $parts);
        $file_name = 'class-' . $to_kebab($class_name) . '.php';

        $path = BT_DIR . implode('/', $folders) . '/' . $file_name;

        if (file_exists($path)) {
            require_once $path;
        }
    }

    public static function register(): void {
        spl_autoload_register([static::class, 'autoload']);
    }
}

Loader::register();