<?php

class WP_FCE_Model_Product extends WP_FCE_Model_Base
{
    private string $product_id;
    private string $title;
    private string $description;

    /**
     * Constructor for the WP_FCE_Model_Product class.
     *
     * @param int $id The ID of the product.
     * @param string $product_id The external product ID.
     * @param string $title The title of the product.
     * @param string $description The description of the product.
     */

    public function __construct(int $id, string $product_id, string $title, string $description)
    {
        $this->id = $id;
        $this->product_id = $product_id;
        $this->title = $title;
        $this->description = $description;
    }

    /**
     * Loads a product by its ID.
     *
     * @param int $id The ID of the product to load.
     *
     * @return WP_FCE_Model_Product The loaded product.
     *
     * @throws \Exception If the product was not found.
     */
    public static function load_by_id(int $id): WP_FCE_Model_Product
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_products';

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$row) {
            throw new \Exception(__('Produkt nicht gefunden.', 'wp-fce'));
        }

        return new WP_FCE_Model_Product(
            (int) $row['id'],
            $row['product_id'],
            $row['title'],
            $row['description']
        );
    }

    //******************************** */
    //************ GETTER ************ */
    //******************************** */

    /**
     * Get the ID of the product.
     *
     * @return int The ID of the product.
     */
    public function get_id(): int
    {
        return $this->id;
    }

    /**
     * Get the external product ID of the product.
     *
     * @return string The product ID of the product.
     */
    public function get_product_id(): string
    {
        return $this->product_id;
    }

    /**
     * Get the title of the product.
     *
     * @return string The title of the product.
     */
    public function get_title(): string
    {
        return $this->title;
    }

    /**
     * Get the description of the product.
     *
     * @return string The description of the product.
     */
    public function get_description(): string
    {
        return $this->description;
    }

    /**
     * Get all FluentCommunity spaces mapped to this product.
     *
     * @return WP_FCE_Model_Fluent_Community_Entity[] Array of spaces mapped to this product.
     */
    public function get_spaces(): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fce_product_space';

        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT space_id FROM {$table} WHERE fce_product_id = %d",
            $this->id
        ));

        if (empty($ids)) {
            return [];
        }

        return $this->space_helper()->get_by_ids($ids);
    }

    /**
     * Get all FluentCommunity spaces mapped to this product.
     *
     * @return WP_FCE_Model_User[] Array of spaces mapped to this product.
     */
    public function get_users(): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fce_product_user';

        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM {$table} WHERE fce_product_id = %d",
            $this->id
        ));

        if (empty($ids)) {
            return [];
        }

        return $this->user_helper()->get_by_ids($ids);
    }

    /**
     * Get all IPNs saved for this product's external ID.
     *
     * @return WP_FCE_Model_IPN[] Array of ipns.
     */
    public function get_ipns(): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fce_ipn_log';

        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$table} WHERE external_product_id = %d",
            $this->get_product_id()
        ));

        if (empty($ids)) {
            return [];
        }

        return $this->ipn_helper()->get_by_ids($ids);
    }

    //******************************** */
    //************ SETTER ************ */
    //******************************** */

    /**
     * Synchronize the current space mappings with a new set of space IDs.
     *
     * @param int[] $space_ids List of space IDs to be assigned.
     * @return void
     */
    public function set_spaces(array $space_ids): void
    {
        $space_ids = array_map('intval', $space_ids);
        $current_ids = array_map(fn(WP_FCE_Model_Fluent_Community_Entity $s) => $s->get_id(), $this->get_spaces());

        $to_add = array_diff($space_ids, $current_ids);
        $to_remove = array_diff($current_ids, $space_ids);

        foreach ($to_add as $space_id) {
            $this->add_space($space_id);
        }

        foreach ($to_remove as $space_id) {
            $this->remove_space($space_id);
        }
    }

    //******************************** */
    //************* CRUDS ************ */
    //******************************** */

    /**
     * Remove a single FluentCommunity space from this product's mappings.
     *
     * @param int $space_id The ID of the space to remove.
     *
     * @return bool True if the space was removed successfully, false otherwise.
     */
    public function remove_space(int $space_id): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_product_space';

        $deleted = $wpdb->delete(
            $table,
            [
                'fce_product_id' => $this->id,
                'space_id'       => $space_id
            ],
            ['%d', '%d']
        );

        return $deleted !== false && $deleted > 0;
    }

    /**
     * Add a single FluentCommunity space to this product's mappings.
     *
     * @param int $space_id The ID of the space to add.
     *
     * @return bool True if the space was added successfully, false otherwise.
     *              If the space is already associated with the product, false is returned.
     */
    public function add_space(int $space_id): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_product_space';

        // Verhindere Duplikate durch vorherige Prüfung
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE fce_product_id = %d AND space_id = %d",
            $this->id,
            $space_id
        ));

        if ((int) $exists > 0) {
            return false; // Bereits vorhanden
        }

        $inserted = $wpdb->insert(
            $table,
            [
                'fce_product_id' => $this->id,
                'space_id'       => $space_id,
                'created_at'     => current_time('mysql')
            ],
            ['%d', '%d', '%s']
        );

        return $inserted !== false && $inserted > 0;
    }

    /**
     * Grant access to this product for a specific user.
     *
     * @param int $user_id The WordPress user ID.
     * @param int $expires_on expiration timestamp.
     * @param string $source The source of access (e.g., 'admin', 'ipn').
     * @return bool True on success, false on failure.
     */
    public function add_user(int $user_id, int $expires_on, string $source = 'admin'): bool
    {
        global $wpdb;
        $table      = $wpdb->prefix . 'fce_product_user';
        $product_id = $this->get_id();
        $now        = current_time('mysql');

        // Daten, die immer (neu) gesetzt werden
        $data = [
            'expires_on' => date('Y-m-d H:i:s', $expires_on),
            'source'     => $source,
            'updated_at' => $now,
        ];
        $data_formats = ['%s', '%s', '%s'];

        // 1) Existiert schon ein Link? Dann updaten
        $exists = (bool) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND fce_product_id = %d",
                $user_id,
                $product_id
            )
        );
        if ($exists) {
            $where        = ['user_id' => $user_id, 'fce_product_id' => $product_id];
            $where_formats = ['%d', '%d'];
            return false !== $wpdb->update($table, $data, $where, $data_formats, $where_formats);
        }

        // 2) Neuer Link anlegen
        $insert_data = array_merge($data, [
            'user_id'         => $user_id,
            'fce_product_id'  => $product_id,
            'created_at'      => $now,
        ]);
        $insert_formats = array_merge($data_formats, ['%d', '%d', '%s']);
        return false !== $wpdb->insert($table, $insert_data, $insert_formats);
    }

    /**
     * Remove access to this product for a specific user (delete link).
     *
     * @param int $user_id The WordPress user ID.
     * @return bool        True if the row was deleted or didn't exist, false on failure.
     */
    public function remove_user(int $user_id): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_product_user';

        // Eintrag löschen (wenn keiner da ist, affected_rows bleibt 0 und wir geben true zurück)
        $deleted = $wpdb->delete(
            $table,
            [
                'user_id'        => $user_id,
                'fce_product_id' => $this->get_id(),
            ],
            ['%d', '%d']
        );

        return $deleted !== false;
    }
}
