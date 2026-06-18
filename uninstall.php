<?php
/**
 * Uninstall cleanup for Noravo.
 *
 * @package Noravo
 */

if ( ! defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

delete_option('noravo_settings');
delete_option('noravo_onboarding_complete');
delete_transient('noravo_wc_order_count');
