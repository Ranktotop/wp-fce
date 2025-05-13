<?php

/**
 * Fired during plugin deactivation.
 *
 * @package WP_Fluent_Community_Extreme
 */

if (! defined('ABSPATH')) {
	exit;
}

class WP_FCE_Deactivator
{

	/**
	 * Plugin deactivation callback.
	 */
	public static function deactivate(): void
	{
		global $wpdb;
		$p = $wpdb->prefix;

		// 1) Alle alten FOREIGN KEYS entfernen
		$map = [
			"{$p}fce_product_user"              => ['fk_fcup_user', 'fk_fcup_product'],
			"{$p}fce_product_space"             => ['fk_fcps_product', 'fk_fcps_space'],
			"{$p}fce_product_access_overrides"  => ['fk_fcao_user', 'fk_fcao_entity'],
			"{$p}fce_access_log"                => ['fk_fcal_user'],
		];
		foreach ($map as $table => $keys) {
			foreach ($keys as $fk) {
				$wpdb->query("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk}`");
			}
		}

		// 2) Cron‐Event löschen
		WP_FCE_Cron::unregister_cronjobs();
	}
}
