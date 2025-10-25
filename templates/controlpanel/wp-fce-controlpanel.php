<?php

/**
 * Haupt-Template: wp-fce-payments.php
 * Nur Wrapper + Tab-Navigation + dynamisches Laden der Sektionen
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

// Basis-Daten für alle Sektionen
global $wpdb;
$user = WP_FCE_Helper_User::get_by_id(get_current_user_id());

// Sections
$enable_api_section = (get_option('wp_fce_options')['community_api_enabled'] ?? false);

// Hintergrundbild
$bg_image = get_option('wp_fce_options')['orders_background_image'] ?? null;
$bg_image_url = is_array($bg_image) && !empty($bg_image['url'])
    ? $bg_image['url']
    : plugins_url('public/assets/membership_bg.png', dirname(__DIR__));

// Zurück-Button
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$portal_url = WP_FCE_Helper_Options::get_fluent_portal_url();
$back_url = $portal_url ? $portal_url : home_url();
$is_valid_referer = !empty($referer) && $portal_url && str_contains($referer, $portal_url);
if ($is_valid_referer) {
    $referer_host = parse_url($referer, PHP_URL_HOST);
    $site_host = parse_url(home_url(), PHP_URL_HOST);
    if ($referer_host === $site_host) {
        $back_url = $referer;
    }
}

// Template-Verzeichnis
$template_dir = plugin_dir_path(__FILE__);
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title><?php esc_html_e('My Orders', 'wp-fce'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?= esc_url(plugins_url('templates/controlpanel/style.css', dirname(__DIR__))) ?>" media="all">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .back-button {
            margin-top: 30px;
            text-align: center;
        }
    </style>
</head>

<body style="background-image: url('<?= esc_url($bg_image_url); ?>'); background-size: cover;">

    <?php
    // Hinweis-Box
    $notice_path = plugin_dir_path(dirname(__FILE__)) . 'partials/wp-fce-notice.php';
    if (file_exists($notice_path)) {
        include $notice_path;
    } ?>

    <div class="fce-controlpanel-wrapper">
        <h1><?php esc_html_e('User Control Panel', 'wp-fce'); ?></h1>

        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <button class="tab-button active" data-tab="payment-history">
                <?php esc_html_e('Payment History', 'wp-fce'); ?>
            </button>
            <?php if ($enable_api_section): ?>
                <button class="tab-button" data-tab="community-api">
                    <?php esc_html_e('Community API', 'wp-fce'); ?>
                </button>
            <?php endif; ?>
            <!-- Weitere Tabs können hier hinzugefügt werden -->
        </div>

        <!-- Tab Contents -->

        <!-- Payment History Section -->
        <div id="payment-history" class="tab-content active">
            <?php include $template_dir . 'sections/wp-fce-section-payment-history.php'; ?>
        </div>

        <?php if ($enable_api_section): ?>
            <!-- Community API Section -->
            <div id="community-api" class="tab-content">
                <?php include $template_dir . 'sections/wp-fce-section-community-api.php'; ?>
            </div>
        <?php endif; ?>

        <!-- Back Button -->
        <div class="back-button">
            <a href="<?= esc_url($back_url); ?>" class="back-link">
                ← <?php esc_html_e('Back to Community', 'wp-fce'); ?>
            </a>
        </div>
    </div>

    <!-- jQuery -->


    <!-- Tab System JavaScript -->
    <script>
        $(document).ready(function() {
            // Tab Switching Logic
            $('.tab-button').on('click', function() {
                const targetTab = $(this).data('tab');

                // Remove active class from all tabs and contents
                $('.tab-button').removeClass('active');
                $('.tab-content').removeClass('active');

                // Add active class to clicked tab and corresponding content
                $(this).addClass('active');
                $('#' + targetTab).addClass('active');

                // Trigger section-specific initialization if needed
                $(document).trigger('tabSwitched', [targetTab]);
            });
        });

        // WP FCE Global Variables for AJAX
        var wp_fce = {
            ajax_url: '<?= admin_url('admin-ajax.php'); ?>',
            _nonce: '<?= wp_create_nonce('security_wp-fce'); ?>'
        };
    </script>

</body>

</html>