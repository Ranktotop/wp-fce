<?php
if (!is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

global $wpdb;
$user_id = get_current_user_id();
$table = $wpdb->prefix . 'fce_management_links';

// Get background image
$bg_image_url = function_exists('carbon_get_theme_option')
    ? carbon_get_theme_option('fce_orders_bg_image')
    : '';
if (empty($bg_image_url)) {
    $bg_image_url = plugins_url('wp-fce/public/assets/membership_bg.png', dirname(__DIR__));
}


// Set back url
$back_url = home_url();

if (!empty($_SERVER['HTTP_REFERER'])) {
    $referer_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    $site_host = parse_url(home_url(), PHP_URL_HOST);

    // Sicherheitscheck: nur zurückspringen, wenn Referrer von der gleichen Domain kommt
    if ($referer_host === $site_host) {
        $back_url = $_SERVER['HTTP_REFERER'];
    }
}

$links = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC",
    $user_id
));
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title><?php esc_html_e('Meine Bestellungen', 'wp_fce'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?= esc_url(plugins_url('wp-fce/public/css/fce-orders.css', dirname(__DIR__))) ?>" media="all">
</head>

<body style="background-image: url('<?= esc_url($bg_image_url); ?>'); background-size: cover;">

    <div class="fce-orders-wrapper">
        <h1><?php esc_html_e('Meine Bestellungen', 'wp_fce'); ?></h1>

        <?php if (!empty($links)): ?>
            <table class="fce-orders-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Bestell-Datum', 'wp_fce'); ?></th>
                        <th><?php esc_html_e('Produkt-ID', 'wp_fce'); ?></th>
                        <th><?php esc_html_e('Zahlungsanbieter', 'wp_fce'); ?></th>
                        <th><?php esc_html_e('Zahlung & Rechnung', 'wp_fce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $link): ?>
                        <tr>
                            <td><?= esc_html(date_i18n('d.m.Y H:i', strtotime($link->created_at))); ?></td>
                            <td><?= esc_html($link->product_id); ?></td>
                            <td><?= esc_html($link->source); ?></td>
                            <td><a href="<?= esc_url($link->management_url); ?>" target="_blank" rel="noopener"><?php esc_html_e('Details', 'wp_fce'); ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center;"><?php esc_html_e('Keine Bestellungen gefunden.', 'wp_fce'); ?></p>
        <?php endif; ?>

        <div style="text-align: center;">
            <a href="<?= esc_url($back_url) ?>" class="fce-back-btn">
                &larr; <?php esc_html_e('Zurück zur Community', 'wp_fce'); ?>
            </a>
        </div>
    </div>

</body>

</html>