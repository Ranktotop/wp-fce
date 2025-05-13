<?php

/**
 * File: includes/class-wp-fce-rest-controller.php
 *
 * REST API Controller für Fluent Community Extreme.
 *
 * @package WP_Fluent_Community_Extreme
 */

if (! defined('ABSPATH')) {
    exit;
}

class WP_FCE_REST_Controller
{
    private WP_FCE_Helper_Ipn_Log $helper_ipn_log;

    public function __construct()
    {
        $this->helper_ipn_log = new WP_FCE_Helper_Ipn_Log();
    }

    /**
     * Registriert alle Endpunkte.
     */
    public function register_routes(): void
    {
        $ns = 'wp-fce/v1';

        // 1) IPN-Callback
        register_rest_route($ns, '/ipn', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'handle_ipn'],
            'permission_callback' => [$this, 'permission_check_ipn'],
        ]);

        // 2) Access-Status abfragen
        register_rest_route($ns, '/access/status', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_access_status'],
            'permission_callback' => [$this, 'permission_check_access'],
            'args'                => [
                'user_id'     => ['required' => true,  'validate_callback' => 'is_numeric'],
                'entity_type' => ['required' => true,  'validate_callback' => [$this, 'validate_entity_type']],
                'entity_id'   => ['required' => true,  'validate_callback' => 'is_numeric'],
            ],
        ]);

        // 3) Access-Quellen abfragen
        register_rest_route($ns, '/access/sources', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_access_sources_endpoint'],
            'permission_callback' => [$this, 'permission_check_access'],
            'args'                => [
                'user_id'     => ['required' => true,  'validate_callback' => 'is_numeric'],
                'entity_type' => ['required' => true,  'validate_callback' => [$this, 'validate_entity_type']],
                'entity_id'   => ['required' => true,  'validate_callback' => 'is_numeric'],
            ],
        ]);

        // 4) Mapping erstellen
        register_rest_route($ns, '/mapping', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'create_mapping_endpoint'],
            'permission_callback' => [$this, 'permission_check_manage'],
            'args'                => [
                'product_id' => ['required' => true, 'validate_callback' => 'is_numeric'],
                'space_id'   => ['required' => true, 'validate_callback' => 'is_numeric'],
            ],
        ]);

        // 5) Mapping löschen
        register_rest_route($ns, '/mapping/(?P<product_id>\\d+)', [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => [$this, 'delete_mapping_endpoint'],
            'permission_callback' => [$this, 'permission_check_manage'],
            'args'                => [
                'product_id' => ['validate_callback' => 'is_numeric'],
            ],
        ]);
    }

    /**
     * IPN-Request verarbeiten.
     */
    public function handle_ipn(WP_REST_Request $request)
    {
        $data = $request->get_json_params();

        // 1) JSON sanitisieren & validieren
        $required = [
            'customer.email',
            'product.id',
            'transaction.transaction_date',
            'transaction.transaction_id',
            'source',
        ];
        $ipn = $this->sanitize_and_validate_ipn($data, $required);

        // 2) Pflichtfelder prüfen
        if (empty($ipn['customer']['email']) || empty($ipn['product']['id'])) {
            return new \WP_REST_Response(
                ['error' => 'Missing parameters: customer.email and product.id are required'],
                400
            );
        }

        // 3) Unix-Timestamps in DateTime umwandeln
        try {
            // transaction_date
            $txTs    = (int) $ipn['transaction']['transaction_date'];
            $ipnDate = new \DateTime();
            $ipnDate->setTimestamp($txTs);
            $ipnDate->setTimezone(new \DateTimeZone(wp_timezone_string()));

            // paid_until (oder +100 Jahre, falls leer/0)
            $paidTs = isset($ipn['transaction']['paid_until'])
                ? (int) $ipn['transaction']['paid_until']
                : 0;

            if ($paidTs > 0) {
                $expiryDate = new \DateTime();
                $expiryDate->setTimestamp($paidTs);
                $expiryDate->setTimezone(new \DateTimeZone(wp_timezone_string()));
            } else {
                $expiryDate = new \DateTime('now', new \DateTimeZone(wp_timezone_string()));
                $expiryDate->modify('+100 years');
            }
        } catch (\Exception $e) {
            return new \WP_REST_Response(
                ['error' => 'Invalid timestamp in transaction_date or paid_until'],
                422
            );
        }

        // 4) IPN verarbeiten
        try {
            $this->helper_ipn_log->record_ipn(
                sanitize_email($ipn['customer']['email']),
                sanitize_text_field($ipn['product']['id']),
                sanitize_text_field($ipn['transaction']['transaction_id']),
                $ipn,
                $ipnDate,
                $expiryDate,
                sanitize_text_field($ipn['source'])
            );

            return rest_ensure_response(['success' => true]);
        } catch (\Exception $e) {
            return new \WP_REST_Response(
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * GET /access/status
     */
    public function get_access_status(WP_REST_Request $request)
    {
        $user_id     = (int) $request->get_param('user_id');
        $entity_type = $request->get_param('entity_type');
        $entity_id   = (int) $request->get_param('entity_id');

        $has = WP_FCE_Access_Evaluator::user_has_access($user_id, $entity_type, $entity_id);
        return rest_ensure_response(['has_access' => $has]);
    }

    /**
     * GET /access/sources
     */
    public function get_access_sources_endpoint(WP_REST_Request $request)
    {
        $user_id     = (int) $request->get_param('user_id');
        $entity_type = $request->get_param('entity_type');
        $entity_id   = (int) $request->get_param('entity_id');

        $sources = WP_FCE_Access_Evaluator::get_access_sources($user_id, $entity_type, $entity_id);
        return rest_ensure_response($sources);
    }

    /**
     * POST /mapping
     */
    public function create_mapping_endpoint(WP_REST_Request $request)
    {
        $pid = (int) $request->get_param('product_id');
        $sid = (int) $request->get_param('space_id');

        try {
            WP_FCE_Helper_Product_Space::create_mapping_and_assign($pid, $sid);
            return rest_ensure_response(['success' => true]);
        } catch (\Exception $e) {
            return new WP_Error('mapping_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * DELETE /mapping/{product_id}
     */
    public function delete_mapping_endpoint(WP_REST_Request $request)
    {
        $pid = (int) $request->get_param('product_id');

        try {
            WP_FCE_Helper_Product_Space::remove_mappings_for_product($pid);
            return rest_ensure_response(['success' => true]);
        } catch (\Exception $e) {
            return new WP_Error('mapping_delete_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Prüft, ob das übergebene Entity-Type gültig ist.
     */
    public function validate_entity_type($param): bool
    {
        return in_array($param, ['space', 'course'], true);
    }

    /**
     * Berechtigungs-Check für IPN (z.B. mittels API-Key).
     */
    public function permission_check_ipn(WP_REST_Request $request): bool
    {
        $sent_key = sanitize_text_field($request->get_param('apikey'));
        // check if param exists
        if (empty($sent_key)) {
            return false;
        }

        // Get stored options array from Redux
        $options = get_option('wp_fce_options', []);

        // Extract expected key
        $expected_key = isset($options['api_key']) ? sanitize_text_field($options['api_key']) : '';

        // If either key is missing, fail
        if (empty($expected_key)) {
            return false;
        }

        // Use hash_equals to prevent timing attacks when comparing secrets
        return hash_equals($expected_key, $sent_key);
    }

    /**
     * Berechtigung für Access-Abfragen (z.B. nutzerbezogen).
     */
    public function permission_check_access(WP_REST_Request $request): bool
    {
        return current_user_can('read');
    }

    /**
     * Berechtigung für Mapping-Management.
     */
    public function permission_check_manage(WP_REST_Request $request): bool
    {
        return current_user_can('manage_options');
    }

    /**
     * Sanitize a deeply nested array and validate required fields
     *
     * @param array $data The input array to sanitize.
     * @param array $required_fields Array of required keys in dot notation (e.g. 'product.id').
     * @return array The sanitized data. Throws WP_Error if validation fails.
     */
    private function sanitize_and_validate_ipn(array $data, array $required_fields = []): array
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
