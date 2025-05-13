<?php
// File: includes/helpers/class-wp-fce-helper-user.php

use RuntimeException;

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
     * Find a user by email.
     *
     * @param  string                $email
     * @return WP_FCE_Model_User|null
     */
    public static function get_by_email(string $email): ?WP_FCE_Model_User
    {
        return static::findOneBy(['user_email' => $email]);
    }

    /**
     * Create a new WP user and return its model.
     *
     * @param  string                $email
     * @param  string                $login    Optional. If empty, derived from email.
     * @param  string                $password Optional. If empty, a random password is generated.
     * @return WP_FCE_Model_User
     * @throws RuntimeException      On invalid email or WP error.
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
            throw new RuntimeException('WP insert_user error: ' . $user_id->get_error_message());
        }

        return static::get_by_id($user_id);
    }

    /**
     * Get or create a user by email.
     *
     * @param  string                $email
     * @param  string                $login    Optional login for creation.
     * @param  string                $password Optional password for creation.
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
            error_log("Failed to get or create user '{$email}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieve all users, ordered by display_name.
     *
     * @return WP_FCE_Model_User[]
     */
    public static function get_all(): array
    {
        return static::find([], ['display_name' => 'ASC']);
    }
}
