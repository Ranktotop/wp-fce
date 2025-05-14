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
            return ['state' => false, 'message' => __('Invalid product ID', 'wp-fce')];
        }

        try {
            //Before we delete the product, we remove all users from product exclusive spaces
            $exclusive_spaces = WP_FCE_Helper_Fcom::get_spaces_exclusive_to_product((int) $data['product_id']);
            foreach ($exclusive_spaces as $space) {
                $space->revoke_all_user_access();
            }
            WP_FCE_Helper_Product::delete((int) $data['product_id']);

            return ['state' => true, 'message' => __('Product successfully deleted', 'wp-fce')];
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
            return ['state' => false, 'message' => __('Invalid product ID', 'wp-fce')];
        }

        $name = sanitize_text_field($data['name'] ?? '');
        $desc = sanitize_textarea_field($data['description'] ?? '');

        try {
            WP_FCE_Helper_Product::update((int) $data['product_id'], $name, $desc);
            return ['state' => true, 'message' => __('Product successfully updated', 'wp-fce')];
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
            return ['state' => false, 'message' => __('Invalid product ID', 'wp-fce')];
        }

        try {
            $product_id = (int) $data['product_id'];
            $product = WP_FCE_Helper_Product::get_by_id($product_id);

            $communities = $product->get_mapped_communities();
            $courses = $product->get_mapped_courses();
            $spaces = array_merge($communities, $courses);

            // Rückgabe als einfache Datenstruktur (nicht Objekte)
            $mapping = array_map(function (WP_FCE_Model_Fcom $space) {
                return [
                    'id'    => $space->get_id(),
                    'title' => $space->get_title(),
                    'type'  => $space->get_type(),
                ];
            }, $spaces);

            return ['state' => true, 'mapping' => $mapping, 'message' => __('Sucessfully retrieved product mapping', 'wp-fce')];
        } catch (\Exception $e) {
            return ['state' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Deletes all space assignments of a product.
     *
     * @param array $data Must contain the key 'product_id'.
     * @param array $meta Optional, ignored here.
     * @return array JSON response (state => bool, message => string)
     */
    private function delete_product_mapping(array $data, array $meta): array
    {
        // 1) Validierung
        if (empty($data['product_id']) || ! is_numeric($data['product_id'])) {
            return [
                'state'   => false,
                'message' => __('Invalid product ID', 'wp-fce'),
            ];
        }

        $product_id = (int) $data['product_id'];

        try {
            // Remove all space mappings
            WP_FCE_Helper_Product_Space::remove_mappings_for_product($product_id);

            //update access
            WP_FCE_Cron::check_expirations(product_id: $product_id);

            return [
                'state'   => true,
                'message' => sprintf(__('All mappings successfully deleted for product #%d', 'wp-fce'), $product_id)
            ];
        } catch (\Exception $e) {
            // 4) Fehler-Antwort
            return [
                'state'   => false,
                'message' => sprintf(
                    /* translators: %s = Fehlermeldung */
                    __('Error deleting mappings for product #%d: %s', 'wp-fce'),
                    $product_id,
                    $e->getMessage()
                ),
            ];
        }
    }

    private function delete_access_rule(array $data, array $meta): array
    {
        if (!isset($data['rule_id']) || !is_numeric($data['rule_id'])) {
            return ['state' => false, 'message' => __('Invalid rule ID', 'wp-fce')];
        }

        try {
            $rule = WP_FCE_Helper_Access_Override::get_by_id((int) $data['rule_id']);
            if (!$rule) {
                return ['state' => false, 'message' => __('Rule not found', 'wp-fce')];
            }
            WP_FCE_Helper_Access_Override::remove_overrides($rule->get_user_id(), $rule->get_product_id());

            //update access
            WP_FCE_Cron::check_expirations(user_id: $rule->get_user_id(), product_id: $rule->get_product_id());

            return ['state' => true, 'message' => __('Rule successfully deleted', 'wp-fce')];
        } catch (\Exception $e) {
            return ['state' => false, 'message' => $e->getMessage()];
        }
    }

    private function update_access_rule(array $data, array $meta): array
    {
        if (!isset($data['rule_id']) || !is_numeric($data['rule_id'])) {
            return ['state' => false, 'message' => __('Invalid rule ID', 'wp-fce')];
        }

        $mode = sanitize_text_field($data['mode'] ?? '');
        $valid_until = sanitize_text_field($data['valid_until'] ?? '');
        $comment = sanitize_text_field($data['comment'] ?? '');

        if (!$valid_until) {
            return ['state' => false, 'message' => __('Invalid valid until date', 'wp-fce')];
        }

        try {
            $rule = WP_FCE_Helper_Access_Override::get_by_id((int) $data['rule_id']);
            if (!$rule) {
                return ['state' => false, 'message' => __('Rule not found', 'wp-fce')];
            }

            //patch rule
            WP_FCE_Helper_Access_Override::patch_override($rule->get_id(), $valid_until, $mode, $comment);

            //update access
            WP_FCE_Cron::check_expirations(user_id: $rule->get_user_id(), product_id: $rule->get_product_id());

            return ['state' => true, 'message' => __('Rule successfully updated', 'wp-fce')];
        } catch (\Exception $e) {
            return ['state' => false, 'message' => $e->getMessage()];
        }
    }
}
