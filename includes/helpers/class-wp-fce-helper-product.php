<?php

class WP_FCE_Helper_Product extends WP_FCE_Helper_Base
{

    //******************************** */
    //************ GETTER ************ */
    //******************************** */

    /**
     * Retrieve multiple product entities based on their IDs.
     *
     * @param int[] $ids List of product IDs.
     * @return WP_FCE_Model_Product[] Array of WP_FCE_Model_Product objects.
     */

    public function get_by_ids(array $ids): array
    {
        global $wpdb;

        if (empty($ids)) {
            return [];
        }

        $table = $wpdb->prefix . 'fce_products';
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, product_id, title, description 
             FROM {$table} 
             WHERE id IN ($placeholders)",
                ...$ids
            ),
            ARRAY_A
        );

        if (empty($rows) || !is_array($rows)) {
            return [];
        }

        return array_map(fn($row) => new WP_FCE_Model_Product(
            (int) $row['id'],
            $row['product_id'],
            $row['title'],
            $row['description']
        ), $rows);
    }


    /**
     * Get a product by its ID.
     *
     * @param int $id The ID of the product to retrieve.
     *
     * @return WP_FCE_Model_Product The product with the given ID, or throw an exception if no product was found.
     *
     * @throws \Exception If the product was not found.
     */
    public function get_by_id(int $id): WP_FCE_Model_Product
    {
        return WP_FCE_Model_Product::load_by_id($id);
    }

    /**
     * Lade ein Produkt anhand der externen Produkt-ID.
     *
     * @param string $external_id Die externe Produkt-ID (z. B. von Digistore24/CopeCart).
     * @return WP_FCE_Model_Product
     * @throws \Exception Wenn kein Produkt gefunden wurde.
     */
    public function get_by_external_product_id(string $external_id): WP_FCE_Model_Product
    {
        global $wpdb;
        $table_products = $wpdb->prefix . 'fce_products';

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_products} WHERE product_id = %s",
                $external_id
            ),
            ARRAY_A
        );

        if (!$row) {
            throw new \Exception(__('Produkt mit dieser externen ID wurde nicht gefunden.', 'wp-fce'));
        }

        return new WP_FCE_Model_Product(
            (int) $row['id'],
            $row['product_id'],
            $row['title'],
            $row['description']
        );
    }


    /**
     * Get all products (WP_FCE_Model_Product objects)
     *
     * @return WP_FCE_Model_Product[] Array of WP_FCE_Model_Product objects
     */
    public function get_all(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_products';

        $results = $wpdb->get_results("SELECT id, product_id, title, description FROM {$table} ORDER BY created_at DESC", ARRAY_A);

        if (empty($results)) {
            return [];
        }

        $products = [];

        foreach ($results as $row) {
            $products[] = new WP_FCE_Model_Product(
                (int) $row['id'],
                (string) $row['product_id'],
                (string) $row['title'],
                (string) $row['description']
            );
        }

        return $products;
    }

    /**
     * Get all products without any space or course mapping.
     *
     * @return WP_FCE_Model_Product[] Array of products without any mapping.
     */
    public function get_unmapped_products(): array
    {
        global $wpdb;

        $table_products = $wpdb->prefix . 'fce_products';
        $table_mapping  = $wpdb->prefix . 'fce_product_space';

        $rows = $wpdb->get_results("
		SELECT p.id, p.product_id, p.title, p.description
		FROM {$table_products} p
		LEFT JOIN {$table_mapping} m ON m.fce_product_id = p.id
		WHERE m.fce_product_id IS NULL
		ORDER BY p.title ASC
	", ARRAY_A);

        return array_map(fn($row) => new WP_FCE_Model_Product(
            (int) $row['id'],
            $row['product_id'],
            $row['title'],
            $row['description']
        ), $rows);
    }

    /**
     * Get all products that have any space or course mapping.
     *
     * Array format:
     * [
     *     'product' => WP_FCE_Model_Product,
     *     'spaces'  => WP_FCE_Model_Fluent_Community_Entity[]
     * ]
     *
     * @return array Array of products with their mapped spaces.
     */
    public function get_all_mapped_products_with_entities(): array
    {
        global $wpdb;

        $table_products = $wpdb->prefix . 'fce_products';
        $table_mapping  = $wpdb->prefix . 'fce_product_space';
        $table_fcom     = $wpdb->prefix . 'fcom_spaces';

        $rows = $wpdb->get_results("
		SELECT
			p.id AS product_id,
			p.product_id AS external_id,
			p.title AS product_title,
			p.description AS product_description,
			s.id AS space_id,
			s.title AS space_title,
			s.slug AS space_slug,
			s.type AS space_type
		FROM {$table_products} p
		INNER JOIN {$table_mapping} m ON m.fce_product_id = p.id
		INNER JOIN {$table_fcom} s ON s.id = m.space_id
		ORDER BY p.title ASC, s.title ASC
	", ARRAY_A);

        $result = [];

        foreach ($rows as $row) {
            $key = $row['product_id'];

            if (!isset($result[$key])) {
                $result[$key] = [
                    'product' => new WP_FCE_Model_Product(
                        (int) $row['product_id'],
                        $row['external_id'],
                        $row['product_title'],
                        $row['product_description']
                    ),
                    'spaces' => [],
                ];
            }

            $result[$key]['spaces'][] = new WP_FCE_Model_Fluent_Community_Entity(
                (int) $row['space_id'],
                $row['space_title'],
                $row['space_slug'],
                $row['space_type']
            );
        }

        return array_values($result);
    }

    //******************************** */
    //************* CRUD ************* */
    //******************************** */

    /**
     * Löscht ein Produkt vollständig inkl. aller Relationen und entzieht Nutzern ggf. den Zugriff.
     *
     * @param int $product_id
     * @return bool
     * @throws \Exception
     */
    public function delete_product_by_id(int $product_id): bool
    {
        global $wpdb;

        $table_products         = $wpdb->prefix . 'fce_products';
        $table_mappings_product = $wpdb->prefix . 'fce_product_space';
        $table_mappings_user    = $wpdb->prefix . 'fce_product_user';
        $table_mappings_access_overrides    = $wpdb->prefix . 'fce_product_access_overrides';

        // 0. Produkt-Objekt + Nutzer laden
        $product        = $this->get_by_id($product_id);
        $product_users  = $product->get_users(); // → WP_FCE_Model_User[]

        // 1. Allen Nutzern den Zugriff korrekt entziehen
        foreach ($product_users as $user) {
            $this->revoke_access($user->get_id(), $product->get_id());
        }

        // 2. Mappings löschen
        $wpdb->delete($table_mappings_product, [
            'fce_product_id' => $product_id,
        ], ['%d']);

        $wpdb->delete($table_mappings_user, [
            'fce_product_id' => $product_id,
        ], ['%d']);

        $wpdb->delete($table_mappings_access_overrides, [
            'fce_product_id' => $product_id,
        ], ['%d']);

        // 3. Produkt selbst löschen
        $deleted = $wpdb->delete($table_products, ['id' => $product_id], ['%d']);

        if ($deleted === false || $deleted === 0) {
            throw new \Exception(__('Fehler beim Löschen des Produkts', 'wp-fce'));
        }

        return true;
    }

    /**
     * Updates a product by its ID.
     *
     * @param int    $product_id    The ID of the product to update.
     * @param string $title         The new title of the product.
     * @param string $description   The new description of the product.
     *
     * @return bool True if the product was updated, false otherwise.
     *
     * @throws \Exception If the title is empty, or if a database error occurs.
     */
    public function update_product_by_id(int $product_id, string $title, string $description): bool
    {
        if (trim($title) === '') {
            throw new \Exception(__('Titel darf nicht leer sein', 'wp-fce'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'fce_products';

        $updated = $wpdb->update(
            $table,
            [
                'title'       => $title,
                'description' => $description,
                'updated_at'  => current_time('mysql')
            ],
            ['id' => $product_id],
            ['%s', '%s', '%s'],
            ['%d']
        );

        if ($updated === false) {
            throw new \Exception(__('Datenbankfehler beim Aktualisieren', 'wp-fce'));
        }

        return true;
    }


    /**
     * Creates a new product or updates an existing one.
     *
     * Checks if a product with the given external ID ($product_id) already exists.
     * If it does, it updates the existing product with the new title and description.
     * If it doesn't, it creates a new product with the given data.
     *
     * After the product is created/updated, it loads the product model and applies all
     * non-expired IPNs to the product.
     *
     * @param string $product_id    The external ID of the product.
     * @param string $title         The title of the product.
     * @param string $description   The description of the product.
     *
     * @return bool True if the product was created/updated, false otherwise.
     *
     * @throws \Exception If the product could not be created/updated.
     */
    public function create_product(string $product_id, string $title, string $description): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_products';

        // 1) Existierendes Produkt prüfen
        $existing_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE product_id = %s",
                $product_id
            )
        );

        if ($existing_id) {
            // Update bestehendes Produkt
            $wpdb->update(
                $table,
                [
                    'title'       => $title,
                    'description' => $description,
                    'updated_at'  => current_time('mysql'),
                ],
                ['id' => $existing_id],
                ['%s', '%s', '%s'],
                ['%d']
            );
            $product_id_db = $existing_id;
        } else {
            // Neues Produkt einfügen
            $inserted = $wpdb->insert($table, [
                'product_id'  => $product_id,
                'title'       => $title,
                'description' => $description,
                'created_at'  => current_time('mysql'),
            ]);

            if (! $inserted) {
                throw new \Exception(__('Produkt konnte nicht gespeichert werden.', 'wp-fce'));
            }

            $product_id_db = (int) $wpdb->insert_id;
        }

        // 2) Modell laden
        $product = WP_FCE_Model_Product::load_by_id($product_id_db);

        // 3) IPNs holen und anwenden
        $ipns = $this->ipn()->get_latest_ipns_by_user_and_product($product->get_id());
        foreach ($ipns as $ipn) {
            if (! $ipn->is_expired()) {
                try {
                    $this->ipn()->apply($ipn);
                } catch (\Exception $e) {
                    fce_log(sprintf('Fehler beim Anwenden von IPN #%d: %s', $ipn->get_id(), $e->getMessage()), 'error');
                }
            }
        }

        return true;
    }

    /**
     * Grants access to a product for a specific user and enrolls them in associated spaces/courses.
     *
     * This function creates a database entry linking the user to the specified product
     * and calls the FluentCommunity API to grant access to any associated spaces or courses.
     *
     * @param int $user_id The WordPress user ID.
     * @param int $product_id The internal product ID (not external).
     * @param int $expires_on The expiration timestamp for the access.
     * @param string $source The source of the access grant (e.g., 'admin', 'ipn').
     *
     * @return bool True if access was granted successfully, false otherwise.
     */

    public function grant_access(int $user_id, int $product_id, int $expires_on, string $source): bool
    {
        // 1) DB-Eintrag
        $product = $this->get_by_id($product_id);
        if (! $product->add_user($user_id, $expires_on, $source)) {
            return false;
        }

        // 2) FluentCommunity-API aufrufen
        $spaces = $product->get_spaces(); // WP_FCE_Model_Fluent_Community_Entity[]
        foreach ($spaces as $space) {
            $space->grant_access($user_id);
        }

        return true;
    }

    /**
     * Revoke access to a product for a user and unenroll from associated spaces/courses in FluentCommunity.
     *
     * This function removes the link between the user and the specified product, and subsequently
     * checks all other products the user has access to. If there is no other product that grants
     * access to the same spaces, the user's access to those spaces is revoked.
     *
     * @param int $user_id The WordPress user ID.
     * @param int $product_id The internal product ID.
     * 
     * @return bool True if the product-user link was removed and spaces were correctly managed, false otherwise.
     */

    public function revoke_access(int $user_id, int $product_id): bool
    {
        // 1) Produkt und Spaces laden
        $product         = $this->get_by_id($product_id);
        $product_spaces  = $product->get_spaces();

        // 2) Produkt-User-Verknüpfung entfernen
        if (! $product->remove_user($user_id)) {
            return false;
        }

        // 3) Alle restlichen Produkte des Nutzers laden
        $user = $this->user()->get_by_id($user_id);
        $user_products = array_filter(
            $user->get_products(),
            fn(WP_FCE_Model_Product $p) => $p->get_id() !== $product_id
        );

        // 4) Set aus allen Space-IDs, auf die der Nutzer noch Zugriff hat
        $remaining_space_ids = [];
        foreach ($user_products as $other_product) {
            foreach ($other_product->get_spaces() as $space) {
                $remaining_space_ids[$space->get_id()] = true;
            }
        }

        // 5) Zugriff nur entziehen, wenn Space nicht mehr von anderem Produkt abgedeckt ist
        foreach ($product_spaces as $space) {
            if (! isset($remaining_space_ids[$space->get_id()])) {
                $space->revoke_access($user_id);
            }
        }

        return true;
    }

    /**
     * Entzieht allen Nutzern den Zugang zu abgelaufenen Produkten.
     *
     * @return void
     */
    public function revoke_expired_accesses(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_product_user';

        $rows = $wpdb->get_results("
        SELECT user_id, fce_product_id
        FROM {$table}
    ");

        $access_manager = new WP_FCE_Access_Manager();

        foreach ($rows as $row) {
            $user_id    = (int) $row->user_id;
            $product_id = (int) $row->fce_product_id;

            try {
                $product = $this->get_by_id($product_id);
                $external_id = $product->get_product_id();

                $access_until = $access_manager->get_access_until($user_id, $external_id);

                if ($access_until === null || $access_until < time()) {
                    $this->revoke_access($user_id, $product_id);
                    fce_log("Zugriff via Cron entzogen: user {$user_id} / product {$product_id}", 'info');
                }
            } catch (\Exception $e) {
                fce_log("Fehler beim Entzug im Cron für user {$user_id} / product {$product_id}: " . $e->getMessage(), 'error');
            }
        }
    }
}
