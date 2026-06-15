<?php
/**
 * WooCommerce notification integration.
 *
 * @package Fomozo
 */

declare(strict_types=1);

namespace Fomozo\Integrations;

use Fomozo\Notifications\NotificationProviderInterface;
use Fomozo\Settings\SettingsRepository;

/**
 * Builds purchase notifications from recent WooCommerce orders.
 */
final class WooCommerceIntegration implements IntegrationInterface, NotificationProviderInterface {
	private SettingsRepository $settings;

	/** @param SettingsRepository $settings Plugin settings store. */
	public function __construct(SettingsRepository $settings) {
		$this->settings = $settings;
	}

	public function id(): string {
		return 'woocommerce';
	}

	public function source(): string {
		return 'woocommerce';
	}

	public function label(): string {
		return __('WooCommerce', 'fomozo');
	}

	public function description(): string {
		return __('Show recent purchase activity as elegant social proof notifications.', 'fomozo');
	}

	/** Whether WooCommerce is active and usable. */
	public function is_available(): bool {
		return class_exists('WooCommerce') && function_exists('wc_get_orders');
	}

	public function is_recommended(): bool {
		return $this->is_available();
	}

	/** Whether the store has at least one qualifying order. */
	public function has_real_data(): bool {
		if (! $this->is_available()) {
			return false;
		}

		$count = get_transient('fomozo_wc_order_count');

		if (false === $count) {
			$orders = wc_get_orders(
				array(
					'limit'  => 1,
					'status' => array('wc-processing', 'wc-completed'),
					'return' => 'ids',
				)
			);

			$count = is_array($orders) ? count($orders) : 0;
			set_transient('fomozo_wc_order_count', $count, HOUR_IN_SECONDS);
		}

		return (int) $count > 0;
	}

	/**
	 * Builds purchase notifications from recent orders.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function notifications(int $limit): array {
		if (! $this->is_available() || ! $this->settings->is_source_enabled('woocommerce')) {
			return array();
		}

		$orders = wc_get_orders(
			array(
				'limit'   => $limit,
				'status'  => array('wc-processing', 'wc-completed'),
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);

		if (! is_array($orders)) {
			return array();
		}

		$notifications = array();

		foreach ($orders as $order) {
			if (! is_a($order, 'WC_Order')) {
				continue;
			}

			$item_name = $this->first_item_name($order);
			$city      = sanitize_text_field((string) $order->get_billing_city());
			$place     = $city ? sprintf(
				/* translators: %s is a city name. */
				__('Someone in %s', 'fomozo'),
				$city
			) : __('A customer', 'fomozo');

			$notifications[] = array(
				'type'      => 'purchase',
				'title'     => __('New purchase', 'fomozo'),
				'message'   => sprintf(
					/* translators: 1: customer place label, 2: product name. */
					__('%1$s purchased %2$s', 'fomozo'),
					$place,
					$item_name
				),
				'timestamp' => $order->get_date_created() ? $order->get_date_created()->getTimestamp() : time(),
				'cta_url'   => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/'),
				'image'     => $this->first_item_image($order),
				'source'    => 'woocommerce',
			);
		}

		return $notifications;
	}

	/** Returns the name of the first line item in an order. */
	private function first_item_name(object $order): string {
		$items = $order->get_items();

		foreach ($items as $item) {
			if (is_object($item) && method_exists($item, 'get_name')) {
				return sanitize_text_field((string) $item->get_name());
			}
		}

		return __('a product', 'fomozo');
	}

	/** Returns the thumbnail URL of the first line item product. */
	private function first_item_image(object $order): string {
		$items = $order->get_items();

		foreach ($items as $item) {
			if (! is_object($item) || ! method_exists($item, 'get_product')) {
				continue;
			}

			$product = $item->get_product();

			if ($product && method_exists($product, 'get_image_id')) {
				$image = wp_get_attachment_image_url((int) $product->get_image_id(), 'thumbnail');

				return $image ? esc_url_raw($image) : '';
			}
		}

		return '';
	}
}
