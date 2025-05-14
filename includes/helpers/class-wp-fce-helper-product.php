<?php
// File: includes/helpers/class-wp-fce-helper-product.php

/**
 * @extends WP_FCE_Helper_Base<WP_FCE_Model_Product>
 */
class WP_FCE_Helper_Product extends WP_FCE_Helper_Base
{
    /** @var string Table name without prefix */
    protected static string $table = 'fce_products';

    /**
     * @var class-string<WP_FCE_Model_Product>
     */
    protected static string $model_class = WP_FCE_Model_Product::class;

    /**
     * Find one product by SKU.
     */
    public static function get_by_sku(string $sku): ?WP_FCE_Model_Product
    {
        return static::findOneBy(['sku' => $sku]);
    }

    /**
     * Create a new product.
     *
     * @param  string $sku
     * @param  string $name
     * @param  string $description
     * @return WP_FCE_Model_Product
     * @throws Exception On duplicate SKU or DB error.
     */
    public static function create(string $sku, string $name, string $description): WP_FCE_Model_Product
    {
        global $wpdb;
        $table = static::getTableName();

        // Unique check
        $exists = static::get_by_sku($sku);
        if ($exists) {
            throw new \Exception(sprintf(
                __('Product with SKU "%s" already exists.', 'wp-fce'),
                $sku
            ));
        }

        $ok = $wpdb->insert(
            $table,
            compact('sku', 'name', 'description'),
            ['%s', '%s', '%s']
        );
        if (false === $ok) {
            throw new \Exception("DB insert error: {$wpdb->last_error}");
        }

        return static::get_by_id((int)$wpdb->insert_id);
    }

    /**
     * Update an existing product.
     *
     * @param  int    $id
     * @param  string $name
     * @param  string $description
     * @return WP_FCE_Model_Product
     * @throws Exception On DB error.
     */
    public static function update(int $id, string $name, string $description): WP_FCE_Model_Product
    {
        global $wpdb;
        $table = static::getTableName();

        $ok = $wpdb->update(
            $table,
            compact('name', 'description'),
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );
        if (false === $ok) {
            throw new \Exception("DB update error: {$wpdb->last_error}");
        }

        return static::get_by_id($id);
    }

    /**
     * Delete a product by ID.
     *
     * @param  int $id
     * @return bool
     * @throws Exception On DB error.
     */
    public static function delete(int $id): bool
    {
        global $wpdb;
        $ok = $wpdb->delete(
            static::getTableName(),
            ['id' => $id],
            ['%d']
        );
        if (false === $ok) {
            throw new \Exception("DB delete error: {$wpdb->last_error}");
        }
        return (bool)$ok;
    }

    /**
     * Retrieve all products that are not assigned to any space.
     *
     * @return WP_FCE_Model_Product[] Array of unassigned products.
     */
    public static function get_unmapped_products(): array
    {
        global $wpdb;

        $product_table = static::getTableName();           // e.g. wp_fce_products
        $space_table   = WP_FCE_Helper_Product_Space::getTableName();

        // Left join to find products with no matching entry in product_space
        $sql = "
        SELECT p.*
        FROM {$product_table} AS p
        LEFT JOIN {$space_table} AS ps
          ON p.id = ps.product_id
        WHERE ps.product_id IS NULL
    ";

        $rows = $wpdb->get_results($sql, ARRAY_A);
        if (empty($rows)) {
            return [];
        }

        // Map raw rows to hydrated Product models
        return array_map(
            fn(array $row) => static::$model_class::load_by_row($row),
            $rows
        );
    }

    /**
     * Return all products which have at least one mapping in fce_product_space.
     *
     * @return WP_FCE_Model_Product[]
     */
    public static function get_mapped_products(): array
    {
        global $wpdb;

        $product_table = static::getTableName();           // e.g. wp_fce_products
        $space_table   = WP_FCE_Helper_Product_Space::getTableName();

        // Inner join to find products with at least one mapping,
        // and DISTINCT to avoid duplicates if multiple spaces per product.
        $sql = "
        SELECT DISTINCT p.*
        FROM {$product_table} AS p
        INNER JOIN {$space_table} AS ps
          ON p.id = ps.product_id
    ";

        $rows = $wpdb->get_results($sql, ARRAY_A);
        if (empty($rows)) {
            return [];
        }

        return array_map(
            fn(array $row) => static::$model_class::load_by_row($row),
            $rows
        );
    }
}
