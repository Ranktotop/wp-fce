<?php

/**
 * Community API Sektion
 * Datei: templates/sections/wp-fce-section-community-api.php
 * Enthält alle Funktionen und HTML für die Community API Integration
 */


//Init helper
$community_api_helper = new WP_FCE_Helper_Community_API($user);

/**
 * Formatiert Credentials für die Anzeige
 */
function community_api_format_credentials($credentials)
{
    if (empty($credentials) || !is_array($credentials)) {
        return [];
    }

    return array_map(function ($cred) {
        return [
            'platform' => ucfirst($cred['platform'] ?? 'Unknown'),
            'api_key_display' => substr($cred['api_key'] ?? '', 0, 20) . '...',
            'full_key' => $cred['api_key'] ?? ''
        ];
    }, $credentials);
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
    <?php endif; ?>

<?php endif; ?>

<script>
    $(document).ready(function() {
        // Community API specific JavaScript
        console.log('Community API section loaded');

        class CommunityAPIManager {
            constructor() {
                this.bindEvents();
            }

            bindEvents() {
                // Auto-setup button
                $('#auto-setup-api-key').on('click', () => {
                    this.autoSetupApiKey();
                });

                // Remove API Key button
                $('#remove-api-key').on('click', () => {
                    this.removeApiKey();
                });

                // Refresh data button
                $('#refresh-community-data').on('click', () => {
                    this.refreshCommunityData();
                });
            }

            saveManualApiKey() {
                const apiKey = $('#manual-api-key').val().trim();

                if (!apiKey) {
                    this.showMessage('Please enter an API key', 'error');
                    return;
                }

                this.showLoading('Validating API key...');

                this.makeAjaxCall('save_community_api_key', {
                        api_key: apiKey
                    })
                    .then((response) => {
                        if (response.state) {
                            this.showMessage(response.message, 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            this.showMessage(response.message, 'error');
                        }
                    })
                    .catch(() => {
                        this.showMessage('Connection error. Please try again.', 'error');
                    })
                    .finally(() => {
                        this.hideLoading();
                    });
            }

            autoSetupApiKey() {
                this.showLoading('Searching for your account...');

                this.makeAjaxCall('auto_set_community_api_key', {})
                    .then((response) => {
                        if (response.state) {
                            this.showMessage(response.message, 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            this.showMessage(response.message, 'info');
                            $('#community-api-manual-input').show();
                            $('#community-api-auto-setup').hide();
                        }
                    })
                    .catch(() => {
                        this.showMessage('Connection error. Please try again.', 'error');
                        $('#community-api-manual-input').show();
                        $('#community-api-auto-setup').hide();
                    })
                    .finally(() => {
                        this.hideLoading();
                    });
            }

            removeApiKey() {
                if (!confirm('Are you sure you want to remove your API key?')) {
                    return;
                }

                this.showLoading('Removing API key...');

                this.makeAjaxCall('remove_community_api_key', {})
                    .then((response) => {
                        if (response.state) {
                            this.showMessage(response.message, 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            this.showMessage(response.message, 'error');
                        }
                    })
                    .catch(() => {
                        this.showMessage('Connection error. Please try again.', 'error');
                    })
                    .finally(() => {
                        this.hideLoading();
                    });
            }

            refreshCommunityData() {
                this.showLoading('Refreshing data...');

                this.makeAjaxCall('get_community_user_data', {})
                    .then((response) => {
                        if (response.state) {
                            this.updateUserDataDisplay(response.data);
                            this.showMessage(response.message, 'success');
                        } else {
                            this.showMessage(response.message, 'error');
                            if (response.message.includes('removed')) {
                                setTimeout(() => location.reload(), 2000);
                            }
                        }
                    })
                    .catch(() => {
                        this.showMessage('Connection error. Please try again.', 'error');
                    })
                    .finally(() => {
                        this.hideLoading();
                    });
            }

            makeAjaxCall(func, data) {
                return $.ajax({
                    url: wp_fce.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wp_fce_handle_ajax_callback',
                        func: func,
                        data: data,
                        meta: {},
                        _nonce: wp_fce._nonce
                    }
                }).then((response) => {
                    return typeof response === 'string' ? JSON.parse(response) : response;
                });
            }

            updateUserDataDisplay(userData) {
                if (userData.user_name) {
                    $('#community-user-name').text(userData.user_name);
                }
                if (userData.current_balance !== undefined) {
                    $('#community-user-balance').html('<strong>' + userData.current_balance + '</strong> Credits');
                }
                if (userData.credentials) {
                    this.updateCredentialsDisplay(userData.credentials);
                }
            }

            updateCredentialsDisplay(credentials) {
                const container = $('#community-credentials');
                if (container.length === 0) return;

                container.empty();
                credentials.forEach((cred) => {
                    const truncatedKey = cred.api_key ?
                        cred.api_key.substring(0, 20) + '...' :
                        'N/A';

                    container.append(`
                    <div class="credential-item">
                        <span class="credential-platform">${this.capitalize(cred.platform || 'Unknown')}</span>
                        <span class="credential-key">${truncatedKey}</span>
                    </div>
                `);
                });
            }

            showMessage(message, type = 'info') {
                const messageClass = 'api-' + type;
                const messageHtml = `<div class="api-message ${messageClass}">${message}</div>`;

                // Remove existing messages
                $('.api-message').remove();

                // Add new message
                $('#community-api-messages').html(messageHtml);

                // Auto-hide success messages
                if (type === 'success') {
                    setTimeout(() => {
                        $('.api-success').fadeOut();
                    }, 3000);
                }
            }

            showLoading(message = 'Loading...') {
                $('#community-api-loading').html(`
                <div class="api-message api-loading">
                    <span class="spinner"></span> ${message}
                </div>
            `).show();
            }

            hideLoading() {
                $('#community-api-loading').hide();
            }

            capitalize(str) {
                return str.charAt(0).toUpperCase() + str.slice(1);
            }
        }

        // Initialize Community API Manager
        const communityAPI = new CommunityAPIManager();

        // Event listener für Tab-Wechsel
        $(document).on('tabSwitched', function(event, tabName) {
            if (tabName === 'community-api') {
                console.log('Community API tab activated');
                // Hier können spezifische Aktionen für diese Sektion ausgeführt werden
            }
        });
    });
</script>