<?php
// File: includes/helpers/class-wp-fce-helper-product-space.php

if (! defined('ABSPATH')) {
    exit;
}

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
    public static function create_mapping_and_assign_users(
        int    $product_id,
        int    $space_id
    ): void {
        // Create the mapping first
        $mapping = new static::$model_class();
        $mapping->product_id  = $product_id;
        $mapping->space_id    = $space_id;
        $mapping->save();

        // Process ipns and assign access
        static::assign_retroactive_access($product_id);
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
        int    $product_id
    ): void {

        // Get SKU from product model
        $product = WP_FCE_Helper_Product::get_by_id($product_id);
        $sku     = $product->get_sku();
        if (! $sku) {
            throw new \Exception("SKU not found for product {$product_id}");
        }

        // Load all IPN logs for this SKU
        $logs = WP_FCE_Helper_Ipn_Log::get_for_sku($sku);

        // For each ipn, ensure a WP user exists and create a product_user entry
        foreach ($logs as $log) {
            $start_date  = $log->get_ipn_date();
            $expiry_date = $log->get_paid_until();

            // Get/Create wp user
            $login = sanitize_user(current(explode('@', $log->get_user_email())), true);
            $wp_user = WP_FCE_Helper_User::get_or_create($log->get_user_email(), $login, wp_generate_password());
            if ($wp_user == null) {
                continue;
            }

            // Create/update product_user
            $existing = WP_FCE_Helper_Product_User::get_by_user_product_transaction(
                $wp_user->get_id(),
                $product_id,
                $log->get_transaction_id()
            );
            if ($existing != null) {
                $existing->set_source('ipn');
                $existing->set_transaction_id($log->get_transaction_id());
                $existing->set_start_date($start_date);
                $existing->set_expiry_date($expiry_date);
                $existing->set_status($log->is_expired() ? 'expired' : 'active');
                $existing->set_note('Retroactive via IPN');
                $existing->save();
            } else {
                WP_FCE_Helper_Product_User::create(
                    $wp_user->get_id(),
                    $product_id,
                    'ipn',
                    $log->get_transaction_id(),
                    $start_date,
                    $expiry_date,
                    $log->is_expired() ? 'expired' : 'active',
                    'Retroactive via IPN'
                );
            }
        }
    }
}
