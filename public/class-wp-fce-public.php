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
	private Wp_Fce_Public_Ajax_Handler $public_ajax_handler;
	private Wp_Fce_Public_Form_Handler $public_form_handler;

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
			'title'     => __('Control Panel', 'wp-fce'),
			'svg_icon'  => '',
			'url'       => site_url('/wp-fce/controlpanel'),
		];

		return $data;
	}

	public function enqueue_profile_link_css(): void
	{
		$css_url = plugins_url('wp-fce/public/css/fce-profile-link.css', dirname(__DIR__));
		echo '<link rel="stylesheet" href="' . esc_url($css_url) . '" media="all">';
		// Font Awesome 5 CDN
		$font_awesome_url = WP_FCE_Helper_Options::get_string_option('font_awesome_cdn_url');
		// if url is not false or empty use default cdn url
		if ($font_awesome_url === false || empty($font_awesome_url)) {
			$font_awesome_url = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js";
		}
		echo '<script src="' . esc_url($font_awesome_url) . '" crossorigin="anonymous"></script>';
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
		add_rewrite_rule('^wp-fce/controlpanel/?$', 'index.php?wp_fce_page=controlpanel', 'top');
		add_filter('query_vars', function ($vars) {
			$vars[] = 'wp_fce_page';
			return $vars;
		});

		add_filter('template_include', [$this, 'load_custom_template']);
		// Prüfen ob Rules neu geschrieben werden müssen
		$rules = get_option('rewrite_rules');
		if (!isset($rules['^wp-fce/controlpanel/?$'])) {
			flush_rewrite_rules(false);
		}
	}

	/**
	 * Liefert das Template für unsere Bestellseite
	 */
	public function load_custom_template($template)
	{
		if (get_query_var('wp_fce_page') === 'controlpanel') {
			return plugin_dir_path(dirname(__FILE__)) . 'templates/controlpanel/wp-fce-controlpanel.php';
		}

		return $template;
	}

	public function redirect_to_portal()
	{
		// Prüfen ob wir wirklich auf der Root-URL sind
		$request_uri = $_SERVER['REQUEST_URI'] ?? '';
		$parsed_home = parse_url(home_url());
		$home_path = $parsed_home['path'] ?? '/';

		// Normalisiere die Pfade
		$home_path = rtrim($home_path, '/') . '/';
		$request_path = parse_url($request_uri, PHP_URL_PATH);
		$request_path = rtrim($request_path, '/') . '/';

		// Nur weiterleiten wenn wir exakt auf der Home-URL sind
		if ($request_path !== $home_path) {
			return;
		}

		// Diese Parameter verhindern eine Weiterleitung
		$blocking_params = [
			'page',
			'p',
			'post_type',
			'preview',
			's',
			'author',
			'category_name',
			'tag',
			'action',
			'doing_wp_cron',
			"page_id"
			// weitere WordPress-spezifische Parameter nach Bedarf
		];

		// Prüfen ob einer der blockierenden Parameter vorhanden ist
		if (!empty($_GET)) {
			foreach ($blocking_params as $param) {
				if (isset($_GET[$param])) {
					return; // Nicht weiterleiten wenn blockierender Parameter gefunden
				}
			}
		}

		$activated = WP_FCE_Helper_Options::get_bool_option('redirect_home_to_portal', false);
		if (!$activated) {
			return;
		}

		// Portal URL von Fluent Community ermitteln
		$portal_url = WP_FCE_Helper_Options::get_fluent_portal_url(include_query: true);

		if (!$portal_url) {
			return;
		}

		// Alle GET-Parameter übernehmen (z.B. UTM-Parameter)
		if (!empty($_GET)) {
			$portal_url = add_query_arg($_GET, $portal_url);
		}

		// Weiterleitung durchführen
		wp_safe_redirect($portal_url, 302);
		exit;
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

	/**
	 * Registers the form handler for the public area.
	 *
	 * This function ensures that the Wp_Fce_Public_Form_Handler class is initialized
	 * and calls the handle_public_form_callback method of that class to register the
	 * form processing callback functions.
	 *
	 * @since 1.0.0
	 */
	public function register_form_handler(): void
	{
		//Make sure its initialized
		if (!isset($this->public_form_handler)) {
			$this->public_form_handler = new Wp_Fce_Public_Form_Handler();
		}

		$this->public_form_handler->handle_public_form_callback();
	}

	/**
	 * Registers the Ajax handler for the public area.
	 *
	 * This function ensures that the Wp_Fce_Public_Ajax_Handler class is initialized
	 * and calls the handle_public_ajax_callback method of that class to register the
	 * form processing callback functions.
	 *
	 * @since 1.0.0
	 */
	public function register_ajax_handler(): void
	{
		//Make sure its initialized
		if (!isset($this->public_ajax_handler)) {
			$this->public_ajax_handler = new Wp_Fce_Public_Ajax_Handler();
		}

		$this->public_ajax_handler->handle_public_ajax_callback();
	}

	public function register_fluent_community_filters()
	{
		$preventGifConversion = Wp_Fce_Helper_Options::get_bool_option('prevent_gif_conversion', false);

		if ($preventGifConversion) {
			add_filter('fluent_community/convert_image_to_webp', function ($convert, $file) {
				if (isset($file['type']) && $file['type'] === 'image/gif') {
					return false;
				}
				return $convert;
			}, 10, 2);
		}
	}
}
