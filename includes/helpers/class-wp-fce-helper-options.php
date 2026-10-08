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
}
