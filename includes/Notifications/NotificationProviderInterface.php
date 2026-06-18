<?php
/**
 * Notification provider contract.
 *
 * @package Noravo
 */

declare( strict_types=1 );

namespace Noravo\Notifications;

interface NotificationProviderInterface {
	/** Source key used in settings and API responses. */
	public function source(): string;

	/**
	 * Returns notification payloads for this source.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function notifications( int $limit ): array;
}
