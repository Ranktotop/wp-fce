<?php
// File: includes/helpers/class-wp-fce-helper-product-space.php

if (! defined('ABSPATH')) {
    exit;
}

use DateTime;
use RuntimeException;

/**
 * @extends WP_FCE_Helper_Base<WP_FCE_Model_Product_Space>
 */
class WP_FCE_Helper_Product_Space extends WP_FCE_Helper_Base
{
    /**
     * Table name without WP prefix.
     *
     * @var string
     */
    protected static string $table = 'fce_product_space';

    /**
     * Model class this helper works with.
     *
     * @var class-string<WP_FCE_Model_Product_Space>
     */
    protected static string $model_class = WP_FCE_Model_Product_Space::class;

    /**
     * Check if a mapping exists between product and space.
     *
     * @param  int  $product_id
     * @param  int  $space_id
     * @return bool
     */
    public static function has_mapping(int $product_id, int $space_id): bool
    {
        return null !== static::findOneBy([
            'product_id' => $product_id,
            'space_id'   => $space_id,
        ]);
    }

    /**
     * Find all space mappings for a given product.
     *
     * @param  int  $product_id
     * @return WP_FCE_Model_Product_Space[]
     */
    public static function get_for_product(int $product_id): array
    {
        return static::find(['product_id' => $product_id]);
    }

    /**
     * Create a new product-space mapping and then assign retroactive access.
     *
     * @param  int    $product_id
     * @param  int    $space_id
     * @return void
     * @throws Exception On DB error or missing product.
     */
    public static function create_mapping_and_assign(
        int    $product_id,
        int    $space_id
    ): void {
        // 1) Insert mapping via Model
        $mapping = new static::$model_class();
        $mapping->product_id  = $product_id;
        $mapping->space_id    = $space_id;
        $mapping->save();

        // 2) Retroactive access
        static::assign_retroactive_access($product_id, $space_id);
    }

    /**
     * Remove all space mappings for a given product.
     *
     * @param  int $product_id
     * @return void
     */
    public static function remove_mappings_for_product(int $product_id): void
    {
        $mappings = static::find(['product_id' => $product_id]);
        foreach ($mappings as $mapping) {
            $mapping->delete();
        }
    }

    /**
     * For each historic IPN on this product's SKU, create or update
     * a product_user record so users get retroactive access.
     *
     * @param  int    $product_id
     * @param  int    $space_id
     * @return void
     * @throws Exception On missing product or DB errors.
     */
    public static function assign_retroactive_access(
        int    $product_id,
        int    $space_id
    ): void {
        global $wpdb;

        // 1) Get SKU from product model
        $product = WP_FCE_Helper_Product::get_by_id($product_id);
        $sku     = $product->get_sku();
        if (! $sku) {
            throw new \Exception("Product ID {$product_id} not found.");
        }

        // 2) Load all IPN logs for this SKU
        $ipn_table = $wpdb->prefix . 'fce_ipn_log';
        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT user_email, transaction_id, ipn_date, ipn
                 FROM {$ipn_table}
                 WHERE external_product_id = %s",
                $sku
            ),
            ARRAY_A
        ) ?: [];

        // 3) For each log, ensure a WP user exists and upsert a product_user entry
        foreach ($logs as $row) {
            $payload = json_decode($row['ipn'], true);
            $start_date  = new DateTime($row['ipn_date']);
            $expiry_date = ! empty($payload['expiry_date'])
                ? new DateTime($payload['expiry_date'])
                : null;

            // a) Ensure WP user
            $wp_user = get_user_by('email', $row['user_email']);
            if ($wp_user) {
                $user_id = $wp_user->ID;
            } else {
                $login = sanitize_user(current(explode('@', $row['user_email'])), true);
                $user_id = wp_insert_user([
                    'user_login' => $login,
                    'user_email' => $row['user_email'],
                    'user_pass'  => wp_generate_password(),
                ]);
                if (is_wp_error($user_id)) {
                    continue;
                }
            }

            // b) Upsert via Product_User helper
            $pu = WP_FCE_Helper_Product_User::get_by_user_product_transaction(
                $user_id,
                $product_id,
                $row['transaction_id']
            ) ?? new WP_FCE_Model_Product_User();

            $pu->user_id        = $user_id;
            $pu->product_id     = $product_id;
            $pu->source         = 'ipn';
            $pu->transaction_id = $row['transaction_id'];
            $pu->start_date     = $start_date;
            $pu->expiry_date    = $expiry_date;
            $pu->status         = 'active';
            $pu->note           = 'Retroactive via mapping';
            $pu->save();
        }
    }
}
