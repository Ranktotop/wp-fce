<?php
class Wp_Fce_Public_Form_Handler
{

    /**
     * Handles form submissions in the public area.
     *
     * This function is attached to the 'public_init' action hook and is called
     * on every public page load. It is responsible for handling form submissions
     * and redirecting the user to the appropriate page after submission.
     *
     * It currently handles the creation of new products, but could be extended
     * to handle other types of form submissions in the future.
     *
     * @since 1.0.0
     */
    public function handle_public_form_callback(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        if (isset($_POST['wp_fce_form_action']) && $_POST['wp_fce_form_action'] === 'set_community_api_key') {
            $this->handle_set_community_api_key();
        }
        if (isset($_POST['wp_fce_form_action']) && $_POST['wp_fce_form_action'] === 'set_community_api_credentials') {
            $this->handle_set_community_api_credentials();
        }

        // weitere: elseif ($_POST['wp_fce_form_action'] === '...') ...
    }

    private function handle_set_community_api_key(): void
    {
        if (
            !isset($_POST['wp_fce_nonce']) ||
            !wp_verify_nonce($_POST['wp_fce_nonce'], 'wp_fce_set_community_api_key')
        ) {
            return;
        }

        $user_id = intval(sanitize_text_field($_POST['community_api_user_id'] ?? ''));
        $api_key = sanitize_text_field($_POST['community_api_key'] ?? '');

        if (empty($api_key) || empty($user_id)) {
            wp_safe_redirect(add_query_arg('fce_error', urlencode(__('Invalid API key or user ID', 'wp-fce')), $_SERVER['REQUEST_URI']));
            exit;
        }

        //make sure user exists
        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_safe_redirect(add_query_arg('fce_error', urlencode(__('User not found', 'wp-fce')), $_SERVER['REQUEST_URI']));
            exit;
        }

        try {
            update_user_meta($user_id, 'wp_fce_community_api_key', $api_key);
            wp_safe_redirect(add_query_arg('fce_success', urlencode(__('Community API key saved successfully', 'wp-fce')), $_SERVER['REQUEST_URI']));
            exit;
        } catch (\Exception $e) {
            wp_safe_redirect(add_query_arg('fce_error', urlencode($e->getMessage()), $_SERVER['REQUEST_URI']));
            exit;
        }
    }

    private function handle_set_community_api_credentials(): void
    {
        //verify nonce
        if (
            !isset($_POST['wp_fce_nonce']) ||
            !wp_verify_nonce($_POST['wp_fce_nonce'], 'wp_fce_set_community_api_credentials')
        ) {
            return;
        }

        //get user id from payload
        $user_id = intval(sanitize_text_field($_POST['community_api_user_id'] ?? ''));
        if (empty($user_id)) {
            return;
        }

        //make sure user exists
        try {
            $user = WP_FCE_Helper_User::get_by_id($user_id);
        } catch (\Exception $e) {
            wp_safe_redirect(add_query_arg('fce_error', urlencode(__('User not found', 'wp-fce')), $_SERVER['REQUEST_URI']));
            exit;
        }

        //load helper
        $helper = new WP_FCE_Helper_Community_Api($user);
        $result = $helper->set_credentials($_POST['credentials'] ?? []);

        if ($result) {
            wp_safe_redirect(add_query_arg('fce_success', urlencode(__('Credentials saved successfully', 'wp-fce')), $_SERVER['REQUEST_URI']));
            exit;
        } else {
            wp_safe_redirect(add_query_arg('fce_error', urlencode(__('Failed to save credentials', 'wp-fce')), $_SERVER['REQUEST_URI']));
            exit;
        }
    }
}
