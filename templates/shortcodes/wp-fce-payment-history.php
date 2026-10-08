<?php

/**
 * Template for the [wp_fce_payment_history] shortcode.
 *
 * Available variables:
 * @var WP_FCE_Model_Ipn_Log[] $ipns          Latest IPN per product of the current user
 * @var array                  $payment_stats Keys 'total_payments', 'recent_payments', 'payment_sources'
 * @var string|false           $customer_account_url FluentCart customer account URL, if the user has FluentCart purchases
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wp-fce-payment-history">
    <?php if ($customer_account_url): ?>
        <div class="wp-fce-customer-account-notice">
            <p><?php esc_html_e('Your purchases from our shop, including invoices and subscriptions, can be found in your customer account.', 'wp-fce'); ?></p>
            <a class="wp-fce-button" href="<?= esc_url($customer_account_url); ?>"><?php esc_html_e('Go to my customer account', 'wp-fce'); ?></a>
        </div>
    <?php endif; ?>

    <?php if (!empty($ipns)): ?>

        <!-- Payment Statistics -->
        <div class="widgets-stats-container">
            <div class="widgets-stat-item-info">
                <span class="widgets-stat-item-number"><?= esc_html($payment_stats['total_payments']); ?></span>
                <span class="widgets-stat-item-label"><?php esc_html_e('Total Payments', 'wp-fce'); ?></span>
            </div>
            <div class="widgets-stat-item-info">
                <span class="widgets-stat-item-number"><?= esc_html($payment_stats['recent_payments']); ?></span>
                <span class="widgets-stat-item-label"><?php esc_html_e('Last 30 Days', 'wp-fce'); ?></span>
            </div>
            <div class="widgets-stat-item-info">
                <span class="widgets-stat-item-number"><?= esc_html(count($payment_stats['payment_sources'])); ?></span>
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
                        <?php $management_link = $ipn->get_management_link(); ?>
                        <tr>
                            <td><?= esc_html(wp_date('d.m.Y H:i',$ipn->get_ipn_date()->getTimestamp())); ?></td>
                            <td><strong><?= esc_html($ipn->get_external_product_id()); ?></strong></td>
                            <td class="fce-table__col--hide-mobile"><?= esc_html($ipn->get_source()); ?></td>
                            <td>
                                <?php if ($management_link): ?>
                                    <a href="<?= esc_url($management_link); ?>" target="_blank" rel="noopener"><?php esc_html_e('View Details', 'wp-fce'); ?></a>
                                <?php else: ?>
                                    <?php esc_html_e('No details available', 'wp-fce'); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php elseif (!$customer_account_url): ?>
        <p><?php esc_html_e('You have not made any payments yet.', 'wp-fce'); ?></p>
    <?php endif; ?>
</div>
