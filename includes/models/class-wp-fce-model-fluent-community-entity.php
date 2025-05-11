<?php

/**
 * Represents a FluentCommunity Space or Course entry.
 */
class WP_FCE_Model_Fluent_Community_Entity extends WP_FCE_Model_Base
{
    private string $title;
    private string $slug;
    private string $type;
    private WP_FCE_Helper_Product $product_helper;

    /**
     * Constructor for a FluentCommunity Entity (space, course, etc.)
     *
     * @param int    $id    The ID of the entity.
     * @param string $title The title.
     * @param string $slug  The slug.
     * @param string $type  The type (e.g. space_group, community, course).
     */
    public function __construct(int $id, string $title, string $slug, string $type)
    {
        $this->id    = $id;
        $this->title = $title;
        $this->slug  = $slug;
        $this->type  = $type;
        $this->product_helper = new WP_FCE_Helper_Product();
    }

    /**
     * Loads a space/course entity by its ID.
     *
     * @param int $id
     * @return WP_FCE_Model_Fluent_Community_Entity
     * @throws \Exception If no entity was found.
     */
    public static function load_by_id(int $id): WP_FCE_Model_Fluent_Community_Entity
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, title, slug, type FROM {$wpdb->prefix}fcom_spaces WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        if (!$row) {
            throw new \Exception(__('Fluent Community Entity wurde nicht gefunden.', 'wp-fce'));
        }

        return new self(
            (int) $row['id'],
            $row['title'],
            $row['slug'],
            $row['type']
        );
    }

    //******************************** */
    //************ CHECKER *********** */
    //******************************** */

    /**
     * Checks if this entity is a course.
     *
     * @return bool
     */
    public function is_course(): bool
    {
        return $this->type === 'course';
    }

    /**
     * Checks if this entity is a space or group.
     *
     * @return bool
     */
    public function is_space(): bool
    {
        return in_array($this->type, ['community'], true);
    }

    //******************************** */
    //************ GETTER ************ */
    //******************************** */

    /**
     * Get the ID of the entity.
     *
     * @return int The ID of the entity.
     */

    public function get_id(): int
    {
        return $this->id;
    }

    /**
     * Get the title of the entity.
     *
     * @return string The title of the entity.
     */
    public function get_title(): string
    {
        return $this->title;
    }

    /**
     * Get the slug of the entity.
     *
     * @return string The slug of the entity.
     */
    public function get_slug(): string
    {
        return $this->slug;
    }

    /**
     * Get the type of the entity.
     *
     * @return string The type of the entity.
     */

    public function get_type(): string
    {
        return $this->type;
    }

    /**
     * Get all products mapped to this FluentCommunity space/course.
     *
     * @return WP_FCE_Model_Product[] Array of products mapped to this entity.
     */
    public function get_products(): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fce_product_space';

        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT fce_product_id FROM {$table} WHERE space_id = %d",
            $this->id
        ));

        if (empty($ids)) {
            return [];
        }

        $helper = new WP_FCE_Helper_Product();
        return $helper->get_by_ids($ids);
    }

    /**
     * Get all users assigned to this FluentCommunity space/course.
     *
     * @return WP_FCE_Model_User[] Array of users assigned to this entity.
     */
    public function get_users(): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fcom_space_user';

        $user_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM {$table} WHERE space_id = %d",
            $this->id
        ));

        if (empty($user_ids)) {
            return [];
        }

        $user_helper = new WP_FCE_Helper_User();
        return $user_helper->get_by_ids($user_ids);
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
    public function add_product(int $product_id): bool
    {
        $product = $this->product_helper->get_by_id($product_id);
        return $product->add_space($this->id);
    }
}
