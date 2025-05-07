<?php

/**
 * Register the "Product" Custom Post Type.
 *
 * @since 1.0.0
 */
class WP_FCE_CPT_Product
{
    /**
     * Register the "product" post type.
     *
     * @return void
     */
    public function register_post_type()
    {
        $labels = [
            'name'               => _x('Produkte', 'post type general name', 'wp-fce'),
            'singular_name'      => _x('Produkt',  'post type singular name', 'wp-fce'),
            'menu_name'          => _x('Produkte', 'admin menu',            'wp-fce'),
            'name_admin_bar'     => _x('Produkt',  'add new on admin bar',  'wp-fce'),
            'add_new'            => _x('Neu hinzufügen', 'Produkt',            'wp-fce'),
            'add_new_item'       => __('Neues Produkt hinzufügen',      'wp-fce'),
            'new_item'           => __('Neues Produkt',                 'wp-fce'),
            'edit_item'          => __('Produkt bearbeiten',            'wp-fce'),
            'view_item'          => __('Produkt ansehen',               'wp-fce'),
            'all_items'          => __('Alle Produkte',                 'wp-fce'),
            'search_items'       => __('Produkte durchsuchen',           'wp-fce'),
            'not_found'          => __('Keine Produkte gefunden.',      'wp-fce'),
            'not_found_in_trash' => __('Keine Produkte im Papierkorb.', 'wp-fce'),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'show_in_menu'       => true,
            'menu_icon'          => 'dashicons-cart',
            'supports'           => ['title', 'editor', 'thumbnail'],
            'has_archive'        => false,
            'rewrite'            => ['slug' => 'product'],
            'show_in_rest'       => true,
        ];

        register_post_type('product', $args);
    }

    /**
     * Get all FluentCommunity Space IDs mapped to this product.
     *
     * @param int $product_id
     * @return int[] Array of space IDs
     */
    public static function get_spaces(int $product_id): array
    {
        if (function_exists('carbon_get_post_meta')) {
            $spaces = carbon_get_post_meta($product_id, 'fce_spaces');
        } else {
            $spaces = get_post_meta($product_id, 'fce_spaces', true);
        }

        // Sicherstellen, dass wir ein Array haben
        if (! is_array($spaces)) {
            return [];
        }

        // Alle IDs als Integer zurückgeben
        return array_map('intval', $spaces);
    }

    /**
     * Get all FluentCommunity Course IDs mapped to this product.
     *
     * @param int $product_id
     * @return int[] Array of course IDs
     */
    public static function get_courses(int $product_id): array
    {
        if (function_exists('carbon_get_post_meta')) {
            $courses = carbon_get_post_meta($product_id, 'fce_courses');
        } else {
            $courses = get_post_meta($product_id, 'fce_courses', true);
        }

        if (! is_array($courses)) {
            return [];
        }

        return array_map('intval', $courses);
    }
}
