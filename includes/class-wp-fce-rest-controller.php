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
     * @param int      $product_id              ID des zugeordneten Produkts
     * @param int|null $paid_until_timestamp    Unix-Timestamp aus IPN (oder null)
     * @return void
     */
    public static function upsert_subscription(int $user_id, int $product_id, ?int $paid_until_timestamp): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fce_user_product_subscriptions';

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
            "SELECT id FROM {$table} WHERE user_id = %d AND product_id = %d",
            $user_id,
            $product_id
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
                    'product_id'   => $product_id,
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

        // 2) Wichtige Werte sanitizen
        $email      = isset($params['customer']['email'])
            ? sanitize_email($params['customer']['email'])
            : '';
        $externalId = isset($params['product']['id'])
            ? sanitize_text_field($params['product']['id'])
            : '';

        $paid_until = isset($params['transaction']['paid_until'])
            ? $params['transaction']['paid_until']
            : '';
        //if paid_until is null, use current time + 100 years
        if ($paid_until === '') {
            $paid_until = (new DateTime())
                ->modify('+100 years')
                ->format('Y-m-d H:i:s');
        }

        if (empty($email) || empty($externalId)) {
            return new \WP_REST_Response(
                ['error' => 'Missing parameters: customer.email and product.id are required'],
                400
            );
        }

        // 3) Event-Typ bestimmen aus transaction-Flags
        $event = '';
        if (isset($params['transaction']) && is_array($params['transaction'])) {
            $txn = $params['transaction'];
            if (! empty($txn['is_drop'])) {
                $event = 'refund';
            } elseif (! empty($txn['is_topup'])) {
                $event = 'sale';           // oder 'payment_completed', je nach eurer Konvention
            } elseif (! empty($txn['is_test'])) {
                $event = 'test';
            }
        }
        // Error if event is unknown
        if (empty($event)) {
            return new \WP_REST_Response(
                ['error' => 'Missing parameters: transaction.event is required'],
                400
            );
        }

        // User anlegen oder abrufen
        $helper_user = new WP_FCE_Helper_User();
        $user = $helper_user->get_or_create_user($email);
        if ($user === False) {
            return new \WP_REST_Response(
                ['error' => "Could not create or find user with email $email"],
                500
            );
        }

        // 7) Zugriff gewähren oder entziehen
        if (in_array($event, ['sale', 'test'], true)) {
            if (!$helper_user->grant_access($user->ID, $externalId, $paid_until)) {
                return new \WP_REST_Response(
                    ['error' => "Could not grant user $user->ID access to product $externalId"],
                    500
                );
            }
        } elseif ('refund' === $event) {
            if (!$helper_user->revoke_access($user->ID, $externalId)) {
                return new \WP_REST_Response(
                    ['error' => "Could not revoke user $user->ID access to product $externalId"],
                    500
                );
            }
        }

        // 8) Erfolg zurückmelden
        return new \WP_REST_Response(
            ['success' => true, 'event' => $event],
            200
        );
    }
}
