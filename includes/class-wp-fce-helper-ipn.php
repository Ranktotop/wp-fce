<?php

/**
 * Central helper class for Ipns
 *
 */
class WP_FCE_Helper_Ipn
{

    /**
     * WP_FCE_Helper constructor.
     *
     */
    public static function save_ipn(
        string $email,
        string $transaction_id,
        int $ipn_date,
        string $external_product_id,
        string $source,
        array $ipn
    ): ?WP_FCE_Model_Ipn {
        global $wpdb;

        $table = $wpdb->prefix . 'fce_ipn_log';
        $ipn_date_formatted = date('Y-m-d H:i:s', $ipn_date);
        $ipn_json = wp_json_encode($ipn, JSON_UNESCAPED_UNICODE);

        $result = $wpdb->insert($table, [
            'user_email'          => $email,
            'transaction_id'      => $transaction_id,
            'ipn_date'            => $ipn_date_formatted,
            'external_product_id' => $external_product_id,
            'source'              => $source,
            'ipn'                 => $ipn_json
        ]);

        if ($result === false) {
            return null;
        }

        return new WP_FCE_Model_Ipn(
            (int) $wpdb->insert_id,
            $email,
            $transaction_id,
            $ipn_date_formatted,
            $external_product_id,
            $source,
            $ipn_json
        );
    }

    /**
     * Fetch all IPN entries for a given external product ID and email, ordered by transaction date.
     *
     * @param string $external_product_id
     * @param string $user_email
     * @return WP_FCE_Model_Ipn[] Array of IPN model instances.
     */
    public static function get_ipns_by_product_and_email(string $external_product_id, string $user_email): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fce_ipn_log';

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table}
             WHERE external_product_id = %s AND user_email = %s
             ORDER BY ipn_date DESC",
                $external_product_id,
                $user_email
            ),
            ARRAY_A
        );

        $results = [];

        foreach ($rows as $row) {
            $results[] = new WP_FCE_Model_Ipn(
                (int) $row['id'],
                $row['user_email'],
                $row['transaction_id'],
                $row['ipn_date'],
                $row['external_product_id'],
                $row['source'],
                $row['ipn']
            );
        }

        return $results;
    }

    public static function get_distinct_ipn_email_product_combinations(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_ipn_log';

        return $wpdb->get_results(
            "SELECT DISTINCT user_email, external_product_id FROM {$table}",
            ARRAY_A
        );
    }
}
