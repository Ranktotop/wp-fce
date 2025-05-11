<?php

/**
 * REST API Controller für IPN-Callbacks.
 *
 * @since 1.0.0
 */
class WP_FCE_REST_Controller
{

    /**
     * Route registrieren.
     *
     * @since 1.0.0
     * @return void
     */
    public function register_routes()
    {
        register_rest_route(
            'wp-fce/v1',
            '/ipn',
            [
                'methods'             => \WP_REST_Server::CREATABLE,      // POST
                'callback'            => [$this, 'handle_ipn'],
                'permission_callback' => '__return_true',                 // öffentlich, IPN-Provider authentifizieren selbst
            ]
        );
    }

    /**
     * Checks if the given API-Key matches the stored Secret-Key.
     *
     * @param string $key The API-Key to check.
     *
     * @return bool True, if the keys match, false otherwise.
     */
    private function verify_api_key(string $key): bool
    {

        // Gespeicherten Secret-Key aus den Theme-Options holen
        $expected_key = function_exists('carbon_get_theme_option')
            ? carbon_get_theme_option('ipn_secret_key')
            : '';

        #if no key is set in settings or given key is not set return false
        if (empty($expected_key) || empty($key)) {
            return false;
        }

        return $key === $expected_key;
    }

    /**
     * Insert or update a subscription record for a given user × product.
     *
     * @param int      $user_id                 WP-User­ID
     * @param int      $product_mapping_id              ID des zugeordneten Produkts
     * @param int|null $paid_until_timestamp    Unix-Timestamp aus IPN (oder null)
     * @return void
     */
    public static function upsert_subscription(int $user_id, int $product_mapping_id, ?int $paid_until_timestamp): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_user_subscriptions';

        // Wenn kein paid_until gesendet, 100 Jahre setzen
        if (is_null($paid_until_timestamp)) {
            $paid_until = (new DateTime())
                ->modify('+100 years')
                ->format('Y-m-d H:i:s');
        } else {
            $paid_until = (new DateTime('@' . $paid_until_timestamp))
                ->setTimezone(new DateTimeZone(wp_timezone_string()))
                ->format('Y-m-d H:i:s');
        }

        // Gibt es schon einen Datensatz?
        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND product_mapping_id = %d",
            $user_id,
            $product_mapping_id
        ));

        if ($existing_id) {
            // Update
            $wpdb->update(
                $table,
                [
                    'paid_until'   => $paid_until,
                    'expired_flag' => 0,
                    'updated_at'   => current_time('mysql'),
                ],
                ['id' => $existing_id]
            );
        } else {
            // Insert
            $wpdb->insert(
                $table,
                [
                    'user_id'      => $user_id,
                    'product_mapping_id'   => $product_mapping_id,
                    'paid_until'   => $paid_until,
                    'created_at'   => current_time('mysql'),
                    'updated_at'   => current_time('mysql'),
                ]
            );
        }
    }

    /**
     * IPN-Request verarbeiten.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handle_ipn(\WP_REST_Request $request)
    {
        // Verify API Key
        $provided_key = sanitize_text_field($request->get_param('apikey'));
        if ($this->verify_api_key($provided_key) === false) {
            return new \WP_REST_Response(
                ['error' => 'Unauthorized: invalid API key'],
                403
            );
        }

        // 1) JSON-Body auslesen
        $params = $request->get_json_params();
        $required_fields = ['customer.email', 'product.id', 'transaction.paid_until', 'transaction.is_drop', 'transaction.is_topup', 'transaction.is_test', 'transaction.order_date', 'transaction.management_url', 'source'];
        $ipn = $this->sanitize_and_validate_ipn($params, $required_fields);

        //if paid_until is null, use current time + 100 years
        if ($ipn["transaction"]['paid_until'] === '' || $ipn["transaction"]['paid_until'] === null) {
            $ipn["transaction"]['paid_until'] = (new DateTime())
                ->modify('+100 years')
                ->format('Y-m-d H:i:s');
        }

        if (empty($ipn["customer"]['email']) || empty($ipn["product"]['id'])) {
            return new \WP_REST_Response(
                ['error' => 'Missing parameters: customer.email and product.id are required'],
                400
            );
        }
        $helper_ipn = new WP_FCE_Helper_Ipn();
        $ipn = $helper_ipn->create_ipn(
            $ipn["customer"]['email'],
            $ipn["transaction"]['transaction_id'],
            $ipn["transaction"]['transaction_date'],
            $ipn["product"]['id'],
            $ipn["source"],
            $ipn
        );

        if ($ipn === null) {
            return new \WP_REST_Response(
                ['error' => 'Could not save IPN'],
                500
            );
        }

        $this->apply_ipn($ipn);

        // 8) Erfolg zurückmelden
        return new \WP_REST_Response(
            ['success' => true, 'transaction_id' => $ipn->get_transaction_id()],
            200
        );
    }

    /**
     * Apply the given ipn to the user and product
     *
     * This method will:
     * - Check if the new ipn is newer than the latest ipn saved for this product/user
     * - If yes, apply the ipn to the user and product
     * - If no, do nothing
     *
     * @param WP_FCE_Model_Ipn $ipn The ipn to apply
     *
     * @return void
     */
    private function apply_ipn(WP_FCE_Model_Ipn $ipn)
    {

        $helper_ipn = new WP_FCE_Helper_Ipn();

        // Get last ipn saved for this product/user
        $last_ipns = $helper_ipn->get_ipns_by_product_and_email($ipn->get_external_product_id(), $ipn->get_user_email());
        $latest_ipn = !empty($last_ipns) ? $last_ipns[0] : null;

        //If the new ipn is legacy, ignore it
        if ($latest_ipn !== null && $latest_ipn->get_ipn_date_timestamp() > $ipn->get_ipn_date_timestamp()) {
            return;
        }

        // Get the product mapping
        $product_mapping = $ipn->get_product_mapping();
        if ($product_mapping === null) {
            return new \WP_REST_Response(
                ['error' => 'Could not find product mapping for product with id ' . $ipn->get_external_product_id()],
                500
            );
        }

        // User anlegen oder abrufen
        $helper_user = new WP_FCE_Helper_User();
        $user = $helper_user->get_or_create_user($ipn->get_user_email());
        if ($user === False) {
            return new \WP_REST_Response(
                ['error' => "Could not create or find user with email " . $ipn->get_user_email()],
                500
            );
        }

        // 7) Zugriff gewähren oder entziehen
        if ($ipn->is_topup_event()) {
            foreach ($product_mapping["space_ids"] as $space_id) {
                $helper_user->grant_space_access($user->ID, $space_id);
            }
            foreach ($product_mapping["course_ids"] as $course_id) {
                $helper_user->grant_course_access($user->ID, $course_id);
            }
        } else {
            foreach ($product_mapping["space_ids"] as $space_id) {
                $helper_user->revoke_space_access($user->ID, $space_id);
            }
            foreach ($product_mapping["course_ids"] as $course_id) {
                $helper_user->revoke_course_access($user->ID, $course_id);
            }
        }
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
