# Fomozo v1.0.0 Architecture

Fomozo is built as a lightweight, modular WordPress plugin. The v1.0.0 goal is a strong commercial foundation: fast frontend notifications, a clean admin setup, demo data for instant activation, and an integration pattern that can grow without turning the plugin into a framework.

## Composition

- `fomozo.php` bootstraps constants, the autoloader, activation defaults, and the plugin instance.
- `includes/Plugin.php` is the composition root. It wires settings, assets, REST, frontend rendering, providers, integrations, and admin UI.
- `includes/Settings/SettingsRepository.php` owns option defaults, sanitization, and persistence.
- `includes/Notifications/*` defines JSON-like notification provider contracts, provider collection, and output sanitization.
- `includes/Integrations/*` defines integration detection and the WooCommerce provider.
- `includes/Rest/NotificationsController.php` exposes sanitized public notification data.
- `includes/Frontend/Frontend.php` renders the frontend root and loads assets only when notifications are enabled.
- `includes/Admin/AdminPage.php` provides onboarding, feature toggles, integration detection, and display settings.

## Notification Object

Providers return associative arrays that match the public engine contract:

```json
{
  "type": "purchase",
  "title": "New purchase",
  "message": "A customer purchased Premium Hoodie",
  "icon": "bag",
  "image": "",
  "timestamp": 1710000000,
  "cta_url": "/shop",
  "source": "woocommerce"
}
```

All provider output passes through `NotificationSanitizer` before it reaches REST.

## Provider Pattern

Any future source can implement `NotificationProviderInterface` and register with:

```php
add_action(
	'fomozo_register_notification_providers',
	static function ( \Fomozo\Notifications\NotificationProviderRegistry $providers ): void {
		$providers->register( new AcmeProvider() );
	}
);
```

This keeps rendering independent from data sources.

## Integration Pattern

Integrations implement `IntegrationInterface` for admin detection and recommendation. If an integration also provides notification data, it can implement `NotificationProviderInterface`.

WooCommerce v1.0.0 behavior:

- Detects WooCommerce lazily.
- Uses `wc_get_orders()` only when needed.
- Caches the real-order existence check in a transient.
- Converts recent processing/completed orders into privacy-conscious purchase notifications.
- Uses product thumbnails when available.

## Data Strategy

Fomozo v1.0.0 uses `wp_options` only:

- `fomozo_settings` stores feature toggles and display settings.
- `fomozo_onboarding_complete` tracks the first-run experience.

No custom tables are needed for v1.0.0. Future analytics or event history can add dedicated storage when justified.

## Frontend Strategy

The frontend is a small vanilla JavaScript queue:

- Fetches sanitized notifications from `fomozo/v1/notifications`.
- Queues notifications client-side.
- Supports delay, interval, max-per-page, position, animation, icons, images, CTA URLs, and responsive layout.
- Uses CSS transitions and honors reduced-motion preferences.

No frontend framework or external dependency is loaded.

## Security

- Admin saves require `manage_options`.
- Settings saves use `check_admin_referer()`.
- Input is unslashed and sanitized before persistence.
- Output is escaped in PHP templates.
- REST output is sanitized and intentionally public.
- WooCommerce output avoids exposing full customer identities.

## Extensibility

The architecture leaves clear room for future Pro features:

- Advanced targeting can filter provider output before REST response.
- Analytics can listen for future impression/click events.
- Scheduling and segmentation can become provider decorators.
- AI-generated copy can transform notification messages before sanitization.
- SaaS connectivity can register as another provider without changing the renderer.
