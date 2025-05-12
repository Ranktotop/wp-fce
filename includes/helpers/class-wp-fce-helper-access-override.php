<?php

class WP_FCE_Helper_Access_Override extends WP_FCE_Helper_Base
{

    //******************************** */
    //*********** CHECKER ************ */
    //******************************** */

    /**
     * Gibt zurück, ob Zugriff für diesen User und Produkt explizit erlaubt wurde.
     */
    public function has_grant(int $user_id, string $product_id): bool
    {
        $override = $this->get_active_override($user_id, $product_id);
        return $override !== null;
    }

    //******************************** */
    //************ GETTER ************ */
    //******************************** */

    /**
     * Lade einen Override anhand seiner ID.
     *
     * @param int $id
     * @return WP_FCE_Model_Access_Override
     * @throws \Exception
     */
    public function get_by_id(int $id): WP_FCE_Model_Access_Override
    {
        return WP_FCE_Model_Access_Override::load_by_id($id);
    }

    /**
     * Lade mehrere Overrides anhand einer Liste von IDs.
     *
     * @param int[] $ids
     * @return WP_FCE_Model_Access_Override[]
     */
    public function get_by_ids(array $ids): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_product_access_overrides';

        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $query = "SELECT * FROM {$table} WHERE id IN ($placeholders)";
        $prepared = $wpdb->prepare($query, $ids);
        $rows = $wpdb->get_results($prepared, ARRAY_A);

        if (empty($rows) || !is_array($rows)) {
            return [];
        }

        return array_map(function ($row) {
            return new WP_FCE_Model_Access_Override(
                (int) $row['id'],
                (int) $row['user_id'],
                (string) $row['fce_product_id'],
                (string) $row['mode'],
                (int) strtotime($row['valid_until'])
            );
        }, $rows);
    }

    /**
     * Liefert alle Access Overrides.
     *
     * @return WP_FCE_Model_Access_Override[]
     */
    public function get_all(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_product_access_overrides';

        $rows = $wpdb->get_results("SELECT * FROM {$table}", ARRAY_A);

        return array_map(function ($row) {
            return new WP_FCE_Model_Access_Override(
                (int) $row['id'],
                (int) $row['user_id'],
                (string) $row['fce_product_id'],
                (string) $row['mode'],
                (int) strtotime($row['valid_until'])
            );
        }, $rows);
    }

    /**
     * Liefert den aktiven Override für einen Nutzer + Produkt (falls vorhanden).
     *
     * @param int $user_id
     * @param string $product_id
     * @return WP_FCE_Model_Access_Override|null
     */
    public function get_active_override(int $user_id, string $product_id): ?WP_FCE_Model_Access_Override
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_product_access_overrides';

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} 
                 WHERE user_id = %d 
                 AND fce_product_id = %s 
                 AND valid_until > %s
                 LIMIT 1",
                $user_id,
                $product_id,
                current_time('mysql')
            ),
            ARRAY_A
        );

        if (!$row) {
            return null;
        }

        return new WP_FCE_Model_Access_Override(
            (int) $row['id'],
            (int) $row['user_id'],
            (string) $row['fce_product_id'],
            (string) $row['mode'],
            (int) strtotime($row['valid_until'])
        );
    }

    //******************************** */
    //************* CRUD ************* */
    //******************************** */

    /**
     * Erstellt einen neuen Override für einen Nutzer und ein Produkt.
     *
     * @param int $user_id
     * @param string $product_id
     * @param int $valid_until Timestamp
     * @param string $reason Optionaler Grund
     * @return bool Erfolg
     */
    public function create_override(int $user_id, string $product_id, string $mode, int $valid_until): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_product_access_overrides';

        return false !== $wpdb->insert($table, [
            'user_id'             => $user_id,
            'fce_product_id' => $product_id,
            'mode'              => $mode,
            'valid_until'       => date('Y-m-d H:i:s', $valid_until),
            'created_at'          => current_time('mysql'),
        ], ['%d', '%s', '%s', '%s', '%s']);
    }

    /**
     * Aktualisiert einen bestehenden Override.
     *
     * @param int $id
     * @param int|null $valid_until Optional neuer Gültigkeitszeitpunkt
     * @param string|null $reason Optional neuer Grund
     * @return bool Erfolg
     */
    public function update_override_by_id(int $id, ?string $mode = null, ?int $valid_until = null): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_product_access_overrides';

        $data = ['updated_at' => current_time('mysql')];
        $format = ['%s'];

        if ($valid_until !== null) {
            $data['valid_until'] = date('Y-m-d H:i:s', $valid_until);
            $format[] = '%s';
        }

        if ($mode !== null) {
            $data['mode'] = $mode;
            $format[] = '%s';
        }

        return false !== $wpdb->update(
            $table,
            $data,
            ['id' => $id],
            $format,
            ['%d']
        );
    }
    /**
     * Entfernt einen Override anhand seiner ID.
     *
     * @param int $id
     * @return bool Erfolg
     */
    public function delete_override_by_id(int $id): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_product_access_overrides';

        return false !== $wpdb->delete($table, ['id' => $id], ['%d']);
    }
}
