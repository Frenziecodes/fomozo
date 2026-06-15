<?php
/**
 * Integration registry.
 *
 * @package Fomozo
 */

declare(strict_types=1);

namespace Fomozo\Integrations;

/**
 * Stores registered third-party integrations.
 */
final class IntegrationRegistry {
	/** @var array<string, IntegrationInterface> */
	private array $integrations = array();

	/** Adds an integration to the registry. */
	public function register(IntegrationInterface $integration): void {
		$this->integrations[$integration->id()] = $integration;
	}

	/**
	 * @return array<string, IntegrationInterface> All registered integrations.
	 */
	public function all(): array {
		return $this->integrations;
	}
}
