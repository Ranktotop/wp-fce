<?php

class WP_FCE_Model_User
{
    private \WP_User $user;
    private WP_FCE_Helper_Ipn $ipn_helper;
    private WP_FCE_Helper_Product $product_helper;

    /**
     * Constructor
     *
     * @param \WP_User $user The user model
     */
    public function __construct(\WP_User $user)
    {
        $this->user = $user;
        $this->ipn_helper = new WP_FCE_Helper_Ipn();
        $this->product_helper = new WP_FCE_Helper_Product();
    }

    public function get_id(): int
    {
        return (int) $this->user->ID;
    }

    public function get_email(): string
    {
        return $this->user->user_email;
    }

    /**
     * Returns the latest ipn for given external product
     *
     * @param string $external_product_id to get ipn for
     * @return WP_FCE_Model_Ipn|null The latest ipn or null if no ipn was found
     */
    public function get_latest_ipn_for_external_product(string $external_product_id): WP_FCE_Model_Ipn|null
    {
        // get all IPNs by external product ID and users email
        $ipns = $this->ipn_helper::get_ipns_by_product_and_email($external_product_id, $this->get_email());
        if (empty($ipns)) {
            return null;
        }
        return $ipns[0];
    }

    /**
     * Get all latest ipns for all products the user has an ipn to
     *
     * @return WP_FCE_Model_Ipn[] Array of WP_FCE_Model_Ipn
     */
    public function get_all_latest_ipns(bool $only_subscribed = false): array
    {
        $results = [];

        $combinations = $this->ipn_helper::get_distinct_ipn_email_product_combinations();

        foreach ($combinations as $entry) {
            if ($entry['user_email'] !== $this->get_email()) {
                continue;
            }

            $latest_ipn = $this->get_latest_ipn_for_external_product($entry['external_product_id']);
            if ($latest_ipn !== null) {
                if ($only_subscribed && $latest_ipn->is_expired()) {
                    continue;
                }
                $results[] = $latest_ipn;
            }
        }

        return $results;
    }

    /**
     * Get all product accesses, either via IPN or admin override.
     *
     * @return array<int, array{
     *     source: 'ipn'|'override',
     *     mapping: array,
     *     expires: int
     * }>
     */
    public function get_all_active_product_access(): array
    {
        $results = [];

        // 1. Admin-Overrides auslesen
        global $wpdb;
        $table = $wpdb->prefix . 'fce_product_access_overrides';
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d AND granted_until > %s",
            $this->get_id(),
            current_time('mysql')
        ), ARRAY_A);

        foreach ($rows as $row) {
            $mapping = (new WP_FCE_Helper_Product())->get_product_mapping_by_external_product_id($row['external_product_id']);
            if (!$mapping) {
                continue;
            }

            $results[] = [
                'source'  => 'override',
                'ipn'     => null,
                'mapping' => $mapping,
                'expires' => strtotime($row['granted_until']),
            ];
        }

        // 2. IPNs berücksichtigen
        $ipns = $this->get_all_latest_ipns(True);
        foreach ($ipns as $ipn) {

            $mapping = $ipn->get_product_mapping();
            if (!$mapping) {
                continue;
            }
            $mapping['external_product_id'] = $ipn->get_external_product_id();
            $results[] = [
                'source'  => 'ipn',
                'ipn'     => $ipn,
                'mapping' => $mapping,
                'expires' => $ipn->get_paid_until_timestamp(),
            ];
        }

        return $results;
    }
}
