<?php

/**
 * Payment History Sektion
 * Datei: templates/sections/wp-fce-section-payment-history.php
 * Enthält alle Funktionen und HTML für die Payment History
 */

// ===== FUNKTIONEN FÜR DIESE SEKTION =====


/**
 * Formatiert ein Datum für die Anzeige
 */
function format_payment_date($timestamp)
{
    return date_i18n('d.m.Y H:i', $timestamp);
}

/**
 * Generiert den HTML-Link für Payment Details
 */
function get_payment_details_link($ipn)
{
    $management_link = $ipn->get_management_link();

    if ($management_link) {
        return sprintf(
            '<a href="%s" target="_blank" rel="noopener">%s</a>',
            esc_url($management_link),
            esc_html__('View Details', 'wp-fce')
        );
    }

    return esc_html__('No details available', 'wp-fce');
}

/**
 * Calculates payment statistics from an array of IPN logs.
 * @param WP_FCE_Model_Ipn_Log[] $ipns Array of WP_FCE_Model_Ipn_Log
 * @return array statistics with keys 'total_payments', 'payment_sources', 'recent_payments'.
 */
function get_payment_statistics($ipns)
{
    $stats = [
        'total_payments' => count($ipns),
        'payment_sources' => [],
        'recent_payments' => 0
    ];

    #get datetime 30 days ago
    $thirty_days_ago = new DateTime();
    $thirty_days_ago->modify('-30 days');

    foreach ($ipns as $ipn) {
        // Quellen zählen
        $source = $ipn->get_source();
        $stats['payment_sources'][$source] = ($stats['payment_sources'][$source] ?? 0) + 1;

        // Recent payments zählen
        if ($ipn->get_ipn_date() > $thirty_days_ago) {
            $stats['recent_payments']++;
        }
    }

    return $stats;
}

// ===== DATEN FÜR DIESE SEKTION LADEN =====

//fetch latest ipns from database for current user by email
$ipns = WP_FCE_Helper_Ipn_Log::get_latest_ipns_for_user($user->get_email());

//calculate stats
$payment_stats = get_payment_statistics($ipns);

?>

<h2><?php esc_html_e('Payment History', 'wp-fce'); ?></h2>

<?php if (!empty($ipns)): ?>

    <!-- Payment Statistics -->
    <div class="widgets-stats-container">
        <div class="widgets-stat-item-info">
            <span class="widgets-stat-item-number"><?= $payment_stats['total_payments']; ?></span>
            <span class="widgets-stat-item-label"><?php esc_html_e('Total Payments', 'wp-fce'); ?></span>
        </div>
        <div class="widgets-stat-item-info">
            <span class="widgets-stat-item-number"><?= $payment_stats['recent_payments']; ?></span>
            <span class="widgets-stat-item-label"><?php esc_html_e('Last 30 Days', 'wp-fce'); ?></span>
        </div>
        <div class="widgets-stat-item-info">
            <span class="widgets-stat-item-number"><?= count($payment_stats['payment_sources']); ?></span>
            <span class="widgets-stat-item-label"><?php esc_html_e('Payment Sources', 'wp-fce'); ?></span>
        </div>
    </div>

    <!-- Payment History Table -->
    <div class="fce-table-wrapper">
        <table class="fce-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Order Date', 'wp-fce'); ?></th>
                    <th><?php esc_html_e('Product ID', 'wp-fce'); ?></th>
                    <th class="fce-table__col--hide-mobile"><?php esc_html_e('Payment Processor', 'wp-fce'); ?></th>
                    <th><?php esc_html_e('Invoice and Details', 'wp-fce'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ipns as $ipn): ?>
                    <tr>
                        <td><?= esc_html(format_payment_date($ipn->get_ipn_date())); ?></td>
                        <td><strong><?= esc_html($ipn->get_external_product_id()); ?></strong></td>
                        <td class="fce-table__col--hide-mobile"><?= esc_html($ipn->get_source()); ?></td>
                        <td><?= get_payment_details_link($ipn); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>
    <p><?php esc_html_e('You have not made any payments yet.', 'wp-fce'); ?></p>
<?php endif; ?>