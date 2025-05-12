<?php

/**
 * REST API Controller für IPN-Callbacks.
 *
 * @since 1.0.0
 */
class WP_FCE_REST_Controller
{

    /**
     * Checks if the given API-Key matches the stored Secret-Key.
     *
     * @param string $key The API-Key to check.
     *
     * @return bool True, if the keys match, false otherwise.
     */
    private function verify_api_key(string $key): bool
    {
        // Get stored options array from Redux
        $options = get_option('wp_fce_options', []);

        // Extract expected key
        $expected_key = isset($options['api_key']) ? sanitize_text_field($options['api_key']) : '';

        // If either key is missing, fail
        if (empty($expected_key) || empty($key)) {
            return false;
        }

        // Use hash_equals to prevent timing attacks when comparing secrets
        return hash_equals($expected_key, $key);
    }

    /**
     * IPN-Request verarbeiten.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handle_ipn(\WP_REST_Request $request): \WP_REST_Response
    {
        // Verify API Key
        $provided_key = sanitize_text_field($request->get_param('apikey'));
        if ($this->verify_api_key($provided_key) === false) {
            return new \WP_REST_Response(
                ['error' => 'Unauthorized: invalid API key'],
                403
            );
        }

        // 1) Read JSON-Body
        $params = $request->get_json_params();
        $required_fields = ['customer.email', 'product.id', 'transaction.paid_until', 'transaction.is_drop', 'transaction.is_topup', 'transaction.is_test', 'transaction.order_date', 'transaction.management_url', 'source'];
        $ipn = $this->sanitize_and_validate_ipn($params, $required_fields);

        //if paid_until is null, use current time + 100 years
        if ($ipn["transaction"]['paid_until'] === '' || $ipn["transaction"]['paid_until'] === null) {
            $ipn["transaction"]['paid_until'] = (new DateTime())
                ->modify('+100 years')
                ->format('Y-m-d H:i:s');
        }

        // make sure essential fields are not empty
        if (empty($ipn["customer"]['email']) || empty($ipn["product"]['id'])) {
            return new \WP_REST_Response(
                ['error' => 'Missing parameters: customer.email and product.id are required'],
                400
            );
        }

        // 2) Save ipn to DB
        $helper_ipn = new WP_FCE_Helper_Ipn();
        try {
            //Create the ipn
            $ipn = $helper_ipn->create_ipn(
                $ipn["customer"]['email'],
                $ipn["transaction"]['transaction_id'],
                $ipn["transaction"]['transaction_date'],
                $ipn["product"]['id'],
                $ipn["source"],
                $ipn
            );
        } catch (\Exception $e) {
            return new \WP_REST_Response(
                ['error' => $e->getMessage()],
                500
            );
        }

        // 3) Apply IPN
        try {
            $success = $helper_ipn->apply($ipn);
        } catch (\Exception $e) {
            return new \WP_REST_Response(
                ['error' => $e->getMessage()],
                500
            );
        }
        if (! $success) {
            return new \WP_REST_Response(
                ['error' => 'processing_failed'],
                500
            );
        }

        // 8) Erfolg zurückmelden
        return new \WP_REST_Response(
            ['success' => true, 'hash' => $ipn->get_ipn_hash()],
            200
        );
    }

    /**
     * Sanitize a deeply nested array and validate required fields
     *
     * @param array $data The input array to sanitize.
     * @param array $required_fields Array of required keys in dot notation (e.g. 'product.id').
     * @return array The sanitized data. Throws WP_Error if validation fails.
     */
    public function sanitize_and_validate_ipn(array $data, array $required_fields = []): array
    {
        $missing_fields = [];

        $sanitize_recursive = function ($input) use (&$sanitize_recursive) {
            if (is_array($input)) {
                $output = [];
                foreach ($input as $key => $value) {
                    $lower_key = strtolower($key);
                    if (strpos($lower_key, 'email') !== false && is_string($value)) {
                        $output[$key] = sanitize_email($value);
                    } elseif (is_bool($value)) {
                        $output[$key] = (bool) $value;
                    } elseif (is_int($value)) {
                        $output[$key] = (int) $value;
                    } elseif (is_float($value) || is_numeric($value) && strpos($value, '.') !== false) {
                        $output[$key] = (float) $value;
                    } elseif (is_numeric($value)) {
                        $output[$key] = (int) $value;
                    } elseif (is_string($value)) {
                        $output[$key] = sanitize_text_field($value);
                    } else {
                        $output[$key] = $sanitize_recursive($value);
                    }
                }
                return $output;
            }
            return sanitize_text_field($input);
        };

        $sanitized = $sanitize_recursive($data);

        foreach ($required_fields as $path) {
            $segments = explode('.', $path);
            $ref = $sanitized;
            foreach ($segments as $segment) {
                if (is_array($ref) && array_key_exists($segment, $ref)) {
                    $ref = $ref[$segment];
                } else {
                    $missing_fields[] = $path;
                    break;
                }
            }
        }

        if (!empty($missing_fields)) {
            throw new \WP_REST_Response([
                'error' => 'Missing required fields',
                'missing_fields' => $missing_fields
            ], 400);
        }

        return $sanitized;
    }
}
