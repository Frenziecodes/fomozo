<?php
/**
 * Plugin Name: Fomozo
 * Plugin URI: https://fomozo.com
 * Description: Boost conversions with Fomozo: Social Proof & FOMO Notifications for WordPress.
 * Version: 1.0.0
 * Requires at least: 6.2
 * Requires PHP: 8.0
 * Author: Fomozo Team
 * Author URI: https://fomozo.com
 * Text Domain: fomozo
 * Domain Path: /languages
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Fomozo
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

define('FOMOZO_VERSION', '1.0.0');
define('FOMOZO_FILE', __FILE__);
define('FOMOZO_PATH', plugin_dir_path(__FILE__));
define('FOMOZO_URL', plugin_dir_url(__FILE__));
define('FOMOZO_BASENAME', plugin_basename(__FILE__));

require_once FOMOZO_PATH . 'includes/Autoloader.php';

\Fomozo\Autoloader::register();

register_activation_hook(
	__FILE__,
	static function (): void {
		\Fomozo\Plugin::activate();
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		\Fomozo\Plugin::instance()->boot();
	}
);
