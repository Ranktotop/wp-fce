<?php

class WP_FCE_Helper_User extends WP_FCE_Helper_Base
{

    //******************************** */
    //************ GETTER ************ */
    //******************************** */

    /**
     * Load multiple user entities based on their IDs.
     *
     * @param int[] $ids List of user IDs.
     * @return WP_FCE_Model_User[] Array of WP_FCE_Model_User objects.
     */
    public function get_by_ids(array $ids): array
    {
        global $wpdb;

        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, user_login, user_email, display_name 
             FROM {$wpdb->users} 
             WHERE ID IN ($placeholders)",
                ...$ids
            ),
            ARRAY_A
        );

        if (empty($rows) || !is_array($rows)) {
            return [];
        }

        return array_map(fn($row) => new WP_FCE_Model_User(
            (int) $row['ID'],
            $row['user_login'],
            $row['user_email'],
            $row['display_name']
        ), $rows);
    }

    /**
     * Load a single user entity by ID.
     *
     * @param int $id User ID.
     * @return WP_FCE_Model_User
     * @throws \Exception If user not found.
     */
    public function get_by_id(int $id): WP_FCE_Model_User
    {
        return WP_FCE_Model_User::load_by_id($id);
    }

    /**
     * Load all users.
     *
     * @return WP_FCE_Model_User[] Array of WP_FCE_Model_User objects.
     */
    public function get_all(): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT ID, user_login, user_email, display_name 
             FROM {$wpdb->users} 
             ORDER BY display_name ASC",
            ARRAY_A
        );

        return array_map(fn($row) => new WP_FCE_Model_User(
            (int) $row['ID'],
            $row['user_login'],
            $row['user_email'],
            $row['display_name']
        ), $rows);
    }

    /**
     * Load a single user entity by email.
     *
     * @param string $email User email.
     * @return WP_FCE_Model_User
     * @throws \Exception If user not found.
     */
    public function get_by_email(string $email): WP_FCE_Model_User
    {
        global $wpdb;

        // Query user by email
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT ID, user_login, user_email, display_name
                 FROM {$wpdb->users}
                 WHERE user_email = %s",
                $email
            ),
            ARRAY_A
        );

        if (! $row) {
            throw new \Exception(__('Benutzer wurde nicht gefunden.', 'wp-fce'));
        }

        // Hydrate model
        return new WP_FCE_Model_User(
            (int)   $row['ID'],
            $row['user_login'],
            $row['user_email'],
            $row['display_name']
        );
    }

    //******************************** */
    //************* CRUD ************* */
    //******************************** */

    /**
     * Create a new WordPress user by email (with optional login and password),
     * and return the corresponding WP_FCE_Model_User.
     *
     * @param string $email    The email address for the new user.
     * @param string $login    Optional. The desired user_login. If empty, derived from the email local-part.
     * @param string $password Optional. The desired password. If empty, a random password is generated.
     * @return WP_FCE_Model_User The newly created user model.
     * @throws \Exception      If user creation fails.
     */
    public function create(string $email, string $login = '', string $password = ''): WP_FCE_Model_User
    {
        // E-Mail validieren
        if (! is_email($email)) {
            throw new \Exception(__('Ungültige E-Mail-Adresse.', 'wp-fce'));
        }

        // Login-Name festlegen
        if (empty($login)) {
            $base  = sanitize_user(strstr($email, '@', true), true);
            $login = $base;
            $i     = 1;
            while (username_exists($login)) {
                $login = $base . $i++;
            }
        } else {
            $login = sanitize_user($login, true);
            if (username_exists($login)) {
                throw new \Exception(__('Der Benutzername existiert bereits.', 'wp-fce'));
            }
        }

        // Passwort festlegen
        if (empty($password)) {
            $password = wp_generate_password(12, false);
        }

        // Benutzer anlegen
        $user_data = [
            'user_login' => $login,
            'user_email' => sanitize_email($email),
            'user_pass'  => $password,
        ];
        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            throw new \Exception(
                sprintf(__('Benutzer konnte nicht angelegt werden: %s', 'wp-fce'), $user_id->get_error_message())
            );
        }

        // WP_User laden, um display_name etc. zu bekommen
        $wp_user = get_user_by('id', $user_id);
        if (! $wp_user) {
            throw new \Exception(__('Benutzer wurde nicht gefunden nach dem Anlegen.', 'wp-fce'));
        }

        // Model zurückgeben
        return new WP_FCE_Model_User(
            (int) $wp_user->ID,
            $wp_user->user_login,
            $wp_user->user_email,
            $wp_user->display_name
        );
    }

    /**
     * Liefert einen WP_FCE_Model_User-Objekt, welches einem Benutzer mit der angegebenen E-Mail entspricht.
     * Wenn der Benutzer nicht existiert, wird ein neuer Benutzer mit passendem Login und Passwort erzeugt.
     * Falls ein Fehler auftritt, wird null zurückgegeben.
     *
     * @param string $email      E-Mail-Adresse des Benutzers.
     * @param string $login      Optionaler Login-Name des Benutzers.
     * @param string $password   Optionaler Passwort des Benutzers.
     *
     * @return WP_FCE_Model_User|null
     */
    public function get_or_create(string $email, string $login = '', string $password = ''): WP_FCE_Model_User|null
    {
        try {
            // Versuche, bestehenden User per E-Mail zu laden
            $user = $this->get_by_email($email);
        } catch (\Exception $e) {
            // Wenn nicht gefunden: neuen User anlegen – und Fehler abfangen
            try {
                $user = $this->create($email, $login, $password);
            } catch (\Exception $e) {
                fce_log(sprintf('No user with email "%s" found and creation failed: %s', $email, $e->getMessage()), 'error');
                return null;
            }
        }

        return $user;
    }
}
