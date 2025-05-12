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

        $product_id  = sanitize_text_field($_POST['fce_new_product_id'] ?? '');
        $title       = sanitize_text_field($_POST['fce_new_product_title'] ?? '');
        $description = sanitize_textarea_field($_POST['fce_new_product_description'] ?? '');

        if (!$product_id || !$title) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>' . esc_html__('Produkt-ID und Titel sind Pflichtfelder.', 'wp-fce') . '</p></div>';
            });
            return;
        }

        try {
            $helper = new WP_FCE_Helper_Product();
            $helper->create_product($product_id, $title, $description);

            wp_safe_redirect(add_query_arg('fce_success', urlencode(__('Produkt wurde angelegt.', 'wp-fce')), $_SERVER['REQUEST_URI']));
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

    private function handle_create_product_mapping(): void
    {
        if (
            ! isset($_POST['wp_fce_nonce']) ||
            ! wp_verify_nonce($_POST['wp_fce_nonce'], 'wp_fce_map_product')
        ) {
            return;
        }

        $product_id = sanitize_text_field($_POST['fce_product_id'] ?? '');
        $space_ids  = array_map('intval', $_POST['fce_spaces'] ?? []);
        $course_ids = array_map('intval', $_POST['fce_courses'] ?? []);

        if ($product_id === '') {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>' . esc_html__('Bitte wähle ein Produkt.', 'wp-fce') . '</p></div>';
            });
            return;
        }

        try {
            $product = WP_FCE_Model_Product::load_by_id($product_id);
            $merged_ids = array_merge($space_ids, $course_ids);
            $product->set_spaces($merged_ids);

            wp_safe_redirect(add_query_arg('fce_success', urlencode(__('Produktzuweisung gespeichert.', 'wp-fce')), $_SERVER['REQUEST_URI']));
            exit;
        } catch (\Exception $e) {
            add_action('admin_notices', function () use ($e) {
                echo '<div class="notice notice-error"><p>' . esc_html($e->getMessage()) . '</p></div>';
            });
        }
    }

    /**
     * Handles the update of a product mapping.
     *
     * This function is called when the form submission contains the
     * 'wp_fce_form_action' parameter with the value 'update_product_mapping'.
     * It verifies the nonce and checks if a product has been selected.
     * If so, it merges space and course IDs and assigns them to the product.
     * Successful operations redirect the user with a success message, 
     * while errors are displayed as admin notices.
     *
     * @since 1.0.0
     */
    private function handle_update_product_mapping(): void
    {
        if (
            !isset($_POST['wp_fce_nonce']) ||
            !wp_verify_nonce($_POST['wp_fce_nonce'], 'wp_fce_update_product_mapping')
        ) {
            return;
        }

        $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        $new_space_ids = isset($_POST['fce_edit_entities']) ? array_map('intval', $_POST['fce_edit_entities']) : [];

        if (!$product_id) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>' . esc_html__('Ungültige Produkt-ID.', 'wp-fce') . '</p></div>';
            });
            return;
        }

        try {
            $product = WP_FCE_Model_Product::load_by_id($product_id);
            $product->set_spaces($new_space_ids);

            wp_safe_redirect(add_query_arg('fce_success', urlencode(__('Zuweisung gespeichert.', 'wp-fce')), $_SERVER['REQUEST_URI']));
            exit;
        } catch (\Exception $e) {
            add_action('admin_notices', function () use ($e) {
                echo '<div class="notice notice-error"><p>' . esc_html($e->getMessage()) . '</p></div>';
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
