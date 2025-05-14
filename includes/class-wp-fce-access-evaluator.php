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
        $all_product_entries = WP_FCE_Helper_Product_User::get_for_user($user_id);

        foreach ($all_product_entries as $entry) {
            // 1a) Override prüfen (hat Vorrang vor Aktivitätsprüfung)
            $override = WP_FCE_Helper_Access_Override::get_latest_override_by_product_user($user_id, $entry->get_product_id(), true);
            if ($override) {
                if ($override->is_deny()) {
                    continue; // explizit verweigert → ignorieren
                }
                if ($override->is_allow()) {
                    return self::$cache[$key] = true; // Zugriff sofort erlauben
                }
            }
        }

        // 2) Jetzt nur aktive Produkte ohne Override prüfen
        $active_product_entries = WP_FCE_Helper_Product_User::get_active_for_user($user_id);

        foreach ($active_product_entries as $entry) {
            if (WP_FCE_Helper_Product_Space::has_mapping($entry->get_product_id(), $entity_id)) {
                return self::$cache[$key] = true;
            }
        }

        // 3) Standard: kein Zugriff
        return self::$cache[$key] = false;
    }

    /**
     * Determine which products or overrides grant or deny access,
     * and return a breakdown of sources.
     *
     * @param  int    $user_id
     * @param  string $entity_type
     * @param  int    $entity_id
     * @return array{
     *   override: array{product_id:int,override_type:string}|null,
     *   products: array<int,array{product_id:int,override?:string,mapped:bool}>,
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

        // 1) Alle aktiven product_user-Einträge für den User holen
        $entries = WP_FCE_Helper_Product_User::get_active_for_user($user_id);

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
