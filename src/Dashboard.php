<?php

use Timber\Site;

/**
 * Emonks Custom Dashboard (OOP versie voor jouw thema-structuur)
 *
 * Features:
 * - Welkom [naam] + sitenaam
 * - Snelle acties (per CPT behalve 'post') + Media + Menu's
 *   - Met blauw icoon-vierkant + korte toelichting
 * - Website overzicht: klikbare tegels per post type met totaal aantal items
 * - Recente activiteit: laatst gewijzigde items (over je CPT's)
 * - Updates & status: core/plugins/themes + Site Health
 * - Hulp nodig? mailbutton
 */
class Dashboard extends Site {

	public function __construct() {
		parent::__construct();
			
		add_filter('screen_layout_columns', array($this, 'force_dashboard_columns'), 10, 2);
		add_filter('get_user_option_screen_layout_dashboard', array($this, 'force_dashboard_one_column_user'));
		add_action('admin_init', array($this, 'force_dashboard_one_column_usermeta'));
		add_action('wp_dashboard_setup', array($this, 'register_dashboard_widget'), 100);
		add_action('admin_init', array($this, 'move_widget_to_top'));
		add_action('admin_head', array($this, 'admin_css'));
		add_action('current_screen', array($this, 'ensure_widget_visible'));
		add_action('wp_dashboard_setup', array($this, 'remove_all_dashboard_widgets_except_ours'), 999);
	}
	
	public function remove_all_dashboard_widgets_except_ours() {
		global $wp_meta_boxes;
	
		$keep = 'emonks_custom_dashboard';
	
		$contexts = array('normal', 'side', 'column3', 'column4');
	
		foreach ($contexts as $context) {
			if (!isset($wp_meta_boxes['dashboard'][$context])) continue;
	
			foreach ($wp_meta_boxes['dashboard'][$context] as $priority => $boxes) {
				if (!is_array($boxes)) continue;
	
				foreach ($boxes as $id => $box) {
					if ($id === $keep) continue;
					unset($wp_meta_boxes['dashboard'][$context][$priority][$id]);
				}
			}
		}
	}
	
	public function force_dashboard_columns($columns, $screen) {
		if (is_object($screen) && isset($screen->id) && $screen->id === 'dashboard') {
			$columns['dashboard'] = 1;
		}
		return $columns;
	}
	
	public function force_dashboard_one_column_user($value) {
		// Forceer altijd 1 kolom voor dashboard (per user setting)
		return 1;
	}
	
	public function force_dashboard_one_column_usermeta() {
		$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		if (!$screen || $screen->id !== 'dashboard') return;
	
		$user_id = get_current_user_id();
		if (!$user_id) return;
	
		update_user_meta($user_id, 'screen_layout_dashboard', 1);
	}
	/**
	 * 1) Dashboard opschonen + widget toevoegen
	 */
	public function register_dashboard_widget() {
		// Standaard widgets weg (pas gerust aan)
		remove_meta_box('dashboard_primary', 'dashboard', 'side');
		remove_meta_box('dashboard_secondary', 'dashboard', 'side');
		remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
		remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
		remove_meta_box('dashboard_activity', 'dashboard', 'normal');
		remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
		remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');

		wp_add_dashboard_widget(
			'emonks_custom_dashboard',
			'Emonks Dashboard',
			array($this, 'render_custom_dashboard')
		);
	}

	/**
	 * 2) Widget bovenaan zetten
	 */
	public function move_widget_to_top() {
		global $wp_meta_boxes;

		if (
			!isset($wp_meta_boxes['dashboard']) ||
			!isset($wp_meta_boxes['dashboard']['normal']) ||
			!isset($wp_meta_boxes['dashboard']['normal']['core']) ||
			!isset($wp_meta_boxes['dashboard']['normal']['core']['emonks_custom_dashboard'])
		) {
			return;
		}

		$widget = array(
			'emonks_custom_dashboard' => $wp_meta_boxes['dashboard']['normal']['core']['emonks_custom_dashboard']
		);

		unset($wp_meta_boxes['dashboard']['normal']['core']['emonks_custom_dashboard']);

		// Voeg toe als eerste
		$wp_meta_boxes['dashboard']['normal']['core'] = $widget + $wp_meta_boxes['dashboard']['normal']['core'];
	}


	/**
	 * Zorg dat de widget zichtbaar is (niet verborgen via Scherminstellingen / dichtgeklapt)
	 * WordPress bewaart dit per gebruiker in usermeta.
	 */
	public function ensure_widget_visible($screen) {
		if (!is_object($screen) || empty($screen->id) || $screen->id !== 'dashboard') {
			return;
		}

		$user_id = get_current_user_id();
		if (!$user_id) {
			return;
		}

		$widget_id = 'emonks_custom_dashboard';

		$hidden = get_user_meta($user_id, 'metaboxhidden_dashboard', true);
		if (!is_array($hidden)) $hidden = array();
		$hidden = array_values(array_diff($hidden, array($widget_id)));
		update_user_meta($user_id, 'metaboxhidden_dashboard', $hidden);

		$closed = get_user_meta($user_id, 'closedpostboxes_dashboard', true);
		if (!is_array($closed)) $closed = array();
		$closed = array_values(array_diff($closed, array($widget_id)));
		update_user_meta($user_id, 'closedpostboxes_dashboard', $closed);
	}


	/**
	 * 3) Styling
	 */
	public function admin_css() {
		if (!function_exists('get_current_screen')) return;
		$screen = get_current_screen();
		if (!$screen || $screen->id !== 'dashboard') return;
		?>
		<style>
			#wpbody-content > .wrap > h1 {
				display: none;
			}

			#emonks_custom_dashboard {
				background: transparent;
				border: 0;
			}
			#emonks_custom_dashboard .inside { padding: 0; margin: 0; }
			#emonks_custom_dashboard .postbox-header { display: none; }
			
			/* Force full-width single column */
			#dashboard-widgets-wrap,
			#dashboard-widgets { width: 100% !important; }
			
			#dashboard-widgets .postbox-container {
				width: 100% !important;
				float: none !important;
			}
			
			/* Verberg onnodige kolom containers als WP ze toch rendert */
			#dashboard-widgets .postbox-container:empty { display: none !important; }

			.emonks-dash { padding: 0; }

			.emonks-hero { margin-bottom: 18px; }
			.emonks-hero h1 {
				margin: 0;
				font-size: 26px;
				line-height: 1.15;
				letter-spacing: -0.02em;
			}
			.emonks-hero .site {
				margin-top: 6px;
				color: #646970;
				font-size: 13px;
			}

			.emonks-grid {
				display: grid;
				grid-template-columns: repeat(2, minmax(0, 1fr));
				gap: 20px;
				align-items: stretch;
				margin-bottom: 50px;
			}

			.emonks-card {
				background: #fff;
				border: 1px solid #dcdcde;
				border-radius: 14px;
				box-shadow: 0 1px 2px rgba(0,0,0,.04);
				padding: 16px;
				height: 100%;
				display: flex;
				flex-direction: column;
			}

			.emonks-card h2 {
				margin: 0 0 12px 0;
				font-size: 12px;
				text-transform: initial;
				letter-spacing: 0;
				margin-bottom: 12px !important;
				color: #3c434a;
			}

			/* Quick actions */
			.emonks-actions { display: grid; gap: 10px; }
			.emonks-action {
				display: grid;
				grid-template-columns: 42px 1fr auto;
				gap: 12px;
				align-items: center;
				padding: 12px;
				border: 1px solid #e5e5e5;
				border-radius: 12px;
				text-decoration: none;
				background: #fff;
				transition: transform .08s ease, box-shadow .08s ease, border-color .08s ease;
			}
			.emonks-action:hover {
				transform: translateY(-1px);
				border-color: #cfd7df;
				box-shadow: 0 6px 18px rgba(0,0,0,.06);
			}

			.emonks-iconbox {
				width: 42px;
				height: 42px;
				border-radius: 10px;
				background: #2271b1;
				display: grid;
				place-items: center;
			}
			.emonks-iconbox .dashicons {
				color: #fff;
				font-size: 20px;
				width: 20px;
				height: 20px;
			}

			.emonks-action .title {
				font-weight: 650;
				color: #1d2327;
				line-height: 1.2;
				margin-bottom: 2px;
			}
			.emonks-action .desc {
				color: #646970;
				font-size: 12px;
				line-height: 1.35;
			}

			.emonks-action .btn {
				white-space: nowrap;
				border-radius: 10px;
				padding: 6px 10px;
			}

			/* Tiles */
			.emonks-tiles {
				display: grid;
				grid-template-columns: repeat(2, minmax(0, 1fr));
				gap: 12px;
			}
			.emonks-tile {
				display: grid;
				grid-template-columns: 42px 1fr;
				gap: 12px;
				align-items: center;
				text-decoration: none;
				padding: 12px;
				border-radius: 14px;
				border: 1px solid #e5e5e5;
				background: #fff;
				transition: transform .08s ease, background .08s ease, border-color .08s ease;
			}
			.emonks-tile:hover {
				background: #fff;
				border-color: #cfd7df;
				transform: translateY(-1px);
				box-shadow: 0 6px 18px rgba(0,0,0,.06);
			}
			.emonks-tile .count {
				font-size: 20px;
				font-weight: 800;
				color: #2271b1;
				line-height: 1;
				margin-top: 3px;
			}
			.emonks-tile .label {
				font-weight: 650;
				color: #1d2327;
				line-height: 1.2;
			}
			.emonks-tile .muted {
				font-size: 12px;
				color: #646970;
				margin-top: 2px;
			}

			/* Bottom grid */
			.emonks-grid-2 {
				display: grid;
				grid-template-columns: repeat(2, minmax(0, 1fr));
				gap: 18px;
				margin-top: 18px;
				align-items: stretch;
			}

			/* Recent list */
			.emonks-recent {
				width: 100%;
				border-collapse: collapse;
			}
			.emonks-recent td {
				border-top: 1px solid #eee;
				padding: 10px 0;
				vertical-align: top;
			}
			.emonks-recent a { text-decoration: none; font-weight: 600; }
			.emonks-recent .meta {
				display: block;
				font-size: 12px;
				color: #646970;
				margin-top: 2px;
			}

			/* Help */

			.emonks-help p { margin: 0 0 12px 0; color: #3c434a; }
			.emonks-help .button { border-radius: 10px; padding: 7px 12px; }
			.emonks-subtitle {
				margin: 18px 0 10px 0;
				font-size: 12px;
				text-transform: uppercase;
				letter-spacing: 0.04em;
				color: #646970;
			}

			@media (max-width: 980px) {
				.emonks-grid { grid-template-columns: 1fr; }
				.emonks-grid-2 { grid-template-columns: 1fr; }
				.emonks-tiles { grid-template-columns: 1fr; }
				.emonks-action { grid-template-columns: 42px 1fr; }
				.emonks-action .btn { display: none; }
			}
		</style>
		<?php
	}

	/**
	 * Helpers
	 */
	private function get_dashboard_post_types() {
		$post_types = get_post_types(array('public' => true), 'objects');
		unset($post_types['post'], $post_types['attachment']);
		return $post_types;
	}

	private function pick_dashicon_for_post_type($post_type) {
		$map = array(
			'page'        => 'dashicons-admin-page',
			'product'     => 'dashicons-cart',
			'event'       => 'dashicons-calendar-alt',
			'vacature'    => 'dashicons-businessperson',
			'project'     => 'dashicons-portfolio',
			'case'        => 'dashicons-portfolio',
			'whitepaper'  => 'dashicons-media-document',
			'verhaal'     => 'dashicons-book-alt',
			'nieuws'      => 'dashicons-megaphone',
		);
		return isset($map[$post_type]) ? $map[$post_type] : 'dashicons-admin-post';
	}

	private function get_recent_activity($post_types, $limit = 7) {
		$type_slugs = array();
		foreach ($post_types as $pt) {
			$type_slugs[] = $pt->name;
		}

		$q = new \WP_Query(array(
			'post_type'      => $type_slugs,
			'posts_per_page' => (int) $limit,
			'post_status'    => array('publish', 'draft', 'pending', 'future', 'private'),
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		));

		return $q->have_posts() ? $q->posts : array();
	}

	private function get_update_counts() {
		// Includes (veilig, ook als ze al geladen zijn)
		if (!function_exists('wp_update_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}
		if (!function_exists('get_plugin_updates')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}

		// Transients updaten
		wp_update_plugins();
		wp_update_themes();

		$core = get_site_transient('update_core');

		$plugin_updates = function_exists('get_plugin_updates') ? get_plugin_updates() : array();
		$theme_updates  = get_site_transient('update_themes');

		$core_count = 0;
		if (is_object($core) && !empty($core->updates) && is_array($core->updates)) {
			foreach ($core->updates as $u) {
				if (!empty($u->response) && $u->response === 'upgrade') {
					$core_count++;
				}
			}
		}

		$plugins_count = is_array($plugin_updates) ? count($plugin_updates) : 0;

		$themes_count = 0;
		if (is_object($theme_updates) && !empty($theme_updates->response) && is_array($theme_updates->response)) {
			$themes_count = count($theme_updates->response);
		}

		return array(
			'core'    => $core_count,
			'plugins' => $plugins_count,
			'themes'  => $themes_count,
		);
	}

	/**
	 * 4) Render dashboard
	 */
	public function render_custom_dashboard() {
		$current_user = wp_get_current_user();
		$name = ($current_user && $current_user->display_name) ? $current_user->display_name : __('daar', 'default');
		$site_name = get_bloginfo('name');

		$post_types = $this->get_dashboard_post_types();

		// Snelle acties (CPT's + media + menus)
		$actions = array();

		foreach ($post_types as $pt) {
			if (!current_user_can($pt->cap->create_posts)) continue;

			$singular = $pt->labels->singular_name ? $pt->labels->singular_name : $pt->name;
			$plural   = $pt->labels->name ? $pt->labels->name : $pt->name;

			$actions[] = array(
				'title' => 'Nieuw ' . $singular,
				'desc'  => 'Maak snel een nieuw item aan voor ' . strtolower($plural) . '.',
				'url'   => admin_url('post-new.php?post_type=' . $pt->name),
				'icon'  => 'dashicons-plus-alt2',
			);
		}

		if (current_user_can('upload_files')) {
			$actions[] = array(
				'title' => 'Media bibliotheek',
				'desc'  => 'Beheer afbeeldingen, PDF’s en andere bestanden.',
				'url'   => admin_url('upload.php'),
				'icon'  => 'dashicons-format-image',
			);
		}

		if (current_user_can('edit_theme_options')) {
			$actions[] = array(
				'title' => 'Menu’s',
				'desc'  => 'Pas navigatie en menu-structuur aan.',
				'url'   => admin_url('nav-menus.php'),
				'icon'  => 'dashicons-menu',
			);
		}

		// Overzicht tegels
		$tiles = array();
		foreach ($post_types as $pt) {
			if (!current_user_can($pt->cap->edit_posts)) continue;

			$count_obj = wp_count_posts($pt->name);
			$total = 0;
			foreach ((array) $count_obj as $status => $num) {
				if ($status === 'trash' || $status === 'auto-draft') continue;
				$total += (int) $num;
			}

			$tiles[] = array(
				'label' => $pt->labels->name ? $pt->labels->name : $pt->name,
				'desc'  => 'Bekijk alle items',
				'count' => $total,
				'url'   => admin_url('edit.php?post_type=' . $pt->name),
				'icon'  => $this->pick_dashicon_for_post_type($pt->name),
			);
		}

		$recent  = $this->get_recent_activity($post_types, 7);
		$updates = $this->get_update_counts();

		$updates_url = admin_url('update-core.php');
		$health_url  = admin_url('site-health.php');
		$mailto      = 'mailto:jeffrey@emonks.nl';
		$phone_number_display = '06-24370701';
		$phone_number_link    = 'tel:+31624370701';

		if (!function_exists('is_plugin_active')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$managewp_plugins = array(
			'worker/init.php',
			'managewp-worker/init.php',
			'managewp-worker/managewp-worker.php',
			'managewp/worker.php',
		);

		$managewp_active = false;
		if (function_exists('is_plugin_active')) {
			foreach ($managewp_plugins as $plugin_file) {
				if (is_plugin_active($plugin_file)) {
					$managewp_active = true;
					break;
				}
			}
		}

		?>
		<div class="emonks-dash">
			<div class="emonks-hero">
				<h1><?php echo esc_html('Welkom ' . $name); ?></h1>
				<div class="site"><?php echo esc_html($site_name); ?></div>
			</div>

			<div class="emonks-grid">
				<!-- Links: Snelle acties -->
				<div class="emonks-card">
					<h2>Snelle acties</h2>

					<div class="emonks-actions">
						<?php if (empty($actions)) : ?>
							<p><?php echo esc_html__('Geen acties beschikbaar voor jouw rechten.', 'default'); ?></p>
						<?php else : ?>
							<?php foreach ($actions as $a) : ?>
								<a class="emonks-action" href="<?php echo esc_url($a['url']); ?>">
									<div class="emonks-iconbox">
										<span class="dashicons <?php echo esc_attr($a['icon']); ?>"></span>
									</div>
									<div>
										<div class="title"><?php echo esc_html($a['title']); ?></div>
										<div class="desc"><?php echo esc_html($a['desc']); ?></div>
									</div>
								</a>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>

				<!-- Rechts: Website overzicht -->
				<div class="emonks-card">
					<h2>Website overzicht</h2>

					<div class="emonks-tiles">
						<?php if (empty($tiles)) : ?>
							<p><?php echo esc_html__('Geen post types gevonden.', 'default'); ?></p>
						<?php else : ?>
							<?php foreach ($tiles as $t) : ?>
								<a class="emonks-tile" href="<?php echo esc_url($t['url']); ?>">
									<div class="emonks-iconbox">
										<span class="dashicons <?php echo esc_attr($t['icon']); ?>"></span>
									</div>
									<div>
										<div class="label"><?php echo esc_html($t['label']); ?></div>
										<div class="muted"><?php echo esc_html($t['desc']); ?></div>
										<div class="count"><?php echo esc_html($t['count']); ?></div>
									</div>
								</a>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="emonks-grid-2">
				<!-- Recente activiteit -->
				<div class="emonks-card">
					<h2>Recente activiteit</h2>

					<?php if (empty($recent)) : ?>
						<p><?php echo esc_html__('Nog geen recente updates gevonden.', 'default'); ?></p>
					<?php else : ?>
						<table class="emonks-recent">
							<tbody>
							<?php foreach ($recent as $p) : ?>
								<?php
								$edit_link = get_edit_post_link($p->ID);
								$type_obj  = get_post_type_object($p->post_type);
								$type_lbl  = ($type_obj && !empty($type_obj->labels->singular_name)) ? $type_obj->labels->singular_name : $p->post_type;

								$when = sprintf(
									'%s • %s',
									$type_lbl,
									human_time_diff(strtotime($p->post_modified_gmt), time()) . ' geleden'
								);
								?>
								<tr>
									<td>
										<a href="<?php echo esc_url($edit_link); ?>">
											<?php echo esc_html(get_the_title($p) ? get_the_title($p) : '(zonder titel)'); ?>
										</a>
										<span class="meta"><?php echo esc_html($when); ?></span>
									</td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>

				<!-- Updates & status -->
				<div class="emonks-card">
					<h2>Updates & status</h2>

					<p style="margin:0 0 12px 0;color:#3c434a;">
						Houd je website veilig en up-to-date.
					</p>

					<div style="display:grid;gap:10px;">
						<a class="emonks-action" href="<?php echo esc_url($updates_url); ?>">
							<div class="emonks-iconbox">
								<span class="dashicons dashicons-update"></span>
							</div>
							<div>
								<div class="title">Updates</div>
								<div class="desc">
									<?php echo esc_html(sprintf('Core: %d • Plugins: %d • Thema’s: %d', (int)$updates['core'], (int)$updates['plugins'], (int)$updates['themes'])); ?>
								</div>
							</div>
							<span class="button button-secondary btn">Check</span>
						</a>

						<a class="emonks-action" href="<?php echo esc_url($health_url); ?>">
							<div class="emonks-iconbox">
								<span class="dashicons dashicons-heart"></span>
							</div>
							<div>
								<div class="title">Site Health</div>
								<div class="desc">Snelle check op prestaties, beveiliging en aanbevelingen.</div>
							</div>
							<span class="button button-secondary btn">Open</span>
						</a>
					</div>
					
					<br><br>
					<h2 class="emonks-title">Emonks onderhoud</h2>

					<div class="emonks-help">
						<?php if ($managewp_active) : ?>
							<p>
								<strong>Emonks onderhoud is actief.</strong> Plugins worden elke maandagochtend vroeg bijgewerkt, uptime monitoring is actief en er worden dagelijks back-ups gemaakt.
							</p>
							<a class="button button-secondary" href="<?php echo esc_url($phone_number_link); ?>">
								Vragen? Bel <?php echo esc_html($phone_number_display); ?>
							</a>
						<?php else : ?>
							<p>
								<strong>Er is geen actief Emonks onderhoud gevonden.</strong> Hierdoor is er geen uptime monitoring, dagelijkse back-ups of support vanuit Emonks actief.
							</p>
							<a class="button button-primary" href="<?php echo esc_url($mailto); ?>">
								Vraag onderhoud aan
							</a>
						<?php endif; ?>
					</div>

					<?php do_action('emonks_dashboard_extra_status_block'); ?>
				</div>
			</div>
		</div>
		<?php
	}
}
