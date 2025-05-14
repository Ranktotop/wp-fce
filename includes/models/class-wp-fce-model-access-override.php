<?php
// File: includes/models/class-wp-fce-model-access-override.php

/**
 * Model for entries in wp_fce_product_access_overrides.
 *
 * Represents manual access overrides (e.g. granting/revoking access)
 * for a user on a specific entity.
 */
class WP_FCE_Model_Access_Override extends WP_FCE_Model_Base
{
    /**
     * Base table name (without WP prefix).
     *
     * @var string
     */
    protected static string $table = 'fce_product_access_overrides';

    /**
     * Columns and their WPDB format strings.
     *
     * @var array<string,string>
     */
    protected static array $db_fields = [
        'id'            => '%d',
        'user_id'       => '%d',
        'product_id'     => '%d',
        'override_type' => '%s',
        'source'        => '%s',
        'comment'       => '%s',
        'created_at'    => '%s',
        'valid_until'   => '%s',
    ];

    /**
     * Columns that should never be mass-updated.
     *
     * @var string[]
     */
    protected static array $guarded = ['id', 'created_at'];

    /**
     * Columns that should be automatically cast.
     *
     * @var array<string,string>
     */
    protected static array $casts = [
        'created_at' => 'datetime',
        'valid_until' => 'datetime'
    ];

    /** @var int|null Primary key */
    public ?int $id = null;

    /** @var int WP user ID */
    public int $user_id = 0;

    /** @var int The ID of the entity within its table */
    public int $product_id = 0;

    /** @var string The type of override (e.g. 'grant', 'revoke') */
    public string $override_type = '';

    /** @var string Source of the override (e.g. 'admin', 'system') */
    public string $source = '';

    /** @var string|null Optional comment or note */
    public ?string $comment = null;

    /** @var DateTime When this override was created */
    public DateTime $created_at;

    /** @var DateTime When this override expires */
    public ?DateTime $valid_until = null;

    /**
     * Get the WP user ID.
     *
     * @return int
     */
    public function get_user_id(): int
    {
        return $this->user_id;
    }

    /**
     * Retrieve the user associated with this access override.
     *
     * @return WP_FCE_Model_User The user entity.
     */
    public function get_user(): WP_FCE_Model_User
    {
        return WP_FCE_Model_User::load_by_id($this->user_id);
    }

    /**
     * Get the product ID.
     *
     * @return string
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

    /** @return DateTime|null */
    public function get_valid_until(): ?DateTime
    {
        return $this->valid_until;
    }

    /**
     * Get the override type.
     *
     * @return string
     */
    public function get_override_type(): string
    {
        return $this->override_type;
    }

    public function is_allow(): bool
    {
        return $this->override_type === 'allow';
    }

    public function is_deny(): bool
    {
        return $this->override_type === 'deny';
    }

    /**
     * Get the source of the override.
     *
     * @return string
     */
    public function get_source(): string
    {
        return $this->source;
    }

    /**
     * Get the optional comment.
     *
     * @return string|null
     */
    public function get_comment(): ?string
    {
        return $this->comment;
    }

    /**
     * Get the creation timestamp.
     *
     * @return DateTime
     */
    public function get_created_at(): DateTime
    {
        return $this->created_at;
    }

    /**
     * Optionally set a new comment.
     *
     * @param  string|null $comment
     * @return static
     */
    public function set_comment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * Set a new source.
     *
     * @param  string $comment
     * @return static
     */
    public function set_source(string $source): static
    {
        $this->source = $source;
        return $this;
    }

    public function set_valid_until(\DateTime|int|string|null $dt): static
    {
        $this->valid_until = self::normalizeDateTime($dt);
        return $this;
    }

    public function is_valid(): bool
    {
        $now = new \DateTime(current_time('mysql'));
        $validUntil = $this->get_valid_until();
        return $validUntil === null || $validUntil > $now;
    }

    public function set_override_type(string $type): static
    {
        $this->override_type = $type;
        return $this;
    }
}
