<?php
/**
 * Notification data sanitizer.
 *
 * @package Fomozo
 */

declare(strict_types=1);

namespace Fomozo\Notifications;

final class NotificationSanitizer {
	/**
	 * Normalizes a raw notification payload for safe frontend use.
	 *
	 * @param array<string, mixed> $notification Raw notification data.
	 * @return array<string, mixed>
	 */
	public static function sanitize(array $notification): array {
		$type = sanitize_key((string) ($notification['type'] ?? 'notice'));

		return array(
			'type'      => $type ?: 'notice',
			'title'     => sanitize_text_field((string) ($notification['title'] ?? '')),
			'message'   => sanitize_text_field((string) ($notification['message'] ?? '')),
			'icon'      => sanitize_text_field((string) ($notification['icon'] ?? self::icon_for_type($type))),
			'image'     => esc_url_raw((string) ($notification['image'] ?? '')),
			'timestamp' => absint($notification['timestamp'] ?? time()),
			'cta_url'   => esc_url_raw((string) ($notification['cta_url'] ?? '')),
			'source'    => sanitize_key((string) ($notification['source'] ?? 'custom')),
		);
	}

	/** Maps notification types to default icon keys. */
	private static function icon_for_type(string $type): string {
		return match ($type) {
			'purchase' => 'bag',
			'signup'   => 'spark',
			'review'   => 'star',
			default    => 'dot',
		};
	}
}
