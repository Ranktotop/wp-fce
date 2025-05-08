<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Wp_Fce
 * @subpackage Wp_Fce/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wp_Fce
 * @subpackage Wp_Fce/includes
 * @author     Your Name <email@example.com>
 */

use Carbon_Fields\Carbon_Fields;

class Wp_Fce
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wp_Fce_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $wp_fce    The string used to uniquely identify this plugin.
	 */
	protected $wp_fce;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Instance of our REST controller.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var WP_FCE_REST_Controller
	 */
	protected $rest_controller;

	/**
	 * @var WP_FCE_CPT_Product
	 */
	protected $product_cpt;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if (defined('WP_FCE_VERSION')) {
			$this->version = WP_FCE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->wp_fce = 'wp-fce';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_global_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wp_Fce_Loader. Orchestrates the hooks of the plugin.
	 * - Wp_Fce_i18n. Defines internationalization functionality.
	 * - Wp_Fce_Admin. Defines all hooks for the admin area.
	 * - Wp_Fce_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-fce-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-fce-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wp-fce-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wp-fce-public.php';

		/**
		 * The custom post type responsible for handling products
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-fce-product.php';

		/**
		 * The admin options class
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wp-fce-options.php';

		/**
		 * The REST API controller for handling IPN callbacks
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-fce-rest-controller.php';

		/**
		 * The class responsible for handling subscription expiration
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-fce-subscription-expiration-handler.php';

		/**
		 * Helper class for products
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-fce-helper-product.php';

		/**
		 * Helper class for user
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-fce-helper-user.php';


		$this->loader = new Wp_Fce_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wp_Fce_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new Wp_Fce_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{

		$plugin_admin = new Wp_Fce_Admin($this->get_wp_fce(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

		// Options‐Klasse instanziieren
		$options = new \WP_Fluent_Community_Extreme_Options();

		// Carbon Fields booten (falls Du das hier und nicht global machst)
		$this->loader->add_action('after_setup_theme',       $options, 'boot');
		// Feld-Definitionen
		$this->loader->add_action('carbon_fields_register_fields', $options, 'fields');
		$this->loader->add_action('before_delete_post', $plugin_admin, 'cleanup_subscriptions_on_product_delete');
		//Add product tables on user views
		$this->loader->add_action('show_user_profile', $plugin_admin, 'render_user_products_table');
		$this->loader->add_action('edit_user_profile', $plugin_admin, 'render_user_products_table');
		// Ausgabe des Formulars zum manuellen Grant
		$this->loader->add_action('show_user_profile', $plugin_admin, 'render_manual_access_form');
		$this->loader->add_action('edit_user_profile',   $plugin_admin, 'render_manual_access_form');
		// Speichern nach Klick auf „Profile aktualisieren“
		$this->loader->add_action('personal_options_update', $plugin_admin, 'save_manual_access');
		$this->loader->add_action('edit_user_profile_update', $plugin_admin, 'save_manual_access');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{

		$plugin_public = new Wp_Fce_Public($this->get_wp_fce(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

		//load style for profile page link
		$this->loader->add_action(
			'fluent_community/portal_head',
			$plugin_public,
			'enqueue_profile_link_css'
		);

		//add profile page link
		$this->loader->add_filter(
			'fluent_community/profile_view_data',
			$plugin_public,
			'add_profile_management_link',
			10,
			2
		);

		//register orders route
		$this->loader->add_action('init', $plugin_public, 'register_routes');
	}

	/**
	 * Register all global hooks (Carbon Fields, REST, u. Ä.).
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function define_global_hooks()
	{
		// 1) Custom Post Type registrieren
		$this->product_cpt = new WP_FCE_CPT_Product();
		$this->loader->add_action('init', $this->product_cpt, 'register_post_type');

		// 2) REST-Controller initialisieren und Route registrieren
		$this->rest_controller = new WP_FCE_REST_Controller();
		$this->loader->add_action('rest_api_init', $this->rest_controller, 'register_routes');

		// 3) Carbon Fields booten
		$this->loader->add_action('after_setup_theme', $this, 'boot_carbon_fields', 0);

		// Cron-Job für Ablaufprüfung für Mitglieder (static, daher keine instanzierung notwendig)
		$this->loader->add_action('wp_fce_cron_check_expirations', 'WP_FCE_Subscription_Expiration_Handler', 'check_expirations');
	}

	/**
	 * Actually boot Carbon Fields.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function boot_carbon_fields()
	{
		Carbon_Fields::boot();
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_wp_fce()
	{
		return $this->wp_fce;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wp_Fce_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}
}
