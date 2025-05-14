<?php
// File: includes/helpers/class-wp-fce-helper-fcom.php

/**
 * @extends WP_FCE_Helper_Base<WP_FCE_Model_Fcom>
 */
class WP_FCE_Helper_Fcom extends WP_FCE_Helper_Base
{
    /**
     * Table name without WP prefix.
     *
     * @var string
     */
    protected static string $table       = 'fcom_spaces';

    /**
     * Model class this helper works with.
     *
     * @var class-string<WP_FCE_Model_Fcom>
     */
    protected static string $model_class = WP_FCE_Model_Fcom::class;

    /**
     * Fetch all FluentCommunity entities of type "community" (spaces), ordered by title.
     *
     * @return WP_FCE_Model_Fcom[]
     */
    public static function get_all_spaces(): array
    {
        return static::find(
            ['type' => 'community'],
            ['title' => 'ASC']
        );
    }

    /**
     * Fetch all FluentCommunity entities of type "course", ordered by title.
     *
     * @return WP_FCE_Model_Fcom[]
     */
    public static function get_all_courses(): array
    {
        return static::find(
            ['type' => 'course'],
            ['title' => 'ASC']
        );
    }

    /**
     * Fetch all FluentCommunity Models which are mapped to the given product exclusively.
     *
     * @param  int  $product_id
     * @return WP_FCE_Model_Fcom[]
     */
    public static function get_spaces_exclusive_to_product(int $product_id): array
    {
        global $wpdb;
        $table = WP_FCE_Helper_Product_Space::getTableName();

        $product = WP_FCE_Model_Product::load_by_id($product_id);
        $spaces = $product->get_mapped_spaces();

        if (empty($spaces)) {
            return [];
        }

        $space_ids = array_map(fn($s) => $s->get_id(), $spaces);

        // Query: finde alle space_id, die auch mit anderen Produkten gemappt sind
        $placeholders = implode(',', array_fill(0, count($space_ids), '%d'));

        $sql = "
        SELECT DISTINCT space_id
        FROM {$table}
        WHERE space_id IN ({$placeholders})
          AND product_id != %d
    ";

        $params = array_merge($space_ids, [$product->get_id()]);

        $used_elsewhere = $wpdb->get_col($wpdb->prepare($sql, ...$params));
        $used_elsewhere = array_map('intval', $used_elsewhere);

        return array_filter(
            $spaces,
            fn($s) => ! in_array($s->get_id(), $used_elsewhere, true)
        );
    }
}
