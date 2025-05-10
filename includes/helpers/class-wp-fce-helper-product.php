<?php

class WP_FCE_Helper_Product
{
    /**
     * Get all products (WP_FCE_Model_Product objects)
     *
     * @return WP_FCE_Model_Product[]
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

    public function delete_product_by_id(int $product_id): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_products';

        $deleted = $wpdb->delete($table, ['id' => $product_id], ['%d']);

        if ($deleted === false || $deleted === 0) {
            throw new \Exception(__('Fehler beim Löschen', 'wp-fce'));
        }

        return true;
    }

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
