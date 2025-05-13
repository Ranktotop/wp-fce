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

        // 1) Hole alle Produkt-IDs, die dieser User jemals hatte (egal ob aktiv/abgelaufen)
        $all_product_ids = WP_FCE_Helper_Product_User::get_all_product_ids($user_id);
        //TODO

        foreach ($all_product_ids as $prod_id) {
            // 1a) Override prüfen (hat Vorrang vor Aktivitätsprüfung)
            $override = WP_FCE_Helper_Access_Override::get_latest_override_by_product($user_id, $prod_id);
            if ($override) {
                if ($override['override_type'] === 'deny') {
                    continue; // explizit verweigert → ignorieren
                }
                if ($override['override_type'] === 'allow') {
                    return self::$cache[$key] = true; // Zugriff sofort erlauben
                }
            }
        }

        // 2) Jetzt nur aktive Produkte ohne Override prüfen
        $active_product_ids = WP_FCE_Helper_Product_User::get_active_product_ids($user_id);

        foreach ($active_product_ids as $prod_id) {
            if (WP_FCE_Helper_Product_Space::has_mapping($prod_id, $entity_id)) {
                return self::$cache[$key] = true;
            }
        }

        // 3) Standard: kein Zugriff
        return self::$cache[$key] = false;
    }

    /**
     * Liefert alle Quellen, die zur Zugriff-Entscheidung geführt haben.
     *
     * @param int    $user_id
     * @param string $entity_type
     * @param int    $entity_id
     * @return array{
     *   override: array{product_id:int,override_type:string}|null,
     *   products: array<int,array{product_id:int,valid:bool,mapped:bool,override?:string}>,
     *   final: bool
     * }
     */
    public static function get_access_sources(int $user_id, string $entity_type, int $entity_id): array
    {
        $result = [
            'override' => null,
            'products' => [],
            'final'    => false,
        ];

        $product_ids = WP_FCE_Helper_Product_User::get_active_product_ids($user_id);

        foreach ($product_ids as $prod_id) {
            $override = WP_FCE_Helper_Access_Override::get_latest_override_by_product($user_id, $prod_id);
            $is_mapped = WP_FCE_Helper_Product_Space::has_mapping($prod_id, $entity_id);

            $entry = [
                'product_id' => $prod_id,
                'valid'      => true,
                'mapped'     => $is_mapped,
            ];

            if ($override) {
                $entry['override'] = $override['override_type'];

                if ($override['override_type'] === 'allow') {
                    $result['override'] = [
                        'product_id'    => $prod_id,
                        'override_type' => 'allow',
                    ];
                    $result['products'][] = $entry;
                    $result['final'] = true;
                    return $result;
                }

                if ($override['override_type'] === 'deny') {
                    // Zugriff explizit verweigert – Produkt überspringen
                    $result['products'][] = $entry;
                    continue;
                }
            }

            // Produkt mapped und nicht per Override verboten?
            if ($is_mapped) {
                $result['products'][] = $entry;
                $result['final'] = true;
                return $result;
            }

            $result['products'][] = $entry;
        }

        // Keine erlaubten Produkte gefunden
        return $result;
    }
}
