<?php
// File: includes/helpers/class-wp-fce-helper-product-user.php

if (! defined('ABSPATH')) {
    exit;
}

use RuntimeException;

/**
 * @extends WP_FCE_Helper_Base<WP_FCE_Model_Product_User>
 */
class WP_FCE_Helper_Product_User extends WP_FCE_Helper_Base
{
    /**
     * Table name without WP prefix.
     *
     * @var string
     */
    protected static string $table = 'fce_product_user';

    /**
     * Model class that this helper works with.
     *
     * @var class-string<WP_FCE_Model_Product_User>
     */
    protected static string $model_class = WP_FCE_Model_Product_User::class;

    /**
     * Find a single product‐user entry by user ID, product ID and transaction ID.
     *
     * @param  int    $user_id
     * @param  int    $product_id
     * @param  string $transaction_id
     * @return WP_FCE_Model_Product_User|null
     */
    public static function get_by_user_product_transaction(
        int $user_id,
        int $product_id,
        string $transaction_id
    ): ?WP_FCE_Model_Product_User {
        return static::findOneBy([
            'user_id'        => $user_id,
            'product_id'     => $product_id,
            'transaction_id' => $transaction_id,
        ]);
    }

    /**
     * Retrieve all active product‐user entries for a given user.
     *
     * Returns an array of arrays with keys:
     *  - product_id  (int)
     *  - start_date  (string, Y-m-d H:i:s)
     *  - expiry_date (string|null, Y-m-d H:i:s or null)
     *
     * @param  int   $user_id
     * @return array<int,array{product_id:int,start_date:string,expiry_date:string|null}>
     */
    public static function get_active_entries(int $user_id): array
    {
        global $wpdb;
        $now   = current_time('mysql');
        $table = static::getTableName();

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT product_id, start_date, expiry_date
                FROM {$table}
                WHERE user_id    = %d
                  AND status     = %s
                  AND start_date <= %s
                  AND (expiry_date IS NULL OR expiry_date > %s)
                ",
                $user_id,
                'active',
                $now,
                $now
            ),
            ARRAY_A
        ) ?: [];

        return array_map(
            static fn(array $row): array => [
                'product_id'  => (int) $row['product_id'],
                'start_date'  => $row['start_date'],
                'expiry_date' => $row['expiry_date'],
            ],
            $rows
        );
    }

    /**
     * Retrieve all active product IDs for a given user.
     *
     * @param  int   $user_id
     * @return int[] Array of product_id
     */
    public static function get_active_product_ids(int $user_id): array
    {
        $entries = static::get_active_entries($user_id);
        return array_column($entries, 'product_id');
    }
}
