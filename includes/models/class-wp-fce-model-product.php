<?php

class WP_FCE_Model_Product
{
    private int $id;
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
}
