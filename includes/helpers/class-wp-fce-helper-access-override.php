<?php
// File: includes/helpers/class-wp-fce-helper-access-override.php

use RuntimeException;

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
     * Fetch the most recent override_type and comment for a given user/product.
     *
     * @param  int $user_id
     * @param  int $product_id
     * @return array{override_type:string,comment:string|null}|null
     */
    public static function get_latest_override_by_product(int $user_id, int $product_id): ?array
    {
        $rows = static::find(
            [
                'user_id'    => $user_id,
                'product_id' => $product_id,
            ],
            ['created_at' => 'DESC'],
            1
        );

        if (empty($rows)) {
            return null;
        }

        /** @var WP_FCE_Model_Access_Override $ov */
        $ov = $rows[0];
        return [
            'override_type' => $ov->get_override_type(),
            'comment'       => $ov->get_comment(),
        ];
    }

    /**
     * Add a new access override entry.
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
        ?string $comment = null
    ): int {
        /** @var WP_FCE_Model_Access_Override $ov */
        $ov = new static::$model_class();
        $ov->user_id       = $user_id;
        $ov->product_id    = $product_id;
        $ov->override_type = $override_type;
        $ov->source        = 'admin';
        $ov->comment       = $comment;
        $ov->save();

        return $ov->get_id();
    }

    /**
     * Remove all overrides for a given user/product.
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
        return $success;
    }
}
