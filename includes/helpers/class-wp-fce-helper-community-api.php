<?php
// File: includes/helpers/class-wp-fce-helper-product-user.php

if (! defined('ABSPATH')) {
    exit;
}

class WP_FCE_Helper_Community_API
{
    private WP_FCE_Model_User $user;
    private array $admin_options;
    private ?array $cached_user_data = null;
    private ?string $cached_user_api_key = null;
    private ?string $cached_master_key = null;

    /**
     * Constructor.
     *
     * @param WP_FCE_Model_User $user
     */
    public function __construct(WP_FCE_Model_User $user)
    {
        $this->user = $user;
        $this->admin_options = get_option('wp_fce_options');
    }

    /*****************************
     ********** CHECKER **********
     *****************************/

    public function is_valid(): bool
    {
        return $this->user->get_id() !== null && $this->get_base_url() !== null && $this->get_master_key() !== null;
    }

    public function has_api_key(): bool
    {
        return $this->get_api_key() !== null;
    }

    /*****************************
     ********** GETTER ***********
     *****************************/

    /**
     * Get the Community API key for this user. If not found in users meta, tries to fetch it from the API using the master key.
     *
     * @return string|null
     */
    public function get_api_key(): ?string
    {
        if (!$this->is_valid()) {
            return null;
        }

        if ($this->cached_user_api_key !== null) {
            return $this->cached_user_api_key;
        }
        //read api key from settings primary
        $api_key = get_user_meta($this->user->get_id(), 'wp_fce_community_api_key', true) ?: null;

        //if not set, try to fetch it from the API using the master key
        if ($api_key === null) {
            $api_key = $this->fetch_api_key_by_email();
        }

        //save result in cache
        $this->cached_user_api_key = $api_key;
        return $this->cached_user_api_key;
    }

    /**
     * Gets the master key from admin options, or null if none is set.
     */
    private function get_master_key(): ?string
    {
        if ($this->cached_master_key !== null) {
            return $this->cached_master_key;
        }
        $this->cached_master_key = $this->admin_options['community_api_master_token'] ?? null;
        return $this->cached_master_key;
    }

    /**
     * Builds the Community API base URL
     */
    private function get_base_url(): ?string
    {
        $url = $this->admin_options['community_api_url'] ?? null;

        if (empty($url)) {
            return null;
        }

        $port = $this->admin_options['community_api_port'] ?? null;
        $ssl = $this->admin_options['community_api_ssl'] ?? true;

        $protocol = $ssl ? 'https' : 'http';
        $port_str = $port ? ':' . intval($port) : '';
        return $protocol . '://' . $url . $port_str;
    }

    /*****************************
     ********** SETTER ***********
     *****************************/

    /**
     * Set or clear the Community API key for this user.
     *
     * @param  string|null $api_key
     * @return void
     */
    public function set_community_api_key(?string $api_key): void
    {
        #delete cache
        $this->cached_user_data = null;
        $this->cached_user_api_key = null;

        if ($api_key) {
            update_user_meta($this->user->get_id(), 'wp_fce_community_api_key', $api_key);
            $this->cached_user_api_key = $api_key;
        } else {
            delete_user_meta($this->user->get_id(), 'wp_fce_community_api_key');
        }
    }

    /*****************************
     ********** REQUESTS *********
     *****************************/

    private function fetch_user_data_by_api_key(string $api_key): ?array
    {
        if (!$this->is_valid()) {
            return null;
        }

        $response = $this->do_request(
            'GET',
            $api_key,
            '/user/account/info'
        );

        if ($response === null) {
            return null;
        }
        return $response;
    }

    private function fetch_api_key_by_email(): ?string
    {
        if (!$this->is_valid()) {
            return null;
        }
        $master_key = $this->get_master_key();
        if ($master_key === null) {
            return null;
        }
        $email = $this->user->get_email();
        if ($email === null) {
            return null;
        }

        $response = $this->do_request(
            'GET',
            $master_key,
            '/admin/user/get_by_email/' . rawurlencode($email)
        );

        if ($response === null || !isset($response['api_key'])) {
            return null;
        }
        return $response['api_key'];
    }

    /**
     * Fetches data from the Community API.
     * Returns null if the request fails or the user has no API key.
     */
    private function do_request(
        string $method,
        string $api_key,
        string $endpoint,
        array|null $form_payload = null,
        array|null $query_params = null,
        array|null $json_payload = null
    ): ?array {
        if (!$this->is_valid()) {
            return null;
        }

        // Basis-URL und Endpoint sauber zusammenführen
        $api_base_url = rtrim($this->get_base_url(), '/');
        if ($endpoint === '' || $endpoint[0] !== '/') {
            $endpoint = '/' . $endpoint;
        }

        // Query-Parameter anhängen (RFC3986-encoding) und vorhandenes "?" berücksichtigen
        if (!empty($query_params)) {
            $query_string = http_build_query($query_params, '', '&', PHP_QUERY_RFC3986);
            $endpoint .= (strpos($endpoint, '?') === false ? '?' : '&') . $query_string;
        }

        $url = $api_base_url . $endpoint;
        $method = strtoupper($method);

        // Basiskonfiguration
        $args = [
            'method'  => $method,
            'timeout' => 10,
            'headers' => [
                'auth-token' => $api_key,
                'Accept'     => 'application/json',
            ],
        ];

        // Payload-Entscheidung:
        // Falls beides gesetzt ist, hat JSON Vorrang (analog zu Python requests' json=).
        if ($json_payload !== null) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($json_payload);
        } elseif ($form_payload !== null) {
            // Für application/x-www-form-urlencoded entweder Array übergeben (WP encodiert)
            // oder selbst encodieren – hier explizit für konsistentes Encoding:
            $args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
            $args['body'] = http_build_query($form_payload, '', '&', PHP_QUERY_RFC3986);
        }

        // Request ausführen (unterstützt GET/POST/PUT/PATCH/DELETE/HEAD etc.)
        $response = wp_remote_request($url, $args);

        // Fehlerbehandlung
        if (is_wp_error($response)) {
            return null;
        }

        $status = wp_remote_retrieve_response_code($response);
        if ($status < 200 || $status >= 300) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);

        // Leerer Body → leeres Array zurückgeben (kein Fehler)
        if ($body === '' || $body === null) {
            return [];
        }

        // JSON dekodieren; bei Fehler null zurück
        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $decoded;
    }
}
