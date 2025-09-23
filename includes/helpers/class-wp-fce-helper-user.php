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
    public static function create(string $email, string $login = '', string $password = '', string $first_name = '',  string $last_name = '', bool $send_welcome_email = true): WP_FCE_Model_User
    {
        if (! is_email($email)) {
            throw new RuntimeException('Invalid email address.');
        }

        // Determine login
        $generated_login = self::generate_valid_username($email, $login, $first_name, $last_name);

        if (empty($password)) {
            $password = wp_generate_password(12, false);
        }

        $userdata = [
            'user_login'   => $generated_login,
            'user_email'   => sanitize_email($email),
            'user_pass'    => $password,
        ];

        $user_id = wp_insert_user($userdata);
        if (is_wp_error($user_id)) {
            throw new RuntimeException('wp_insert_user error: ' . $user_id->get_error_message());
        }

        $created_user = static::get_by_id((int)$user_id);

        if ($send_welcome_email) {
            wp_new_user_notification($created_user->get_id(), null, 'user');
            wp_new_user_notification($created_user->get_id(), null, 'admin');
        }

        return $created_user;
    }

    /**
     * Get or create a user by email.
     *
     * @param  string                $email
     * @param  string                $login
     * @param  string                $password
     * @return WP_FCE_Model_User|null
     */
    public static function get_or_create(string $email, string $login = '', string $password = '', string $first_name = '',  string $last_name = '', bool $send_welcome_email = true): ?WP_FCE_Model_User
    {
        $user = static::get_by_email($email);
        if ($user) {
            return $user;
        }
        try {
            return static::create($email, $login, $password, $first_name, $last_name, $send_welcome_email);
        } catch (\Exception $e) {
            error_log("FCE get_or_create user failed: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Generates a unique, valid username based on provided information
     *
     * @param string $email Required - used as fallback and validation
     * @param string $login Optional - if provided, will be used directly
     * @param string $first_name Optional - used in combination strategies
     * @param string $last_name Optional - used in combination strategies
     * @return string Valid, unique username (max 60 chars)
     * @throws RuntimeException If unable to generate valid username
     */
    private static function generate_valid_username(string $email, string $login = '', string $first_name = '', string $last_name = ''): string
    {
        if (!is_email($email)) {
            throw new RuntimeException('Valid email required for username generation.');
        }

        // Fall 2: Username ist explizit gegeben
        if (!empty($login)) {
            $login = sanitize_user(trim($login), true);

            if (strlen($login) > 60 || empty($login) || username_exists($login)) {
                //fallback 
                return self::generate_valid_username($email, '', $first_name, $last_name);
            }
            return $login;
        }

        // Fall 3: Email + Firstname und/oder Lastname
        if (!empty($first_name) || !empty($last_name)) {
            return self::generate_username_from_names($email, $first_name, $last_name);
        }

        // Fall 1: Nur Email - verwende Teil vor @
        return self::generate_username_from_email($email);
    }

    /**
     * Generate username from first/last name combination
     */
    private static function generate_username_from_names(string $email, string $first_name, string $last_name): string
    {
        $first_name = sanitize_user(trim($first_name), true);
        $last_name = sanitize_user(trim($last_name), true);

        if (empty($first_name) && empty($last_name)) {
            // Fallback to email if names are empty after sanitization
            return self::generate_username_from_email($email);
        }

        if (empty($first_name)) {
            // Kein Firstname -> Email-Teil + Lastname
            $email_part = sanitize_user(strstr($email, '@', true), true);
            if (empty($email_part) || strlen($email_part) < 2) {
                $email_part = 'user';
            }
            $base = $email_part . "_" . $last_name;
        } elseif (empty($last_name)) {
            // Kein Lastname -> Firstname + Random 4-stellig
            $random_suffix = wp_rand(1000, 9999);
            $base = $first_name . '_' . $random_suffix;
        } else {
            // Beide Namen vorhanden -> Firstname + Lastname
            $base = $first_name . "_" . $last_name;
        }

        return self::ensure_unique_username($base);
    }

    /**
     * Generate username from email (part before @)
     */
    private static function generate_username_from_email(string $email): string
    {
        $base = sanitize_user(strstr($email, '@', true), true);

        // Fallback wenn Email-Teil leer oder zu kurz
        if (empty($base) || strlen($base) < 3) {
            $base = 'user';
        }

        return self::ensure_unique_username($base);
    }

    /**
     * Ensures username is unique and within 60 character limit
     */
    private static function ensure_unique_username(string $base): string
    {
        // Try base name first if it fits and is available
        if (strlen($base) <= 60 && !username_exists($base)) {
            return $base;
        }

        // Generate with numbers, ensuring we never exceed 60 chars
        $counter = 1;
        $max_attempts = 1000; // Prevent infinite loop

        while ($counter <= $max_attempts) {
            $suffix = (string) $counter;
            $suffix_length = strlen($suffix);

            // Calculate how much space we have for the base
            $max_base_length = 60 - $suffix_length;

            // Truncate base from the right if necessary
            $truncated_base = substr($base, 0, $max_base_length);
            $potential_username = $truncated_base . $suffix;

            // Double check length and availability
            if (strlen($potential_username) <= 60 && !username_exists($potential_username)) {
                return $potential_username;
            }

            $counter++;
        }

        // Ultimate fallback if all attempts failed
        do {
            $fallback_username = 'user' . wp_rand(10000, 99999);
        } while (username_exists($fallback_username));

        return $fallback_username;
    }
}
