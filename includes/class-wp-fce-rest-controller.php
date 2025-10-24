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
            'permission_callback' => [$this, 'permission_check_admin'],
            'args'                => [
                'user_id'     => ['required' => true,  'validate_callback' => 'is_numeric'],
                'entity_id'   => ['required' => true,  'validate_callback' => 'is_numeric']
            ],
        ]);

        // 3) Access-Quellen abfragen
        register_rest_route($ns, '/access/sources', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_access_sources_endpoint'],
            'permission_callback' => [$this, 'permission_check_admin'],
            'args'                => [
                'user_id'     => ['required' => true,  'validate_callback' => 'is_numeric'],
                'entity_id'   => ['required' => true,  'validate_callback' => 'is_numeric']
            ],
        ]);

        // 4) Mapping erstellen
        register_rest_route($ns, '/mapping', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'create_mapping_endpoint'],
            'permission_callback' => [$this, 'permission_check_admin'],
            'args'                => [
                'product_id' => ['required' => true, 'validate_callback' => 'is_numeric'],
                'space_id'   => ['required' => true, 'validate_callback' => 'is_numeric'],
            ],
        ]);

        // 5) Mapping löschen
        register_rest_route($ns, '/mapping/(?P<product_id>\\d+)', [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => [$this, 'delete_mapping_endpoint'],
            'permission_callback' => [$this, 'permission_check_admin'],
            'args'                => [
                'product_id' => ['validate_callback' => 'is_numeric'],
            ],
        ]);

        // 6) Admin-Override setzen
        register_rest_route($ns, '/override', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'create_override_endpoint'],
            'permission_callback' => [$this, 'permission_check_admin'],
            'args'                => [
                'user_id'      => ['required' => true, 'validate_callback' => 'is_numeric'],
                'product_id'   => ['required' => true, 'validate_callback' => 'is_numeric'],
                'override_type' => ['required' => true, 'validate_callback' => function ($v) {
                    return in_array($v, ['allow', 'deny'], true);
                }],
                'valid_until'  => ['required' => false, 'validate_callback' => 'is_numeric'],
                'comment'      => ['required' => false],
            ],
        ]);

        // 7) Admin-Override löschen
        register_rest_route($ns, '/override', [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => [$this, 'delete_override_endpoint'],
            'permission_callback' => [$this, 'permission_check_admin'],
            'args'                => [
                'user_id'    => ['required' => true, 'validate_callback' => 'is_numeric'],
                'product_id' => ['required' => true, 'validate_callback' => 'is_numeric'],
            ],
        ]);

        // 8) Neuer Benutzer erstellen
        register_rest_route($ns, '/register-user', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'handle_user_registration'],
            'permission_callback' => [$this, 'permission_check_admin'],
            'args'                => [
                'user_email'      => ['required' => true, 'validate_callback' => 'is_email'],
                'user_first_name' => ['required' => false, 'validate_callback' => function ($value) {
                    return is_string($value) || empty($value);
                }],
                'user_last_name'  => ['required' => false, 'validate_callback' => function ($value) {
                    return is_string($value) || empty($value);
                }],
                'send_welcome_email' => ['required' => false, 'validate_callback' => function ($value) {
                    return is_bool($value) || is_null($value);
                }],
            ],
        ]);
    }

    /**
     * IPN-Request verarbeiten.
     */
    public function handle_ipn(\WP_REST_Request $request)
    {
        try {
            $model = ProxyIPNModelCreateRequest::fromArray($request->get_json_params());
            $ipn   = new ProxyIPNClass($model);
        } catch (\Exception $e) {
            return new \WP_REST_Response(['error' => $e->getMessage()], 400);
        }

        // 1) Fehlenden Pflichtwert korrekt beenden: RETURN (nicht throw)
        $txDate = $ipn->get_transaction_date(); // nur EINMAL aufrufen
        if ($txDate === null) {
            return new \WP_REST_Response([
                'error'          => 'Missing required fields',
                'missing_fields' => ['transaction.transaction_date'],
            ], 400);
        }

        // 2) Zeitumrechnung – Analyzer weiß jetzt: $txDate ist DateTimeImmutable
        try {
            /** @var \DateTimeImmutable $txDate */
            $ipnDate = $txDate->setTimezone(new \DateTimeZone(\wp_timezone_string()));

            $paidUntil = $ipn->get_transaction_paid_until();
            if ($paidUntil !== null) {
                $expiryDate = $paidUntil->setTimezone(new \DateTimeZone(\wp_timezone_string()));
            } else {
                $expiryDate = new \DateTimeImmutable('now', new \DateTimeZone(\wp_timezone_string()));
                $expiryDate = $expiryDate->modify('+100 years');
            }
        } catch (\Throwable $e) {
            return new \WP_REST_Response(
                ['error' => 'Invalid timestamp in transaction.paid_until'],
                422
            );
        }

        // 3) Verarbeitung
        try {
            $ipnDate   = \DateTime::createFromImmutable($ipnDate);
            $expiryDate = \DateTime::createFromImmutable($expiryDate);
            $this->helper_ipn_log->record_ipn(
                \sanitize_email($ipn->get_customer_email()),
                \sanitize_text_field($ipn->get_product_id()),
                \sanitize_text_field($ipn->get_transaction_id()),
                $model->toArray(),
                $ipnDate,     // jetzt nicht mehr nullable
                $expiryDate,  // ebenfalls nicht nullable
                \sanitize_text_field($ipn->get_source())
            );

            return \rest_ensure_response(['success' => true]);
        } catch (\Throwable $e) {
            return new \WP_REST_Response(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /access/status
     *
     * Gibt zurück, ob der Nutzer Zugriff auf den Space/Kurs hat.
     */
    public function get_access_status(WP_REST_Request $request)
    {
        $user_id     = (int) $request->get_param('user_id');
        $entity_id   = (int) $request->get_param('entity_id');

        try {
            $has = WP_FCE_Access_Evaluator::user_has_access($user_id, $entity_id);
            return rest_ensure_response(['has_access' => $has]);
        } catch (\Exception $e) {
            return new WP_REST_Response(
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * GET /access/sources
     *
     * Liefert Details darüber, warum der Zugriff gewährt/verweigert wurde.
     */
    public function get_access_sources_endpoint(WP_REST_Request $request)
    {
        $user_id     = (int) $request->get_param('user_id');
        $entity_id   = (int) $request->get_param('entity_id');

        try {
            $sources = WP_FCE_Access_Evaluator::get_access_sources($user_id, $entity_id);
            return rest_ensure_response($sources);
        } catch (\Exception $e) {
            return new WP_REST_Response(
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * POST /mapping
     */
    public function create_mapping_endpoint(WP_REST_Request $request)
    {
        $pid = (int) $request->get_param('product_id');
        $sid = (int) $request->get_param('space_id');

        try {
            WP_FCE_Helper_Product_Space::create_mapping_and_assign_users($pid, $sid);
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
     * Endpoint zum Anlegen eines Overrides.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function create_override_endpoint(WP_REST_Request $request)
    {
        $user_id       = (int) $request->get_param('user_id');
        $product_id    = (int) $request->get_param('product_id');
        $override_type = $request->get_param('override_type');
        $comment       = $request->get_param('comment') ?: null;
        $valid_until = $request->get_param('valid_until');

        try {
            // Override anlegen – wir nutzen 'product' als entity_type
            $override_id = WP_FCE_Helper_Access_Override::add_override(
                $user_id,
                $product_id,
                $override_type,
                $valid_until,
                $comment
            );

            return new WP_REST_Response([
                'success'     => true,
                'override_id' => $override_id,
            ], 201);
        } catch (Exception $e) {
            return new WP_Error(
                'fce_override_create_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Endpoint zum Löschen aller Admin-Overrides für ein Produkt/User-Kombination.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function delete_override_endpoint(WP_REST_Request $request)
    {
        $user_id    = (int) $request->get_param('user_id');
        $product_id = (int) $request->get_param('product_id');

        try {
            // Alle Overrides entfernen
            $ok = WP_FCE_Helper_Access_Override::remove_overrides(
                $user_id,
                $product_id
            );

            if (! $ok) {
                throw new \Exception('No overrides deleted.');
            }

            return new WP_REST_Response([
                'success' => true,
            ], 200);
        } catch (Exception $e) {
            return new WP_Error(
                'fce_override_delete_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function handle_user_registration(WP_REST_Request $request)
    {
        $json_data = $request->get_json_params();

        // JSON-Daten extrahieren
        $email = sanitize_email($json_data['user_email'] ?? '');
        $first_name = sanitize_text_field($json_data['user_first_name'] ?? '');
        $last_name = sanitize_text_field($json_data['user_last_name'] ?? '');
        $send_welcome_email = $json_data['send_welcome_email'] ?? true; // Default: true

        //make sure email is given and valid        
        if (empty($email) || !is_email($email)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('A valid email address is required.', 'wp-fce')
            ], 400);
        }

        try {
            // get or create the user and send welcome email
            $user = WP_FCE_Helper_User::get_or_create($email, '', '', $first_name, $last_name, $send_welcome_email);
            $was_created = !email_exists($email) ? 'created' : 'existing';

            // get all public entities (spaces/courses)
            /** @var WP_FCE_Model_Fcom[] $public_entities */
            $public_entities = array_merge(
                WP_FCE_Helper_Fcom::get_all_public_spaces(),
                WP_FCE_Helper_Fcom::get_all_public_courses()
            );

            // grant access to all public entities
            $granted_access = [];
            $failed_access = [];

            foreach ($public_entities as $entity) {
                try {
                    $entity->grant_user_access($user->get_id(), 'member', 'by_api_registration');
                    $granted_access[] = [
                        'id' => $entity->get_id(),
                        'title' => $entity->get_title(),
                        'type' => $entity->get_type()
                    ];
                } catch (Exception $e) {
                    $failed_access[] = [
                        'id' => $entity->get_id(),
                        'title' => $entity->get_title(),
                        'type' => $entity->get_type(),
                        'error' => $e->getMessage()
                    ];
                    error_log("FCE: Failed to grant access to {$entity->get_type()} {$entity->get_id()} for user {$user->get_id()}: " . $e->getMessage());
                }
            }

            // return success with detailed info
            $response_data = [
                'success' => true,
                'user_status' => $was_created, // 'created' or 'existing'
                'user_id' => $user->get_id(),
                'username' => $user->get_login(),
                'email' => $user->get_email(),
                'granted_access_count' => count($granted_access),
                'granted_access' => $granted_access
            ];

            // Include failed access info if any (for debugging)
            if (!empty($failed_access)) {
                $response_data['failed_access'] = $failed_access;
            }

            $status_code = $was_created === 'created' ? 201 : 200;
            return new WP_REST_Response($response_data, $status_code);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Registration failed: ', 'wp-fce') . $e->getMessage()
            ], 500);
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
        $expected_key = isset($options['api_key_ipn']) ? sanitize_text_field($options['api_key_ipn']) : '';

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
    public function permission_check_admin(WP_REST_Request $request): bool
    {
        $sent_key = sanitize_text_field($request->get_param('apikey'));
        // check if param exists
        if (empty($sent_key)) {
            return false;
        }

        // Get stored options array from Redux
        $options = get_option('wp_fce_options', []);

        // Extract expected key
        $expected_key = isset($options['api_key_admin']) ? sanitize_text_field($options['api_key_admin']) : '';

        // If either key is missing, fail
        if (empty($expected_key)) {
            return false;
        }

        // Use hash_equals to prevent timing attacks when comparing secrets
        return hash_equals($expected_key, $sent_key);
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
