<?php
// File: includes/models/class-wp-fce-model-ipn-log.php

/**
 * Model for entries in wp_fce_ipn_log.
 *
 * Represents an Instant Payment Notification (IPN) record.
 * Automatically decodes the JSON payload into a PHP array and
 * casts ipn_date into a DateTime object.
 */
class WP_FCE_Model_Ipn_Log extends WP_FCE_Model_Base
{
    /**
     * Base table name (without WP prefix).
     *
     * @var string
     */
    protected static string $table = 'fce_ipn_log';

    /**
     * Columns and their WPDB format strings.
     *
     * @var array<string,string>
     */
    protected static array $db_fields = [
        'id'                   => '%d',
        'user_email'           => '%s',
        'transaction_id'       => '%s',
        'ipn_date'             => '%s',
        'external_product_id'  => '%s',
        'source'               => '%s',
        'ipn'                  => '%s',
        'ipn_hash'             => '%s',
    ];

    /**
     * Columns that should never be mass-updated.
     *
     * @var string[]
     */
    protected static array $guarded = ['id'];

    /**
     * Columns that should be automatically cast.
     * - ipn_date → DateTime
     * - ipn      → JSON decode into array
     *
     * @var array<string,string>
     */
    protected static array $casts = [
        'ipn_date' => 'datetime',
        'ipn'      => 'json',
    ];

    /** @var int|null Primary key */
    public ?int $id = null;

    /** @var string Payer's email address */
    public string $user_email = '';

    /** @var string External transaction ID */
    public string $transaction_id = '';

    /** @var DateTime Date/time when the IPN was received */
    public DateTime $ipn_date;

    /** @var string External product identifier (SKU) */
    public string $external_product_id = '';

    /** @var string Source of the IPN (e.g. 'paypal', 'stripe') */
    public string $source = '';

    /** @var mixed[] Decoded JSON payload */
    public array $ipn = [];

    /** @var string Hash of the IPN payload for integrity */
    public string $ipn_hash = '';

    /**********************************/
    /* GETTER                         */
    /**********************************/

    /**
     * Get the payer's email.
     *
     * @return string
     */
    public function get_user_email(): string
    {
        return $this->user_email;
    }

    /**
     * Get the transaction ID.
     *
     * @return string
     */
    public function get_transaction_id(): string
    {
        return $this->transaction_id;
    }

    /**
     * Get the IPN receipt date.
     *
     * @return DateTime
     */
    public function get_ipn_date(): DateTime
    {
        return $this->ipn_date;
    }

    /**
     * Get the external product identifier (SKU).
     *
     * @return string
     */
    public function get_external_product_id(): string
    {
        return $this->external_product_id;
    }

    /**
     * Get the IPN source.
     *
     * @return string
     */
    public function get_source(): string
    {
        return $this->source;
    }

    /**
     * Get the decoded IPN payload.
     *
     * @return mixed[]
     */
    public function get_ipn(): array
    {
        return $this->ipn;
    }

    /**
     * Get the IPN payload hash.
     *
     * @return string
     */
    public function get_ipn_hash(): string
    {
        return $this->ipn_hash;
    }

    public function get_paid_until(): ?DateTime
    {
        $paidUntil = $this->ipn["transaction"]["paid_until"] ?? null;

        if ($paidUntil === null) {
            return null;
        }

        try {
            return new DateTime($paidUntil);
        } catch (Exception $e) {
            // Optional: Log oder Fehlerbehandlung
            return null;
        }
    }

    /**
     * Check if the IPN's paid_until date is in the past.
     *
     * @return bool True if the paid_until date is set and in the past, false otherwise.
     */
    public function is_expired(): bool
    {
        $dt = $this->get_paid_until();
        if ($dt === null) {
            return false;
        }
        return $this->get_paid_until() < new DateTime();
    }

    public function get_management_link(): ?string
    {
        return $this->ipn['transaction']['management_url'] ?? null;
    }

    /**********************************/
    /* SETTER                         */
    /**********************************/

    /**
     * Set the IPN payload hash.
     *
     * @param  string  $hash
     * @return void
     */
    public function set_ipn_hash(string $hash): void
    {
        $this->ipn_hash = $hash;
    }

    public function set_user_email(string $email): void
    {
        $this->user_email = $email;
    }

    public function set_transaction_id(string $transaction_id): void
    {
        $this->transaction_id = $transaction_id;
    }

    public function set_source(string $source): void
    {
        $this->source = $source;
    }

    public function set_ipn(array $ipn): void
    {
        $this->ipn = $ipn;
    }

    public function set_ipn_date(DateTime $date): void
    {
        $this->ipn_date = $date;
    }

    public function set_external_product_id(string $product_id): void
    {
        $this->external_product_id = $product_id;
    }
}
