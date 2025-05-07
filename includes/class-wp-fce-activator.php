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
		self::db_user_product_subscriptions();
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
          KEY user_product (user_id, product_id)
        ) {$charset_collate};
        ";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);

		if (! wp_next_scheduled('wp_fce_cron_check_expirations')) {
			wp_schedule_event(time(), 'hourly', 'wp_fce_cron_check_expirations');
		}
	}
}
