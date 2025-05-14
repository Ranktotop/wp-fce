<?php
if (!is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

global $wpdb;
$user_id = get_current_user_id();
$user        = WP_FCE_Helper_User::get_by_id($user_id);
$user_email  = $user->get_email();

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

        <?php if (!empty($ipns)): ?>
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