<?php
/**
 * Integration contract.
 *
 * @package Fomozo
 */

declare(strict_types=1);

namespace Fomozo\Integrations;

interface IntegrationInterface {
	public function id(): string;

	public function label(): string;

	public function description(): string;

	public function is_available(): bool;

	public function is_recommended(): bool;
}
