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
        // Get all product-user entries with valid expiry dates
        $entries  = WP_FCE_Helper_Product_User::get_with_expiry_date();

        //Set state to expired/active based on expiry dates
        foreach ($entries as $entry) {
            $entry->renew();
        }

        //sync access
        self::sync_space_accesses();
    }

    /**
     * Syncs all product-user entries with their mapped spaces.
     *
     * Iterates over all product-user entries and checks if the user has access to the mapped spaces.
     * If the entry is active, the user is given access to all mapped spaces.
     * If the entry is inactive, the user is only revoked from the space if there is no other active
     * product-user entry for the same space.
     *
     * @return void
     */
    public static function sync_space_accesses(): void
    {
        $all = WP_FCE_Helper_Product_User::get_all();
        foreach ($all as $entry) {
            $is_active = $entry->is_active();

            foreach ($entry->get_mapped_spaces() as $mapping) {
                $space = $mapping->get_space();
                if ($is_active) {
                    // Zugriff sicherstellen
                    $space->grant_user_access($entry->get_user_id());
                } else {
                    // Nur entfernen, wenn KEIN anderes aktives Produkt auf diesen Space mapped ist
                    $other_active = WP_FCE_Helper_Product_User::has_other_active_product_for_space($entry->get_user_id(), $entry->get_product_id(), $space->get_id());
                    if (! $other_active) {
                        $space->revoke_user_access($entry->get_user_id());
                    }
                }
            }
        }
    }
}
