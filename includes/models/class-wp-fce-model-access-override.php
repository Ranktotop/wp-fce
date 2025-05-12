<?php

class WP_FCE_Model_Access_Override extends WP_FCE_Model_Base
{
    private int $user_id;
    private string $fce_product_id;
    private string $mode;
    private int $valid_until;

    public function __construct(int $id, int $user_id, string $fce_product_id, string $mode, int $valid_until)
    {
        $this->id = $id;
        $this->user_id = $user_id;
        $this->fce_product_id = $fce_product_id;
        $this->mode = $mode;
        $this->valid_until = $valid_until;
    }

    /**
     * Loads an access override by its ID.
     *
     * @param int $id The ID of the access override to load.
     *
     * @return WP_FCE_Model_Access_Override The loaded access override.
     *
     * @throws \Exception If the access override was not found.
     */
    public static function load_by_id(int $id): WP_FCE_Model_Access_Override
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_product_access_overrides';

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$row) {
            throw new \Exception(__('Override nicht gefunden.', 'wp-fce'));
        }

        return new WP_FCE_Model_Access_Override(
            (int) $row['id'],
            $row['user_id'],
            $row['fce_product_id'],
            $row['mode'],
            (int) strtotime($row['valid_until'])
        );
    }

    //******************************** */
    //*********** CHECKER ************ */
    //******************************** */

    public function is_active(): bool
    {
        return time() < $this->valid_until;
    }

    public function is_deny(): bool
    {
        return $this->get_mode() === 'deny';
    }

    //******************************** */
    //************ GETTER ************ */
    //******************************** */

    public function get_user_id(): int
    {
        return $this->user_id;
    }

    public function get_product_id(): string
    {
        return $this->fce_product_id;
    }

    public function get_valid_until(): int
    {
        return $this->valid_until;
    }

    public function get_mode(): string
    {
        return $this->mode;
    }

    public function get_user(): WP_FCE_Model_User
    {
        return $this->user_helper()->get_by_id($this->get_user_id());
    }

    public function get_product(): WP_FCE_Model_Product
    {
        return $this->product_helper()->get_by_id($this->get_product_id());
    }
}
