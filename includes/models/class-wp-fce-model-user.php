<?php
// File: includes/models/class-wp-fce-model-user.php

use DateTime;
use RuntimeException;

/**
 * Model for WP users (wp_users table).
 *
 * CRUD is delegated to WordPress user functions.
 * Provides read‐only access to WP_User data plus relations
 * to FluentCommunity spaces and product access.
 */
class WP_FCE_Model_User extends WP_FCE_Model_Base
{
    /**
     * Base table name without WP prefix.
     *
     * @var string
     */
    protected static string $table = 'users';

    /**
     * Columns and their WPDB format strings.
     *
     * @var array<string,string>
     */
    protected static array $db_fields = [
        'id'           => '%d',
        'user_login'   => '%s',
        'user_email'   => '%s',
        'display_name' => '%s',
    ];

    /**
     * Columns that should never be mass-updated.
     *
     * @var string[]
     */
    protected static array $guarded = ['id'];

    /** @var int|null Primary key (maps to wp_users.ID) */
    public ?int $id = null;

    /** @var string WP user_login */
    public string $user_login = '';

    /** @var string WP user_email */
    public string $user_email = '';

    /** @var string WP display_name */
    public string $display_name = '';

    /**
     * Load a WP user by ID.
     *
     * @param  int           $id
     * @return static
     * @throws RuntimeException If not found.
     */
    public static function load_by_id(int $id): static
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
        if (! $row) {
            throw new RuntimeException("WP user {$id} not found.");
        }

        // Map column names to our property keys
        return static::load_by_row([
            'id'           => (int)   $row['ID'],
            'user_login'   =>         $row['user_login'],
            'user_email'   =>         $row['user_email'],
            'display_name' =>         $row['display_name'],
        ]);
    }

    /**
     * Save (insert or update) via WP functions.
     *
     * @throws RuntimeException On failure.
     */
    public function save(): void
    {
        if ($this->id) {
            // update existing user
            $userdata = [
                'ID'           => $this->id,
                'user_email'   => $this->user_email,
                'display_name' => $this->display_name,
            ];
            $result = wp_update_user($userdata);
            if (is_wp_error($result)) {
                throw new RuntimeException('wp_update_user error: ' . $result->get_error_message());
            }
        } else {
            // create new user
            if (empty($this->user_login) || empty($this->user_email)) {
                throw new RuntimeException('user_login and user_email are required to create a new WP user.');
            }
            $userdata = [
                'user_login'   => $this->user_login,
                'user_email'   => $this->user_email,
                'display_name' => $this->display_name,
                'user_pass'    => wp_generate_password(),
            ];
            $new_id = wp_insert_user($userdata);
            if (is_wp_error($new_id)) {
                throw new RuntimeException('wp_insert_user error: ' . $new_id->get_error_message());
            }
            $this->id = $new_id;
        }
    }

    /**
     * Delete this user via WP function.
     *
     * @throws RuntimeException On failure.
     */
    public function delete(): void
    {
        if (! $this->id) {
            return;
        }
        $result = wp_delete_user($this->id);
        if (! $result) {
            throw new RuntimeException("Failed to delete WP user {$this->id}.");
        }
        $this->id = null;
    }

    /**
     * Get the WP user_login.
     *
     * @return string
     */
    public function get_login(): string
    {
        return $this->user_login;
    }

    /**
     * Get the WP user_email.
     *
     * @return string
     */
    public function get_email(): string
    {
        return $this->user_email;
    }

    /**
     * Get the WP display_name.
     *
     * @return string
     */
    public function get_name(): string
    {
        return $this->display_name;
    }

    /**
     * Get all FluentCommunity spaces (communities) assigned to this user.
     *
     * @return WP_FCE_Model_Fcom[]
     */
    public function get_spaces(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fcom_space_user';
        $ids   = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT space_id FROM {$table} WHERE user_id = %d",
                $this->get_id()
            )
        );

        return $ids
            ? WP_FCE_Helper_Fcom::find(['id' => $ids])
            : [];
    }

    /**
     * Get all products this user has access to.
     *
     * @return WP_FCE_Model_Product[]
     */
    public function get_products(): array
    {
        return WP_FCE_Helper_Product_User::find(['user_id' => $this->get_id()]);
    }
}
