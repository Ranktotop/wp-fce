<?php

class WP_FCE_Helper_Ipn implements WP_FCE_Helper_Interface
{

    //******************************** */
    //************ GETTER ************ */
    //******************************** */

    /**
     * Retrieve multiple ipn entities based on their IDs.
     *
     * @param int[] $ids List of ipn IDs.
     * @return WP_FCE_Model_Ipn[] Array of WP_FCE_Model_Ipn objects.
     */

    public function get_by_ids(array $ids): array
    {
        global $wpdb;

        if (empty($ids)) {
            return [];
        }

        $table = $wpdb->prefix . 'fce_ipn_log';
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id IN ($placeholders)", ...$ids),
            ARRAY_A
        );

        return array_map(fn($row) => new WP_FCE_Model_Ipn(
            (int) $row['id'],
            $row['user_email'],
            $row['transaction_id'],
            $row['ipn_date'],
            $row['external_product_id'],
            $row['source'],
            $row['ipn']
        ), $rows);
    }

    /**
     * Retrieve a single IPN entry by its ID.
     *
     * @param int $id The ID of the IPN.
     * @return WP_FCE_Model_Ipn The IPN model instance.
     * @throws Exception If not found.
     */
    public function get_by_id(int $id): WP_FCE_Model_Ipn
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_ipn_log';

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$row) {
            throw new \Exception(__('IPN nicht gefunden.', 'wp-fce'));
        }

        return new WP_FCE_Model_Ipn(
            (int) $row['id'],
            $row['user_email'],
            $row['transaction_id'],
            $row['ipn_date'],
            $row['external_product_id'],
            $row['source'],
            $row['ipn']
        );
    }

    /**
     * Retrieve all IPN entries.
     *
     * @return WP_FCE_Model_Ipn[] List of all IPN entries.
     */
    public function get_all(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_ipn_log';

        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY ipn_date DESC", ARRAY_A);

        return array_map(fn($row) => new WP_FCE_Model_Ipn(
            (int) $row['id'],
            $row['user_email'],
            $row['transaction_id'],
            $row['ipn_date'],
            $row['external_product_id'],
            $row['source'],
            $row['ipn']
        ), $rows);
    }

    //******************************** */
    //************* CRUD ************* */
    //******************************** */

    /**
     * Delete an IPN entry by its ID.
     *
     * @param int $id
     * @return bool True on success.
     * @throws \Exception If deletion fails.
     */
    public function delete_ipn_by_id(int $id): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_ipn_log';

        $deleted = $wpdb->delete($table, ['id' => $id], ['%d']);

        if ($deleted === false) {
            throw new \Exception(__('Fehler beim Löschen des IPN-Eintrags.', 'wp-fce'));
        }

        return true;
    }

    /**
     * Create a new IPN entry.
     *
     * @param string $user_email          The user's email address.
     * @param string $transaction_id      The external transaction ID.
     * @param int    $ipn_date            Timestamp of the IPN event (UNIX time).
     * @param string $external_product_id External product ID from the provider.
     * @param string $source              The IPN source (e.g. 'digistore', 'copecart').
     * @param array  $ipn_raw_json        Associative array representing the full IPN payload.
     *
     * @return int The newly inserted IPN entry ID.
     * @throws \Exception If the insert fails.
     */
    public function create_ipn(
        string $user_email,
        string $transaction_id,
        int $ipn_date,
        string $external_product_id,
        string $source,
        array $ipn_raw_json
    ): int {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_ipn_log';

        $inserted = $wpdb->insert(
            $table,
            [
                'user_email'          => $user_email,
                'transaction_id'      => $transaction_id,
                'ipn_date'            => date('Y-m-d H:i:s', $ipn_date),
                'external_product_id' => $external_product_id,
                'source'              => $source,
                'ipn'                 => wp_json_encode($ipn_raw_json),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($inserted === false) {
            throw new \Exception(__('Fehler beim Erstellen des IPN-Eintrags.', 'wp-fce'));
        }

        return true;
    }
}
