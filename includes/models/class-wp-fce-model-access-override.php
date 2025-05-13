<?php
// File: includes/models/class-wp-fce-model-access-override.php

use DateTime;
use RuntimeException;

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
        'entity_type'   => '%s',
        'entity_id'     => '%d',
        'override_type' => '%s',
        'source'        => '%s',
        'comment'       => '%s',
        'created_at'    => '%s',
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
    ];

    /** @var int|null Primary key */
    public ?int $id = null;

    /** @var int WP user ID */
    public int $user_id = 0;

    /** @var string The type of entity (e.g. 'space', 'course', 'bundle') */
    public string $entity_type = '';

    /** @var int The ID of the entity within its table */
    public int $entity_id = 0;

    /** @var string The type of override (e.g. 'grant', 'revoke') */
    public string $override_type = '';

    /** @var string Source of the override (e.g. 'admin', 'system') */
    public string $source = '';

    /** @var string|null Optional comment or note */
    public ?string $comment = null;

    /** @var DateTime When this override was created */
    public DateTime $created_at;

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
     * Get the entity type.
     *
     * @return string
     */
    public function get_entity_type(): string
    {
        return $this->entity_type;
    }

    /**
     * Get the entity ID.
     *
     * @return int
     */
    public function get_entity_id(): int
    {
        return $this->entity_id;
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
}
