<?php
// File: includes/helpers/class-wp-fce-helper-ipn-log.php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * @extends WP_FCE_Helper_Base<WP_FCE_Model_Ipn_Log>
 */
class WP_FCE_Helper_Ipn_Log extends WP_FCE_Helper_Base
{
    /**
     * Table name without WP prefix.
     *
     * @var string
     */
    protected static string $table       = 'fce_ipn_log';

    /**
     * Model class this helper works with.
     *
     * @var class-string<WP_FCE_Model_Ipn_Log>
     */
    protected static string $model_class = WP_FCE_Model_Ipn_Log::class;

    /**
     * Retrieve a single IPN entry by its hash.
     *
     * @param  string                  $hash
     * @return WP_FCE_Model_Ipn_Log|null
     */
    public static function get_by_hash(string $hash): ?WP_FCE_Model_Ipn_Log
    {
        return static::findOneBy(['ipn_hash' => $hash]);
    }

    /**
     * Record an incoming IPN, create WP user if needed, and upsert product-user access.
     *
     * @param  string         $user_email
     * @param  string         $external_product_id
     * @param  string         $transaction_id
     * @param  mixed[]        $ipn_payload       Decoded IPN payload
     * @param  DateTime       $ipn_date
     * @param  DateTime|null  $expiry_date
     * @param  string         $source            Source tag (default 'ipn')
     * @return void
     * @throws Exception      On DB error or missing product.
     */
    public static function record_ipn(
        string   $user_email,
        string   $external_product_id,
        string   $transaction_id,
        array    $ipn_payload,
        DateTime $ipn_date,
        ?DateTime $expiry_date = null,
        string   $source = 'ipn'
    ): void {
        // 1) idempotent: skip if this hash already exists
        $json    = json_encode($ipn_payload);
        $hash    = md5($json);
        if (static::get_by_hash($hash)) {
            return;
        }

        // 2) insert new IPN log
        /** @var WP_FCE_Model_Ipn_Log $ipnLog */
        $ipnLog = new static::$model_class();
        $ipnLog->set_user_email($user_email);
        $ipnLog->set_transaction_id($transaction_id);
        $ipnLog->set_ipn_date($ipn_date);
        $ipnLog->set_external_product_id($external_product_id);
        $ipnLog->set_source($source);
        $ipnLog->set_ipn($ipn_payload);
        $ipnLog->set_ipn_hash($hash);
        $ipnLog->save();

        // 3) ensure WP user exists
        $wp_user = WP_FCE_Helper_User::get_or_create(
            $user_email,
            sanitize_user(current(explode('@', $user_email)), true),
            wp_generate_password()
        );
        if (! $wp_user) {
            throw new RuntimeException("Could not create or retrieve WP user for email {$user_email}");
        }
        $user_id = $wp_user->get_id();

        // 4) find product by SKU
        $product = WP_FCE_Helper_Product::get_by_sku($external_product_id);
        if (! $product) {
            // no matching product; IPN log is stored, nothing further
            return;
        }
        $product_id = $product->get_id();

        // 5) upsert product-user access
        $existing = WP_FCE_Helper_Product_User::get_by_user_product_transaction(
            $user_id,
            $product_id,
            $transaction_id
        );

        if ($existing) {
            $existing->set_source($source);
            $existing->set_transaction_id($transaction_id);
            $existing->set_start_date($ipn_date);
            $existing->set_expiry_date($expiry_date);
            $existing->set_status($ipnLog->is_expired() ? 'expired' : 'active');
            $existing->set_note('Processed by IPN');
            $existing->save();
        } else {
            WP_FCE_Helper_Product_User::create(
                $user_id,
                $product_id,
                $source,
                $transaction_id,
                $ipn_date,
                $expiry_date,
                $ipnLog->is_expired() ? 'expired' : 'active',
                'Processed by IPN'
            );
        }

        // update access
        WP_FCE_Cron::check_expirations(user_id: $user_id, product_id: $product_id);
    }

    /**
     * Retrieve all IPN logs for a given product SKU.
     *
     * @param  string  $sku
     * @return WP_FCE_Model_Ipn_Log[]
     */
    public static function get_for_sku(string $sku): array
    {
        return static::find(['external_product_id' => $sku]);
    }

    /**
     * Retrieve the latest IPN logs for a given user's email address.
     * 
     * @param  string  $user_email
     * @return WP_FCE_Model_Ipn_Log[]
     */
    public static function get_latest_ipns_for_user(string $user_email): array
    {
        global $wpdb;
        $table = static::getTableName();

        $sql = "
        SELECT ipn.*
        FROM {$table} ipn
        INNER JOIN (
            SELECT MAX(id) as max_id
            FROM {$table}
            WHERE user_email = %s
            GROUP BY external_product_id
        ) latest ON ipn.id = latest.max_id
    ";

        $rows = $wpdb->get_results(
            $wpdb->prepare($sql, $user_email),
            ARRAY_A
        ) ?: [];

        return array_map(
            fn(array $row) => static::$model_class::load_by_row($row),
            $rows
        );
    }
}
