<?php
// File: includes/helpers/class-wp-fce-helper-options.php

class WP_FCE_Helper_Options
{
    /**
     * Get a metabox option from a specific post/page.
     *
     * @param  int    $post_id
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function get_metabox_option(int $post_id, string $key, mixed $default = null): string|array|bool|null
    {
        $meta = redux_post_meta('wp_fce_options', $post_id);

        if (!is_array($meta)) {
            return $default;
        }

        return $meta[$key] ?? $default;
    }

    /**
     * Get a plugin metabox string option. Empty strings are treated as non-existing.
     * Returns false if the option is not found or is an empty string.
     *
     * @param  int    $post_id
     * @param  string $key
     * @param  string  $default
     * @return mixed
     */
    public static function get_string_metabox_option(int $post_id, string $key): string|false
    {
        $value = self::get_metabox_option($post_id, $key, "");
        return is_string($value) && trim($value) !== '' ? $value : false;
    }

    /**
     * Get a plugin metabox bool option. Returns default if the option is not found or is not '1' or '0'.
     *
     * @param  int    $post_id
     * @param  string $key
     * @param  bool  $default
     * @return mixed
     */
    public static function get_bool_metabox_option(int $post_id, string $key, bool $default = false): bool
    {
        $default_str = $default ? '1' : '0';
        $value = self::get_metabox_option($post_id, $key, $default_str);
        return $value === '1' ? true : ($value === '0' ? false : $default);
    }


    /**
     * Get a plugin option.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function get_option(string $key, mixed $default = null): string|array|bool|null
    {
        $options = get_option('wp_fce_options', []);
        return $options[$key] ?? $default;
    }

    /**
     * Get a plugin string option. Empty strings are treated as non-existing.
     * Returns false if the option is not found or is an empty string.
     *
     * @param  string $key
     * @return mixed
     */
    public static function get_string_option(string $key): string|false
    {
        $value = self::get_option($key, "");
        return is_string($value) && trim($value) !== '' && $value !== "none" ? $value : false;
    }

    /**
     * Get a plugin int option. Empty strings are treated as non-existing.
     * Returns false if the option is not found or is an empty value.
     *
     * @param  string $key
     * @return mixed
     */
    public static function get_int_option(string $key): int|false
    {
        $value = self::get_string_option($key);
        return is_numeric($value) ? (int)$value : false;
    }

    /**
     * Get a plugin bool option. Returns default if the option is not found or is not '1' or '0'.
     *
     * @param  string $key
     * @param  bool  $default
     * @return mixed
     */
    public static function get_bool_option(string $key, bool $default = false): bool
    {
        $default_str = $default ? '1' : '0';
        $value = self::get_option($key, $default_str);
        return $value === '1' ? true : ($value === '0' ? false : $default);
    }

    /**
     * Get the Fluent Portal URL.
     *
     * Retrieves the URL for the Fluent Portal if it exists and is configured.
     *
     * @since 1.0.0
     * 
     * @return string|false The Fluent Portal URL as a string if available, or false if not configured or unavailable.
     */
    public static function get_fluent_portal_url(bool $include_query = false): string|false
    {
        $fcom_settings = get_option('fluent_community_settings', []);
        $slug = $fcom_settings['slug'] ?? '';
        if (empty($slug)) {
            return false;
        }
        $portal_url = home_url($slug);
        if (!empty($_GET) && $include_query) {
            $portal_url = add_query_arg($_GET, $portal_url);
        }
        return $portal_url;
    }

    public static function get_buy_credits_threshold(): int
    {
        if (!self::get_buy_credits_url()) {
            return -1; // -1 means do never show the buy credits link
        }
        $threshold = self::get_int_option('community_api_buy_url_threshold', -2);
        return is_numeric($threshold) ? (int)$threshold : -2;
    }

    public static function get_buy_credits_url(): string|false
    {
        return self::get_string_option('community_api_buy_url');
    }
}
