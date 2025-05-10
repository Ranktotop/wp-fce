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
            $this->handle_product_form_submission();
        }

        // weitere: elseif ($_POST['wp_fce_form_action'] === '...') ...
    }

    /**
     * Verarbeitet ein Formular-Submission für ein neues Produkt,
     * prüft Nonce und Pflichtfelder und speichert dann das Produkt.
     */
    private function handle_product_form_submission(): void
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
}
