<?php
class Wp_Fce_Admin_Ajax_Handler
{
    /**
     * Ajax generic Callback-Function
     */
    public function handle_admin_ajax_callback()
    {
        /**
         * Do not forget to check your nonce for security!
         *
         * @link https://codex.wordpress.org/Function_Reference/wp_verify_nonce
         */
        if (! wp_verify_nonce($_POST['_nonce'], 'security_wp-fce')) {
            wp_send_json_error();
            die();
        }

        // Check if given function exists
        $functionName = $_POST['func'];
        if (! method_exists($this, $functionName)) {
            wp_send_json_error();
            die();
        }

        $_POST['data'] = (isset($_POST['data'])) ? $_POST['data'] : array();
        $_POST['meta'] = (isset($_POST['meta'])) ? $_POST['meta'] : array();

        // Call function and send back result
        $result = $this->$functionName($_POST['data'], $_POST['meta']);
        die(json_encode($result));
    }

    /**
     * Löscht ein Produkt anhand der ID.
     *
     * @param array $data Muss den Key 'id' enthalten.
     * @param array $meta Optional, wird hier ignoriert.
     * @return array JSON-Antwort (success/fail)
     */
    private function delete_product(array $data, array $meta): array
    {
        if (!isset($data['product_id']) || !is_numeric($data['product_id'])) {
            return ['state' => false, 'message' => __('Ungültige Produkt-ID', 'wp-fce')];
        }

        try {
            $helper = new WP_FCE_Helper_Product();
            $helper->delete_product_by_id((int) $data['product_id']);
            return ['state' => true, 'message' => __('Produkt erfolgreich gelöscht', 'wp-fce')];
        } catch (\Exception $e) {
            return ['state' => false, 'message' => $e->getMessage()];
        }
    }

    private function update_product(array $data, array $meta): array
    {
        if (!isset($data['product_id']) || !is_numeric($data['product_id'])) {
            return ['state' => false, 'message' => __('Ungültige Produkt-ID', 'wp-fce')];
        }

        $title = sanitize_text_field($data['title'] ?? '');
        $description = sanitize_textarea_field($data['description'] ?? '');

        try {
            $helper = new WP_FCE_Helper_Product();
            $helper->update_product_by_id((int) $data['product_id'], $title, $description);
            return ['state' => true, 'message' => __('Produkt erfolgreich aktualisiert', 'wp-fce')];
        } catch (\Exception $e) {
            return ['state' => false, 'message' => $e->getMessage()];
        }
    }
}
