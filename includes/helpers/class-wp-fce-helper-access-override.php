<?php
// File: includes/helpers/class-wp-fce-helper-access-override.php

/**
 * @extends WP_FCE_Helper_Base<WP_FCE_Model_Access_Override>
 */
class WP_FCE_Helper_Access_Override extends WP_FCE_Helper_Base
{
    /**
     * Table name without WP prefix.
     *
     * @var string
     */
    protected static string $table = 'fce_product_access_overrides';

    /**
     * Model class this helper works with.
     *
     * @var class-string<WP_FCE_Model_Access_Override>
     */
    protected static string $model_class = WP_FCE_Model_Access_Override::class;

    /**
     * Fetch the most recent for a given user/product.
     *
     * @param  int $user_id
     * @param  int $product_id
     * @param  bool $only_active Only return the first override whose valid_until is NULL or in the future
     * @return WP_FCE_Model_Access_Override|null
     */
    public static function get_latest_override_by_product_user(int $user_id, int $product_id, bool $only_active = false): ?WP_FCE_Model_Access_Override
    {
        // Retrieve all overrides for this user & product, newest first
        /** @var WP_FCE_Model_Access_Override[] $rows */
        $rows = static::find(
            [
                'user_id'    => $user_id,
                'product_id' => $product_id,
            ],
            ['created_at' => 'DESC']
        );

        if (empty($rows)) {
            return null;
        }

        // Only return the first override whose valid_until is NULL or in the future
        if ($only_active) {
            foreach ($rows as $ov) {
                if ($ov->is_valid()) {
                    return $ov;
                }
            }
            // no active found
            return null;
        }

        // Return the first override
        return $rows[0];
    }

    /**
     * Add a new access override entry.
     * Automatically checks expirations after adding the override.
     *
     * @param  int         $user_id
     * @param  int         $product_id
     * @param  string      $override_type  'allow' or 'deny'
     * @param  string|null $comment
     * @return int          The new override ID.
     * @throws Exception    On DB error.
     */
    public static function add_override(
        int $user_id,
        int $product_id,
        string $override_type,
        \DateTime|int|string $valid_until,
        ?string $comment = null
    ): int {

        // Normalize to DateTime|null
        $valid_until = static::normalizeDateTime($valid_until);

        /** @var WP_FCE_Model_Access_Override $ov */
        $ov = new static::$model_class();
        $ov->user_id       = $user_id;
        $ov->product_id    = $product_id;
        $ov->override_type = $override_type;
        $ov->source        = 'admin';
        $ov->comment       = $comment;
        $ov->valid_until   = $valid_until;
        $ov->save();

        // If the user does not have an entry for this product yet, create one
        $existing = WP_FCE_Helper_Product_User::get_by_user_product($user_id, $product_id);
        $now      = new \DateTime('now', new \DateTimeZone(wp_timezone_string()));

        if ($existing === null && $override_type === 'allow') {
            WP_FCE_Helper_Product_User::create(
                $user_id,
                $product_id,
                'admin',
                'override-manual',
                $now,
                $valid_until,
                'active',
                'Zugang manuell durch Admin-Override erzeugt'
            );
        }

        //update access
        WP_FCE_Cron::check_expirations(user_id: $user_id, product_id: $product_id);

        return $ov->get_id();
    }

    /**
     * Remove all overrides for a given user/product.
     * Automatically checks expirations after removing the overrides.
     *
     * @param  int $user_id
     * @param  int $product_id
     * @return bool True on success, false on failure.
     */
    public static function remove_overrides(int $user_id, int $product_id): bool
    {
        $overrides = static::find([
            'user_id'    => $user_id,
            'product_id' => $product_id,
        ]);

        $success = true;
        foreach ($overrides as $ov) {
            if (! $ov->delete()) {
                $success = false;
            }
        }

        //temporary set admin dummy to expired
        $result = WP_FCE_Helper_Product_User::expire_admin_dummy($user_id, $product_id);
        $sync_needed = is_int($result) && $result > 0;

        //sync access with fluent community if needed
        if ($sync_needed) {
            WP_FCE_Cron::check_expirations(user_id: $user_id, product_id: $product_id);
        }

        // Delete dummy entries if existing
        WP_FCE_Helper_Product_User::delete_admin_dummy($user_id, $product_id);

        return $success;
    }

    /**
     * Patches an existing access override with new parameters.
     * Automatically checks expirations after applying the patch.
     *
     * @param int $override_id The unique identifier of the access override to patch
     * @param \DateTime|int|string $valid_until The expiration date/time for the override. Can be a DateTime object, Unix timestamp, or date string
     * @param string $mode The override mode/type to apply
     * @param string|null $comment Optional comment describing the reason for the override patch
     * @return void
     */
    public static function patch_override(int $override_id, \DateTime|int|string $valid_until, string $mode, ?string $comment = null)
    {
        $ov = static::get_by_id($override_id);

        if ($ov === null) {
            throw new \Exception(sprintf(__('Override not found by ID %d.', 'wp-fce'), $override_id));
        }
        $ov->set_valid_until($valid_until);
        $ov->set_override_type($mode);
        $ov->set_comment($comment);
        $ov->save();

        if ($ov->is_deny()) {
            //temporary set admin dummy to expired
            $result = WP_FCE_Helper_Product_User::expire_admin_dummy($ov->get_user_id(), $ov->get_product_id());
            $sync_needed = is_int($result) && $result > 0;

            //sync access with fluent community if needed
            if ($sync_needed) {
                WP_FCE_Cron::check_expirations(user_id: $ov->get_user_id(), product_id: $ov->get_product_id());
            }
            //remove dummy entry
            WP_FCE_Helper_Product_User::delete_admin_dummy($ov->get_user_id(), $ov->get_product_id());
        } else if ($ov->is_allow()) {
            // If the user does not have an entry for this product yet, create one
            $existing = WP_FCE_Helper_Product_User::get_by_user_product($ov->get_user_id(), $ov->get_product_id());
            $now      = new \DateTime('now', new \DateTimeZone(wp_timezone_string()));

            if ($existing === null) {
                WP_FCE_Helper_Product_User::create(
                    $ov->get_user_id(),
                    $ov->get_product_id(),
                    'admin',
                    'override-manual',
                    $now,
                    $valid_until,
                    'active',
                    'Zugang manuell durch Admin-Override erzeugt'
                );

                //update access
                WP_FCE_Cron::check_expirations(user_id: $ov->get_user_id(), product_id: $ov->get_product_id());
            }
        }
    }
}
