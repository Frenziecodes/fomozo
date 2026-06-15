<?php
/**
 * Frontend integration.
 *
 * @package Fomozo
 */

declare(strict_types=1);

namespace Fomozo\Frontend;

use Fomozo\Assets\AssetManager;
use Fomozo\Settings\SettingsRepository;

final class Frontend {
	private SettingsRepository $settings;

	private AssetManager $assets;

	public function __construct(SettingsRepository $settings, AssetManager $assets) {
		$this->settings = $settings;
		$this->assets   = $assets;
	}

	public function register(): void {
		add_action('wp_enqueue_scripts', array($this, 'enqueue'));
		add_action('wp_footer', array($this, 'render_root'));
	}

	public function enqueue(): void {
		if (! $this->settings->is_enabled()) {
			return;
		}

		$this->assets->enqueue_frontend();
	}

	public function render_root(): void {
		if (! $this->settings->is_enabled()) {
			return;
		}

		$settings = $this->settings->all();

		printf(
			'<div id="fomozo-root" class="fomozo-root fomozo-%1$s fomozo-animation-%2$s" aria-live="polite" aria-atomic="true"></div>',
			esc_attr($settings['position']),
			esc_attr($settings['animation'])
		);
	}
}
