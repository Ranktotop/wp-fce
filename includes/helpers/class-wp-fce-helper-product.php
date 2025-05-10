<?php

class WP_FCE_Helper_Product
{

    //******************************** */
    //************ GETTER ************ */
    //******************************** */

    /**
     * Get all products (WP_FCE_Model_Product objects)
     *
     * @return WP_FCE_Model_Product[] Array of WP_FCE_Model_Product objects
     */
    public function get_all_products(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_products';

        $results = $wpdb->get_results("SELECT id, product_id, title, description FROM {$table} ORDER BY created_at DESC", ARRAY_A);

        if (empty($results)) {
            return [];
        }

        $products = [];

        foreach ($results as $row) {
            $products[] = new WP_FCE_Model_Product(
                (int) $row['id'],
                (string) $row['product_id'],
                (string) $row['title'],
                (string) $row['description']
            );
        }

        return $products;
    }


    /**
     * Get a product by its ID.
     *
     * @param int $id The ID of the product to retrieve.
     *
     * @return WP_FCE_Model_Product The product with the given ID, or throw an exception if no product was found.
     *
     * @throws \Exception If the product was not found.
     */
    public function get_product_by_id(int $id): WP_FCE_Model_Product
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fce_products';

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$row) {
            throw new \Exception(__('Produkt wurde nicht gefunden.', 'wp-fce'));
        }

        return new WP_FCE_Model_Product(
            (int) $row['id'],
            $row['product_id'],
            $row['title'],
            $row['description']
        );
    }

    /**
     * Get all products without any space or course mapping.
     *
     * @return WP_FCE_Model_Product[] Array of products without any mapping.
     */
    public function get_unmapped_products(): array
    {
        global $wpdb;

        $table_products = $wpdb->prefix . 'fce_products';
        $table_mapping  = $wpdb->prefix . 'fce_product_space';

        $rows = $wpdb->get_results("
		SELECT p.id, p.product_id, p.title, p.description
		FROM {$table_products} p
		LEFT JOIN {$table_mapping} m ON m.fce_product_id = p.id
		WHERE m.fce_product_id IS NULL
		ORDER BY p.title ASC
	", ARRAY_A);

        return array_map(fn($row) => new WP_FCE_Model_Product(
            (int) $row['id'],
            $row['product_id'],
            $row['title'],
            $row['description']
        ), $rows);
    }

    /**
     * Get all products that have any space or course mapping.
     *
     * Array format:
     * [
     *     'product' => WP_FCE_Model_Product,
     *     'spaces'  => WP_FCE_Model_Fluent_Community_Entity[]
     * ]
     *
     * @return array Array of products with their mapped spaces.
     */
    public function get_all_mapped_products_with_entities(): array
    {
        global $wpdb;

        $table_products = $wpdb->prefix . 'fce_products';
        $table_mapping  = $wpdb->prefix . 'fce_product_space';
        $table_fcom     = $wpdb->prefix . 'fcom_spaces';

        $rows = $wpdb->get_results("
		SELECT
			p.id AS product_id,
			p.product_id AS external_id,
			p.title AS product_title,
			p.description AS product_description,
			s.id AS space_id,
			s.title AS space_title,
			s.slug AS space_slug,
			s.type AS space_type
		FROM {$table_products} p
		INNER JOIN {$table_mapping} m ON m.fce_product_id = p.id
		INNER JOIN {$table_fcom} s ON s.id = m.space_id
		ORDER BY p.title ASC, s.title ASC
	", ARRAY_A);

        $result = [];

        foreach ($rows as $row) {
            $key = $row['product_id'];

            if (!isset($result[$key])) {
                $result[$key] = [
                    'product' => new WP_FCE_Model_Product(
                        (int) $row['product_id'],
                        $row['external_id'],
                        $row['product_title'],
                        $row['product_description']
                    ),
                    'spaces' => [],
                ];
            }

            $result[$key]['spaces'][] = new WP_FCE_Model_Fluent_Community_Entity(
                (int) $row['space_id'],
                $row['space_title'],
                $row['space_slug'],
                $row['space_type']
            );
        }

        return array_values($result);
    }

    //******************************** */
    //************* CRUD ************* */
    //******************************** */

    /**
     * Deletes a product by its ID.
     *
     * @param int $product_id The ID of the product to delete.
     *
     * @return bool True if the product was deleted, false otherwise.
     *
     * @throws \Exception If there was an error while deleting the product.
     */
    public function delete_product_by_id(int $product_id): bool
    {
        global $wpdb;

        $table_products = $wpdb->prefix . 'fce_products';
        $table_mappings = $wpdb->prefix . 'fce_product_space';

        // 1. Mappings löschen
        $wpdb->delete($table_mappings, [
            'fce_product_id' => $product_id,
        ], ['%d']);

        // 2. Produkt löschen
        $deleted = $wpdb->delete($table_products, ['id' => $product_id], ['%d']);

        if ($deleted === false || $deleted === 0) {
            throw new \Exception(__('Fehler beim Löschen des Produkts', 'wp-fce'));
        }

        return true;
    }

    /**
     * Updates a product by its ID.
     *
     * @param int    $product_id    The ID of the product to update.
     * @param string $title         The new title of the product.
     * @param string $description   The new description of the product.
     *
     * @return bool True if the product was updated, false otherwise.
     *
     * @throws \Exception If the title is empty, or if a database error occurs.
     */
    public function update_product_by_id(int $product_id, string $title, string $description): bool
    {
        if (trim($title) === '') {
            throw new \Exception(__('Titel darf nicht leer sein', 'wp-fce'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'fce_products';

        $updated = $wpdb->update(
            $table,
            [
                'title'       => $title,
                'description' => $description,
                'updated_at'  => current_time('mysql')
            ],
            ['id' => $product_id],
            ['%s', '%s', '%s'],
            ['%d']
        );

        if ($updated === false) {
            throw new \Exception(__('Datenbankfehler beim Aktualisieren', 'wp-fce'));
        }

        return true;
    }

    /**
     * Creates a new product in the database.
     *
     * @param string $product_id    Unique identifier for the product.
     * @param string $title         The title of the product.
     * @param string $description   The description of the product.
     *
     * @return bool True if the product was created successfully, false otherwise.
     *
     * @throws \Exception If the product ID is already taken.
     * @throws \Exception If the product could not be saved.
     */
    public function create_product(string $product_id, string $title, string $description): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_products';

        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE product_id = %s", $product_id));
        if ($exists) {
            throw new \Exception(__('Produkt-ID ist bereits vergeben.', 'wp-fce'));
        }

        $inserted = $wpdb->insert($table, [
            'product_id'   => $product_id,
            'title'        => $title,
            'description'  => $description,
            'created_at'   => current_time('mysql'),
        ]);

        if (!$inserted) {
            throw new \Exception(__('Produkt konnte nicht gespeichert werden.', 'wp-fce'));
        }

        return true;
    }
}
