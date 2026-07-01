<?php
/**
 * Admin settings and onboarding page.
 *
 * @package Noravo
 */

declare(strict_types=1);

namespace Noravo\Admin;

use Noravo\Assets\AssetManager;
use Noravo\Integrations\IntegrationRegistry;
use Noravo\Settings\SettingsRepository;

/**
 * Renders the settings screen and handles admin form submissions.
 */
final class AdminPage {
	private SettingsRepository $settings;

	private IntegrationRegistry $integrations;

	private AssetManager $assets;

	/**
	 * @param SettingsRepository  $settings     Settings store.
	 * @param IntegrationRegistry $integrations Registered integrations.
	 * @param AssetManager        $assets       Admin asset loader.
	 */
	public function __construct(SettingsRepository $settings, IntegrationRegistry $integrations, AssetManager $assets) {
		$this->settings     = $settings;
		$this->integrations = $integrations;
		$this->assets       = $assets;
	}

	/** Registers admin menu, assets, and save handler hooks. */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this->assets, 'enqueue_admin' ) );
		add_action( 'admin_post_noravo_save_settings', array( $this, 'save' ) );
		add_filter( 'plugin_action_links_' . NORAVO_BASENAME, array( $this, 'action_links' ) );
	}

	/** Adds the top-level Noravo admin menu item. */
	public function add_menu(): void {
		add_menu_page(
			__( 'Dashboard', 'noravo' ),
			__( 'Noravo', 'noravo' ),
			'manage_options',
			'noravo',
			array( $this, 'render_dashboard' ),
			'dashicons-megaphone',
			58
		);

		global $submenu;

		if ( isset( $submenu['noravo'][0][0]) ) {
			$submenu['noravo'][0][0] = __( 'Dashboard', 'noravo' );
		}

		add_submenu_page(
			'noravo',
			__( 'Campaigns', 'noravo' ),
			__( 'Campaigns', 'noravo' ),
			'manage_options',
			'noravo-campaigns',
			array( $this, 'render_campaigns' )
		);

		add_submenu_page(
			'noravo',
			__( 'Integrations', 'noravo' ),
			__( 'Integrations', 'noravo' ),
			'manage_options',
			'noravo-integrations',
			array( $this, 'render_integrations' )
		);

		add_submenu_page(
			'noravo',
			__( 'Appearance', 'noravo' ),
			__( 'Appearance', 'noravo' ),
			'manage_options',
			'noravo-appearance',
			array( $this, 'render_appearance' )
		);

		add_submenu_page(
			'noravo',
			__( 'Settings', 'noravo' ),
			__( 'Settings', 'noravo' ),
			'manage_options',
			'noravo-settings',
			array( $this, 'render_settings' )
		);
	}

	/**
	 * Prepends a Settings link on the plugins list screen.
	 *
	 * @param array<int, string> $links Existing plugin action links.
	 * @return array<int, string>
	 */
	public function action_links(array $links): array {
		$settings = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url(admin_url( 'admin.php?page=noravo-settings' ) ),
			esc_html__( 'Settings', 'noravo' )
		);

		array_unshift( $links, $settings );

		return $links;
	}

	/** Validates and persists settings submitted from the admin form. */
	public function save(): void {
		if (! current_user_can( 'manage_options' ) ) {
			wp_die(esc_html__( 'You do not have permission to manage Noravo settings.', 'noravo' ) );
		}

		check_admin_referer( 'noravo_save_settings' );

		$form    = isset( $_POST['noravo_form']) ? sanitize_key(wp_unslash( $_POST['noravo_form']) ) : '';
		$updates = array();

		if ( 'campaigns' === $form ) {
			$updates['enabled']   = isset( $_POST['enabled']);
			$updates['demo_mode'] = isset( $_POST['demo_mode']);
		}

		if ( 'integrations' === $form ) {
			$updates['enabled_sources'] = isset( $_POST['enabled_sources'])
				? array_map( 'sanitize_key', (array) wp_unslash( $_POST['enabled_sources']) )
				: array();
		}

		if ( 'appearance' === $form ) {
			$updates['position']  = isset( $_POST['position']) ? sanitize_text_field(wp_unslash( $_POST['position']) ) : '';
			$updates['animation'] = isset( $_POST['animation']) ? sanitize_text_field(wp_unslash( $_POST['animation']) ) : '';
		}

		if ( 'settings' === $form ) {
			$updates['time_format']      = isset( $_POST['time_format']) ? sanitize_text_field(wp_unslash( $_POST['time_format']) ) : '';
			$updates['customer_display'] = isset( $_POST['customer_display']) ? sanitize_text_field(wp_unslash( $_POST['customer_display']) ) : '';
			$updates['initial_delay']    = isset( $_POST['initial_delay']) ? absint(wp_unslash( $_POST['initial_delay']) ) : 0;
			$updates['interval']         = isset( $_POST['interval']) ? absint(wp_unslash( $_POST['interval']) ) : 0;
			$updates['max_per_page']     = isset( $_POST['max_per_page']) ? absint(wp_unslash( $_POST['max_per_page']) ) : 0;
		}

		if ( ! empty( $updates) ) {
			$this->settings->update( $updates);
		}

		update_option( 'noravo_onboarding_complete', 'yes', false);

		$redirect = isset( $_POST['redirect_to']) ? esc_url_raw(wp_unslash( $_POST['redirect_to']) ) : admin_url( 'admin.php?page=noravo' );

		wp_safe_redirect(wp_nonce_url(add_query_arg( 'updated', 'true', $redirect), 'noravo_settings_updated' ) );
		exit;
	}

	/** Outputs the dashboard admin page. */
	public function render_dashboard(): void {
		if (! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings       = $this->settings->all();
		$is_onboarding  = 'yes' !== get_option( 'noravo_onboarding_complete', 'no' );
		$integrations   = $this->integrations->all();
		$enabled_count  = count( $settings['enabled_sources']);
		?>
		<div class="wrap noravo-admin">
			<div class="noravo-shell">
				<?php $this->header( __( 'Dashboard', 'noravo' ), __( 'Overview of notifications and plugin status.', 'noravo' ), $settings); ?>
				<?php $this->updated_notice(); ?>

				<?php if ( $is_onboarding) : ?>
					<section class="noravo-panel noravo-onboarding">
						<div>
							<h2><?php esc_html_e( 'Start with a confident preview', 'noravo' ); ?></h2>
							<p><?php esc_html_e( 'Demo mode is enabled by default so you can see Noravo working immediately. Connect WooCommerce when you are ready to use real purchase activity.', 'noravo' ); ?></p>
						</div>
						<ul>
							<?php foreach ( $integrations as $integration) : ?>
								<li>
									<strong><?php echo esc_html( $integration->label() ); ?></strong>
									<span><?php echo $integration->is_available() ? esc_html__( 'Detected', 'noravo' ) : esc_html__( 'Not installed', 'noravo' ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>

				<div class="noravo-grid">
					<section class="noravo-panel">
						<h2><?php esc_html_e( 'Summary', 'noravo' ); ?></h2>
						<div class="noravo-summary">
							<div>
								<strong><?php echo $settings['enabled'] ? esc_html__( 'Live', 'noravo' ) : esc_html__( 'Paused', 'noravo' ); ?></strong>
								<span><?php esc_html_e( 'Notification status', 'noravo' ); ?></span>
							</div>
							<div>
								<strong><?php echo $settings['demo_mode'] ? esc_html__( 'On', 'noravo' ) : esc_html__( 'Off', 'noravo' ); ?></strong>
								<span><?php esc_html_e( 'Demo mode', 'noravo' ); ?></span>
							</div>
							<div>
								<strong><?php echo esc_html((string) $enabled_count); ?></strong>
								<span><?php esc_html_e( 'Enabled sources', 'noravo' ); ?></span>
							</div>
							<div>
								<strong><?php echo esc_html(ucwords(str_replace( '-', ' ', (string) $settings['position']) ) ); ?></strong>
								<span><?php esc_html_e( 'Position', 'noravo' ); ?></span>
							</div>
						</div>
					</section>

					<section class="noravo-panel">
						<h2><?php esc_html_e( 'Integrations', 'noravo' ); ?></h2>
						<?php foreach ( $integrations as $integration) : ?>
							<?php $available = $integration->is_available(); ?>
							<label class="noravo-check">
								<input type="checkbox" <?php checked($available); ?> disabled>
								<span>
									<strong>
										<?php echo esc_html( $integration->label() ); ?>
										<?php $this->help( $integration->description() ); ?>
									</strong>
								</span>
								<em><?php echo $available ? esc_html__( 'Detected', 'noravo' ) : esc_html__( 'Not installed', 'noravo' ); ?></em>
							</label>
						<?php endforeach; ?>
					</section>
				</div>
			</div>
		</div>
		<?php
	}

	/** Outputs the campaigns admin page. */
	public function render_campaigns(): void {
		if (! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = $this->settings->all();
		?>
		<div class="wrap noravo-admin">
			<div class="noravo-shell">
				<?php $this->header( __( 'Campaigns', 'noravo' ), __( 'Control whether notification campaigns run on the frontend.', 'noravo' ), $settings); ?>
				<?php $this->updated_notice(); ?>
				<form method="post" action="<?php echo esc_url(admin_url( 'admin-post.php' ) ); ?>" class="noravo-grid">
					<?php $this->form_fields( 'campaigns'); ?>
					<section class="noravo-panel">
						<h2><?php esc_html_e( 'Campaign Status', 'noravo' ); ?></h2>
						<?php $this->toggle( 'enabled', __( 'Enable notifications', 'noravo' ), __( 'Show Noravo notifications on the frontend.', 'noravo' ), $settings['enabled']); ?>
						<?php $this->toggle( 'demo_mode', __( 'Demo mode', 'noravo' ), __( 'Use sample notifications for instant previews.', 'noravo' ), $settings['demo_mode']); ?>
					</section>
					<?php $this->save_actions(); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/** Outputs the integrations admin page. */
	public function render_integrations(): void {
		if (! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings        = $this->settings->all();
		$integrations    = $this->integrations->all();
		$enabled_sources = $settings['enabled_sources'];
		?>
		<div class="wrap noravo-admin">
			<div class="noravo-shell">
				<?php $this->header( __( 'Integrations', 'noravo' ), __( 'Connect and configure notification sources.', 'noravo' ), $settings); ?>
				<?php $this->updated_notice(); ?>
				<form method="post" action="<?php echo esc_url(admin_url( 'admin-post.php' ) ); ?>" class="noravo-grid">
					<?php $this->form_fields( 'integrations'); ?>
					<section class="noravo-panel">
						<h2><?php esc_html_e( 'Available Integrations', 'noravo' ); ?></h2>
						<input type="hidden" name="enabled_sources[]" value="demo">
						<?php foreach ( $integrations as $integration) : ?>
							<label class="noravo-check">
								<input type="checkbox" name="enabled_sources[]" value="<?php echo esc_attr( $integration->id() ); ?>" <?php checked(in_array( $integration->id(), $enabled_sources, true) ); ?> <?php disabled(! $integration->is_available() ); ?>>
								<span>
									<strong>
										<?php echo esc_html( $integration->label() ); ?>
										<?php $this->help( $integration->description() ); ?>
									</strong>
								</span>
								<em><?php echo $integration->is_available() ? esc_html__( 'Available', 'noravo' ) : esc_html__( 'Install plugin', 'noravo' ); ?></em>
							</label>
						<?php endforeach; ?>
					</section>
					<?php $this->save_actions(); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/** Outputs the appearance admin page. */
	public function render_appearance(): void {
		if (! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = $this->settings->all();
		?>
		<div class="wrap noravo-admin">
			<div class="noravo-shell">
				<?php $this->header( __( 'Appearance', 'noravo' ), __( 'Customize how notifications look on the frontend.', 'noravo' ), $settings); ?>
				<?php $this->updated_notice(); ?>
				<form method="post" action="<?php echo esc_url(admin_url( 'admin-post.php' ) ); ?>" class="noravo-grid">
					<?php $this->form_fields( 'appearance'); ?>
					<section class="noravo-panel">
						<h2><?php esc_html_e( 'Display', 'noravo' ); ?></h2>
						<div class="noravo-field">
							<label for="noravo-position">
								<?php esc_html_e( 'Position', 'noravo' ); ?>
								<?php $this->help(__( 'Where notifications appear on the visitor-facing site.', 'noravo' ) ); ?>
							</label>
							<select id="noravo-position" name="position">
								<?php foreach (array( 'bottom-left', 'bottom-right', 'top-left', 'top-right' ) as $position) : ?>
									<option value="<?php echo esc_attr( $position); ?>" <?php selected( $settings['position'], $position); ?>><?php echo esc_html(ucwords(str_replace( '-', ' ', $position) )); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="noravo-field">
							<label for="noravo-animation">
								<?php esc_html_e( 'Animation', 'noravo' ); ?>
								<?php $this->help(__( 'The entrance style used when each notification appears.', 'noravo' ) ); ?>
							</label>
							<select id="noravo-animation" name="animation">
								<option value="slide" <?php selected( $settings['animation'], 'slide' ); ?>><?php esc_html_e( 'Slide', 'noravo' ); ?></option>
								<option value="fade" <?php selected( $settings['animation'], 'fade' ); ?>><?php esc_html_e( 'Fade', 'noravo' ); ?></option>
							</select>
						</div>
					</section>
					<?php $this->save_actions(); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/** Outputs the settings admin page. */
	public function render_settings(): void {
		if (! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = $this->settings->all();
		?>
		<div class="wrap noravo-admin">
			<div class="noravo-shell">
				<?php $this->header( __( 'Settings', 'noravo' ), __( 'Control global behavior and timing.', 'noravo' ), $settings); ?>
				<?php $this->updated_notice(); ?>
				<form method="post" action="<?php echo esc_url(admin_url( 'admin-post.php' ) ); ?>" class="noravo-grid">
					<?php $this->form_fields( 'settings'); ?>
					<section class="noravo-panel">
						<h2><?php esc_html_e( 'Notification Rules', 'noravo' ); ?></h2>
						<div class="noravo-field">
							<label for="noravo-time-format">
								<?php esc_html_e( 'Time display', 'noravo' ); ?>
								<?php $this->help(__( 'How notification timestamps are shown after the first day.', 'noravo' ) ); ?>
							</label>
							<select id="noravo-time-format" name="time_format">
								<option value="rounded" <?php selected( $settings['time_format'], 'rounded' ); ?>><?php esc_html_e( 'Rounded', 'noravo' ); ?></option>
								<option value="days_hours" <?php selected( $settings['time_format'], 'days_hours' ); ?>><?php esc_html_e( 'Days and hours', 'noravo' ); ?></option>
								<option value="full" <?php selected( $settings['time_format'], 'full' ); ?>><?php esc_html_e( 'Full detail', 'noravo' ); ?></option>
							</select>
						</div>
						<div class="noravo-field">
							<label for="noravo-customer-display">
								<?php esc_html_e( 'Customer display', 'noravo' ); ?>
								<?php $this->help(__( 'Choose how customer names appear in purchase notifications.', 'noravo' ) ); ?>
							</label>
							<select id="noravo-customer-display" name="customer_display">
								<option value="location" <?php selected( $settings['customer_display'], 'location' ); ?>><?php esc_html_e( 'Hide name, show location', 'noravo' ); ?></option>
								<option value="full_name" <?php selected( $settings['customer_display'], 'full_name' ); ?>><?php esc_html_e( 'Show full name', 'noravo' ); ?></option>
								<option value="masked_name" <?php selected( $settings['customer_display'], 'masked_name' ); ?>><?php esc_html_e( 'Show first name and masked last name', 'noravo' ); ?></option>
							</select>
						</div>
						<div class="noravo-field-row">
							<?php $this->number( 'initial_delay', __( 'Initial delay', 'noravo' ), __( 'How long Noravo waits before showing the first notification, in milliseconds.', 'noravo' ), $settings['initial_delay']); ?>
							<?php $this->number( 'interval', __( 'Interval', 'noravo' ), __( 'How long Noravo waits between notifications, in milliseconds.', 'noravo' ), $settings['interval']); ?>
							<?php $this->number( 'max_per_page', __( 'Maximum per page', 'noravo' ), __( 'The most notifications a visitor can see during a single page visit.', 'noravo' ), $settings['max_per_page']); ?>
						</div>
					</section>
					<?php $this->save_actions(); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/** Renders the shared page header. */
	private function header(string $title, string $description, array $settings): void {
		?>
		<header class="noravo-hero">
			<div>
				<p class="noravo-kicker"><?php esc_html_e( 'Modern social proof and trust signals for WordPress', 'noravo' ); ?></p>
				<h1><?php echo esc_html( $title); ?></h1>
				<p><?php echo esc_html( $description); ?></p>
			</div>
			<div class="noravo-status">
				<span><?php echo $settings['enabled'] ? esc_html__( 'Live', 'noravo' ) : esc_html__( 'Paused', 'noravo' ); ?></span>
			</div>
		</header>
		<?php
	}

	/** Renders the saved settings notice. */
	private function updated_notice(): void {
		$show_updated = isset( $_GET['updated'], $_GET['_wpnonce'])
			&& wp_verify_nonce(sanitize_text_field(wp_unslash( $_GET['_wpnonce']) ), 'noravo_settings_updated' )
			&& 'true' === sanitize_text_field(wp_unslash( $_GET['updated']) );

		if ( ! $show_updated) {
			return;
		}
		?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Noravo settings saved.', 'noravo' ); ?></p></div>
		<?php
	}

	/** Renders shared form hidden fields. */
	private function form_fields(string $form): void {
		?>
		<input type="hidden" name="action" value="noravo_save_settings">
		<input type="hidden" name="noravo_form" value="<?php echo esc_attr( $form); ?>">
		<input type="hidden" name="redirect_to" value="<?php echo esc_url(admin_url( 'admin.php?page=' . sanitize_key( wp_unslash( $_GET['page'] ?? 'noravo' ) ) ) ); ?>">
		<?php wp_nonce_field( 'noravo_save_settings' ); ?>
		<?php
	}

	/** Renders shared form actions. */
	private function save_actions(): void {
		?>
		<div class="noravo-actions">
			<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save settings', 'noravo' ); ?></button>
		</div>
		<?php
	}

	/** Renders a styled checkbox toggle field. */
	private function toggle(string $name, string $label, string $description, bool $checked): void {
		?>
		<label class="noravo-toggle">
			<input type="checkbox" name="<?php echo esc_attr( $name); ?>" <?php checked( $checked); ?>>
			<span></span>
			<div>
				<strong>
					<?php echo esc_html( $label); ?>
					<?php $this->help( $description); ?>
				</strong>
			</div>
		</label>
		<?php
	}

	/** Renders a numeric settings field. */
	private function number(string $name, string $label, string $description, int $value): void {
		?>
		<div class="noravo-field">
			<label for="noravo-<?php echo esc_attr( $name); ?>">
				<?php echo esc_html( $label); ?>
				<?php $this->help( $description); ?>
			</label>
			<input id="noravo-<?php echo esc_attr( $name); ?>" type="number" name="<?php echo esc_attr( $name); ?>" value="<?php echo esc_attr((string) $value); ?>" min="0" step="1">
		</div>
		<?php
	}

	/** Renders an inline help tooltip trigger. */
	private function help(string $description): void {
		?>
		<span class="noravo-help" tabindex="0" aria-label="<?php echo esc_attr( $description); ?>">
			<span aria-hidden="true">?</span>
			<small role="tooltip"><?php echo esc_html( $description); ?></small>
		</span>
		<?php
	}
}
