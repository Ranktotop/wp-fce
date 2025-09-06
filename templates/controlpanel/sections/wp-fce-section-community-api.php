<?php

/**
 * Community API Sektion
 * Datei: templates/sections/wp-fce-section-community-api.php
 * Enthält alle Funktionen und HTML für die Community API Integration
 */


//Init helper
$community_api_helper = new WP_FCE_Helper_Community_API($user);

/**
 * Generiert den HTML-Link für Transaction Details
 */
function get_transaction_details_link($management_link)
{
    if ($management_link) {
        return sprintf(
            '<a href="%s" target="_blank" rel="noopener">%s</a>',
            esc_url($management_link),
            esc_html__('View Details', 'wp_fce')
        );
    }

    return esc_html__('No details available', 'wp_fce');
}
?>

<style>
    .credentials-platform {
        font-weight: 600;
        text-transform: capitalize;
    }

    .credentials-input-cell {
        padding: 10px 15px !important;
    }

    .credentials-table-input {
        width: 100%;
        margin-right: 0;
        padding: 8px;
    }

    .credentials-save-container {
        margin-top: 25px;
        text-align: center;
    }
</style>

<h2><?php esc_html_e('Community API', 'wp_fce'); ?></h2>

<!-- Messages Container -->
<div id="community-api-messages"></div>
<div id="community-api-loading" style="display: none;"></div>

<?php if (!$community_api_helper->has_api_key() || !$community_api_helper->get_user_data()): ?>
    <!-- Setup Section -->
    <div class="widgets-stats-container">
        <div class="widgets-stat-item-<?php echo $community_api_helper->has_api_key() ? 'error' : 'info'; ?>">
            <h3><?php esc_html_e('Connect Your Community Account', 'wp_fce'); ?></h3>
            <!-- Manual Input Section -->
            <div id="community-api-manual-input">
                <?php if ($community_api_helper->has_api_key()): ?>
                    <p class="error"><?php esc_html_e('API Key seems to be invalid! Please enter a valid one!', 'wp_fce'); ?></p>
                <?php else: ?>
                    <p><?php esc_html_e('Please enter your Community API key:', 'wp_fce'); ?></p>
                <?php endif; ?>

                <form id="community-api-form" method="post" action="">
                    <input type="hidden" name="wp_fce_form_action" value="set_community_api_key">
                    <?php wp_nonce_field('wp_fce_set_community_api_key', 'wp_fce_nonce'); ?>
                    <input type="hidden" name="community_api_user_id" value="<?= esc_attr($user->get_id()); ?>">
                    <input type="text" name="community_api_key" style="width:50%; text-align:center;" placeholder="<?php esc_attr_e('Enter your API key...', 'wp-fce'); ?>" required>
                    <br>
                    <button type="submit" class="default_button">
                        <?php esc_html_e('Save API Key', 'wp_fce'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

<?php else: ?>

    <!-- User Data Display -->
    <?php if ($community_api_helper->get_user_data()): ?>

        <h3><?php esc_html_e('User Infos', 'wp_fce'); ?></h3>
        <div class="widgets-stats-container">

            <div class="widgets-stat-item-info">
                <div class="widgets-stat-item-number"><?php esc_html_e('User ID', 'wp_fce'); ?></div>
                <div class="widgets-stat-item-label"><?= esc_html($community_api_helper->get_user_data()['user_id'] ?? 'N/A'); ?></div>
            </div>

            <div class="widgets-stat-item-info">
                <div class="widgets-stat-item-number"><?php esc_html_e('Name', 'wp_fce'); ?></div>
                <div class="widgets-stat-item-label"><?= esc_html($community_api_helper->get_user_data()['user_name'] ?? 'N/A'); ?></div>
            </div>

            <div class="widgets-stat-item-info">
                <div class="widgets-stat-item-number"><?php esc_html_e('Email', 'wp_fce'); ?></div>
                <div class="widgets-stat-item-label"><?= esc_html($community_api_helper->get_user_data()['user_email'] ?? 'N/A'); ?></div>
            </div>

            <?php $current_balance = $community_api_helper->get_user_data()['current_balance'] ?? 0; ?>
            <div class="widgets-stat-item-<?php echo $current_balance > 0 ? 'info' : 'error'; ?>">
                <div class="widgets-stat-item-number"><?php esc_html_e('Balance', 'wp_fce'); ?></div>
                <div class="widgets-stat-item-label"><?= esc_html($current_balance); ?> Credits</div>
            </div>
        </div>
        <h3><?php esc_html_e('Credentials', 'wp_fce'); ?></h3>
        <div class="credentials-container">
            <form method="post" action="">
                <input type="hidden" name="wp_fce_form_action" value="set_community_api_credentials">
                <?php wp_nonce_field('wp_fce_set_community_api_credentials', 'wp_fce_nonce'); ?>
                <input type="hidden" name="community_api_user_id" value="<?= esc_attr($user->get_id()); ?>">
                <table class="fce-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Platform', 'wp_fce'); ?></th>
                            <th><?php esc_html_e('API Key', 'wp_fce'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($community_api_helper->get_credentials() as $platform => $api_key): ?>
                            <tr>
                                <td class="credentials-platform"><?php echo esc_html($platform); ?></td>
                                <td class="credentials-input-cell">
                                    <input type="text"
                                        name="credentials[<?php echo esc_attr($platform); ?>]"
                                        value="<?php echo esc_attr($api_key); ?>"
                                        placeholder="Enter <?php echo esc_attr($platform); ?> API Key"
                                        class="credentials-table-input">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="credentials-save-container">
                    <button type="submit" name="save_credentials" class="default_button">
                        Save Credentials
                    </button>
                </div>
            </form>
        </div>
        <h3><?php esc_html_e('Transactions', 'wp_fce'); ?></h3>
        <?php

        $transaction_response = $community_api_helper->fetch_transactions();
        $transactions = $transaction_response["transactions"];
        $has_next = $transaction_response["has_next"];
        $has_prev = $transaction_response["has_prev"];
        $total_pages = $transaction_response["total_pages"];

        ?>

        <?php if (!empty($transactions)): ?>
            <table class="fce-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Transaction Date', 'wp_fce'); ?></th>
                        <th><?php esc_html_e('Type', 'wp_fce'); ?></th>
                        <th><?php esc_html_e('Credits', 'wp_fce'); ?></th>
                        <th><?php esc_html_e('Invoice and Details', 'wp_fce'); ?></th>
                        <th><?php esc_html_e('Description', 'wp_fce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $tx): ?>
                        <tr>
                            <td><?php echo esc_html(date('d.m.Y H:i', strtotime($tx['created_at'] ?? ''))); ?></td>
                            <td><?php echo esc_html(ucfirst($tx['transaction_type'] ?? 'N/A')); ?></td>
                            <td><?php echo esc_html($tx['amount_credits'] ?? '0'); ?></td>
                            <td><?php get_transaction_details_link($tx['detail_url'] ?? ''); ?></td>
                            <td><?php echo esc_html($tx['description'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php else: ?>
            <p><?php esc_html_e('You do not have any transactions yet.', 'wp_fce'); ?></p>
        <?php endif; ?>
    <?php endif; ?>

<?php endif; ?>