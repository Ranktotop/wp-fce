<?php
// File: includes/models/class-wp-fce-model-access-log.php

use DateTime;
use RuntimeException;

/**
 * Model for entries in wp_fce_access_log.
 *
 * Records each access decision evaluation for a user entity,
 * with timestamp, decision and optional reason.
 */
class WP_FCE_Model_Access_Log extends WP_FCE_Model_Base
{
    /**
     * Base table name (without WP prefix).
     *
     * @var string
     */
    protected static string $table = 'fce_access_log';

    /**
     * Columns and their WPDB format strings.
     *
     * @var array<string,string>
     */
    protected static array $db_fields = [
        'id'           => '%d',
        'user_id'      => '%d',
        'entity_type'  => '%s',
        'entity_id'    => '%d',
        'evaluated_at' => '%s',
        'decision'     => '%d',
        'reason'       => '%s',
        'source_id'    => '%d',
    ];

    /**
     * Columns that should never be mass-updated.
     *
     * @var string[]
     */
    protected static array $guarded = ['id'];

    /**
     * Columns to cast automatically.
     * - evaluated_at → DateTime
     * - decision     → bool
     *
     * @var array<string,string>
     */
    protected static array $casts = [
        'evaluated_at' => 'datetime',
        'decision'     => 'bool',
    ];

    /** @var int|null Primary key */
    public ?int $id = null;

    /** @var int WP user ID */
    public int $user_id = 0;

    /** @var string Entity type (e.g. 'space', 'course') */
    public string $entity_type = '';

    /** @var int Entity ID */
    public int $entity_id = 0;

    /** @var DateTime Timestamp when decision was made */
    public DateTime $evaluated_at;

    /** @var bool True if access was granted, false if denied */
    public bool $decision = false;

    /** @var string|null Optional reason for decision */
    public ?string $reason = null;

    /** @var int ID of the source override/log that triggered this decision */
    public int $source_id = 0;

    /**
     * Check if access was granted.
     *
     * @return bool
     */
    public function is_granted(): bool
    {
        return $this->decision;
    }

    /**
     * Get the optional reason.
     *
     * @return string|null
     */
    public function get_reason(): ?string
    {
        return $this->reason;
    }

    /**
     * Get the source override/log ID.
     *
     * @return int
     */
    public function get_source_id(): int
    {
        return $this->source_id;
    }
}
