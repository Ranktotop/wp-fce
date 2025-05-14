<?php
// File: includes/helpers/class-wp-fce-helper-product-user.php

if (! defined('ABSPATH')) {
    exit;
}

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
     * Find a single product‐user entry by user ID and product ID.
     *
     * @param  int $user_id
     * @param  int $product_id
     * @return WP_FCE_Model_Product_User|null
     */

    public static function get_by_user_product(
        int $user_id,
        int $product_id
    ): ?WP_FCE_Model_Product_User {
        return static::findOneBy([
            'user_id'        => $user_id,
            'product_id'     => $product_id
        ]);
    }

    /**
     * Find all product‐user entries for given user
     *
     * @param  int  $user_id
     * @return WP_FCE_Model_Product_User[]
     */
    public static function get_for_user(int $user_id): array
    {
        return static::find(['user_id' => $user_id]);
    }

    public static function get_for_product(int $product_id): array
    {
        return static::find(['product_id' => $product_id]);
    }

    /**
     * Retrieve all active product‐user entries for a given user.
     * Active means: start_date <= now AND (expiry_date IS NULL OR expiry_date > now)
     *
     * @param  int   $user_id
     * @return WP_FCE_Model_Product_User[]
     */
    public static function get_active_for_user(int $user_id): array
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
            fn(array $row) => static::$model_class::load_by_row($row),
            $rows
        );
    }

    /**
     * Retrieve all product‐user entries which has passed the expiry date.
     *
     * @return WP_FCE_Model_Product_User[]
     */
    public static function get_expired(): array
    {
        global $wpdb;
        $now   = current_time('mysql');
        $table = static::getTableName();

        // Select all rows where expiry_date is set and is before "now"
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "
            SELECT *
            FROM {$table}
            WHERE expiry_date IS NOT NULL
              AND expiry_date < %s
            ",
                $now
            ),
            ARRAY_A
        ) ?: [];

        // Map each raw row to a hydrated model instance
        return array_map(
            fn(array $row) => static::$model_class::load_by_row($row),
            $rows
        );
    }

    /**
     * Retrieve all product‐user entries which has passed the expiry date but are on state "active".
     *
     * @return WP_FCE_Model_Product_User[]
     */
    public static function get_active_expired(): array
    {
        global $wpdb;
        $now   = current_time('mysql');
        $table = static::getTableName();

        // Select all rows where expiry_date is set and is before "now" and status is "active"
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "
            SELECT *
            FROM {$table}
            WHERE status = %s
              AND expiry_date IS NOT NULL
              AND expiry_date < %s
            ",
                'active',
                $now
            ),
            ARRAY_A
        ) ?: [];

        // Map each raw row to a hydrated model instance
        return array_map(
            fn(array $row) => static::$model_class::load_by_row($row),
            $rows
        );
    }

    /**
     * Retrieve all product‐user entries which have an expiry date set.
     *
     * @param int|null $user_id If set, only entries for this user are returned
     * @param int|null $product_id If set, only entries for this product are returned
     * @return WP_FCE_Model_Product_User[]
     */
    public static function get_with_expiry_date(?int $user_id = null, ?int $product_id = null): array
    {
        global $wpdb;

        $table = static::getTableName();

        $sql = "SELECT * FROM {$table} WHERE expiry_date IS NOT NULL";
        $params = [];

        if ($user_id !== null) {
            $sql .= " AND user_id = %d";
            $params[] = $user_id;
        }

        if ($product_id !== null) {
            $sql .= " AND product_id = %d";
            $params[] = $product_id;
        }

        $query = $params ? $wpdb->prepare($sql, ...$params) : $sql;
        $rows  = $wpdb->get_results($query, ARRAY_A) ?: [];

        return array_map(
            fn(array $row) => WP_FCE_Model_Product_User::load_by_row($row),
            $rows
        );
    }

    /**
     * Create a new product‐user entry.
     *
     * @param  int      $user_id
     * @param  int      $product_id
     * @param  string   $source
     * @param  string   $transaction_id
     * @param  \DateTime|int|string $start_date
     * @param  \DateTime|int|string|null  $expiry_date
     * @param  string   $status
     * @param  string   $note
     *
     * @return WP_FCE_Model_Product_User
     *
     * @throws \Exception
     */
    public static function create(int $user_id, int $product_id, string $source, string $transaction_id, \DateTime|int|string $start_date, \DateTime|int|string|null $expiry_date = null, string $status = 'active', string $note = ''): WP_FCE_Model_Product_User
    {
        global $wpdb;
        $table = static::getTableName();

        // Unique check
        $exists = static::get_by_user_product_transaction($user_id, $product_id, $transaction_id);
        if ($exists) {
            throw new \Exception(sprintf(
                __('Product %d already assigned to user %d with transaction id "%s"', 'wp-fce'),
                $product_id,
                $user_id,
                $transaction_id
            ));
        }

        // If this is a real access, remove dummies first
        if ($source !== 'admin') {
            self::delete_admin_dummy($user_id, $product_id);
        }

        // Normalize to DateTime|null
        $start_date = static::normalizeDateTime($start_date);
        $expiry_date = static::normalizeDateTime($expiry_date);

        // Convert to MySQL DATETIME string or null
        $start_date_str = $start_date->format('Y-m-d H:i:s');
        $expiry_date_str = $expiry_date ? $expiry_date->format('Y-m-d H:i:s') : null;

        // 4) Insert
        $ok = $wpdb->insert(
            $table,
            [
                'user_id'       => $user_id,
                'product_id'    => $product_id,
                'source' => $source,
                'transaction_id'        => $transaction_id,
                'start_date'       => $start_date_str,
                'expiry_date'   => $expiry_date_str,
                'status'        => $status,
                'note'          => $note
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        if (false === $ok) {
            throw new \Exception("DB insert error: {$wpdb->last_error}");
        }

        return static::get_by_id((int) $wpdb->insert_id);
    }

    /**
     * Delete a dummy product-user entry created by an admin override.
     *
     * These dummy entries are created when an admin adds a product to a user
     * without a real transaction ID. They are removed when the admin removes
     * the override.
     *
     * @param int $user_id The user ID.
     * @param int $product_id The product ID.
     * @return void
     */
    public static function delete_admin_dummy(int $user_id, int $product_id): void
    {
        global $wpdb;
        $table = static::getTableName();

        $wpdb->delete(
            $table,
            [
                'user_id'    => $user_id,
                'product_id' => $product_id,
                'source'     => 'admin',
                'transaction_id' => 'override-manual',
            ],
            ['%d', '%d', '%s', '%s']
        );
    }

    /**
     * Check if a user has any other active product in the same space.
     *
     * @param  int  $user_id
     * @param  int  $except_product_id
     * @param  int  $space_id
     * @return bool
     */
    public static function has_other_active_product_for_space(
        int $user_id,
        int $except_product_id,
        int $space_id
    ): bool {
        global $wpdb;

        $product_user = static::getTableName();
        $product_space = WP_FCE_Helper_Product_Space::getTableName();
        $now = current_time('mysql');

        $sql = "
        SELECT COUNT(*) FROM {$product_user} pu
        INNER JOIN {$product_space} ps ON pu.product_id = ps.product_id
        WHERE pu.user_id = %d
          AND pu.product_id != %d
          AND pu.status = 'active'
          AND pu.start_date <= %s
          AND (pu.expiry_date IS NULL OR pu.expiry_date > %s)
          AND ps.space_id = %d
    ";

        $count = (int) $wpdb->get_var($wpdb->prepare(
            $sql,
            $user_id,
            $except_product_id,
            $now,
            $now,
            $space_id
        ));

        return $count > 0;
    }
}
