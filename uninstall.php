<?php
/**
 * Uninstall cleanup for Fomozo.
 *
 * @package Fomozo
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

delete_option('fomozo_settings');
delete_option('fomozo_onboarding_complete');
delete_transient('fomozo_wc_order_count');
