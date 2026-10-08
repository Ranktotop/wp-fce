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
	 * Add a "My Purchases" link to the own FluentCommunity profile.
	 *
	 * The link is only shown if the user has purchases. It points to the configured
	 * payment history page if there are external payments (IPN), otherwise directly
	 * to the FluentCart customer account.
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

		$link_url = $this->get_purchases_link_url(WP_FCE_Helper_User::get_by_id(get_current_user_id()));
		if (!$link_url) {
			return $data;
		}

		$data['profile_nav_actions'][] = [
			'css_class' => 'fce-link-orders',
			'title'     => __('My Purchases', 'wp-fce'),
			'svg_icon'  => '',
			'url'       => $link_url,
		];

		return $data;
	}

	/**
	 * Determines where the "My Purchases" link of a user points to.
	 *
	 * @param WP_FCE_Model_User $user
	 * @return string|false False if the user has no purchases or no matching target is configured.
	 */
	private function get_purchases_link_url(WP_FCE_Model_User $user): string|false
	{
		$history_url = WP_FCE_Helper_Options::get_string_option('profile_link_url');
		$has_external_payments = !empty(WP_FCE_Helper_Ipn_Log::get_latest_ipns_for_user($user->get_email()));

		if ($has_external_payments && $history_url) {
			return $history_url;
		}

		if (WP_FCE_Helper_Fluent_Cart::user_has_orders($user)) {
			return WP_FCE_Helper_Fluent_Cart::get_customer_account_url() ?: $history_url;
		}

		return false;
	}

	public function enqueue_profile_link_css(): void
	{
		$css_url = plugins_url('wp-fce/public/css/fce-profile-link.css', dirname(__DIR__));
		echo '<link rel="stylesheet" href="' . esc_url($css_url) . '" media="all">';
		// Dashicons from WordPress core for the link icon
		echo '<link rel="stylesheet" href="' . esc_url(includes_url('css/dashicons.min.css')) . '" media="all">';
	}

	/**
	 * Registriert die REST-API-Routen des Plugins.
	 */
	public function register_api_routes(): void
	{
		$controller = new WP_FCE_REST_Controller();
		$controller->register_routes();
	}

	/**
	 * Registers all shortcodes of the plugin.
	 */
	public function register_shortcodes(): void
	{
		add_shortcode('wp_fce_payment_history', [$this, 'render_payment_history_shortcode']);
	}

	/**
	 * Renders the payment history of the current user.
	 *
	 * Usage: [wp_fce_payment_history]
	 *
	 * @return string
	 */
	public function render_payment_history_shortcode(): string
	{
		if (!is_user_logged_in()) {
			return '<p>' . esc_html__('Please log in to see your payment history.', 'wp-fce') . '</p>';
		}

		$user = WP_FCE_Helper_User::get_by_id(get_current_user_id());
		$ipns = WP_FCE_Helper_Ipn_Log::get_latest_ipns_for_user($user->get_email());
		$payment_stats = $this->get_payment_statistics($ipns);
		$customer_account_url = WP_FCE_Helper_Fluent_Cart::user_has_orders($user)
			? WP_FCE_Helper_Fluent_Cart::get_customer_account_url()
			: false;

		wp_enqueue_style(
			$this->wp_fce . '-payment-history',
			plugin_dir_url(__FILE__) . 'css/wp-fce-payment-history.css',
			[],
			$this->version
		);

		ob_start();
		include plugin_dir_path(dirname(__FILE__)) . 'templates/shortcodes/wp-fce-payment-history.php';
		return ob_get_clean();
	}

	/**
	 * Calculates payment statistics from an array of IPN logs.
	 *
	 * @param WP_FCE_Model_Ipn_Log[] $ipns
	 * @return array Keys 'total_payments', 'recent_payments', 'payment_sources'.
	 */
	private function get_payment_statistics(array $ipns): array
	{
		$stats = [
			'total_payments'  => count($ipns),
			'payment_sources' => [],
			'recent_payments' => 0,
		];

		$thirty_days_ago = new DateTime('-30 days', wp_timezone());

		foreach ($ipns as $ipn) {
			$source = $ipn->get_source();
			$stats['payment_sources'][$source] = ($stats['payment_sources'][$source] ?? 0) + 1;

			if ($ipn->get_ipn_date() > $thirty_days_ago) {
				$stats['recent_payments']++;
			}
		}

		return $stats;
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
