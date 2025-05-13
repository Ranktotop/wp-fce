<?php
// File: includes/models/class-wp-fce-model-product.php

/**
 * Model for entries in wp_fce_products.
 */
class WP_FCE_Model_Product extends WP_FCE_Model_Base
{
    /**
     * Base table name (without WP prefix).
     *
     * @var string
     */
    protected static string $table = 'fce_products';

    /**
     * Columns and their WPDB format strings.
     *
     * @var array<string,string>
     */
    protected static array $db_fields = [
        'id' => '%d',
        'sku' => '%s',
        'name' => '%s',
        'description' => '%s'
    ];

    /**
     * Columns that should never be mass-updated.
     *
     * @var string[]
     */
    protected static array $guarded = ['id', 'sku'];

    /** @var int|null */
    public ?int   $id           = null;

    /** @var string */
    public string $sku        = '';

    /** @var string */
    public string $name       = '';

    /** @var string */
    public string $description   = '';

    /**
     * Load a single product by its SKU.
     *
     * @param  string         $sku  The product SKU to look up.
     * @return static              A hydrated product instance.
     * @throws RuntimeException    If no product with that SKU exists.
     */
    public static function load_by_sku(string $sku): static
    {
        global $wpdb;
        $instance = new static();
        $sql = $wpdb->prepare(
            "SELECT * FROM {$instance->get_table_name()} WHERE sku = %s LIMIT 1",
            $sku
        );
        $row = $wpdb->get_row($sql, ARRAY_A);

        if (! $row) {
            throw new \RuntimeException("No product found with SKU {$sku}");
        }

        // Ein einziger Hydrations-Aufruf:
        $instance->hydrateRow($row);

        return $instance;
    }

    /**
     * Get the product SKU.
     *
     * @return string
     */
    public function get_sku(): string
    {
        return $this->sku;
    }

    /**
     * Get the product name.
     *
     * @return string
     */
    public function get_name(): string
    {
        return $this->name;
    }

    /**
     * Get the product description.
     *
     * @return string
     */
    public function get_description(): string
    {
        return $this->description;
    }

    /**
     * Get all FluentCommunity "space" entities mapped to this product.
     *
     * @return WP_FCE_Model_Product_Space[]
     */
    private function get_mappings(): array
    {
        return WP_FCE_Helper_Product_Space::get_for_product($this->get_id());
    }

    /**
     * Get all FluentCommunity "space" entities mapped to this product.
     *
     * @return WP_FCE_Model_Fcom[]
     */
    public function get_mapped_communities(): array
    {
        // Extract all space IDs
        $space_ids = array_unique(array_map(fn($m) => $m->get_space_id(), $this->get_mappings()));

        if (empty($space_ids)) {
            return [];
        }

        // 3) Fetch only those spaces of type 'community'
        return WP_FCE_Helper_Fcom::find(
            ['id'   => $space_ids, 'type' => 'community'],
            ['title' => 'ASC']
        );
    }

    /**
     * Get all FluentCommunity "course" entities mapped to this product.
     *
     * @return WP_FCE_Model_Fcom[]
     */
    public function get_mapped_courses(): array
    {
        $course_ids = array_unique(array_map(fn($m) => $m->get_space_id(), $this->get_mappings()));

        if (empty($course_ids)) {
            return [];
        }

        return WP_FCE_Helper_Fcom::find(
            ['id'   => $course_ids, 'type' => 'course'],
            ['title' => 'ASC']
        );
    }

    public function set_name(string $name): static
    {
        if ('' === trim($name)) {
            throw new \InvalidArgumentException('Name darf nicht leer sein.');
        }
        $this->name = $name;
        return $this;
    }

    public function set_description(string $description): static
    {
        $this->description = $description;
        return $this;
    }
}
