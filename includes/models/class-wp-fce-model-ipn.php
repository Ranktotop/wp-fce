<?php

class WP_FCE_Model_Ipn extends WP_FCE_Model_Base
{
    private string $user_email;
    private string $transaction_id;
    private DateTime $ipn_date;
    private string $external_product_id;
    private string $source;
    private array $ipn_data;
    private WP_FCE_Helper_Product $product_helper;
    private WP_FCE_Helper_User $user_helper;

    public function __construct(
        int $id,
        string $user_email,
        string $transaction_id,
        string $ipn_date,
        string $external_product_id,
        string $source,
        string $ipn_raw_json
    ) {
        $this->id = $id;
        $this->user_email = $user_email;
        $this->transaction_id = $transaction_id;

        try {
            $this->ipn_date = new DateTime($ipn_date);
        } catch (Exception $e) {
            $this->ipn_date = new DateTime('@0'); // fallback
        }

        $this->external_product_id = $external_product_id;
        $this->source = $source;

        $data = json_decode($ipn_raw_json, true);
        $this->ipn_data = is_array($data) ? $data : [];

        $this->product_helper = new WP_FCE_Helper_Product();
        $this->user_helper = new WP_FCE_Helper_User();
    }

    /**
     * Loads a ipn by its ID.
     *
     * @param int $id The ID of the ipn to load.
     *
     * @return WP_FCE_Model_Ipn The loaded ipn.
     *
     * @throws \Exception If the ipn was not found.
     */
    public static function load_by_id(int $id): WP_FCE_Model_Ipn
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_ipn_log';

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$row) {
            throw new \Exception(__('IPN-Eintrag nicht gefunden.', 'wp-fce'));
        }

        return new self(
            (int) $row['id'],
            $row['user_email'],
            $row['transaction_id'],
            $row['ipn_date'],
            $row['external_product_id'],
            $row['source'],
            $row['ipn']
        );
    }

    //******************************** */
    //************ CHECKER *********** */
    //******************************** */

    /**
     * Check if the transaction has expired.
     *
     * @param int $day_shift Days to shift the expiration date. Defaults to 3.
     * @return bool True if expired, false otherwise.
     */
    public function is_expired(int $day_shift = 3): bool
    {
        $paid_until_timestamp = $this->get_paid_until_timestamp();
        if ($paid_until_timestamp === null) {
            return true;
        }
        return $paid_until_timestamp < time() + ($day_shift * 24 * 60 * 60);
    }

    /**
     * Determine if the transaction is a topup event.
     *
     * Checks the 'is_topup' flag in the transaction data. If not a topup,
     * checks the 'is_test' flag to identify test transactions.
     *
     * @return bool True if the transaction is a topup or test event, false otherwise.
     */

    public function is_topup_event(): bool
    {
        //Check if it is topup first
        $topup = $this->ipn_data['transaction']['is_topup'] ?? false;
        //If not topup, check if it is test
        if (!$topup) {
            return $this->ipn_data['transaction']['is_test'] ?? false;
        }

        return $topup;
    }

    //******************************** */
    //************ GETTER ************ */
    //******************************** */

    public function get_id(): int
    {
        return $this->id;
    }

    public function get_user_email(): string
    {
        return $this->user_email;
    }

    public function get_transaction_id(): string
    {
        return $this->transaction_id;
    }

    public function get_ipn_date(): DateTime
    {
        return $this->ipn_date;
    }

    public function get_ipn_date_timestamp(): int
    {
        return $this->ipn_date->getTimestamp();
    }

    public function get_external_product_id(): string
    {
        return $this->external_product_id;
    }

    public function get_source(): string
    {
        return $this->source;
    }

    public function get_ipn_data(): array
    {
        return $this->ipn_data;
    }

    public function get_paid_until_timestamp(): ?int
    {
        return $this->ipn_data['transaction']['paid_until'] ?? null;
    }

    /**
     * Returns the user model based on the email in the IPN.
     *
     * @return WP_FCE_Model_User
     * @throws \Exception If no user with that email exists.
     */
    public function get_user(): WP_FCE_Model_User
    {
        $user = get_user_by('email', $this->get_user_email());

        if (!$user) {
            throw new \Exception(sprintf(__('Kein Nutzer mit der E-Mail %s gefunden.', 'wp-fce'), $this->get_user_email()));
        }

        return $this->user_helper->get_by_id($user->ID);
    }

    /**
     * Returns the product model based on the external product ID.
     *
     * @return WP_FCE_Model_Product
     * @throws \Exception If no product was found.
     */
    public function get_product(): WP_FCE_Model_Product
    {
        return $this->product_helper->get_by_external_product_id($this->get_external_product_id());
    }

    /**
     * Get all Spaces/Courses mapped to the product of this IPN.
     *
     * @return WP_FCE_Model_Fluent_Community_Entity[]
     * @throws \Exception
     */
    public function get_spaces(): array
    {
        return $this->get_product()->get_spaces();
    }
}
