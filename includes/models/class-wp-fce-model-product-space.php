<?php
// File: includes/models/class-wp-fce-model-product-space.php

use RuntimeException;

/**
 * Model for entries in wp_fce_product_space.
 *
 * Links a product to a space.
 */
class WP_FCE_Model_Product_Space extends WP_FCE_Model_Base
{
    /**
     * Base table name (without WP prefix).
     *
     * @var string
     */
    protected static string $table = 'fce_product_space';

    /**
     * Columns and their WPDB format strings.
     *
     * @var array<string,string>
     */
    protected static array $db_fields = [
        'id'          => '%d',
        'product_id'  => '%d',
        'space_id'    => '%d',
    ];

    /**
     * Columns that should never be mass-updated.
     *
     * @var string[]
     */
    protected static array $guarded = ['id'];

    /** @var int|null Primary key */
    public ?int $id = null;

    /** @var int Internal product ID */
    public int $product_id = 0;

    /** @var int Space ID (from wp_fcom_spaces) */
    public int $space_id = 0;

    /**
     * Get the product ID.
     *
     * @return int
     */
    public function get_product_id(): int
    {
        return $this->product_id;
    }

    /**
     * Retrieve the product associated with this product-space mapping.
     *
     * @return WP_FCE_Model_Product The product entity.
     */

    public function get_product(): WP_FCE_Model_Product
    {
        return WP_FCE_Model_Product::load_by_id($this->product_id);
    }

    /**
     * Get the space ID.
     *
     * @return int
     */
    public function get_space_id(): int
    {
        return $this->space_id;
    }

    /**
     * Retrieve the space entity associated with this product-space mapping.
     *
     * @return WP_FCE_Model_Fcom The space entity.
     */

    public function get_space(): WP_FCE_Model_Fcom
    {
        return WP_FCE_Model_Fcom::load_by_id($this->space_id);
    }
}
