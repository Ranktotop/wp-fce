<?php
if (!is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

global $wpdb;
$user_id = get_current_user_id();
$user        = WP_FCE_Helper_User::get_by_id($user_id);
$user_email  = $user->get_email();

// Community API Einstellungen laden
$community_api_options = get_option('wp_fce_options');
$api_enabled = $community_api_options['community_api_enabled'] ?? false;
$api_url = $community_api_options['community_api_url'] ?? 'localhost';
$api_port = $community_api_options['community_api_port'] ?? '8000';
$api_ssl = $community_api_options['community_api_ssl'] ?? false;
$master_token = $community_api_options['community_api_master_token'] ?? '';

// Community API Logik
$community_api_key = '';
$show_api_key_input = false;
$api_error_message = '';

if ($api_enabled && !empty($master_token)) {
    // 1. Prüfen ob User bereits API Key hat
    $existing_api_key = get_user_meta($user_id, 'community_api_key', true);

    if (!empty($existing_api_key)) {
        // 2. API Key validieren
        if (validate_community_api_key($existing_api_key, $api_url, $api_port, $api_ssl)) {
            $community_api_key = $existing_api_key;
        } else {
            // 3. Ungültigen Key löschen und von vorne beginnen
            delete_user_meta($user_id, 'community_api_key');
            $existing_api_key = '';
        }
    }

    if (empty($existing_api_key)) {
        // 4. Mit Master Token nach Email suchen
        $found_api_key = search_user_by_email($user_email, $master_token, $api_url, $api_port, $api_ssl);

        if ($found_api_key) {
            // 5. API Key speichern
            update_user_meta($user_id, 'community_api_key', $found_api_key);
            $community_api_key = $found_api_key;
        } else {
            // 6. Eingabefeld anzeigen
            $show_api_key_input = true;
        }
    }
}

// AJAX Handler für manuellen API Key
if (isset($_POST['manual_api_key']) && !empty($_POST['manual_api_key'])) {
    $manual_key = sanitize_text_field($_POST['manual_api_key']);

    if (validate_community_api_key($manual_key, $api_url, $api_port, $api_ssl)) {
        update_user_meta($user_id, 'community_api_key', $manual_key);
        $community_api_key = $manual_key;
        $show_api_key_input = false;
        wp_redirect($_SERVER['REQUEST_URI']);
        exit;
    } else {
        $api_error_message = __('Invalid API Key. Please check your key and try again.', 'wp-fce');
    }
}

// Community API Daten laden (falls verfügbar)
$community_user_data = null;
if (!empty($community_api_key)) {
    $community_user_data = get_community_user_data($community_api_key, $api_url, $api_port, $api_ssl);
}

// Neuste IPNs des Users laden
$ipns = WP_FCE_Helper_Ipn_Log::get_latest_ipns_for_user($user_email);

// Hintergrundbild holen
$bg_image     = get_option('wp_fce_options')['orders_background_image'] ?? null;
$bg_image_url = is_array($bg_image) && !empty($bg_image['url'])
    ? $bg_image['url']
    : plugins_url('wp-fce/public/assets/membership_bg.png', dirname(__DIR__));

// Zurück-Button
$back_url = home_url();
if (!empty($_SERVER['HTTP_REFERER'])) {
    $referer_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    $site_host    = parse_url(home_url(), PHP_URL_HOST);
    if ($referer_host === $site_host) {
        $back_url = $_SERVER['HTTP_REFERER'];
    }
}
/**
 * Hilfsfunktionen für Community API
 */

function get_community_api_base_url($url, $port, $ssl)
{
    $protocol = $ssl ? 'https' : 'http';
    return $protocol . '://' . $url . ':' . $port;
}

function validate_community_api_key($api_key, $url, $port, $ssl)
{
    $api_base_url = get_community_api_base_url($url, $port, $ssl);

    $response = wp_remote_get($api_base_url . '/user/account/info', [
        'headers' => [
            'auth-token' => $api_key,
            'Accept' => 'application/json'
        ],
        'timeout' => 10
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    return wp_remote_retrieve_response_code($response) === 200;
}

function search_user_by_email($email, $master_token, $url, $port, $ssl)
{
    $api_base_url = get_community_api_base_url($url, $port, $ssl);

    $response = wp_remote_get($api_base_url . '/admin/user/get_by_email/' . urlencode($email), [
        'headers' => [
            'auth-token' => $master_token,
            'Accept' => 'application/json'
        ],
        'timeout' => 10
    ]);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    return $data['api_key'] ?? false;
}

function get_community_user_data($api_key, $url, $port, $ssl)
{
    $api_base_url = get_community_api_base_url($url, $port, $ssl);

    $response = wp_remote_get($api_base_url . '/user/account/info', [
        'headers' => [
            'auth-token' => $api_key,
            'Accept' => 'application/json'
        ],
        'timeout' => 10
    ]);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return null;
    }

    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true);
}

?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title><?php esc_html_e('My Orders', 'wp_fce'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?= esc_url(plugins_url('wp-fce/public/css/fce-orders.css', dirname(__DIR__))) ?>" media="all">
</head>

<body style="background-image: url('<?= esc_url($bg_image_url); ?>'); background-size: cover;">



    <div class="fce-orders-wrapper">
        <h1><?php esc_html_e('My Orders', 'wp_fce'); ?></h1>

        <?php if ($api_enabled): ?>
            <div class="community-api-section">
                <h2><?php esc_html_e('Community API', 'wp-fce'); ?></h2>

                <?php if ($show_api_key_input): ?>
                    <p><?php esc_html_e('To access your community account data, please enter your API key:', 'wp-fce'); ?></p>

                    <?php if (!empty($api_error_message)): ?>
                        <div class="api-error"><?= esc_html($api_error_message); ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <input type="text"
                            name="manual_api_key"
                            class="api-key-input"
                            placeholder="<?php esc_attr_e('Enter your API key...', 'wp-fce'); ?>"
                            required>
                        <br>
                        <button type="submit" class="api-submit-btn">
                            <?php esc_html_e('Save API Key', 'wp-fce'); ?>
                        </button>
                    </form>

                <?php elseif ($community_user_data): ?>
                    <h3><?php esc_html_e('Community Account Information', 'wp-fce'); ?></h3>
                    <div class="community-user-info">
                        <div class="info-item">
                            <div class="info-label"><?php esc_html_e('User ID', 'wp-fce'); ?></div>
                            <div class="info-value"><?= esc_html($community_user_data['user_id'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><?php esc_html_e('Name', 'wp-fce'); ?></div>
                            <div class="info-value"><?= esc_html($community_user_data['user_name'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><?php esc_html_e('Email', 'wp-fce'); ?></div>
                            <div class="info-value"><?= esc_html($community_user_data['user_email'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><?php esc_html_e('Current Balance', 'wp-fce'); ?></div>
                            <div class="info-value"><?= esc_html($community_user_data['current_balance'] ?? '0'); ?> Credits</div>
                        </div>
                    </div>

                    <?php if (!empty($community_user_data['credentials'])): ?>
                        <h4><?php esc_html_e('API Credentials', 'wp-fce'); ?></h4>
                        <div class="community-user-info">
                            <?php foreach ($community_user_data['credentials'] as $credential): ?>
                                <div class="info-item">
                                    <div class="info-label"><?= esc_html(ucfirst($credential['platform'] ?? 'Unknown')); ?></div>
                                    <div class="info-value">
                                        <?= esc_html(substr($credential['api_key'] ?? '', 0, 20) . '...'); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif (!empty($community_api_key)): ?>
                    <p style="color: #d63384;">
                        <?php esc_html_e('API key is set but unable to retrieve user data. Please check your connection.', 'wp-fce'); ?>
                    </p>

                <?php else: ?>
                    <p><?php esc_html_e('Community API is enabled but not configured for this user.', 'wp-fce'); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($ipns)): ?>
            <table class="fce-orders-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Order Date', 'wp_fce'); ?></th>
                        <th><?php esc_html_e('Product ID', 'wp_fce'); ?></th>
                        <th><?php esc_html_e('Payment Processor', 'wp_fce'); ?></th>
                        <th><?php esc_html_e('Invoice and Details', 'wp_fce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ipns as $ipn):
                        try {
                    ?>
                            <tr>
                                <td><?= esc_html(date_i18n('d.m.Y H:i', $ipn->get_ipn_date())); ?></td>
                                <td><?= esc_html($ipn->get_external_product_id()); ?></td>
                                <td><?= esc_html($ipn->get_source()); ?></td>
                                <td>
                                    <?php
                                    $management_link = $ipn->get_management_link();
                                    if ($management_link):
                                    ?>
                                        <a href="<?= esc_url($management_link); ?>"
                                            target="_blank"
                                            rel="noopener">
                                            <?php esc_html_e('Details', 'wp_fce'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php } catch (\Exception $e) {
                            continue;
                        } ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center;"><?php esc_html_e('No orders found', 'wp_fce'); ?></p>
        <?php endif; ?>

        <div style="text-align: center;">
            <a href="<?= esc_url($back_url) ?>" class="fce-back-btn">
                &larr; <?php esc_html_e('Back to Community', 'wp_fce'); ?>
            </a>
        </div>
    </div>

</body>

</html>