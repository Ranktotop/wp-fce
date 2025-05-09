<?php

/**
 * Central helper class for Users
 *
 */
class WP_FCE_Helper_User
{
    private string $expiration_table;

    /**
     * WP_FCE_Helper constructor.
     *
     */
    public function __construct()
    {
        global $wpdb;
        $this->expiration_table = $wpdb->prefix . 'fce_user_subscriptions';
    }

    /**
     * Gets a user by its name or creates a new user
     *
     * @return WP_User|false User object or false
     */
    public function get_or_create_user(string $email): WP_User|false
    {
        if (email_exists($email)) {
            $user = get_user_by('email', $email);
        } else {
            $username = sanitize_user(strstr($email, '@', true), true);
            $password = wp_generate_password();
            $user_id  = wp_create_user($username, $password, $email);
            if (is_wp_error($user_id)) {
                return false;
            }
            $user = get_user_by('id', $user_id);
        }
        return $user;
    }

    /**
     * Grant a single user access to a single Space.
     *
     * @param int $user_id  The user ID.
     * @param int $space_id The Space ID.
     *
     * @return void
     */
    public function grant_space_access(int $user_id, int $space_id): void
    {
        \FluentCommunity\App\Services\Helper::addToSpace(
            $space_id,
            $user_id,
            'member',
            'by_automation'
        );
    }

    /**
     * Revoke a single user’s access to a single Space.
     *
     * @param int $user_id  The user ID.
     * @param int $space_id The Space ID.
     *
     * @return void
     */
    public function revoke_space_access(int $user_id, int $space_id): void
    {
        \FluentCommunity\App\Services\Helper::removeFromSpace(
            $space_id,
            $user_id,
            'by_automation'
        );
    }

    /**
     * Enroll a single user in a single Course.
     *
     * @param int $user_id   The user ID.
     * @param int $course_id The Course ID.
     *
     * @return void
     */
    public function grant_course_access(int $user_id, int $course_id): void
    {
        \FluentCommunity\Modules\Course\Services\CourseHelper::enrollCourses(
            [$course_id],
            $user_id
        );
    }

    /**
     * Remove a single user from a single Course.
     *
     * @param int $user_id   The user ID.
     * @param int $course_id The Course ID.
     *
     * @return void
     */
    public function revoke_course_access(int $user_id, int $course_id): void
    {
        \FluentCommunity\Modules\Course\Services\CourseHelper::leaveCourse(
            $course_id,
            $user_id
        );
    }

    /**
     * Get all user IDs that have an subscription to a given product mapping.
     *
     * @param int $product_mapping_id The internal product ID.
     * @param bool $include_expired Include expired subscriptions?
     *
     * @return int[] Array of user IDs.
     */
    public function get_users_for_product_mapping(int $product_mapping_id, bool $include_expired = false): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_user_subscriptions';
        $now   = current_time('mysql');

        // Basis-SQL
        $sql = "SELECT DISTINCT user_id
            FROM {$table}
            WHERE product_mapping_id = %d";

        // Bedingungen für nicht abgelaufene Abos (nur wenn $include_expired false ist)
        if (!$include_expired) {
            $sql .= " AND expired_flag = 0 AND paid_until >= %s";
            $query = $wpdb->prepare($sql, $product_mapping_id, $now);
        } else {
            $query = $wpdb->prepare($sql, $product_mapping_id);
        }

        // Ausführen und Ergebnisse zurückgeben
        $user_ids = $wpdb->get_col($query);
        return $user_ids;
    }

    /**
     * Check if a user has any active subscriptions among given product mappings.
     *
     * @param int   $user_id             The WP_User ID.
     * @param int[] $product_mapping_ids List of product-mapping post IDs to check.
     *
     * @return bool True if at least one active subscription exists, false otherwise.
     */
    public function has_access_to_product_mapping(int $user_id, array $product_mapping_ids): bool
    {
        // nothing to check
        if (empty($product_mapping_ids)) {
            return false;
        }

        global $wpdb;
        $table = $this->expiration_table;
        $now   = current_time('mysql');

        // build placeholders for the IN(…) clause
        $placeholders = implode(',', array_fill(0, count($product_mapping_ids), '%d'));

        //TODO

        // count active, non-expired subscriptions
        $sql = $wpdb->prepare(
            "
        SELECT COUNT(1)
        FROM {$table}
        WHERE user_id      = %d
          AND product_mapping_id   IN ({$placeholders})
          AND expired_flag = 0
          AND paid_until   >= %s
        ",
            array_merge([$user_id], $product_mapping_ids, [$now])
        );

        return (int) $wpdb->get_var($sql) > 0;
    }
}
