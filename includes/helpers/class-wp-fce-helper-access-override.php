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
    protected static string $table       = 'fce_product_access_overrides';

    /**
     * Model class this helper works with.
     *
     * @var class-string<WP_FCE_Model_Access_Override>
     */
    protected static string $model_class = WP_FCE_Model_Access_Override::class;

    /**
     * Fetch the most recent override_type and comment for a given user/entity.
     *
     * @param  int    $user_id
     * @param  string $entity_type
     * @param  int    $entity_id
     * @return array{override_type:string,comment:string|null}|null
     */
    public static function get_latest_override(
        int    $user_id,
        string $entity_type,
        int    $entity_id
    ): ?array {
        $rows = static::find(
            [
                'user_id'     => $user_id,
                'entity_type' => $entity_type,
                'entity_id'   => $entity_id,
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
     * @param  string      $entity_type
     * @param  int         $entity_id
     * @param  string      $override_type
     * @param  string|null $comment
     * @return int                    The new override ID.
     * @throws Exception              On DB error.
     */
    public static function add_override(
        int     $user_id,
        string  $entity_type,
        int     $entity_id,
        string  $override_type,
        ?string $comment = null
    ): int {
        /** @var WP_FCE_Model_Access_Override $ov */
        $ov = new static::$model_class();
        $ov->user_id       = $user_id;
        $ov->entity_type   = $entity_type;
        $ov->entity_id     = $entity_id;
        $ov->override_type = $override_type;
        $ov->source        = 'admin';
        $ov->comment       = $comment;
        $ov->save();

        return $ov->get_id();
    }

    /**
     * Remove all overrides for a given user/entity.
     *
     * @param  int    $user_id
     * @param  string $entity_type
     * @param  int    $entity_id
     * @return bool  True on success, false on failure.
     */
    public static function remove_overrides(
        int    $user_id,
        string $entity_type,
        int    $entity_id
    ): bool {
        $overrides = static::find([
            'user_id'     => $user_id,
            'entity_type' => $entity_type,
            'entity_id'   => $entity_id,
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
