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
     * Updates the state of all entries to expired/active based on expiry dates and start dates.
     *
     * @param int|null $user_id If set, only check expirations for this user
     * @param int|null $product_id If set, only check expirations for this product
     * @return void
     */
    public static function check_expirations(?int $user_id = null, ?int $product_id = null): void
    {
        // Get all product-user entries
        if ($user_id !== null && $product_id !== null) {
            $pu = WP_FCE_Helper_Product_User::get_by_user_product($user_id, $product_id);
            $all = $pu ? [$pu] : [];
        } elseif ($user_id !== null) {
            $all = WP_FCE_Helper_Product_User::get_for_user($user_id);
        } elseif ($product_id !== null) {
            $all = WP_FCE_Helper_Product_User::get_for_product($product_id);
        } else {
            $all = WP_FCE_Helper_Product_User::get_all();
        }

        //Update state to expired/active based on expiry and start date
        foreach ($all as $entry) {
            $entry->renew();
        }

        //sync access
        self::sync_space_accesses($all);
    }

    /**
     * Syncs all product-user entries with their mapped spaces.
     *
     * Iterates over all product-user entries and checks if the user has access to the mapped spaces.
     * If the entry is active, the user is given access to all mapped spaces.
     * If the entry is inactive, the user is only revoked from the space if there is no other active
     * product-user entry for the same space.
     *
     * @param WP_FCE_Model_Product_User[] $user_products The product-user entries to update
     * @return void
     */
    public static function sync_space_accesses(array $user_products): void
    {
        foreach ($user_products as $entry) {
            foreach ($entry->get_mapped_spaces() as $mapping) {
                $space = $mapping->get_space();
                $has_access = WP_FCE_Access_Evaluator::user_has_access(
                    $entry->get_user_id(),
                    $space->get_id(),
                    $user_products
                );

                if ($has_access) {
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
