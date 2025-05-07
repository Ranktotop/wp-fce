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
        $this->expiration_table = $wpdb->prefix . 'fce_user_product_subscriptions';
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
     * Grants access to a user for a given external product ID
     *
     * @param int $user_id The ID of the user to grant access to
     * @param string $externalId The external ID of the product to grant access to
     * @param bool   $prevent_downgrade Whether to block setting an earlier date.
     *
     * @return bool True if access was granted, false otherwise
     */
    public function grant_access(int $user_id, string $externalId, int $grant_until_timestamp, bool $prevent_downgrade = true): bool
    {
        //Get the user as object
        $user = get_user_by('id', $user_id);
        if ($user === false) {
            return false;
        }

        //Get the mapping for given product
        $helper_product = new WP_FCE_Helper_Product();
        $mapping = $helper_product->get_product_mapping_by_external_product_id($externalId);
        if ($mapping == null) {
            return false;
        }

        //Add user to spaces and courses
        foreach ($mapping['space_ids'] as $space_id) {
            \FluentCommunity\App\Services\Helper::addToSpace($space_id, $user->ID, 'member', 'by_automation');
        }
        if (! empty($mapping['course_ids'])) {
            \FluentCommunity\Modules\Course\Services\CourseHelper::enrollCourses($mapping['course_ids'], $user->ID);
        }

        // Set the expiration date in the database
        $paid_until = date('Y-m-d H:i:s', $grant_until_timestamp);
        $this->update_user_expiration($user->ID, $mapping['id'], $paid_until, 0, $prevent_downgrade);
        return true;
    }

    /**
     * Revokes access to a user for a given external product ID
     *
     * @param int $user_id The ID of the user to revoke access from
     * @param int $externalId The external ID of the product to revoke access from
     *
     * @return bool True if access was revoked, false otherwise
     */
    public function revoke_access(int $user_id, int $externalId): bool
    {
        //Get the user as object
        $user = get_user_by('id', $user_id);
        if ($user === false) {
            return false;
        }

        //Get the mapping for given product
        $helper_product = new WP_FCE_Helper_Product();
        $mapping = $helper_product->get_product_mapping_by_external_product_id($externalId);
        if ($mapping == null) {
            return false;
        }

        //Add user to spaces and courses
        foreach ($mapping['space_ids'] as $space_id) {
            \FluentCommunity\App\Services\Helper::removeFromSpace($space_id, $user->ID, 'by_automation');
        }
        if (! empty($mapping['course_ids'])) {
            \FluentCommunity\Modules\Course\Services\CourseHelper::leaveCourses($mapping['course_ids'], $user->ID);
        }
        // Set the expiration date to now
        $paid_until = date('Y-m-d H:i:s', current_time('timestamp'));
        $this->update_user_expiration($user->ID, $mapping['id'], $paid_until, 1, false);
        return true;
    }

    /**
     * Insert or update a user's subscription expiration.
     *
     * If $prevent_downgrade is true, will refuse to set an earlier expiration date
     * than the one already stored.
     *
     * @param int    $user_id           The WP_User ID.
     * @param int    $product_id        The internal product ID.
     * @param string $paid_until        MySQL datetime string for new expiration.
     * @param int    $expired_flag      Flag (0 = active, 1 = expired).
     * @param bool   $prevent_downgrade Whether to block setting an earlier date.
     * @return int|false Rows affected or false on failure.
     */
    public function update_user_expiration(int $user_id, int $product_id, string $paid_until, int $expired_flag = 0, bool $prevent_downgrade = true): int|false
    {
        global $wpdb;

        // 1) Altes Ablaufdatum auslesen
        $old_paid_until = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT paid_until
             FROM {$this->expiration_table}
             WHERE user_id = %d
               AND product_id = %d",
                $user_id,
                $product_id
            )
        );

        // 2) Downgrade-Check nur wenn gewünscht
        if ($prevent_downgrade && $old_paid_until) {
            $old_ts = strtotime($old_paid_until);
            $new_ts = strtotime($paid_until);

            // neues Datum ist gleich oder früher -> abbrechen
            if ($new_ts <= $old_ts) {
                return false;
            }
        }

        // 3) Anlegen oder updaten
        $data   = [
            'user_id'      => $user_id,
            'product_id'   => $product_id,
            'paid_until'   => $paid_until,
            'expired_flag' => $expired_flag,
        ];
        $format = ['%d', '%d', '%s', '%d'];

        return $wpdb->replace($this->expiration_table, $data, $format);
    }
}
