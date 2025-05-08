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


		self::db_user_product_subscriptions();
		self::create_db_user_management_links();
	}

	/**
	 * Legt die Tabelle wp_fce_user_product_subscriptions an (oder aktualisiert sie) und plant den Cron-Job.
	 *
	 * @since 1.0.0
	 */
	private static function db_user_product_subscriptions()
	{
		global $wpdb;

		$table_name      = $wpdb->prefix . 'fce_user_product_subscriptions';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "
			CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			product_id BIGINT UNSIGNED NOT NULL,
			paid_until DATETIME NOT NULL,
			expired_flag TINYINT(1) NOT NULL DEFAULT 0,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY user_product (user_id, product_id)
			) {$charset_collate};
			";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);

		if (! wp_next_scheduled('wp_fce_cron_check_expirations')) {
			wp_schedule_event(time(), 'hourly', 'wp_fce_cron_check_expirations');
		}
	}

	/**
	 * Create the custom table to store user management URLs linked to products
	 *
	 * @return void
	 */
	private static function create_db_user_management_links(): void
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'fce_management_links';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        product_id VARCHAR(100) NOT NULL,
        source VARCHAR(100) NOT NULL,
        management_url TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL,
        UNIQUE KEY idx_unique_entry (user_id, product_id, source, management_url(255))
    ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}
}
