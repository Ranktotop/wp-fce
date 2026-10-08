<?php

/**
 * File: includes/class-wp-fce-migrations.php
 *
 * One-time data migrations that run after plugin updates.
 *
 * @package WP_Fluent_Community_Extreme
 */

if (! defined('ABSPATH')) {
    exit;
}

class WP_FCE_Migrations
{

    /**
     * Option storing the IDs of all migrations that have already run.
     */
    public const OPTION_NAME = 'wp_fce_completed_migrations';

    /**
     * All migrations, keyed by a unique ID. Never rename or remove an ID once released.
     */
    private const MIGRATIONS = [
        'remove_community_api_data' => 'remove_community_api_data',
        'remove_portal_redirect_and_font_awesome_settings' => 'remove_portal_redirect_and_font_awesome_settings',
        'remove_controlpanel_page' => 'remove_controlpanel_page',
    ];

    /**
     * Runs every migration that has not run yet.
     *
     * @return void
     */
    public static function run(): void
    {
        $completed = get_option(self::OPTION_NAME, []);
        if (! is_array($completed)) {
            $completed = [];
        }

        $pending = array_diff(array_keys(self::MIGRATIONS), $completed);
        if (empty($pending)) {
            return;
        }

        foreach ($pending as $id) {
            $method = self::MIGRATIONS[$id];
            self::$method();
            $completed[] = $id;
            fce_log('Migration completed: ' . $id);
        }

        update_option(self::OPTION_NAME, $completed, true);
    }

    /**
     * Removes all data stored by the discontinued Community API integration:
     * the per-user API keys and the community_api_* admin settings.
     *
     * @return void
     */
    private static function remove_community_api_data(): void
    {
        delete_metadata('user', 0, 'wp_fce_community_api_key', '', true);

        self::remove_settings(fn($key) => str_starts_with($key, 'community_api_'));
    }

    /**
     * Removes the discontinued "Redirect Home to Portal" and "Font Awesome CDN URL" settings.
     *
     * @return void
     */
    private static function remove_portal_redirect_and_font_awesome_settings(): void
    {
        self::remove_settings(fn($key) => in_array($key, ['redirect_home_to_portal', 'font_awesome_cdn_url'], true));
    }

    /**
     * Removes the leftovers of the standalone /wp-fce/controlpanel page, which was replaced
     * by the [wp_fce_payment_history] shortcode: its background image setting and its rewrite rule.
     *
     * @return void
     */
    private static function remove_controlpanel_page(): void
    {
        self::remove_settings(fn($key) => $key === 'orders_background_image');
        // Rewrite API is not ready on plugins_loaded; WordPress regenerates the rules on demand.
        delete_option('rewrite_rules');
    }

    /**
     * Removes all admin settings whose key matches the given predicate.
     *
     * @param callable(string): bool $should_remove
     * @return void
     */
    private static function remove_settings(callable $should_remove): void
    {
        $options = get_option('wp_fce_options');
        if (! is_array($options)) {
            return;
        }

        $cleaned = array_filter(
            $options,
            fn($key) => ! $should_remove((string) $key),
            ARRAY_FILTER_USE_KEY
        );

        if (count($cleaned) !== count($options)) {
            update_option('wp_fce_options', $cleaned);
        }
    }
}
