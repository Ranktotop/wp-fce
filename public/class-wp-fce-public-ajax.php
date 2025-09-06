<?php
class Wp_Fce_Public_Ajax_Handler
{
    /**
     * Ajax generic Callback-Function
     */
    public function handle_public_ajax_callback()
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
     * Loads the Community API Transactions page for a user.
     *
     * @param array $data Must contain key 'user_id'.
     * @param array $meta Optional, can contain 'page' for pagination.
     * @return array JSON response (success/fail)
     *
     * @throws \Exception If an error occurs, an Exception object is thrown.
     */
    private function load_community_api_transactions_page_for_user(array $data, array $meta): array
    {
        // Benutzer muss eingeloggt sein
        if (!is_user_logged_in()) {
            wp_send_json_error('Benutzer nicht eingeloggt');
            return ['state' => false, 'message' => __('User not logged in', 'wp-fce')];
        }

        if (!isset($data['user_id'])) {
            return ['state' => false, 'message' => __('Invalid user ID', 'wp-fce')];
        }

        //make sure user ids match
        $current_user_id = get_current_user_id();
        if ($current_user_id !== (int)$data['user_id']) {
            return ['state' => false, 'message' => __('You can only view your own transactions', 'wp-fce')];
        }

        // Seite validieren
        $page = intval($meta['page'] ?? 1);
        $page_size = intval($meta['page_size'] ?? 10);
        if ($page < 1 || $page_size < 1) {
            return ['state' => false, 'message' => __('Invalid page number or page size', 'wp-fce')];
        }

        //make sure user exists
        try {
            $user = WP_FCE_Helper_User::get_by_id($current_user_id);
        } catch (\Exception $e) {
            return ['state' => false, 'message' => __('User not found', 'wp-fce')];
        }

        //load helper
        $helper = new WP_FCE_Helper_Community_Api($user);

        // Transaktionen abrufen
        $transaction_response = $helper->fetch_transactions($page, $page_size);
        if (!$transaction_response) {
            return ['state' => false, 'message' => __('Error fetching transactions', 'wp-fce')];
        }
        //add state to response
        $transaction_response['state'] = true;
        return $transaction_response;
    }
}
