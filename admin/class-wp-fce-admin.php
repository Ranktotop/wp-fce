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

	private Wp_Fce_Admin_Ajax_Handler $admin_ajax_handler;
	private Wp_Fce_Admin_Form_Handler $admin_form_handler;

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

		wp_enqueue_script($this->wp_fce . '-modal', plugin_dir_url(__FILE__) . 'js/wpfce-modal.js', array('jquery'), $this->version, false);
		wp_enqueue_script($this->wp_fce, plugin_dir_url(__FILE__) . 'js/wp-fce-admin.js', array('jquery'), $this->version, false);

		/**
		 * In backend there is global ajaxurl variable defined by WordPress itself.
		 *
		 * This variable is not created by WP in frontend. It means that if you want to use AJAX calls in frontend, then you have to define such variable by yourself.
		 * Good way to do this is to use wp_localize_script.
		 *
		 * @link http://wordpress.stackexchange.com/a/190299/90212
		 *      
		 *       You could also pass this datas with the "data" attribute somewhere in your form.
		 */
		//TODO remove if not needed
		wp_localize_script($this->wp_fce, 'wp_fce', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			/**
			 * Create nonce for security.
			 *
			 * @link https://codex.wordpress.org/Function_Reference/wp_create_nonce
			 */
			'_nonce' => wp_create_nonce('security_wp-fce'),
			'msg_confirm_delete_product' => __('Möchtest du das Produkt wirklich entfernen? Dieser Schritt kann nicht rückgängig gemacht werden!', 'wp-fce'),
			'notice_success' => __('Änderung erfolgreich!', 'wp-fce'),
			'notice_error' => __('Änderung fehlgeschlagen!', 'wp-fce'),
			'label_edit' => __('Bearbeiten', 'wp-fce'),
			'label_save' => __('Speichern', 'wp-fce')
		));
	}

	/**
	 * Register options for Redux Admin Page
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function wp_fce_register_redux_options(): void
	{
		if (!class_exists('Redux')) {
			return;
		}
		Redux::disable_demo();

		Redux::set_args('wp_fce_options', [
			'opt_name'        => 'wp_fce_options',
			'menu_title'      => __('FluentCommunity Extreme', 'wp-fce'),
			'page_title'      => __('FluentCommunity Extreme', 'wp-fce'),
			'menu_type'       => 'menu',
			'page_priority' => 80,
			'allow_sub_menu'  => true,
			'page_slug'       => 'fce_settings',
			'display_version' => false,
		]);

		Redux::set_section('wp_fce_options', [
			'title'  => __('API', 'wp-fce'),
			'id'     => 'general_section',
			'desc'   => __('API-Einstellungen', 'wp-fce'),
			'fields' => [
				[
					'id'    => 'api_key',
					'type'  => 'text',
					'title' => __('API Key', 'wp-fce'),
					'desc'  => __('Wird zur Authentifizierung von externen Aufrufen genutzt.', 'wp-fce'),
				],
			],
		]);
		Redux::set_section('wp_fce_options', [
			'title'  => __('Darstellung', 'wp-fce'),
			'id'     => 'appearance_section',
			'desc'   => __('Gestalte das Layout der Bezahlseite.', 'wp-fce'),
			'icon'   => 'el el-picture',
			'fields' => [
				[
					'id'       => 'orders_background_image',
					'type'     => 'media',
					'url'      => true,
					'title'    => __('Hintergrundbild Zahlungsseite', 'wp-fce'),
					'subtitle' => __('Wird auf der Zahlungsseite als Hintergrund angezeigt.', 'wp-fce'),
					'desc'     => __('Optional. Unterstützt JPG, PNG', 'wp-fce'),
				],
			],
		]);
		Redux::set_section('wp_fce_options', [
			'title'  => __('Produkte verwalten', 'wp-fce'),
			'id'     => 'product_admin_link',
			'desc'   => __('Hier kannst du Produkte hinzufügen oder bearbeiten.', 'wp-fce'),
			'fields' => [
				[
					'id'       => 'product_admin_html',
					'type'     => 'raw',
					'content'  => '<a href="' . admin_url('admin.php?page=fce_admin_manage_products') . '" class="button button-primary">' . __('Zur Produktverwaltung', 'wp-fce') . '</a>',
				],
			],
		]);
	}

	/**
	 * Register the page for managing products.
	 *
	 * This function registers a new top-level menu page in the WordPress admin area.
	 * The page is accessible for users with the 'manage_options' capability, and is
	 * rendered by the 'render_page_manage_products' method of this class.
	 *
	 * The CSS code added in the 'admin_head' action is used to hide the menu item
	 * from the admin menu, so that the page is only accessible via the link in the
	 * FluentCommunity Extreme settings page.
	 */
	public function register_page_manage_products(): void
	{
		add_action('admin_head', function () {
			echo '<style>#toplevel_page_fce_admin_manage_products { display: none !important; }</style>';
		});
		add_menu_page(
			'Produkte verwalten',           // Page Title
			'Produkte verwalten',           // Menu Title
			'manage_options',               // Capability
			'fce_admin_manage_products',                 // Menu Slug
			[$this, 'render_page_manage_products'], // Callback
			'',                             // Icon
			null                            // Position
		);
	}

	public function inject_global_admin_ui(): void
	{
		//only on FCE pages
		$current_screen = get_current_screen();
		if (strpos($current_screen->id, 'fce_') === false) {
			return; // Nur für FCE-Seiten
		}

		// Hinweis-Box
		$notice_path = plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wp-fce-admin-notice.php';
		if (file_exists($notice_path)) {
			include $notice_path;
		}

		// Modal
		$modal_path = plugin_dir_path(dirname(__FILE__)) . 'templates/partials/html-modal-confirm.php';
		if (file_exists($modal_path)) {
			include $modal_path;
		}
	}


	/**
	 * Renders the page for managing products.
	 *
	 * This function renders the page used for managing products. It is called
	 * when the 'fce_admin_manage_products' page is accessed in the WordPress admin area.
	 *
	 * The page is rendered by including the 'partials/wp-fce-admin-product-ui.php'
	 * file, which contains the HTML code for the page.
	 */
	public function render_page_manage_products(): void
	{
		$view = plugin_dir_path(dirname(__FILE__)) . 'templates/wp-fce-admin-manage-products.php';
		if (file_exists($view)) {
			include $view;
		}
	}

	/**
	 * Lädt Skripte und Styles für die Produkte-Adminseite.
	 *
	 * @param string $hook_suffix Aktueller Admin-Page-Hook.
	 */
	public function enqueue_products_assets(string $hook_suffix): void
	{
		// nur auf unserer Seite
		if ($hook_suffix !== 'toplevel_page_fce_products') {
			return;
		}

		// JS
		wp_enqueue_script(
			$this->wp_fce . '-products-js',
			plugin_dir_url(__FILE__) . 'js/wp-fce-admin-products.js',
			['jquery'],
			$this->version,
			true
		);

		// Konfig für AJAX im JS
		wp_localize_script(
			$this->wp_fce . '-products-js',
			'FCE_Products',
			[
				'ajaxUrl'    => admin_url('admin-ajax.php'),
				'nonce'      => wp_create_nonce('fce_products_nonce'),
			]
		);

		// (optional) CSS
		wp_enqueue_style(
			$this->wp_fce . '-products-css',
			plugin_dir_url(__FILE__) . 'css/products-admin.css',
			[],
			$this->version
		);
	}




	/**
	 * AJAX-Handler: Liste aller Produkte zurückgeben.
	 *
	 * @return void
	 */
	public function register_ajax_handler(): void
	{
		//Make sure its initialized
		if (!isset($this->admin_ajax_handler)) {
			$this->admin_ajax_handler = new Wp_Fce_Admin_Ajax_Handler();
		}

		$this->admin_ajax_handler->handle_admin_ajax_callback();
	}

	public function register_form_handler(): void
	{
		//Make sure its initialized
		if (!isset($this->admin_form_handler)) {
			$this->admin_form_handler = new Wp_Fce_Admin_Form_Handler();
		}

		$this->admin_form_handler->handle_admin_form_callback();
	}


	/**
	 * Handles the saving of product UI data in the admin area.
	 *
	 * This function checks if the current context is an admin page and if the
	 * Carbon Fields theme options function is available. It performs the following actions:
	 * - Inserts a new product into the database if the product ID and title are provided
	 *   and the product does not already exist.
	 * - Updates the title and description of existing products based on user input.
	 * - Deletes a product if the delete action is triggered.
	 *
	 * @since 1.0.0
	 * @return void
	 */

	public function handle_product_ui_save(): void
	{
		if (!is_admin() || !function_exists('carbon_get_theme_option')) {
			return;
		}

		global $wpdb;
		$table         = $wpdb->prefix . 'fce_products';
		$redirect_base = remove_query_arg(['fce_error', 'fce_success'], $_SERVER['REQUEST_URI']);

		// 1) Insert-Block
		$product_id  = sanitize_text_field(carbon_get_theme_option('fce_new_product_id'));
		$title       = sanitize_text_field(carbon_get_theme_option('fce_new_product_title'));
		$description = sanitize_textarea_field(carbon_get_theme_option('fce_new_product_description'));

		if ($product_id && $title) {
			$exists = (int) $wpdb->get_var(
				$wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE product_id = %s", $product_id)
			);

			if ($exists) {
				// Duplikat → weiterleiten mit Fehler-Query
				wp_safe_redirect(
					add_query_arg('fce_error', urlencode('Produkt-ID existiert bereits. Es wurden keine Änderungen gespeichert!'), $redirect_base)
				);
				exit;
			}

			$wpdb->insert($table, [
				'product_id'  => $product_id,
				'title'       => $title,
				'description' => $description,
				'created_at'  => current_time('mysql'),
			]);
		}

		// 2) Update-Block
		foreach ($_POST['fce_product_edit_title'] ?? [] as $id => $edit_title) {
			$edit_description = $_POST['fce_product_edit_description'][$id] ?? '';
			$wpdb->update($table, [
				'title'       => sanitize_text_field($edit_title),
				'description' => sanitize_textarea_field($edit_description),
				'updated_at'  => current_time('mysql'),
			], ['id' => (int) $id]);
		}

		// 3) Delete-Block
		if (!empty($_POST['fce_product_delete'])) {
			$wpdb->delete($table, ['id' => (int) $_POST['fce_product_delete']]);
		}

		// Erfolg → weiterleiten mit Success-Query
		wp_safe_redirect(
			add_query_arg('fce_success', urlencode('Einstellungen gespeichert'), $redirect_base)
		);
		exit;
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

		$user_model = new Wp_Fce_Model_User($user);

		// Get all product-mappings the user has access to
		$accesses = $user_model->get_all_active_product_access();

		echo '<h2>' . esc_html__('Aktive Produkte', 'wp-fce') . '</h2>';
		echo '<table class="wp-list-table widefat fixed striped"><thead>';
		echo '<tr><th>' . esc_html__('Produkt', 'wp-fce') . '</th><th>' . esc_html__('Ablaufdatum', 'wp-fce') . '</th><th>' . esc_html__('Quelle', 'wp-fce') . '</th></tr>';
		echo '</thead><tbody>';

		if (empty($accesses)) {
			echo '<tr>';
			echo '<td>' . esc_html__('No active subscriptions', 'wp-fce') . '</td>';
			echo '<td>' . "" . '</td>';
			echo '<td>' . "" . '</td>';
			echo '</tr>';
		}

		foreach ($accesses as $access) {
			$post  = get_post((int) $access['mapping']['id']);
			$title = $post ? get_the_title($post) : sprintf('#%d', $access['mapping']['id']);
			$expires = date_i18n(get_option('date_format'), $access['expires']);

			echo '<tr>';
			echo '<td>' . esc_html($title) . '</td>';
			echo '<td>' . esc_html($expires) . '</td>';
			echo '<td>' . esc_html($access['source'] === 'override' ? 'Admin' : 'IPN') . '</td>';
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
		$screen = get_current_screen();
		if (! in_array($screen->id, ['user-edit', 'profile'], true)) {
			return;
		}

		$helper_product = new WP_FCE_Helper_Product();
		$products = $helper_product->get_products();

		wp_nonce_field('wp_fce_manual_access', 'wp_fce_manual_access_nonce');

		echo '<h2>' . esc_html__('Manuelle Zugriffs­vergabe', 'wp-fce') . '</h2>';
		echo '<table class="form-table"><tbody>';

		// Produktauswahl
		echo '<tr>';
		echo '<th><label for="fce_manual_product">' . esc_html__('Produkt', 'wp-fce') . '</label></th>';
		echo '<td><select name="fce_manual_product" id="fce_manual_product">';
		echo '<option value="">' . esc_html__('-- Bitte wählen --', 'wp-fce') . '</option>';
		foreach ($products as $product) {
			if (function_exists('carbon_get_post_meta')) {
				$external_id = carbon_get_post_meta($product->ID, 'fce_external_id');
			} else {
				$external_id = '';
			}
			if (!$external_id) {
				continue;
			}
			printf(
				'<option value="%s">%s</option>',
				esc_attr($external_id),
				esc_html(get_the_title($product))
			);
		}
		echo '</select></td>';
		echo '</tr>';

		// Ablaufdatum
		echo '<tr>';
		echo '<th><label for="fce_manual_expires">' . esc_html__('Ablaufdatum', 'wp-fce') . '</label></th>';
		echo '<td><input type="date" name="fce_manual_expires" id="fce_manual_expires"></td>';
		echo '</tr>';

		// Grund (optional)
		echo '<tr>';
		echo '<th><label for="fce_manual_reason">' . esc_html__('Grund (optional)', 'wp-fce') . '</label></th>';
		echo '<td><input type="text" name="fce_manual_reason" id="fce_manual_reason" class="regular-text"></td>';
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
		if (
			! current_user_can('edit_user', $user_id)
			|| ! isset($_POST['wp_fce_manual_access_nonce'])
			|| ! wp_verify_nonce(wp_unslash($_POST['wp_fce_manual_access_nonce']), 'wp_fce_manual_access')
		) {
			return;
		}

		$external_product_id = sanitize_text_field($_POST['fce_manual_product'] ?? '');
		$expires = sanitize_text_field($_POST['fce_manual_expires'] ?? '');
		$reason = sanitize_text_field($_POST['fce_manual_reason'] ?? '');

		if (!$external_product_id || !$expires) {
			return;
		}

		$granted_until = strtotime($expires . ' 23:59:59');
		if (!$granted_until) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'fce_product_access_overrides';

		$data = [
			'user_id' => $user_id,
			'external_product_id' => $external_product_id,
			'granted_until' => date('Y-m-d H:i:s', $granted_until),
			'reason' => $reason,
			'updated_at' => current_time('mysql'),
		];

		// Wenn es schon einen Override gibt, aktualisieren – sonst einfügen
		$existing = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM {$table} WHERE user_id = %d AND external_product_id = %s",
			$user_id,
			$external_product_id
		));

		if ($existing) {
			$wpdb->update($table, $data, ['id' => $existing]);
		} else {
			$data['created_at'] = current_time('mysql');
			$wpdb->insert($table, $data);
		}
	}

	/**
	 * Löscht beim Entfernen eines Produkts (CST) alle zugehörigen Zeilen
	 * in wp_fce_user_subscriptions.
	 *
	 * @param int $post_id Die ID des gerade gelöschten Posts.
	 */
	public function revoke_access_to_deleted_product_mapping(int $post_id): void
	{
		// 1) Only for product mapping types
		if (get_post_type($post_id) !== 'fce_product_mapping') {
			return;
		}

		// Externe Produkt-ID aus dem Carbon-Feld lesen
		$helper_product = new WP_FCE_Helper_Product();
		$external_id = $helper_product->get_external_id_by_post_id($post_id);
		if (!$external_id) {
			return;
		}
		// get user which 
		$mapping = $helper_product->get_product_mapping_by_external_product_id($external_id);

		$space_ids = $mapping['space_ids'] ?? [];
		$course_ids = $mapping['course_ids'] ?? [];

		// TODO change this as following: revoke access from all users which currently have access to mapped spaces/courses of the deleted product.
		// Make sure than the access is only revoked, if no other mapping exists with same space/course which the user has valid access to
		global $wpdb;
		$table = $wpdb->prefix . 'fce_user_subscriptions';

		// 2) Alle Subscriptions mit dieser product_mapping_id löschen
		$deleted = $wpdb->delete(
			$table,
			['product_mapping_id' => $post_id],
			['%d']
		);

		// Admin-Overrides mit dieser external_product_id löschen
		if ($external_id) {
			$table = $wpdb->prefix . 'fce_product_access_overrides';
			$deleted = $wpdb->delete($table, [
				'external_product_id' => $external_id
			]);
		}
	}

	/**
	 * Store the current space/course mappings before an update.
	 *
	 * @param int     $post_id     The post ID being updated.
	 * @param array $data The raw post data array (wp_update_post).
	 */
	public function cache_product_mapping(int $post_id, array $data): void
	{
		// Fetch old IDs from post meta (Carbon Fields stores arrays)
		$oldSpaces  = carbon_get_post_meta($post_id, 'fce_spaces')  ?: [];
		$oldCourses = carbon_get_post_meta($post_id, 'fce_courses') ?: [];

		// Cache for comparison in save_post hook
		self::$oldMappings[$post_id] = [
			'spaces'  => array_map('intval', $oldSpaces),
			'courses' => array_map('intval', $oldCourses),
		];
	}

	/**
	 * Wrapper, aufgerufen von Carbon Fields nach dem Speichern des Post-Meta.
	 *
	 * @param int $post_id Die ID des gerade gespeicherten Posts.
	 */
	public function update_product_access_after_cf(int $post_id): void
	{
		// Nur für unseren CPT
		$post = get_post($post_id);
		if (! $post || $post->post_type !== 'fce_product_mapping') {
			return;
		}

		// Existiert ein alter Cache-Eintrag? Dann war es ein Update (kein Insert).
		$is_update = isset(self::$oldMappings[$post_id]);

		// Den alten Sync-Handler mit den gewohnten Argumenten aufrufen:
		$this->update_product_access($post_id, $post, $is_update);
	}

	/**
	 * Reads the cached product mapping and grants/revokes access for updated fields
	 * Runs for example if a product mapping has been changed
	 *
	 * @param int     $post_id The post ID that was just saved.
	 * @param WP_Post $post    The post object after save.
	 * @param bool    $update  Whether this is an existing post being updated.
	 */
	public function update_product_access(int $post_id, WP_Post $post, bool $update): void
	{
		// Only run on updates, not on initial insert
		if (! $update) {
			return;
		}

		// Load cached mappings
		$cached = self::$oldMappings[$post_id] ?? ['spaces' => [], 'courses' => [], 'external_id' => ""];
		$cachedSpaces = $cached['spaces'];
		$cachedCourses = $cached['courses'];

		// Load new mappings
		$newSpaces  = carbon_get_post_meta($post_id, 'fce_spaces')  ?: [];
		$newCourses = carbon_get_post_meta($post_id, 'fce_courses') ?: [];

		// Determine additions/removals
		$addedSpaces   = array_diff($newSpaces,  $cachedSpaces);
		$removedSpaces = array_diff($cachedSpaces, $newSpaces);
		$addedCourses  = array_diff($newCourses,  $cachedCourses);
		$removedCourses = array_diff($cachedCourses, $newCourses);

		// if all are empty, do nothing
		if (empty($addedSpaces) && empty($removedSpaces) && empty($addedCourses) && empty($removedCourses)) {
			unset(self::$oldMappings[$post_id]);
			return;
		}

		$helper_product = new WP_FCE_Helper_Product();
		// Get users with active subscriptions
		$helper_user = new WP_FCE_Helper_User();
		$active_user_ids = $helper_user->get_users_for_product_mapping($post_id);

		foreach ($active_user_ids as $user_id) {
			// Grant access for newly added spaces and courses
			foreach ($addedSpaces as $space_id) {
				$helper_user->grant_space_access($user_id, $space_id);
			}
			foreach ($addedCourses as $course_id) {
				$helper_user->grant_course_access($user_id, $course_id);
			}

			// Revoke access for removed spaces/courses only if no other active product grants it
			foreach ($removedSpaces as $space_id) {
				// TODO check if something has to be changed here
				// Check for other active mappings including this space
				$other_products = $helper_product->get_product_mappings_by_space($space_id, [$post_id]);
				// Check if the user has active subscription to any of the products
				$keep = $helper_user->has_access_to_product_mapping($user_id, $other_products);

				// if no other product grants access, revoke
				if (! $keep) {
					$helper_user->revoke_space_access($user_id, $space_id);
				}
			}

			// Revoke access for removed courses only if no other product grants it
			foreach ($removedCourses as $course_id) {
				// Check for other active mappings including this course
				$other_products = $helper_product->get_product_mappings_by_course($course_id, [$post_id]);
				// Check if the user has active subscription to any of the products
				$keep = $helper_user->has_access_to_product_mapping($user_id, $other_products);

				// if no other product grants access, revoke
				if (! $keep) {
					$helper_user->revoke_course_access($user_id, $course_id);
				}
			}
		}

		// Clean up cache
		unset(self::$oldMappings[$post_id]);
	}

	/**
	 * Validate that external_product_id is unique and non-empty.
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 * @param bool $update
	 * @return void
	 */
	public function validate_external_product_id_on_save(int $post_id, WP_Post $post, bool $update): void
	{
		// Avoid autosave, revisions etc.
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		// Only for our post type
		if ($post->post_type !== 'fce_product_mapping') {
			return;
		}

		// Skip if not manually saving via the editor
		if (!isset($_POST['post_title'])) {
			return;
		}

		// Holen des Wertes direkt aus dem $_POST Array von Carbon Fields
		$raw_value = $_POST['carbon_fields_compact_input']['_fce_external_id'] ?? '';
		$external_id = trim((string) $raw_value);

		if ($external_id === '') {
			// Redirect mit Fehler
			$redirect_url = add_query_arg('wp_fce_error', urlencode(__('Fehler: Die External Product ID darf nicht leer sein.', 'wp-fce')), get_edit_post_link($post_id, 'url'));
			wp_redirect($redirect_url);
			exit;
		}
		$helper_product = new WP_FCE_Helper_Product();
		if (!$helper_product->is_unique_external_product_id($external_id, $post_id)) {
			// Redirect mit Fehler
			$redirect_url = add_query_arg('wp_fce_error', urlencode(__('Diese External Product ID ist bereits vergeben.', 'wp-fce')), get_edit_post_link($post_id, 'url'));
			wp_redirect($redirect_url);
			exit;
		}
	}

	/**
	 * Display error messages from save_post_fce_product_mapping.
	 *
	 * @since 1.0.0
	 */
	public function display_admin_notices(): void
	{
		if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
			settings_errors('fce_products');
		}
	}
}
