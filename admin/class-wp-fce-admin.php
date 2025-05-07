<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Wp_Fce
 * @subpackage Wp_Fce/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wp_Fce
 * @subpackage Wp_Fce/admin
 * @author     Your Name <email@example.com>
 */
class Wp_Fce_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $wp_fce    The ID of this plugin.
	 */
	private $wp_fce;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $wp_fce       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($wp_fce, $version)
	{

		$this->wp_fce = $wp_fce;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Fce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Fce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->wp_fce, plugin_dir_url(__FILE__) . 'css/wp-fce-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Fce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Fce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->wp_fce, plugin_dir_url(__FILE__) . 'js/wp-fce-admin.js', array('jquery'), $this->version, false);
	}

	/**
	 * Render a table of active products and their expiration dates
	 * on the user-edit screen in wp-admin.
	 *
	 * @param WP_User $user The current user object.
	 */
	public function render_user_products_table(WP_User $user): void
	{
		$screen = get_current_screen();
		if (! in_array($screen->id, ['user-edit', 'profile'], true)) {
			return;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'fce_user_product_subscriptions';
		$now   = current_time('mysql');

		$subs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT product_id, paid_until
             FROM {$table}
             WHERE user_id = %d
               AND expired_flag = 0
               AND paid_until >= %s
             ORDER BY paid_until ASC",
				$user->ID,
				$now
			)
		);

		echo '<h2>' . esc_html__('Aktive Produkte', 'wp-fce') . '</h2>';
		echo '<table class="wp-list-table widefat fixed striped"><thead>';
		echo '<tr><th>' . esc_html__('Produkt', 'wp-fce') . '</th><th>' . esc_html__('Ablaufdatum', 'wp-fce') . '</th></tr>';
		echo '</thead><tbody>';

		if (empty($subs)) {
			echo '<tr>';
			echo '<td>' . esc_html__('No active subscriptions', 'wp-fce') . '</td>';
			echo '<td>' . "" . '</td>';
			echo '</tr>';
		}

		foreach ($subs as $sub) {
			$post    = get_post((int) $sub->product_id);
			$title   = $post ? get_the_title($post) : sprintf('#%d', $sub->product_id);
			$expires = date_i18n(get_option('date_format'), strtotime($sub->paid_until));

			echo '<tr>';
			echo '<td>' . esc_html($title) . '</td>';
			echo '<td>' . esc_html($expires) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Ausgabe eines Formulars, um manuell einem User
	 * ein Produkt zuzuweisen (mit Ablaufdatum).
	 *
	 * @param WP_User $user
	 */
	public function render_manual_access_form(WP_User $user): void
	{
		// nur auf user-edit / profile
		$screen = get_current_screen();
		if (! in_array($screen->id, ['user-edit', 'profile'], true)) {
			return;
		}

		$helper_product = new WP_FCE_Helper_Product();

		// Alle Produkte holen (z.B. CPT 'fce_product')
		$products = $helper_product->get_products();

		// Nonce für Sicherheit
		wp_nonce_field('wp_fce_manual_access', 'wp_fce_manual_access_nonce');

		echo '<h2>' . esc_html__('Manuelle Zugriffs­vergabe', 'wp-fce') . '</h2>';
		echo '<table class="form-table"><tbody>';

		// Produkt-Select
		echo '<tr>';
		echo '<th><label for="fce_manual_product">' . esc_html__('Produkt', 'wp-fce') . '</label></th>';
		echo '<td><select name="fce_manual_product" id="fce_manual_product">';
		echo '<option value="">' . esc_html__('-- Bitte wählen --', 'wp-fce') . '</option>';
		foreach ($products as $p) {
			printf(
				'<option value="%d">%s</option>',
				$p->ID,
				esc_html(get_the_title($p))
			);
		}
		echo '</select></td>';
		echo '</tr>';

		// Ablaufdatum
		echo '<tr>';
		echo '<th><label for="fce_manual_expires">' . esc_html__('Ablaufdatum', 'wp-fce') . '</label></th>';
		echo '<td><input type="date" name="fce_manual_expires" id="fce_manual_expires"></td>';
		echo '</tr>';

		echo '</tbody></table>';
	}

	/**
	 * Speichert die manuelle Vergabe eines Produkts aus dem Profil-Formular.
	 *
	 * @param int $user_id
	 */
	public function save_manual_access(int $user_id): void
	{
		// Berechtigung & Nonce prüfen
		if (
			! current_user_can('edit_user', $user_id)
			|| ! isset($_POST['wp_fce_manual_access_nonce'])
			|| ! wp_verify_nonce(wp_unslash($_POST['wp_fce_manual_access_nonce']), 'wp_fce_manual_access')
		) {
			return;
		}

		$product_id = intval($_POST['fce_manual_product'] ?? 0);
		$expires    = sanitize_text_field($_POST['fce_manual_expires'] ?? '');

		if ($product_id && $expires) {
			// get the external id of selected product
			if (function_exists('carbon_get_post_meta')) {
				$external = carbon_get_post_meta($product_id, 'fce_external_id');
			} else {
				$external = get_post_meta($product_id, 'fce_external_id', true);
			}
			$external = is_string($external) ? $external : '';
			//if id is not set, do nothing
			if (!$external) {
				return;
			}
			// Convert the YYYY-MM-DD date string into a timestamp at 23:59:59 on that day
			$grant_until_timestamp = strtotime($expires . ' 23:59:59');

			$helper_user = new WP_FCE_Helper_User();

			// User-Subscription hinzufügen
			$helper_user->grant_access($user_id, $external, $grant_until_timestamp, false);
		}
	}

	/**
	 * Löscht beim Entfernen eines Produkts (CST) alle zugehörigen Zeilen
	 * in wp_fce_user_product_subscriptions.
	 *
	 * @param int $post_id Die ID des gerade gelöschten Posts.
	 */
	public function cleanup_subscriptions_on_product_delete(int $post_id): void
	{
		// 1) Nur für unseren Produkt-CPT
		if (get_post_type($post_id) !== 'product') {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'fce_user_product_subscriptions';

		// 2) Alle Subscriptions mit dieser product_id löschen
		$deleted = $wpdb->delete(
			$table,
			['product_id' => $post_id],
			['%d']
		);

		// optional: Debug-Log, falls Du WP_DEBUG_LOG eingeschaltet hast
		if (defined('WP_DEBUG') && WP_DEBUG && $deleted !== false) {
			error_log(sprintf(
				'wp_fce: %d Subscriptions for product %d removed.',
				$deleted,
				$post_id
			));
		}
	}
}
