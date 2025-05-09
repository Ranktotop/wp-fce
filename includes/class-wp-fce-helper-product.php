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
            'post_type'   => 'fce_product_mapping',
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
        $product_mapping_id = (int) $query->posts[0];

        return [
            'id'                  => $product_mapping_id,
            'external_product_id' => $externalId,
            'space_ids'              => $this->get_spaces_for_product($product_mapping_id),
            'course_ids'             => $this->get_courses_for_product($product_mapping_id),
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
            'post_type'      => 'fce_product_mapping',
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
        $product_mapping_ids = get_posts([
            'post_type'      => 'fce_product_mapping',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        foreach ((array) $product_mapping_ids as $product_mapping_id) {
            // externe ID aus Carbon Fields oder Post-Meta
            if (function_exists('carbon_get_post_meta')) {
                $external = carbon_get_post_meta($product_mapping_id, 'fce_external_id');
            } else {
                $external = get_post_meta($product_mapping_id, 'fce_external_id', true);
            }
            $external = is_string($external) ? $external : '';

            $mappings[] = [
                'id'                  => $product_mapping_id,
                'external_product_id' => $external,
                'space_ids'              => $this->get_spaces_for_product($product_mapping_id),
                'course_ids'             => $this->get_courses_for_product($product_mapping_id),
            ];
        }

        return $mappings;
    }

    /**
     * Get all FluentCommunity Space IDs mapped to the given product.
     *
     * @param int $product_mapping_id
     * @return int[] Array of space IDs
     */
    public function get_spaces_for_product(int $product_mapping_id): array
    {
        if (function_exists('carbon_get_post_meta')) {
            $spaces = carbon_get_post_meta($product_mapping_id, 'fce_spaces');
        } else {
            $spaces = get_post_meta($product_mapping_id, 'fce_spaces', true);
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
     * @param int $product_mapping_id
     * @return int[] Array of course IDs
     */
    public function get_courses_for_product(int $product_mapping_id): array
    {
        if (function_exists('carbon_get_post_meta')) {
            $courses = carbon_get_post_meta($product_mapping_id, 'fce_courses');
        } else {
            $courses = get_post_meta($product_mapping_id, 'fce_courses', true);
        }

        if (! is_array($courses)) {
            return [];
        }

        return array_map('intval', $courses);
    }

    /**
     * Get product‐mapping IDs that include a given Space.
     *
     * @param int $space_id        The ID of the Space to search for.
     * @param int[] $exclude_post_ids  List of product-mapping post IDs to exclude. Defaults to empty array.
     *
     * @return int[] List of Product Mapping IDs mapped to the Space.
     */
    public function get_product_mappings_by_space(int $space_id, array $exclude_post_ids = []): array
    {
        $query_args = [
            'post_type'      => 'fce_product_mapping',
            'post__not_in'   => $exclude_post_ids,
            'meta_query'     => [
                [
                    'key'     => 'fce_spaces',
                    'value'   => sprintf(':"%d";', $space_id),
                    'compare' => 'LIKE',
                ],
            ],
            'fields'         => 'ids',
            'posts_per_page' => -1,
        ];

        return get_posts($query_args);
    }

    /**
     * Get product‐mapping IDs that include a given Course.
     *
     * @param int   $course_id          The ID of the Course to search for.
     * @param int[] $exclude_post_ids   List of product-mapping post IDs to exclude. Defaults to empty array.
     *
     * @return int[] List of Product Mapping IDs mapped to the Course.
     */
    public function get_product_mappings_by_course(int $course_id, array $exclude_post_ids = []): array
    {
        $query_args = [
            'post_type'      => 'fce_product_mapping',
            'post__not_in'   => $exclude_post_ids,
            'meta_query'     => [
                [
                    'key'     => 'fce_courses',
                    'value'   => sprintf(':"%d";', $course_id),
                    'compare' => 'LIKE',
                ],
            ],
            'fields'         => 'ids',
            'posts_per_page' => -1,
        ];

        return get_posts($query_args);
    }

    /**
     * Prüft, ob eine externe Produkt-ID in allen Produkt-Mappings einzigartig ist.
     *
     * @param string $externalId Die zu prüfende externe ID.
     * @param int|null $exclude_post_id
     * @return bool True, wenn noch kein Mapping diese ID nutzt; false sonst.
     */
    public function is_unique_external_product_id(string $externalId, ?int $exclude_post_id = null): bool
    {
        $args = [
            'post_type'      => 'fce_product_mapping',
            'post_status'    => 'any',
            'meta_query'     => [
                [
                    'key'     => '_fce_external_id',
                    'value'   => $externalId,
                    'compare' => '='
                ]
            ],
            'fields'         => 'ids',
            'posts_per_page' => 1,
        ];

        if ($exclude_post_id) {
            $args['post__not_in'] = [$exclude_post_id];
        }

        $query = new \WP_Query($args);

        return !$query->have_posts();
    }

    /**
     * Liefert die externe Produkt-ID zu einem bestimmten Post.
     *
     * @param int $post_id Die ID des Posts, dessen externe Produkt-ID gesucht wird.
     *
     * @return string|null Die externe Produkt-ID oder null, wenn keine gefunden wurde.
     */
    public function get_external_id_by_post_id(int $post_id): string|null
    {
        if (!function_exists('carbon_get_post_meta')) {
            return null;
        }
        $external_id = carbon_get_post_meta($post_id, 'fce_external_product_id');
        return is_string($external_id) && $external_id !== '' ? $external_id : null;
    }
}
