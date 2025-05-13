<?php
class Wp_Fce_Admin_Form_Handler
{

    /**
     * Handles form submissions in the admin area.
     *
     * This function is attached to the 'admin_init' action hook and is called
     * on every admin page load. It is responsible for handling form submissions
     * and redirecting the user to the appropriate page after submission.
     *
     * It currently handles the creation of new products, but could be extended
     * to handle other types of form submissions in the future.
     *
     * @since 1.0.0
     */
    public function handle_admin_form_callback(): void
    {
        if (!is_admin() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        // Zentrale Routing-Logik
        if (isset($_POST['wp_fce_form_action']) && $_POST['wp_fce_form_action'] === 'create_product') {
            $this->handle_create_product();
        }
        if (isset($_POST['wp_fce_form_action']) && $_POST['wp_fce_form_action'] === 'create_product_mapping') {
            $this->handle_create_product_mapping();
        }
        if (isset($_POST['wp_fce_form_action']) && $_POST['wp_fce_form_action'] === 'update_product_mapping') {
            $this->handle_update_product_mapping();
        }
        if (isset($_POST['wp_fce_form_action']) && $_POST['wp_fce_form_action'] === 'create_override') {
            $this->handle_override_access();
        }

        // weitere: elseif ($_POST['wp_fce_form_action'] === '...') ...
    }


    /**
     * Handles the creation of new products.
     *
     * This function is called when the form submission contains the
     * 'wp_fce_form_action' parameter with the value 'create_product'.
     *
     * It checks the nonce and verifies that the product ID and title are
     * not empty. If the checks pass, it creates a new product using the
     * WP_FCE_Helper_Product class and redirects the user to the same page
     * with a success message. If the checks fail, it adds an error message
     * to the admin notices.
     *
     * @since 1.0.0
     */
    private function handle_create_product(): void
    {
        if (
            !isset($_POST['wp_fce_nonce']) ||
            !wp_verify_nonce($_POST['wp_fce_nonce'], 'wp_fce_create_product')
        ) {
            return;
        }

        $sku  = sanitize_text_field($_POST['fce_new_product_sku'] ?? '');
        $name = sanitize_text_field($_POST['fce_new_product_name'] ?? '');
        $desc = sanitize_textarea_field($_POST['fce_new_product_description'] ?? '');

        if (!$sku || !$name) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>' . esc_html__('Product ID and title are required.', 'wp-fce') . '</p></div>';
            });
            return;
        }

        try {
            $helper = new WP_FCE_Helper_Product();
            $helper->create($sku, $name, $desc);

            wp_safe_redirect(add_query_arg('fce_success', urlencode(__('Product created successfully.', 'wp-fce')), $_SERVER['REQUEST_URI']));
            exit;
        } catch (\Exception $e) {
            add_action('admin_notices', function () use ($e) {
                echo '<div class="notice notice-error"><p>' . esc_html($e->getMessage()) . '</p></div>';
            });
        }
    }

    /**
     * Handles the creation of product mappings.
     *
     * This function is called when the form submission contains the
     * 'wp_fce_form_action' parameter with the value 'create_product_mapping'.
     * It verifies the nonce and checks if a product has been selected.
     * If so, it merges space and course IDs and assigns them to the product.
     * Successful operations redirect the user with a success message, 
     * while errors are displayed as admin notices.
     *
     * @since 1.0.0
     */

    /**
     * Handle the product→space mapping form submission.
     *
     * @return void
     */
    private function handle_create_product_mapping(): void
    {
        // 1) Nonce prüfen
        if (
            ! isset($_POST['wp_fce_nonce']) ||
            ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wp_fce_nonce'])), 'wp_fce_map_product')
        ) {
            return;
        }

        // 2) Eingaben säubern
        $product_id = isset($_POST['fce_product_id']) ? intval($_POST['fce_product_id']) : 0;
        $space_ids  = isset($_POST['fce_spaces']) ? array_map('intval', (array) $_POST['fce_spaces']) : [];

        // 3) Pflicht prüfen
        if ($product_id <= 0) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>'
                    . esc_html__('Bitte wähle ein Produkt.', 'wp-fce')
                    . '</p></div>';
            });
            return;
        }

        try {
            // 4) Alte Mappings löschen
            WP_FCE_Helper_Product_Space::remove_mappings_for_product($product_id);

            // 5) Neue Mappings anlegen und retroaktiv Zugänge vergeben
            foreach ($space_ids as $space_id) {
                WP_FCE_Helper_Product_Space::create_mapping_and_assign($product_id, $space_id);
            }

            // 6) Erfolg – Redirect mit Hinweis
            $redirect_url = add_query_arg(
                'fce_success',
                urlencode(__('Produktzuweisung gespeichert.', 'wp-fce')),
                $_SERVER['REQUEST_URI']
            );
            wp_safe_redirect($redirect_url);
            exit;
        } catch (\Exception $e) {
            // 7) Fehler anzeigen
            add_action('admin_notices', function () use ($e) {
                echo '<div class="notice notice-error"><p>'
                    . esc_html($e->getMessage())
                    . '</p></div>';
            });
        }
    }

    /**
     * Handle the product→space mapping update form submission.
     *
     * @return void
     */
    private function handle_update_product_mapping(): void
    {
        // 1) Nonce prüfen
        if (
            ! isset($_POST['wp_fce_nonce']) ||
            ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wp_fce_nonce'])), 'wp_fce_update_product_mapping')
        ) {
            return;
        }

        // 2) Eingaben säubern
        $product_id     = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $new_space_ids  = isset($_POST['fce_edit_entities'])
            ? array_map('intval', (array) $_POST['fce_edit_entities'])
            : [];

        // 3) Pflicht prüfen
        if ($product_id <= 0) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>'
                    . esc_html__('Invalid product ID.', 'wp-fce')
                    . '</p></div>';
            });
            return;
        }

        try {
            // 4) Alle alten Mappings entfernen
            WP_FCE_Helper_Product_Space::remove_mappings_for_product($product_id);

            // 5) Neue Mappings anlegen und retroaktiv Zugänge vergeben
            foreach ($new_space_ids as $space_id) {
                WP_FCE_Helper_Product_Space::create_mapping_and_assign($product_id, $space_id);
            }

            // 6) Erfolg – Redirect mit Hinweis
            $redirect_url = add_query_arg(
                'fce_success',
                urlencode(__('Successfully updated product mappings.', 'wp-fce')),
                $_SERVER['REQUEST_URI']
            );
            wp_safe_redirect($redirect_url);
            exit;
        } catch (\Exception $e) {
            // 7) Fehler anzeigen
            add_action('admin_notices', function () use ($e) {
                echo '<div class="notice notice-error"><p>'
                    . esc_html($e->getMessage())
                    . '</p></div>';
            });
        }
    }

    /**
     * Handles creation or update of admin-defined access overrides.
     *
     * @since 1.0.0
     */
    private function handle_override_access(): void
    {
        if (
            !isset($_POST['wp_fce_nonce']) ||
            !wp_verify_nonce($_POST['wp_fce_nonce'], 'wp_fce_create_override')
        ) {
            return;
        }

        $user_id     = (int) ($_POST['user_id'] ?? 0);
        $product_id  = (int) ($_POST['product_id'] ?? 0);
        $mode        = sanitize_text_field($_POST['mode'] ?? '');
        $valid_until = sanitize_text_field($_POST['valid_until'] ?? '');

        if (
            !$user_id ||
            !$product_id ||
            !in_array($mode, ['grant', 'deny'], true) ||
            empty($valid_until)
        ) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>' . esc_html__('Ungültige Eingaben für Override.', 'wp-fce') . '</p></div>';
            });
            return;
        }

        // Konvertiere ins externe Produkt-ID-Format
        $product = WP_FCE_Model_Product::load_by_id($product_id);
        $timestamp = strtotime($valid_until);

        try {
            $helper = new WP_FCE_Helper_Access_Override();

            $existing = $helper->get_active_override($user_id, $product->get_id());

            if ($existing) {
                $helper->update_override_by_id($existing->get_id(), $mode, $timestamp);
            } else {
                $helper->create_override($user_id, $product->get_id(), $mode, $timestamp);
            }

            //update access
            $access_manager = new WP_FCE_Access_Manager();
            $access_manager->update_access($user_id, $product->get_id(), null, "admin");

            wp_safe_redirect(add_query_arg('fce_success', urlencode(__('Zugriffsüberschreibung gespeichert.', 'wp-fce')), $_SERVER['REQUEST_URI']));
            exit;
        } catch (\Exception $e) {
            add_action('admin_notices', function () use ($e) {
                echo '<div class="notice notice-error"><p>' . esc_html($e->getMessage()) . '</p></div>';
            });
        }
    }
}
