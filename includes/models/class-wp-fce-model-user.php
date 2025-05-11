<?php

class WP_FCE_Model_User extends WP_FCE_Model_Base
{
    private string $login;
    private string $email;
    private string $display_name;
    private WP_FCE_Helper_Ipn $ipn_helper;
    private WP_FCE_Helper_Fluent_Community_Entity $fcom_helper;
    private WP_FCE_Helper_Product $product_helper;

    /**
     * Constructor
     *
     * @param \WP_User $user The user model
     */
    public function __construct(int $id, string $login, string $email, string $display_name)
    {
        $this->id = $id;
        $this->login = $login;
        $this->email = $email;
        $this->display_name = $display_name;
        $this->ipn_helper = new WP_FCE_Helper_Ipn();
        $this->product_helper = new WP_FCE_Helper_Product();
        $this->fcom_helper = new WP_FCE_Helper_Fluent_Community_Entity();
    }

    /**
     * Loads a user by its ID.
     *
     * @param int $id The ID of the user to load.
     * @return WP_FCE_Model_User
     * @throws \Exception If the user was not found.
     */
    public static function load_by_id(int $id): WP_FCE_Model_User
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT ID, user_login, user_email, display_name FROM {$wpdb->users} WHERE ID = %d",
                $id
            ),
            ARRAY_A
        );

        if (!$row) {
            throw new \Exception(__('Benutzer wurde nicht gefunden.', 'wp-fce'));
        }

        return new self(
            (int) $row['ID'],
            $row['user_login'],
            $row['user_email'],
            $row['display_name']
        );
    }

    //******************************** */
    //************ GETTER ************ */
    //******************************** */

    public function get_id(): int
    {
        return (int) $this->id;
    }

    public function get_email(): string
    {
        return $this->email;
    }

    /**
     * Get all space entities assigned to this user via FluentCommunity.
     *
     * @return WP_FCE_Model_Space[] Array of space models
     */
    public function get_spaces(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fcom_space_user';

        $space_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT space_id FROM {$table} WHERE user_id = %d",
            $this->get_id()
        ));

        return $this->fcom_helper->get_by_ids($space_ids);
    }

    /**
     * Get all Products this user has access to.
     *
     * @return WP_FCE_Model_Product[] Array of spaces mapped to this product.
     */
    public function get_products(): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fce_product_user';

        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT fce_product_id FROM {$table} WHERE user_id = %d",
            $this->id
        ));

        if (empty($ids)) {
            return [];
        }

        return $this->product_helper->get_by_ids($ids);
    }

    //******************************** */
    //************* CRUDS ************ */
    //******************************** */

    /**
     * Grant access to a product for this user.
     *
     * @param int $product_id The internal product ID (not external).
     * @param int|null $expires_on Optional expiration timestamp.
     * @param string $source The source of access (e.g., 'admin', 'ipn').
     * @return bool True on success, false on failure.
     */
    public function add_product(int $product_id, ?int $expires_on = null, string $source = 'admin'): bool
    {
        $product = $this->product_helper->get_by_id($product_id);
        return $product->add_user($this->get_id(), $expires_on, $source);
    }
}
