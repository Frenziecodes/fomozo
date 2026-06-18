<?php
/**
 * Minimal PSR-4 autoloader.
 *
 * @package Noravo
 */

declare( strict_types=1 );

namespace Noravo;

/**
 * Registers the plugin PSR-4 autoloader.
 */
final class Autoloader {
	/** Hooks the Noravo namespace autoloader into SPL. */
	public static function register(): void {
		spl_autoload_register(
			static function ( string $class ): void {
				$prefix = 'Noravo\\';

				if ( 0 !== strpos( $class, $prefix ) ) {
					return;
				}

				$relative = substr( $class, strlen( $prefix ) );
				$path     = NORAVO_PATH . 'includes/' . str_replace('\\', '/', $relative) . '.php';

				if ( is_readable( $path ) ) {
					require_once $path;
				}
			}
		);
	}
}
