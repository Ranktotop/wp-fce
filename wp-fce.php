<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://marcmeese.com
 * @since             1.0.0
 * @package           Wp_Fce
 *
 * @wordpress-plugin
 * Plugin Name:       FluentCommunity Extreme Add-On
 * Plugin URI:        http://example.com/wp-fce-uri/
 * Description:       Adds an API to FluentCommunity for supporting external payment processors
 * Version:           1.1.6
 * Author:            Marc Meese
 * Author URI:        https://marcmeese.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-fce
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('WP_FCE_VERSION', '1.1.6');

//  Composer‑Autoloader laden (für Carbon Fields)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require_once __DIR__ . '/vendor/autoload.php';
}

// ——— GLOBALER LOGGER ———
if (! function_exists('fce_log')) {
	/**
	 * Einfacher Logger für WP_FCE.
	 *
	 * @param mixed  $message String, Array oder Objekt.
	 * @param string $level   Optional. Log-Level (info, warning, error).
	 */
	function fce_log($message, string $level = 'info'): void
	{
		if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
			// 1) Zeitstempel holen (WP-Lokalzeit)
			$timestamp = date_i18n('Y-m-d H:i:s');
			// 2) Prefix mit Datum, Plugin-Tag und Level
			$prefix = sprintf('[%s] [WP_FCE][%s] ', $timestamp, strtoupper($level));

			// 3) Loggen
			if (is_array($message) || is_object($message)) {
				error_log($prefix . print_r($message, true));
			} else {
				error_log($prefix . $message);
			}
		}
	}
}
// ————————————————

// Enable GitHub-based plugin updates using plugin-update-checker
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$updateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/Ranktotop/wp-fce/',
	__FILE__,
	'wp-fce'
);

// Verwende GitHub Releases (nicht den Branch-Zip)
$updateChecker->getVcsApi()->enableReleaseAssets();

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-fce-activator.php
 */
function activate_wp_fce()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wp-fce-activator.php';
	Wp_Fce_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-fce-deactivator.php
 */
function deactivate_wp_fce()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wp-fce-deactivator.php';
	Wp_Fce_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wp_fce');
register_deactivation_hook(__FILE__, 'deactivate_wp_fce');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-wp-fce.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wp_fce()
{

	$plugin = new Wp_Fce();
	$plugin->run();
}
run_wp_fce();
