<?php

/**
 * File: includes/class-wp-fce-cron.php
 *
 * Cron job for checking and expiring product-user entries.
 *
 * @package WP_Fluent_Community_Extreme
 */

if (! defined('ABSPATH')) {
    exit;
}

class WP_FCE_Cron
{

    /**
     * Name of the cron hook.
     */
    public const HOOKS = ['wp_fce_cron_check_expirations'];

    public static function register_cron_actions(): void
    {
        add_action('wp_fce_cron_check_expirations', [self::class, 'check_expirations']);
    }

    /**
     * Schedule the hourly event.
     *
     * @return void
     */
    public static function register_cronjobs(): void
    {
        foreach (self::HOOKS as $hook) {
            if (! wp_next_scheduled($hook)) {
                wp_schedule_event(time(), 'hourly', $hook);
            }
        }
    }

    /**
     * Clear the scheduled event.
     *
     * @return void
     */
    public static function unregister_cronjobs(): void
    {
        foreach (self::HOOKS as $hook) {
            wp_clear_scheduled_hook($hook);
        }
    }

    /**
     * The callback fired by WP-Cron to expire product_user entries.
     *
     * @return void
     */
    public static function check_expirations(): void
    {
        global $wpdb;

        $now   = current_time('mysql');
        $table = $wpdb->prefix . 'fce_product_user';

        // Fetch all active entries whose expiry_date <= now
        $expired = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id FROM {$table}
                 WHERE status = %s
                   AND expiry_date IS NOT NULL
                   AND expiry_date <= %s",
                'active',
                $now
            ),
            ARRAY_A
        );

        foreach ($expired as $row) {
            $wpdb->update(
                $table,
                ['status' => 'expired'],
                ['id' => (int) $row['id']],
                ['%s'],
                ['%d']
            );
        }
    }
}
