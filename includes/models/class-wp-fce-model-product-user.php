<?php
// File: includes/models/class-wp-fce-model-product-user.php

use DateTime;
use RuntimeException;

/**
 * Model for entries in wp_fce_product_user.
 *
 * Links a WP user to a product, with metadata like source,
 * transaction ID, start/expiry dates, status and optional note.
 */
class WP_FCE_Model_Product_User extends WP_FCE_Model_Base
{
    /**
     * Base table name (without WP prefix).
     *
     * @var string
     */
    protected static string $table = 'fce_product_user';

    /**
     * Columns and their WPDB format strings.
     *
     * @var array<string,string>
     */
    protected static array $db_fields = [
        'id'             => '%d',
        'user_id'        => '%d',
        'product_id'     => '%d',
        'source'         => '%s',
        'transaction_id' => '%s',
        'start_date'     => '%s',
        'expiry_date'    => '%s',
        'status'         => '%s',
        'note'           => '%s',
    ];

    /**
     * Columns that should never be mass-updated.
     *
     * @var string[]
     */
    protected static array $guarded = ['id'];

    /**
     * Columns that should be automatically cast.
     * Supported types: 'datetime'
     *
     * @var array<string,string>
     */
    protected static array $casts = [
        'start_date'  => 'datetime',
        'expiry_date' => 'datetime',
    ];

    /** @var int|null Primary key */
    public ?int $id = null;

    /** @var int WP user ID */
    public int $user_id;

    /** @var int Product ID */
    public int $product_id;

    /** @var string Source identifier (e.g. 'checkout', 'import') */
    public string $source;

    /** @var string|null External transaction ID, if any */
    public ?string $transaction_id = null;

    /** @var DateTime Subscription or assignment start */
    public DateTime $start_date;

    /** @var DateTime|null Subscription or assignment expiry */
    public ?DateTime $expiry_date = null;

    /** @var string Current status (e.g. 'active', 'expired') */
    public string $status;

    /** @var string|null Optional note or comment */
    public ?string $note = null;

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
     * Get the product ID.
     *
     * @return int
     */
    public function get_product_id(): int
    {
        return $this->product_id;
    }

    /**
     * Get the source string.
     *
     * @return string
     */
    public function get_source(): string
    {
        return $this->source;
    }

    /**
     * Get the external transaction ID.
     *
     * @return string|null
     */
    public function get_transaction_id(): ?string
    {
        return $this->transaction_id;
    }

    /**
     * Get the start date.
     *
     * @return DateTime
     */
    public function get_start_date(): DateTime
    {
        return $this->start_date;
    }

    /**
     * Get the expiry date.
     *
     * @return DateTime|null
     */
    public function get_expiry_date(): ?DateTime
    {
        return $this->expiry_date;
    }

    /**
     * Get the current status.
     *
     * @return string
     */
    public function get_status(): string
    {
        return $this->status;
    }

    /**
     * Get the optional note.
     *
     * @return string|null
     */
    public function get_note(): ?string
    {
        return $this->note;
    }

    /**
     * Set the start date.
     *
     * @param  DateTime $start_date
     * @return static
     */
    public function set_start_date(DateTime $start_date): static
    {
        $this->start_date = $start_date;
        return $this;
    }

    /**
     * Set the expiry date.
     *
     * @param  DateTime|null $expiry_date
     * @return static
     */
    public function set_expiry_date(?DateTime $expiry_date): static
    {
        $this->expiry_date = $expiry_date;
        return $this;
    }

    /**
     * Set the status string.
     *
     * @param  string $status
     * @return static
     */
    public function set_status(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Set the note.
     *
     * @param  string|null $note
     * @return static
     */
    public function set_note(?string $note): static
    {
        $this->note = $note;
        return $this;
    }

    /**
     * (Optional) Load a record by user_id + product_id.
     *
     * @param  int    $user_id
     * @param  int    $product_id
     * @return static
     * @throws RuntimeException If no match found.
     */
    public static function load_by_user_and_product(int $user_id, int $product_id): static
    {
        global $wpdb;
        $instance = new static();
        $sql = $wpdb->prepare(
            "SELECT * FROM {$instance->get_table_name()} WHERE user_id = %d AND product_id = %d LIMIT 1",
            $user_id,
            $product_id
        );
        $row = $wpdb->get_row($sql, ARRAY_A);
        if (! $row) {
            throw new RuntimeException("No record for user {$user_id} and product {$product_id}");
        }
        $instance->hydrateRow($row);

        return $instance;
    }
}
