<?php
// File: includes/helpers/class-wp-fce-helper-user.php

/**
 * @extends WP_FCE_Helper_Base<WP_FCE_Model_User>
 */
class WP_FCE_Helper_User extends WP_FCE_Helper_Base
{
    /**
     * Table name without WP prefix.
     *
     * @var string
     */
    protected static string $table       = 'users';

    /**
     * Model class this helper works with.
     *
     * @var class-string<WP_FCE_Model_User>
     */
    protected static string $model_class = WP_FCE_Model_User::class;

    /**
     * Load multiple users by their IDs.
     *
     * @param  int[]              $ids
     * @return WP_FCE_Model_User[]
     */
    public static function get_by_ids(array $ids): array
    {
        global $wpdb;
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $sql = $wpdb->prepare(
            "SELECT ID, user_login, user_email, display_name
             FROM {$wpdb->users}
             WHERE ID IN ($placeholders)",
            ...$ids
        );
        $rows = $wpdb->get_results($sql, ARRAY_A) ?: [];

        return array_map(
            fn(array $r) => static::$model_class::load_by_row($r),
            $rows
        );
    }

    /**
     * Load a single user by ID.
     *
     * @param  int                $id
     * @return WP_FCE_Model_User
     * @throws RuntimeException   If not found.
     */
    public static function get_by_id(int $id): WP_FCE_Model_User
    {
        return WP_FCE_Model_User::load_by_id($id);
    }

    /**
     * Load all users, ordered by display_name.
     *
     * @return WP_FCE_Model_User[]
     */
    public static function get_all(): array
    {
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT ID, user_login, user_email, display_name
             FROM {$wpdb->users}
             ORDER BY display_name ASC",
            ARRAY_A
        ) ?: [];

        return array_map(
            fn(array $r) => static::$model_class::load_by_row($r),
            $rows
        );
    }

    /**
     * Find a user by email.
     *
     * @param  string                $email
     * @return WP_FCE_Model_User|null
     */
    public static function get_by_email(string $email): ?WP_FCE_Model_User
    {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT ID, user_login, user_email, display_name
                 FROM {$wpdb->users}
                 WHERE user_email = %s",
                sanitize_email($email)
            ),
            ARRAY_A
        );
        if (! $row) {
            return null;
        }
        return static::$model_class::load_by_row($row);
    }

    /**
     * Create a new WP user by email (and optional login/password).
     *
     * @param  string                $email
     * @param  string                $login
     * @param  string                $password
     * @return WP_FCE_Model_User
     * @throws RuntimeException      On error.
     */
    public static function create(string $email, string $login = '', string $password = ''): WP_FCE_Model_User
    {
        if (! is_email($email)) {
            throw new RuntimeException('Invalid email address.');
        }

        // Determine login
        if (empty($login)) {
            $base  = sanitize_user(strstr($email, '@', true), true);
            $login = $base;
            $i     = 1;
            while (username_exists($login)) {
                $login = $base . $i++;
            }
        } else {
            $login = sanitize_user($login, true);
            if (username_exists($login)) {
                throw new RuntimeException('Username already exists.');
            }
        }

        if (empty($password)) {
            $password = wp_generate_password(12, false);
        }

        $userdata = [
            'user_login'   => $login,
            'user_email'   => sanitize_email($email),
            'user_pass'    => $password,
        ];
        $user_id = wp_insert_user($userdata);
        if (is_wp_error($user_id)) {
            throw new RuntimeException('wp_insert_user error: ' . $user_id->get_error_message());
        }

        return static::get_by_id((int)$user_id);
    }

    /**
     * Get or create a user by email.
     *
     * @param  string                $email
     * @param  string                $login
     * @param  string                $password
     * @return WP_FCE_Model_User|null
     */
    public static function get_or_create(string $email, string $login = '', string $password = ''): ?WP_FCE_Model_User
    {
        $user = static::get_by_email($email);
        if ($user) {
            return $user;
        }
        try {
            return static::create($email, $login, $password);
        } catch (\Exception $e) {
            error_log("FCE get_or_create user failed: " . $e->getMessage());
            return null;
        }
    }
}
