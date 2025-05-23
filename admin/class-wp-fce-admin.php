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
			'msg_confirm_delete_product' => __('Do you really want to delete the product? This action cannot be undone!', 'wp-fce'),
			'msg_confirm_delete_product_mapping' => __('Do you really want to delete all mappings of this product? This action cannot be undone!', 'wp-fce'),
			'msg_confirm_delete_access_rule' => __('Are you sure you want to delete the rule? This action cannot be undone!', 'wp-fce'),
			'notice_success' => __('Saved successfully!', 'wp-fce'),
			'notice_error' => __('Error occurred!', 'wp-fce'),
			'label_edit' => __('Edit', 'wp-fce'),
			'label_save' => __('Save', 'wp-fce'),
			'label_confirm' => __('Are you sure?', 'wp-fce'),
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
			'menu_title'      => 'FluentCommunity Extreme',
			'page_title'      => 'FluentCommunity Extreme',
			'menu_type'       => 'menu',
			'page_priority' => 80,
			'allow_sub_menu'  => true,
			'page_slug'       => 'fce_settings',
			'display_version' => false,
		]);

		// ► Schlüssel vorab holen (gibt '' zurück, wenn noch nicht gesetzt)
		$api_key_ipn   = Redux::get_option('wp_fce_options', 'api_key_ipn');
		$api_key_admin = Redux::get_option('wp_fce_options', 'api_key_admin');

		// Platzhalter einsetzen, falls leer
		$ipn_param   = $api_key_ipn   ?: '{ipn_api_key}';
		$admin_param = $api_key_admin ?: '{admin_api_key}';

		// Basis-URL der REST-API
		$base_url = untrailingslashit(rest_url('wp-fce/v1'));
		Redux::set_section('wp_fce_options', [
			'title'  => __('API', 'wp-fce'),
			'id'     => 'general_section',
			'desc'   => __('API-Settings', 'wp-fce'),

			// ───────────────────────────────────────────────
			// 1) FELDER ZUERST
			// ───────────────────────────────────────────────
			'fields' => [
				[
					'id'    => 'api_key_ipn',
					'type'  => 'text',
					'title' => __('API Key (IPN)', 'wp-fce'),
					'desc'  => __('Used to validate IPN requests.', 'wp-fce'),
				],
				[
					'id'    => 'api_key_admin',
					'type'  => 'text',
					'title' => __('API Key (Admin)', 'wp-fce'),
					'desc'  => __('Used to validate Admin requests to the REST API.', 'wp-fce'),
				],

				// ────────────────────────────────────────────
				// 2) HINWEIS-BLOCK MIT URL-LISTE
				// ────────────────────────────────────────────
				[
					'id'      => 'api_endpoints_info',
					'type'    => 'raw',            // alternativ 'info'
					'title'   => __('Endpoint-Overview', 'wp-fce'),
					'content' => sprintf(
						'<style>
                    .redux-endpoint-list code{
                        display:inline-block;
                        padding:2px 6px;
                        border-radius:3px;
                        background:#f1f1f1;
                        margin:2px 0;
                        font-family:monospace;
                    }
                </style>
                <div class="redux-endpoint-list">
                    <p><strong>%1$s</strong></p>
                    <ul style="margin-left:1.25rem">
                        <li><code>%1$s/ipn?apikey=%2$s</code> <em>(POST)</em></li>
                        <li><code>%1$s/access/status?user_id={user_id}&amp;entity_id={entity_id}&amp;apikey=%3$s</code> <em>(GET)</em></li>
                        <li><code>%1$s/access/sources?user_id={user_id}&amp;entity_id={entity_id}&amp;apikey=%3$s</code> <em>(GET)</em></li>
                        <li><code>%1$s/mapping?apikey=%3$s</code> <em>(POST)</em></li>
                        <li><code>%1$s/mapping/{product_id}?apikey=%3$s</code> <em>(DELETE)</em></li>
                        <li><code>%1$s/override?apikey=%3$s</code> <em>(POST)</em></li>
                        <li><code>%1$s/override?user_id={user_id}&amp;product_id={product_id}&amp;apikey=%3$s</code> <em>(DELETE)</em></li>
                    </ul>
                </div>',
						esc_url($base_url),
						esc_html($ipn_param),
						esc_html($admin_param)
					),
				],
			],
		]);
		Redux::set_section('wp_fce_options', [
			'title'  => __('Appearance', 'wp-fce'),
			'id'     => 'appearance_section',
			'desc'   => __('Defines the appearance of the payments-overview page', 'wp-fce'),
			'icon'   => 'el el-picture',
			'fields' => [
				[
					'id'       => 'orders_background_image',
					'type'     => 'media',
					'url'      => true,
					'title'    => __('Background Image', 'wp-fce'),
					'subtitle' => __('Is shown on the payments-overview page', 'wp-fce'),
					'desc'     => __('Optional. Supports PNG and JPEG', 'wp-fce'),
				],
			],
		]);
		Redux::set_section('wp_fce_options', [
			'title'  => __('Manage Products', 'wp-fce'),
			'id'     => 'product_admin_link',
			'desc'   => __('Manage products, mappings and access rules', 'wp-fce'),
			'fields' => [
				[
					'id'       => 'product_admin_manage_products',
					'type'     => 'raw',
					'content'  => '<a href="' . admin_url('admin.php?page=fce_admin_manage_products') . '" class="button button-primary">' . __('Manage products', 'wp-fce') . '</a>',
				],
				[
					'id'       => 'product_admin_map_products',
					'type'     => 'raw',
					'content'  => '<a href="' . admin_url('admin.php?page=fce_admin_map_products') . '" class="button button-primary">' . __('Manage mappings', 'wp-fce') . '</a>',
				],
				[
					'id'       => 'product_admin_manage_access',
					'type'     => 'raw',
					'content'  => '<a href="' . admin_url('admin.php?page=fce_admin_manage_access') . '" class="button button-primary">' . __('Manage access', 'wp-fce') . '</a>',
				]
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

	/**
	 * Register the page for mapping products.
	 *
	 * This function registers a new top-level menu page in the WordPress admin area.
	 * The page is accessible for users with the 'manage_options' capability, and is
	 * rendered by the 'render_page_map_products' method of this class.
	 *
	 * The CSS code added in the 'admin_head' action is used to hide the menu item
	 * from the admin menu, so that the page is only accessible via the link in the
	 * FluentCommunity Extreme settings page.
	 */
	public function register_page_map_products(): void
	{
		add_action('admin_head', function () {
			echo '<style>#toplevel_page_fce_admin_map_products { display: none !important; }</style>';
		});
		add_menu_page(
			'Produkte zuweisen',           // Page Title
			'Produkte zuweisen',           // Menu Title
			'manage_options',               // Capability
			'fce_admin_map_products',                 // Menu Slug
			[$this, 'render_page_map_products'], // Callback
			'',                             // Icon
			null                            // Position
		);
	}

	public function register_page_manage_access(): void
	{
		add_action('admin_head', function () {
			echo '<style>#toplevel_page_fce_admin_manage_access { display: none !important; }</style>';
		});
		add_menu_page(
			'Zugänge verwalten',           // Page Title
			'Zugänge verwalten',           // Menu Title
			'manage_options',               // Capability
			'fce_admin_manage_access',                 // Menu Slug
			[$this, 'render_page_manage_access'], // Callback
			'',                             // Icon
			null                            // Position
		);
	}

	/**
	 * Injects global admin UI components for FCE pages.
	 *
	 * This function includes specific UI components, such as a notice box and
	 * a modal confirmation dialog, only on pages related to FluentCommunity Extreme (FCE).
	 * It checks the current screen identifier to ensure these components are
	 * loaded exclusively on FCE-related admin pages.
	 *
	 * @since 1.0.0
	 */

	/**
	 * Injects global admin UI components for FCE pages.
	 *
	 * This function includes specific UI components, such as a notice box and
	 * a modal confirmation dialog, only on pages related to FluentCommunity Extreme (FCE).
	 * It checks the current screen identifier to ensure these components are
	 * loaded exclusively on FCE-related admin pages.
	 *
	 * @since 1.0.0
	 */
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
	 * Renders the page for mapping products.
	 *
	 * This function renders the page used for mapping products. It is called
	 * when the 'fce_admin_map_products' page is accessed in the WordPress admin area.
	 *
	 * The page is rendered by including the 'templates/wp-fce-admin-map-products.php'
	 * file, which contains the HTML code for the page.
	 */

	public function render_page_map_products(): void
	{
		$view = plugin_dir_path(dirname(__FILE__)) . 'templates/wp-fce-admin-map-products.php';
		if (file_exists($view)) {
			include $view;
		}
	}

	public function render_page_manage_access(): void
	{
		$view = plugin_dir_path(dirname(__FILE__)) . 'templates/wp-fce-admin-manage-access.php';
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
	 * Registers the Ajax handler for the admin area.
	 *
	 * This function ensures that the Wp_Fce_Admin_Ajax_Handler class is initialized
	 * and calls the handle_admin_ajax_callback method of that class to register the
	 * form processing callback functions.
	 *
	 * @since 1.0.0
	 */
	public function register_ajax_handler(): void
	{
		//Make sure its initialized
		if (!isset($this->admin_ajax_handler)) {
			$this->admin_ajax_handler = new Wp_Fce_Admin_Ajax_Handler();
		}

		$this->admin_ajax_handler->handle_admin_ajax_callback();
	}

	/**
	 * Registers the form handler for the admin area.
	 *
	 * This function ensures that the Wp_Fce_Admin_Form_Handler class is initialized
	 * and calls the handle_admin_form_callback method of that class to register the
	 * form processing callback functions.
	 *
	 * @since 1.0.0
	 */
	public function register_form_handler(): void
	{
		//Make sure its initialized
		if (!isset($this->admin_form_handler)) {
			$this->admin_form_handler = new Wp_Fce_Admin_Form_Handler();
		}

		$this->admin_form_handler->handle_admin_form_callback();
	}
}
