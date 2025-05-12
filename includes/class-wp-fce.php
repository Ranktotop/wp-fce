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
		 * Load Redux Framework
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/redux/redux-core/framework.php';

		/**
		 * The classes responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wp-fce-admin.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wp-fce-admin-ajax.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wp-fce-admin-form.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wp-fce-public.php';

		/**
		 * The model classes
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/models/class-wp-fce-model-base.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/models/class-wp-fce-model-user.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/models/class-wp-fce-model-product.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/models/class-wp-fce-model-fluent-community-entity.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/models/class-wp-fce-model-ipn.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/models/class-wp-fce-model-access-override.php';

		/**
		 * The helper classes
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/helpers/class-wp-fce-helper-base.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/helpers/class-wp-fce-helper-user.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/helpers/class-wp-fce-helper-product.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/helpers/class-wp-fce-helper-fluent-community-entity.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/helpers/class-wp-fce-helper-ipn.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/helpers/class-wp-fce-helper-access-override.php';

		/**
		 * Controller classes
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-fce-access-manager.php';

		/**
		 * The REST API controller for handling IPN callbacks
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-fce-rest-controller.php';

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

		//Register js and css
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

		//Register ajax handler
		$this->loader->add_action('wp_ajax_wp_fce_handle_ajax_callback', $plugin_admin, 'register_ajax_handler');
		$this->loader->add_action('wp_ajax_nopriv_wp_fce_handle_ajax_callback', $plugin_admin, 'register_ajax_handler');

		//Register Redux
		$this->loader->add_action('after_setup_theme', $plugin_admin, 'wp_fce_register_redux_options');

		// Register Admin Pages
		$this->loader->add_action('in_admin_footer', $plugin_admin, 'inject_global_admin_ui');
		$this->loader->add_action('admin_menu', $plugin_admin, 'register_page_manage_products');
		$this->loader->add_action('admin_menu', $plugin_admin, 'register_page_map_products');
		$this->loader->add_action('admin_menu', $plugin_admin, 'register_page_manage_access');
		$this->loader->add_action('admin_init', $plugin_admin, 'register_form_handler');
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

		//Register REST API Route
		$this->loader->add_action('rest_api_init', $plugin_public, 'register_api_routes');

		//Register cronjob
		$this->loader->add_action('wp_fce_cron_check_expirations', $plugin_public, 'run_expiration_cron');

		//Register Front-End Routes#
		$this->loader->add_action('init', $plugin_public, 'register_front_end_routes');

		//Add payments link on user profiles
		$this->loader->add_action('fluent_community/portal_head', $plugin_public, 'enqueue_profile_link_css');
		$this->loader->add_filter('fluent_community/profile_view_data', $plugin_public, 'add_profile_management_link', 10, 2);
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
