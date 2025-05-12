<?php

class WP_FCE_Access_Manager
{
    private WP_FCE_Helper_Access_Override $override_helper;
    private WP_FCE_Helper_Ipn $ipn_helper;
    private WP_FCE_Helper_Product $product_helper;
    private WP_FCE_Helper_User $user_helper;

    public function __construct()
    {
        $this->override_helper = new WP_FCE_Helper_Access_Override();
        $this->ipn_helper      = new WP_FCE_Helper_Ipn();
        $this->product_helper  = new WP_FCE_Helper_Product();
        $this->user_helper     = new WP_FCE_Helper_User();
    }

    //******************************** */
    //************ GETTER ************ */
    //******************************** */

    /**
     * Gibt das Ablaufdatum aus der fce_product_user-Tabelle zurück (wenn vorhanden).
     *
     * @param int $user_id
     * @param int $product_id
     * @return int|null UNIX-Timestamp oder null wenn nicht vorhanden
     */
    private function get_expiry_date_for_user_product(int $user_id, int $product_id): ?int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_product_user';

        $expires_on = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT expires_on FROM {$table}
             WHERE user_id = %d
               AND fce_product_id = %d
             LIMIT 1",
                $user_id,
                $product_id
            )
        );

        return $expires_on ? strtotime($expires_on) : null;
    }

    /**
     * Liefert den Zeitstempel, bis wann ein User Zugriff auf ein Produkt hat.
     * Berücksichtigt:
     * - aktiven Admin-Override (grant/deny)
     * - IPNs (wenn vorhanden)
     * - DB-Eintrag fce_product_user (nur als Fallback)
     *
     * @param int $user_id
     * @param string $product_id
     * @return int|null UNIX-Timestamp oder null wenn kein Zugriff
     */
    public function get_access_until(int $user_id, string $product_id): ?int
    {
        // 1. Admin-Override prüfen
        $override = $this->override_helper->get_active_override($user_id, $product_id);
        if ($override) {
            return $override->get_mode() === 'deny'
                ? null
                : $override->get_valid_until();
        }

        // 2. Neuester gültiger IPN für diesen User und dieses Produkt
        $email = $this->user_helper->get_by_id($user_id)->get_email();
        $product = $this->product_helper->get_by_id($product_id);
        $latest_ipn = $this->ipn_helper->get_latest_ipn_for_user_product($email, $product->get_product_id());

        if ($latest_ipn) {
            return !$latest_ipn->is_expired()
                ? $latest_ipn->get_paid_until_timestamp()
                : null;
        }

        // 3. Fallback: Prüfung aus fce_product_user
        try {
            $product = $this->product_helper->get_by_id($product_id);
            return $this->get_expiry_date_for_user_product($user_id, $product->get_id());
        } catch (\Exception $e) {
            // Falls Produkt nicht existiert
            fce_log(sprintf('Zugriffsprüfung: Produkt mit id "%s" nicht gefunden.', $product_id), 'warning');
            return null;
        }
    }

    /**
     * Aktualisiert den Zugriff auf ein Produkt für einen User.
     *
     * Berücksichtigt:
     * - aktiven Admin-Override (grant/deny)
     * - IPNs (wenn vorhanden)
     * - DB-Eintrag fce_product_user (nur als Fallback)
     *
     * Wenn access_until nicht angegeben wird, wird das berechnete Datum genommen.
     * Wenn das berechnete Datum in der Zukunft liegt, wird der Zugriff auf das
     * Produkt gewährt, andernfalls wird er entzogen.
     *
     * @param int $user_id
     * @param string $product_id
     * @param int|null $access_until optional, wenn nicht angegeben wird berechnete
     *                               Zugriffsdatum genommen
     * @param string $source Optionaler Quelltext (z.B. 'admin', 'ipn')
     * @return bool Erfolg
     */
    public function update_access(int $user_id, string $product_id, ?int $access_until, string $source = "admin"): bool
    {
        //update access
        $calculated_access_until = $this->get_access_until(
            $user_id,
            $product_id
        );
        // if access_until is not given use calculated one
        $access_until = $access_until ?? $calculated_access_until;

        if ($calculated_access_until && $calculated_access_until > time()) {
            return $this->product_helper->grant_access($user_id, $product_id, $access_until, $source);
        } else {
            return $this->product_helper->revoke_access($user_id, $product_id);
        }
    }
}
