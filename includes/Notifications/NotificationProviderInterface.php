<?php
/**
 * Notification provider contract.
 *
 * @package Fomozo
 */

declare(strict_types=1);

namespace Fomozo\Notifications;

interface NotificationProviderInterface {
	public function source(): string;

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function notifications(int $limit): array;
}
