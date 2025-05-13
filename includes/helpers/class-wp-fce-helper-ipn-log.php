<?php
// File: includes/helpers/class-wp-fce-helper-ipn-log.php

if (! defined('ABSPATH')) {
    exit;
}

use DateTime;
use RuntimeException;

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
        $ipnLog->user_email           = $user_email;
        $ipnLog->transaction_id       = $transaction_id;
        $ipnLog->ipn_date             = $ipn_date;
        $ipnLog->external_product_id  = $external_product_id;
        $ipnLog->source               = $source;
        $ipnLog->ipn                  = $ipn_payload;
        $ipnLog->ipn_hash             = $hash;
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
        $pu = WP_FCE_Helper_Product_User::get_by_user_product_transaction(
            $user_id,
            $product_id,
            $transaction_id
        ) ?? new WP_FCE_Model_Product_User();

        $pu->user_id        = $user_id;
        $pu->product_id     = $product_id;
        $pu->source         = $source;
        $pu->transaction_id = $transaction_id;
        $pu->start_date     = $ipn_date;
        $pu->expiry_date    = $expiry_date;
        $pu->status         = 'active';
        $pu->note           = 'Processed by IPN';
        $pu->save();
    }
}
