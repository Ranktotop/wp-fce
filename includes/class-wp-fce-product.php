<?php

/**
 * Register the "Product" Custom Post Type.
 *
 * @since 1.0.0
 */
class WP_FCE_CPT_Product
{

    /**
     * Hook into WP init to register the CPT.
     */
    public function __construct()
    {
        add_action('init', [$this, 'register_post_type']);
    }

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
}
