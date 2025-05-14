<?php

/**
 * File: includes/class-wp-fce-access-evaluator.php
 *
 * Central service for evaluating user access to Spaces/Courses.
 *
 * @package WP_Fluent_Community_Extreme
 */

if (! defined('ABSPATH')) {
    exit;
}

class WP_FCE_Access_Evaluator
{

    /**
     * Internal cache to avoid repeated DB queries in one request.
     *
     * @var bool[]
     */
    private static array $cache = [];


    /**
     * Check if a user has access to a given Space/Course.
     *
     * Evaluates the following access rules in order:
     *
     * 1. Check for active product-user entries with a mapped product to the given space.
     * 2. Check for admin overrides for the given product-user combination.
     *
     * @param int $user_id The ID of the user to check.
     * @param int $space_id The ID of the Space/Course to check access for.
     * @param array<WP_FCE_Model_Product_User>|null $product_user_entries Optional array of product-user entries to consider.
     * @return bool True if the user has access, false otherwise.
     */
    public static function user_has_access(
        int $user_id,
        int $space_id,
        ?array $product_user_entries = null
    ): bool {
        $key = "{$user_id}:{$space_id}";
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        // If no product_user_entries are provided, get all for the user
        if ($product_user_entries === null) {
            $product_user_entries = WP_FCE_Helper_Product_User::get_for_user($user_id);
        }

        //Save the denied products for later
        $deny_products = [];

        // Iterate all product-user entries
        foreach ($product_user_entries as $entry) {
            $product_id = $entry->get_product_id();

            // Check if the product has an admin override
            $override = WP_FCE_Helper_Access_Override::get_latest_override_by_product_user($user_id, $product_id, true);
            if ($override) {
                if ($override->is_deny()) {
                    $deny_products[] = $product_id;
                    continue;
                }
                if ($override->is_allow()) {
                    return self::$cache[$key] = true;
                }
            }
        }

        // Iterate all active product-user entries and check if they are mapped to the given space. Also check for detected denied products
        $active_entries = array_filter(
            $product_user_entries,
            fn($e) =>
            $e->is_active() && !in_array($e->get_product_id(), $deny_products, true)
        );

        foreach ($active_entries as $entry) {
            if (WP_FCE_Helper_Product_Space::has_mapping($entry->get_product_id(), $space_id)) {
                return self::$cache[$key] = true;
            }
        }

        return self::$cache[$key] = false;
    }


    /**
     * Determine which products or overrides grant or deny access,
     * and return a breakdown of sources.
     *
     * @param  int    $user_id
     * @param  int    $entity_id
     * @return array{
     *   override: array{product_id:int,override_type:string}|null,
     *   products: array<int,array{product_id:int,override?:string,mapped:bool}>,
     *   final: bool
     * }
     */
    public static function get_access_sources(int $user_id, int $entity_id): array
    {
        $result = [
            'override' => null,
            'products' => [],
            'final'    => false,
        ];

        // 1) Alle aktiven product_user-Einträge für den User holen
        $entries = WP_FCE_Helper_Product_User::get_for_user($user_id, 'active');

        foreach ($entries as $entry) {
            $prod_id  = $entry->get_product_id();
            // 2a) Neuesten Override für diesen User+Produkt prüfen
            $overrideModel = WP_FCE_Helper_Access_Override::get_latest_override_by_product_user(
                $user_id,
                $prod_id,
                true
            );

            // 2b) Mapping prüfen
            $is_mapped = WP_FCE_Helper_Product_Space::has_mapping($prod_id, $entity_id);

            $prodInfo = [
                'product_id' => $prod_id,
                'mapped'     => $is_mapped,
            ];

            if ($overrideModel) {
                $type = $overrideModel->get_override_type();
                $prodInfo['override'] = $type;

                if ($overrideModel->is_allow()) {
                    // Allow override schlägt alles
                    $result['override'] = [
                        'product_id'    => $prod_id,
                        'override_type' => 'allow',
                    ];
                    $result['products'][] = $prodInfo;
                    $result['final']      = true;
                    return $result;
                }

                if ($overrideModel->is_deny()) {
                    // Deny override: Produkt bleibt mit override markiert, Suche fortsetzen
                    $result['products'][] = $prodInfo;
                    continue;
                }
            }

            // 3) Kein Override oder Override neutral: bei Mapping Zugriff gewähren
            if ($is_mapped) {
                $result['products'][] = $prodInfo;
                $result['final']      = true;
                return $result;
            }

            // 4) Weder override noch Mapping → einfach mitschreiben
            $result['products'][] = $prodInfo;
        }

        // 5) Keine allow-/mapped-Produkte gefunden
        return $result;
    }
}
