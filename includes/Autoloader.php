<?php
/**
 * Minimal PSR-4 autoloader.
 *
 * @package Fomozo
 */

declare(strict_types=1);

namespace Fomozo;

final class Autoloader {
	public static function register(): void {
		spl_autoload_register(
			static function (string $class): void {
				$prefix = 'Fomozo\\';

				if (0 !== strpos($class, $prefix)) {
					return;
				}

				$relative = substr($class, strlen($prefix));
				$path     = FOMOZO_PATH . 'includes/' . str_replace('\\', '/', $relative) . '.php';

				if (is_readable($path)) {
					require_once $path;
				}
			}
		);
	}
}
