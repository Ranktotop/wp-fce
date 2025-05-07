<?php

/**
 * Central helper class for Products
 *
 */
class WP_FCE_Helper_Product
{

    /**
     * WP_FCE_Helper constructor.
     *
     */
    public function __construct()
    {
        // z.B. add_action(...), wenn benötigt
    }

    /**
     * Get product and their mapped spaces/courses for specific external id
     *
     * @return array Mapping:
     *   [
     *     'id'                  => int,           // internal post-id of the product
     *     'external_product_id' => string,        // external product id (fce_external_id)
     *     'space_ids'              => int[],         // Space-IDs
     *     'course_ids'             => int[],         // Course-IDs
     *   ]
     */
    public function get_product_mapping_by_external_product_id(string $externalId): array|null
    {
        $query = new \WP_Query([
            'post_type'   => 'product',
            'meta_query'  => [[
                'key'     => 'fce_external_id',
                'value'   => $externalId,
                'compare' => '=',
            ]],
            'fields'      => 'ids',
            'posts_per_page' => 1,
        ]);
        if (empty($query->posts)) {
            return null;
        }
        $product_id = (int) $query->posts[0];

        // 5) Mapping auslesen
        $spaces  = $this->get_spaces_for_product($product_id);
        $courses = $this->get_courses_for_product($product_id);
        return [
            'id'                  => $product_id,
            'external_product_id' => $externalId,
            'space_ids'              => $this->get_spaces_for_product($product_id),
            'course_ids'             => $this->get_courses_for_product($product_id),
        ];
    }

    /**
     * Get all products (WP_Post objects)
     *
     * @return WP_Post[]
     */
    public function get_products(): array
    {
        return get_posts([
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
    }

    /**
     * Get all products and their mapped spaces/courses
     *
     * @return array[] Array von Mappings, je Mapping:
     *   [
     *     'id'                  => int,           // internal post-id of the product
     *     'external_product_id' => string,        // external product id (fce_external_id)
     *     'space_ids'              => int[],         // Space-IDs
     *     'course_ids'             => int[],         // Course-IDs
     *   ]
     */
    public function get_product_mappings(): array
    {
        $mappings = [];

        // Alle Produkt-Posts abrufen
        $product_ids = get_posts([
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        foreach ((array) $product_ids as $product_id) {
            // externe ID aus Carbon Fields oder Post-Meta
            if (function_exists('carbon_get_post_meta')) {
                $external = carbon_get_post_meta($product_id, 'fce_external_id');
            } else {
                $external = get_post_meta($product_id, 'fce_external_id', true);
            }
            $external = is_string($external) ? $external : '';

            $mappings[] = [
                'id'                  => $product_id,
                'external_product_id' => $external,
                'space_ids'              => $this->get_spaces_for_product($product_id),
                'course_ids'             => $this->get_courses_for_product($product_id),
            ];
        }

        return $mappings;
    }

    /**
     * Get all FluentCommunity Space IDs mapped to the given product.
     *
     * @param int $product_id
     * @return int[] Array of space IDs
     */
    public function get_spaces_for_product(int $product_id): array
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
     * Get all FluentCommunity Course IDs mapped to the given product.
     *
     * @param int $product_id
     * @return int[] Array of course IDs
     */
    public function get_courses_for_product(int $product_id): array
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
