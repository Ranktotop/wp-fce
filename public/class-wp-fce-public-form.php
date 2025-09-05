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
            return;
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
}
