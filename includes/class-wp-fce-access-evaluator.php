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
     * Prüft, ob ein User Zugriff auf ein Entity (Space oder Course) hat.
     *
     * @param int    $user_id     WP-User-ID.
     * @param string $entity_type 'space' oder 'course'.
     * @param int    $entity_id   ID des Spaces oder Kurses.
     * @return bool               true, wenn Zugriff gewährt, sonst false.
     */
    public static function user_has_access(int $user_id, string $entity_type, int $entity_id): bool
    {
        $key = "{$user_id}:{$entity_type}:{$entity_id}";

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        // 1) Admin Override
        $override = WP_FCE_Helper_Access_Override::get_latest_override($user_id, $entity_type, $entity_id);
        if ($override && $override['override_type'] === 'deny') {
            return self::$cache[$key] = false;
        }
        if ($override && $override['override_type'] === 'allow') {
            return self::$cache[$key] = true;
        }

        // 2) Produkt-Besitz + Space-Mapping prüfen
        $product_ids = WP_FCE_Helper_Product_User::get_active_product_ids($user_id);
        foreach ($product_ids as $prod_id) {
            if (WP_FCE_Helper_Product_Space::has_mapping($prod_id, $entity_id)) {
                return self::$cache[$key] = true;
            }
        }

        // 3) Default: kein Zugriff
        return self::$cache[$key] = false;
    }

    /**
     * Liefert alle Quellen, die zur Zugriff-Entscheidung geführt haben.
     *
     * @param int    $user_id
     * @param string $entity_type
     * @param int    $entity_id
     * @return array{override: string|null, products: array<int,array{product_id:int,valid:bool,mapped:bool}>, final: bool|null}
     */
    public static function get_access_sources(int $user_id, string $entity_type, int $entity_id): array
    {
        $result = [
            'override' => null,
            'products' => [],
            'final'    => false,
        ];

        // 1) Admin-Override
        $override = WP_FCE_Helper_Access_Override::get_latest_override($user_id, $entity_type, $entity_id);
        if ($override) {
            $result['override'] = $override['override_type'];
            $result['final']    = ($override['override_type'] === 'allow');
            return $result;
        }

        // 2) Produkt-Besitz + Mapping
        $product_ids = WP_FCE_Helper_Product_User::get_active_product_ids($user_id);
        foreach ($product_ids as $prod_id) {
            $is_mapped = WP_FCE_Helper_Product_Space::has_mapping($prod_id, $entity_id);
            $result['products'][] = [
                'product_id' => $prod_id,
                'valid'      => true,
                'mapped'     => $is_mapped,
            ];
            if ($is_mapped) {
                $result['final'] = true;
                return $result;
            }
        }

        // 3) Default
        return $result;
    }
}
