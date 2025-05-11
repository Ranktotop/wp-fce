<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Wp_Fce
 * @subpackage Wp_Fce/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wp_Fce
 * @subpackage Wp_Fce/includes
 * @author     Your Name <email@example.com>
 */
class Wp_Fce_Activator
{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate()
	{
		// Check if FluentCommunity is active
		include_once(ABSPATH . 'wp-admin/includes/plugin.php');
		if (!is_plugin_active('fluent-community/fluent-community.php')) {
			// Prevent activation
			deactivate_plugins(plugin_basename(__FILE__));

			// Show error message
			wp_die(
				'Dieses Plugin erfordert das Plugin "Fluent Community". Bitte installiere und aktiviere es zuerst.',
				'Plugin-Anforderung nicht erfüllt',
				['back_link' => true]
			);
		}

		self::create_db_ipn_log();
		self::create_db_product_access_overrides();
		self::create_db_products();
		self::create_db_product_space();
		self::create_db_product_user();
		flush_rewrite_rules();
	}

	private static function create_db_products(): void
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'fce_products';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		product_id VARCHAR(100) NOT NULL,
		title VARCHAR(255) NOT NULL,
		description TEXT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
		UNIQUE KEY idx_product_id (product_id),
		PRIMARY KEY (id)
	) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function create_db_product_space(): void
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'fce_product_space';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		fce_product_id BIGINT UNSIGNED NOT NULL,
		space_id BIGINT UNSIGNED NOT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		UNIQUE KEY idx_product_space (fce_product_id, space_id),
		INDEX idx_space_id (space_id),
		PRIMARY KEY (id)
	) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function create_db_product_user(): void
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'fce_product_user';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		fce_product_id BIGINT UNSIGNED NOT NULL,
		user_id BIGINT UNSIGNED NOT NULL,
		is_active TINYINT(1) NOT NULL DEFAULT 1,
		expires_on DATETIME DEFAULT NULL,
		source VARCHAR(50) DEFAULT 'ipn',
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		UNIQUE KEY idx_unique_assignment (fce_product_id, user_id),
		KEY idx_user_product (user_id, fce_product_id),
		PRIMARY KEY (id)
	) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function create_db_ipn_log(): void
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'fce_ipn_log';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_email VARCHAR(255) NOT NULL,
		transaction_id VARCHAR(100) NOT NULL,
		ipn_date DATETIME NOT NULL,
		external_product_id VARCHAR(100) NOT NULL,
		source VARCHAR(100) NOT NULL,
		ipn LONGTEXT NOT NULL,
		UNIQUE KEY idx_unique_entry (transaction_id),
		KEY idx_user_product (user_email, external_product_id),
		PRIMARY KEY (id)
	) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function create_db_product_access_overrides(): void
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'fce_product_access_overrides';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL,
		external_product_id VARCHAR(100) NOT NULL,
		granted_until DATETIME NOT NULL,
		reason TEXT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
		UNIQUE KEY idx_unique_override (user_id, external_product_id),
		INDEX idx_granted_until (granted_until),
		PRIMARY KEY (id)
	) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}
}
