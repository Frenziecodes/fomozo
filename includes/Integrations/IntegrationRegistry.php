<?php
/**
 * Integration registry.
 *
 * @package Fomozo
 */

declare(strict_types=1);

namespace Fomozo\Integrations;

final class IntegrationRegistry {
	/** @var array<string, IntegrationInterface> */
	private array $integrations = array();

	public function register(IntegrationInterface $integration): void {
		$this->integrations[$integration->id()] = $integration;
	}

	/**
	 * @return array<string, IntegrationInterface>
	 */
	public function all(): array {
		return $this->integrations;
	}
}
