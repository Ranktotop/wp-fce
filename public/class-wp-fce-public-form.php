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
        // Nonce check
        // We don't check nonce here, because each handler uses its own nonce field

        // User check
        // We don't check if user is logged in here, because some functions might be public to all
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        // weitere: elseif ($_POST['wp_fce_form_action'] === '...') ...
    }

    /**
     * Get a verified user from the request.
     * This function checks the nonce, verifies that the user is logged in,
     * and ensures that the user ID in the request matches the logged-in user.
     */
    private function get_verified_user(array $post, string $user_id_key, string $nonce_key): WP_FCE_Model_User
    {
        //Nonce check
        if (
            !isset($post['wp_fce_nonce']) ||
            !wp_verify_nonce($post['wp_fce_nonce'], $nonce_key)
        ) {
            exit;
        }
        // User check
        if (!is_user_logged_in()) {
            wp_safe_redirect(add_query_arg('fce_error', urlencode(__('User not logged in', 'wp-fce')), $_SERVER['REQUEST_URI']));
            exit;
        }
        $user_id = intval(sanitize_text_field($post[$user_id_key] ?? ''));
        if (empty($user_id)) {
            wp_safe_redirect(add_query_arg('fce_error', urlencode(__('Invalid user ID', 'wp-fce')), $_SERVER['REQUEST_URI']));
            exit;
        }

        //make sure user ids match
        $current_user_id = get_current_user_id();
        if ($current_user_id !== $user_id) {
            wp_safe_redirect(add_query_arg('fce_error', urlencode(__('Access denied', 'wp-fce')), $_SERVER['REQUEST_URI']));
            exit;
        }

        //make sure user exists
        try {
            $user = WP_FCE_Helper_User::get_by_id($user_id);
        } catch (\Exception $e) {
            wp_safe_redirect(add_query_arg('fce_error', urlencode(__('User not found', 'wp-fce')), $_SERVER['REQUEST_URI']));
            exit;
        }
        return $user;
    }
}
