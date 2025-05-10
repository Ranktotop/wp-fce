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
     * Deletes a product by its ID.
     *
     * @param array $data Must contain key 'product_id'.
     * @param array $meta Optional, ignored.
     * @return array JSON response (success/fail)
     *
     * @throws \Exception If an error occurs, an Exception object is thrown.
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

    /**
     * Updates a product by its ID.
     *
     * @param array $data Must contain key 'product_id'.
     * @param array $meta Optional, ignored.
     * @return array JSON response (success/fail)
     *
     * @throws \Exception If an error occurs, an Exception object is thrown.
     */
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

    /**
     * Returns the assignment of Spaces and Courses for a product.
     *
     * @param array $data Must contain the key 'product_id'.
     * @param array $meta Optional, ignored here.
     * @return array JSON response (success/fail)
     *
     * @throws \Exception When an error occurs, an exception object is thrown.
     */
    private function get_product_mapping(array $data, array $meta): array
    {
        if (empty($data['product_id']) || !is_numeric($data['product_id'])) {
            return ['state' => false, 'message' => __('Ungültige Produkt-ID', 'wp-fce')];
        }

        try {
            $helper  = new WP_FCE_Helper_Product();
            $product = $helper->get_product_by_id((int) $data['product_id']);

            $spaces = $product->get_mapped_spaces();

            // Rückgabe als einfache Datenstruktur (nicht Objekte)
            $mapping = array_map(function (WP_FCE_Model_Fluent_Community_Entity $space) {
                return [
                    'id'    => $space->get_id(),
                    'title' => $space->get_title(),
                    'type'  => $space->get_type(),
                ];
            }, $spaces);

            return ['state' => true, 'mapping' => $mapping, 'message' => __('Mapping erfolgreich geladen', 'wp-fce')];
        } catch (\Exception $e) {
            return ['state' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Deletes all assignments (spaces/courses) of a product.
     *
     * @param array $data Must contain the key 'product_id'.
     * @param array $meta Optional, ignored here.
     * @return array JSON response (success/fail)
     */
    private function delete_product_mapping(array $data, array $meta): array
    {
        if (empty($data['product_id']) || !is_numeric($data['product_id'])) {
            return ['state' => false, 'message' => __('Ungültige Produkt-ID', 'wp-fce')];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'fce_product_space';
        $deleted = $wpdb->delete($table, ['fce_product_id' => (int) $data['product_id']]);

        if ($deleted === false) {
            return ['state' => false, 'message' => __('Fehler beim Löschen.', 'wp-fce')];
        }

        return ['state' => true, 'message' => __('Alle Zuweisungen gelöscht.', 'wp-fce')];
    }
}
