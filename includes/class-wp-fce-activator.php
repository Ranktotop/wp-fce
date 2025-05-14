<?php

/**
 * Fired during plugin activation.
 *
 * @package WP_Fluent_Community_Extreme
 */

if (! defined('ABSPATH')) {
	exit;
}

class WP_FCE_Activator
{

	/**
	 * Plugin activation callback.
	 */
	public static function activate(): void
	{
		// Require FluentCommunity
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		if (! is_plugin_active('fluent-community/fluent-community.php')) {
			deactivate_plugins(plugin_basename(__FILE__));
			wp_die(
				__('Dieses Plugin erfordert das Plugin "Fluent Community". Bitte installiere und aktiviere es zuerst.', 'wp-fce'),
				__('Plugin-Anforderung nicht erfüllt', 'wp-fce'),
				['back_link' => true]
			);
		}

		// 1) Tabellen (ohne FK) anlegen
		self::create_db_ipn_log();
		self::create_db_products();
		self::create_db_product_user();
		self::create_db_product_space();
		self::create_db_product_access_overrides();
		self::create_db_access_log();

		// 2) FOREIGN KEY Constraints einmalig hinzufügen
		self::add_foreign_keys();

		// 3) Rewrite-Regeln flushen & Cron schedule
		flush_rewrite_rules();
		WP_FCE_Cron::register_cronjobs();
	}

	private static function create_db_ipn_log(): void
	{
		global $wpdb;
		$t = $wpdb->prefix . 'fce_ipn_log';
		$c = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE `{$t}` (
            `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_email`          VARCHAR(255)      NOT NULL,
            `transaction_id`      VARCHAR(100)      NOT NULL,
            `ipn_date`            DATETIME          NOT NULL,
            `external_product_id` VARCHAR(100)      NOT NULL,
            `source`              VARCHAR(100)      NOT NULL,
            `ipn`                 LONGTEXT          NOT NULL,
            `ipn_hash`            CHAR(32)          NOT NULL,
            PRIMARY KEY  (`id`),
            UNIQUE KEY  `idx_ipn_hash`        (`ipn_hash`),
            KEY         `idx_user_email_prod` (`user_email`,`external_product_id`)
        ) ENGINE=InnoDB {$c};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function create_db_products(): void
	{
		global $wpdb;
		$t = $wpdb->prefix . 'fce_products';
		$c = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE `{$t}` (
            `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `sku`         VARCHAR(100)      NOT NULL,
            `name`        VARCHAR(200)      NOT NULL,
            `description` TEXT              NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_sku` (`sku`)
        ) ENGINE=InnoDB {$c};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function create_db_product_user(): void
	{
		global $wpdb;
		$t = $wpdb->prefix . 'fce_product_user';
		$c = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE `{$t}` (
            `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id`        BIGINT UNSIGNED NOT NULL,
            `product_id`     BIGINT UNSIGNED NOT NULL,
            `source`         ENUM('ipn','admin','import','sync') NOT NULL DEFAULT 'ipn',
            `transaction_id` VARCHAR(100)      DEFAULT NULL,
            `start_date`     DATETIME          NOT NULL,
            `expiry_date`    DATETIME          DEFAULT NULL,
            `status`         ENUM('active','expired','revoked','cancelled') NOT NULL DEFAULT 'active',
            `note`           TEXT              DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_user_product` (`user_id`,`product_id`)
        ) ENGINE=InnoDB {$c};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function create_db_product_space(): void
	{
		global $wpdb;
		$t = $wpdb->prefix . 'fce_product_space';
		$c = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE `{$t}` (
            `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `product_id` BIGINT UNSIGNED NOT NULL,
            `space_id`   BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_product_space` (`product_id`,`space_id`)
        ) ENGINE=InnoDB {$c};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function create_db_product_access_overrides(): void
	{
		global $wpdb;

		$t = $wpdb->prefix . 'fce_product_access_overrides';
		$c = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE `{$t}` (
		`id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		`user_id`       BIGINT UNSIGNED NOT NULL,
		`product_id`    BIGINT UNSIGNED NOT NULL,
		`override_type` ENUM('allow','deny') NOT NULL,
		`source`        ENUM('admin','import') NOT NULL DEFAULT 'admin',
		`comment`       TEXT NULL,
		`created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`valid_until`   DATETIME    NULL,
		PRIMARY KEY (`id`),
		KEY `idx_user_product` (`user_id`, `product_id`)
	) ENGINE=InnoDB {$c};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function create_db_access_log(): void
	{
		global $wpdb;
		$t = $wpdb->prefix . 'fce_access_log';
		$c = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE `{$t}` (
            `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id`      BIGINT UNSIGNED NOT NULL,
            `entity_type`  ENUM('space','course') NOT NULL,
            `entity_id`    BIGINT UNSIGNED NOT NULL,
            `evaluated_at` DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `decision`     TINYINT(1)        NOT NULL,
            `reason`       TEXT              NULL,
            `source_id`    BIGINT UNSIGNED   NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_log_user_entity` (`user_id`,`entity_type`,`entity_id`)
        ) ENGINE=InnoDB {$c};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	/**
	 * Fügt alle fehlenden FOREIGN KEY-Constraints per ALTER TABLE hinzu.
	 * Wird nur beim initialen Activate() ausgeführt.
	 */
	private static function add_foreign_keys(): void
	{
		global $wpdb;
		$p = $wpdb->prefix;

		$fks = [
			// product_user → users / products
			"ALTER TABLE `{$p}fce_product_user`
         ADD CONSTRAINT `fk_fcup_user`
         FOREIGN KEY (`user_id`)    REFERENCES `{$p}users`(`ID`) ON DELETE CASCADE",
			"ALTER TABLE `{$p}fce_product_user`
         ADD CONSTRAINT `fk_fcup_product`
         FOREIGN KEY (`product_id`) REFERENCES `{$p}fce_products`(`id`) ON DELETE CASCADE",

			// product_space → products / spaces
			"ALTER TABLE `{$p}fce_product_space`
         ADD CONSTRAINT `fk_fcps_product`
         FOREIGN KEY (`product_id`) REFERENCES `{$p}fce_products`(`id`) ON DELETE CASCADE",
			"ALTER TABLE `{$p}fce_product_space`
         ADD CONSTRAINT `fk_fcps_space`
         FOREIGN KEY (`space_id`)   REFERENCES `{$p}fcom_spaces`(`id`)  ON DELETE CASCADE",

			// access_overrides → users / products (NEU)
			"ALTER TABLE `{$p}fce_product_access_overrides`
         ADD CONSTRAINT `fk_fcao_user`
         FOREIGN KEY (`user_id`)    REFERENCES `{$p}users`(`ID`) ON DELETE CASCADE",
			"ALTER TABLE `{$p}fce_product_access_overrides`
         ADD CONSTRAINT `fk_fcao_product`
         FOREIGN KEY (`product_id`) REFERENCES `{$p}fce_products`(`id`) ON DELETE CASCADE",

			// access_log → users
			"ALTER TABLE `{$p}fce_access_log`
         ADD CONSTRAINT `fk_fcal_user`
         FOREIGN KEY (`user_id`)    REFERENCES `{$p}users`(`ID`) ON DELETE CASCADE",
		];

		foreach ($fks as $sql) {
			$wpdb->query($sql); // Fehler ignorieren, falls Constraint bereits existiert
		}
	}
}
