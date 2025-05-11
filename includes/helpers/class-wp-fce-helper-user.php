<?php

class WP_FCE_Helper_User implements WP_FCE_Helper_Interface
{

    //******************************** */
    //************ GETTER ************ */
    //******************************** */

    /**
     * Load multiple user entities based on their IDs.
     *
     * @param int[] $ids List of user IDs.
     * @return WP_FCE_Model_User[] Array of WP_FCE_Model_User objects.
     */
    public function get_by_ids(array $ids): array
    {
        global $wpdb;

        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, user_login, user_email, display_name 
             FROM {$wpdb->users} 
             WHERE ID IN ($placeholders)",
                ...$ids
            ),
            ARRAY_A
        );

        return array_map(fn($row) => new WP_FCE_Model_User(
            (int) $row['ID'],
            $row['user_login'],
            $row['user_email'],
            $row['display_name']
        ), $rows);
    }

    /**
     * Load a single user entity by ID.
     *
     * @param int $id User ID.
     * @return WP_FCE_Model_User
     * @throws \Exception If user not found.
     */
    public function get_by_id(int $id): WP_FCE_Model_User
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT ID, user_login, user_email, display_name 
                 FROM {$wpdb->users} 
                 WHERE ID = %d",
                $id
            ),
            ARRAY_A
        );

        if (!$row) {
            throw new \Exception(__('Benutzer wurde nicht gefunden.', 'wp-fce'));
        }

        return new WP_FCE_Model_User(
            (int) $row['ID'],
            $row['user_login'],
            $row['user_email'],
            $row['display_name']
        );
    }

    /**
     * Load all users.
     *
     * @return WP_FCE_Model_User[] Array of WP_FCE_Model_User objects.
     */
    public function get_all(): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT ID, user_login, user_email, display_name 
             FROM {$wpdb->users} 
             ORDER BY display_name ASC",
            ARRAY_A
        );

        return array_map(fn($row) => new WP_FCE_Model_User(
            (int) $row['ID'],
            $row['user_login'],
            $row['user_email'],
            $row['display_name']
        ), $rows);
    }
}
