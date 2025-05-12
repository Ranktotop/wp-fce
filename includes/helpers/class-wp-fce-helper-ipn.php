<?php

class WP_FCE_Helper_Ipn extends WP_FCE_Helper_Base
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

        if (empty($rows) || !is_array($rows)) {
            return [];
        }

        return array_map(fn($row) => new WP_FCE_Model_Ipn(
            (int) $row['id'],
            $row['user_email'],
            $row['transaction_id'],
            $row['ipn_date'],
            $row['external_product_id'],
            $row['source'],
            $row['ipn'],
            $row['ipn_hash']
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
        return WP_FCE_Model_Ipn::load_by_id($id);
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
            $row['ipn'],
            $row['ipn_hash']
        ), $rows);
    }

    /**
     * Liefert die neuesten IPNs pro user_email für ein Produkt.
     *
     * @param string $product_id
     * @return WP_FCE_Model_Ipn[]
     */
    public function get_latest_ipns_by_user_and_product(string $product_id): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_ipn_log';
        $product = WP_FCE_Model_Product::load_by_id($product_id);

        // 1) Nur die neuesten IPN-IDs pro user_email
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "
            SELECT MAX(id) as id
            FROM {$table}
            WHERE external_product_id = %s
            GROUP BY user_email
            ",
                $product->get_product_id()
            )
        );

        if (! $rows) {
            return [];
        }

        // 2) Modelle laden
        $models = [];
        foreach ($rows as $row) {
            try {
                $models[] = WP_FCE_Model_Ipn::load_by_id((int) $row->id);
            } catch (\Exception $e) {
                fce_log("Fehler beim Laden von IPN #{$row->id}: " . $e->getMessage(), 'error');
            }
        }

        return $models;
    }

    /**
     * Gibt den neuesten IPN für einen Nutzer + Produkt zurück – unabhängig vom Status.
     *
     * @param string $user_email Die E-Mail des Nutzers
     * @param string $external_product_id Die Produkt-ID aus dem IPN
     * @return WP_FCE_Model_Ipn|null Der neueste IPN oder null, wenn keiner gefunden wurde
     */
    public function get_latest_ipn_for_user_product(string $user_email, string $external_product_id): ?WP_FCE_Model_Ipn
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_ipn_log';

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "
            SELECT id
            FROM {$table}
            WHERE user_email = %s
              AND external_product_id = %s
            ORDER BY ipn_date DESC
            LIMIT 1
            ",
                $user_email,
                $external_product_id
            ),
            ARRAY_A
        );

        if (!$row) {
            return null;
        }

        try {
            return WP_FCE_Model_Ipn::load_by_id((int)$row['id']);
        } catch (\Exception $e) {
            fce_log("Fehler beim Laden von IPN #{$row['id']}: " . $e->getMessage(), 'error');
            return null;
        }
    }


    /**
     * Liefert die neuesten IPNs je Produkt für einen User (per Email).
     *
     * @param string $user_email
     * @return WP_FCE_Model_Ipn[] Array mit jeweils dem neuesten IPN pro Produkt
     */
    public function get_latest_ipns_for_user(string $user_email): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_ipn_log';

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "
            SELECT MAX(id) as id
            FROM {$table}
            WHERE user_email = %s
            GROUP BY external_product_id
            ",
                $user_email
            )
        );

        if (empty($rows)) {
            return [];
        }

        $models = [];
        foreach ($rows as $row) {
            try {
                $models[] = WP_FCE_Model_Ipn::load_by_id((int)$row->id);
            } catch (\Exception $e) {
                fce_log("Fehler beim Laden von IPN #{$row->id}: {$e->getMessage()}", 'error');
            }
        }

        return $models;
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
     * Insert a new IPN entry (or load existing) and return the corresponding IPN-Model.
     *
     * @param string $user_email          The user's email address.
     * @param string $transaction_id      The external transaction ID.
     * @param int    $ipn_date            Timestamp of the IPN event (UNIX time).
     * @param string $external_product_id External product ID from the provider.
     * @param string $source              The IPN source (e.g. 'digistore', 'copecart').
     * @param array  $ipn_raw_json        Associative array representing the full IPN payload.
     *
     * @return WP_FCE_Model_Ipn           Das geladene oder neu erstellte IPN-Modell.
     * @throws \Exception                Wenn das Insert aus anderem Grund fehlschlägt.
     */
    public function create_ipn(
        string $user_email,
        string $transaction_id,
        int    $ipn_date,
        string $external_product_id,
        string $source,
        array  $ipn_raw_json
    ): WP_FCE_Model_Ipn {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_ipn_log';

        // 1) Roh-JSON serialisieren und Hash berechnen
        $ipn_raw  = wp_json_encode($ipn_raw_json);
        $ipn_hash = md5($ipn_raw);

        // 2) Vorhandenen Eintrag anhand des Hash prüfen
        $existing_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE ipn_hash = %s LIMIT 1",
                $ipn_hash
            )
        );
        if ($existing_id) {
            return WP_FCE_Model_Ipn::load_by_id($existing_id);
        }

        // 3) Insert-Daten und -Formate
        $data = [
            'user_email'          => $user_email,
            'transaction_id'      => $transaction_id,
            'ipn_date'            => date('Y-m-d H:i:s', $ipn_date),
            'external_product_id' => $external_product_id,
            'source'              => $source,
            'ipn'                 => $ipn_raw,
            'ipn_hash'            => $ipn_hash,
        ];
        $formats = ['%s', '%s', '%s', '%s', '%s', '%s', '%s'];

        // 4) Einfügen und Fehlerbehandlung
        $result = $wpdb->insert($table, $data, $formats);
        if (false === $result) {
            throw new \Exception(__('Fehler beim Erstellen des IPN-Eintrags.', 'wp-fce'));
        }

        // 5) Neu angelegt: lade und gib das Modell zurück
        $new_id = (int) $wpdb->insert_id;
        return WP_FCE_Model_Ipn::load_by_id($new_id);
    }


    /**
     * Apply this IPN: create or get the user, then grant or revoke product access.
     *
     * @param WP_FCE_Model_Ipn $ipn The IPN-Datenbank-Model.
     * @return bool True on success, false on failure.
     */
    public function apply(WP_FCE_Model_Ipn $ipn): bool
    {
        // 1) User anlegen oder laden
        $user_helper = new WP_FCE_Helper_User();
        $user = $user_helper->get_or_create($ipn->get_user_email());
        if ($user === null) {
            return false;
        }
        $user_id = $user->get_id();

        // 2) Produkt laden
        $product_helper = new WP_FCE_Helper_Product();
        $product = $product_helper->get_by_external_product_id($ipn->get_external_product_id());

        // 3) Create user-product-connection
        $access_manager = new WP_FCE_Access_Manager();
        return $access_manager->update_access($user_id, $product->get_id(), $ipn->get_paid_until_timestamp(), $ipn->get_source());
    }
}
