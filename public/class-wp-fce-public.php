<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Wp_Fce
 * @subpackage Wp_Fce/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wp_Fce
 * @subpackage Wp_Fce/public
 * @author     Your Name <email@example.com>
 */
class Wp_Fce_Public
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
	 * @param      string    $wp_fce       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($wp_fce, $version)
	{

		$this->wp_fce = $wp_fce;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style($this->wp_fce, plugin_dir_url(__FILE__) . 'css/wp-fce-public.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		wp_enqueue_script($this->wp_fce, plugin_dir_url(__FILE__) . 'js/wp-fce-public.js', array('jquery'), $this->version, false);
	}

	/**
	 * Add a custom link to the FluentCommunity profile menu
	 *
	 * @param array $data
	 * @param object $xprofile
	 * @return array
	 */
	public function add_profile_management_link($data, $xprofile): array
	{
		if (!is_user_logged_in() || get_current_user_id() !== (int) $xprofile->user_id) {
			return $data;
		}

		$data['profile_nav_actions'][] = [
			'css_class' => 'fce-link-orders',
			'title'     => __('Zahlungen', 'wp_fce'),
			'svg_icon'  => '',
			'url'       => site_url('/wp-fce/payments'),
		];

		return $data;
	}

	public function enqueue_profile_link_css(): void
	{
		$css_url = plugins_url('wp-fce/public/css/fce-profile-link.css', dirname(__DIR__));
		echo '<link rel="stylesheet" href="' . esc_url($css_url) . '" media="all">';
		// Font Awesome 5 CDN
		echo '<script src="https://kit.fontawesome.com/8b4c7209d4.js" crossorigin="anonymous"></script>';
	}

	/**
	 * Registriert eine benutzerdefinierte Route für /wp-fce/bestellungen
	 */
	public function register_api_routes(): void
	{
		$controller = new WP_FCE_REST_Controller();
		$controller->register_routes();
	}

	public function register_front_end_routes(): void
	{
		add_rewrite_rule('^wp-fce/payments/?$', 'index.php?wp_fce_page=payments', 'top');
		add_filter('query_vars', function ($vars) {
			$vars[] = 'wp_fce_page';
			return $vars;
		});

		add_filter('template_include', [$this, 'load_custom_template']);
	}

	/**
	 * Liefert das Template für unsere Bestellseite
	 */
	public function load_custom_template($template)
	{
		if (get_query_var('wp_fce_page') === 'payments') {
			return plugin_dir_path(dirname(__FILE__)) . 'templates/wp-fce-payments.php';
		}

		return $template;
	}

	/**
	 * IPN-Request verarbeiten.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function handle_ipn(\WP_REST_Request $request): \WP_REST_Response
	{
		$controller = new Wp_Fce_Rest_Controller();
		return $controller->handle_ipn($request);
	}
}
