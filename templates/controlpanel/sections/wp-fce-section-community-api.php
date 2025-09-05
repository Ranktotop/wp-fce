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
    .community-api-content {
        max-width: 800px;
    }

    .api-setup-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #0073aa;
    }

    .api-key-input {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin: 10px 0;
        font-family: monospace;
        font-size: 14px;
    }

    .api-button {
        background: #0073aa;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        margin-right: 10px;
        margin-bottom: 5px;
        transition: background 0.3s ease;
    }

    .api-button:hover {
        background: #005a87;
        color: white;
        text-decoration: none;
    }

    .api-button.secondary {
        background: #6c757d;
    }

    .api-button.secondary:hover {
        background: #5a6268;
    }

    .api-button.danger {
        background: #dc3545;
    }

    .api-button.danger:hover {
        background: #c82333;
    }

    .api-message {
        padding: 12px;
        border-radius: 4px;
        margin: 10px 0;
    }

    .api-error {
        color: #d63384;
        background: #f8d7da;
        border-left: 4px solid #dc3545;
    }

    .api-success {
        color: #2e7d32;
        background: #e8f5e8;
        border-left: 4px solid #4caf50;
    }

    .api-info {
        color: #1976d2;
        background: #e3f2fd;
        border-left: 4px solid #2196f3;
    }

    .api-loading {
        background: #e3f2fd;
        color: #1976d2;
        text-align: center;
    }

    .spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid #1976d2;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .community-user-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin: 20px 0;
    }

    .info-item {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 6px;
        border-left: 4px solid #0073aa;
    }

    .info-label {
        font-weight: bold;
        color: #555;
        font-size: 0.9em;
        margin-bottom: 5px;
    }

    .info-value {
        font-size: 1.1em;
        color: #333;
    }

    .credentials-list {
        background: white;
        border: 1px solid #ddd;
        border-radius: 6px;
        overflow: hidden;
    }

    .credential-item {
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .credential-item:last-child {
        border-bottom: none;
    }

    .credential-platform {
        font-weight: 600;
        color: #0073aa;
    }

    .credential-key {
        font-family: monospace;
        color: #666;
        font-size: 0.9em;
    }
</style>

<h2><?php esc_html_e('Community API', 'wp_fce'); ?></h2>

<!-- Messages Container -->
<div id="community-api-messages"></div>
<div id="community-api-loading" style="display: none;"></div>

<?php if (!$community_api_helper->has_api_key()): ?>

    <!-- Setup Section -->
    <div class="widgets-stats-container">
        <div class="widgets-stat-item-info">
            <h3><?php esc_html_e('Connect Your Community Account', 'wp_fce'); ?></h3>
            <!-- Manual Input Section -->
            <div id="community-api-manual-input">
                <p><?php esc_html_e('Please enter your Community API key:', 'wp_fce'); ?></p>

                <form id="community-api-form" method="post" action="">
                    <input type="hidden" name="wp_fce_form_action" value="set_community_api_key">
                    <?php wp_nonce_field('wp_fce_set_community_api_key', 'wp_fce_nonce'); ?>
                    <input type="hidden" name="community_api_user_id" value="<?= esc_attr($user->get_id()); ?>">
                    <input type="text" name="community_api_key" placeholder="<?php esc_attr_e('Enter your API key...', 'wp-fce'); ?>" required>
                    <button type="submit" class="api-button">
                        <?php esc_html_e('Save API Key', 'wp_fce'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

<?php else: ?>

    <!-- User Data Display -->
    <?php if ($api_status['user_data']): ?>
        <div class="api-setup-section">
            <h3><?php esc_html_e('Community Account Information', 'wp_fce'); ?></h3>

            <div class="community-user-info">
                <div class="info-item">
                    <div class="info-label"><?php esc_html_e('User ID', 'wp_fce'); ?></div>
                    <div class="info-value"><?= esc_html($api_status['user_data']['user_id'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label"><?php esc_html_e('Name', 'wp_fce'); ?></div>
                    <div class="info-value" id="community-user-name"><?= esc_html($api_status['user_data']['user_name'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label"><?php esc_html_e('Email', 'wp_fce'); ?></div>
                    <div class="info-value"><?= esc_html($api_status['user_data']['user_email'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label"><?php esc_html_e('Current Balance', 'wp_fce'); ?></div>
                    <div class="info-value" id="community-user-balance">
                        <strong><?= esc_html($api_status['user_data']['current_balance'] ?? '0'); ?></strong> Credits
                    </div>
                </div>
            </div>

            <?php
            $formatted_credentials = community_api_format_credentials($api_status['user_data']['credentials'] ?? []);
            if (!empty($formatted_credentials)):
            ?>
                <h4><?php esc_html_e('API Credentials', 'wp_fce'); ?></h4>
                <div class="credentials-list" id="community-credentials">
                    <?php foreach ($formatted_credentials as $credential): ?>
                        <div class="credential-item">
                            <span class="credential-platform"><?= esc_html($credential['platform']); ?></span>
                            <span class="credential-key"><?= esc_html($credential['api_key_display']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div style="margin-top: 20px;">
                <button type="button" class="api-button" id="refresh-community-data">
                    <?php esc_html_e('Refresh Data', 'wp_fce'); ?>
                </button>
                <button type="button" class="api-button danger" id="remove-api-key">
                    <?php esc_html_e('Remove API Key', 'wp_fce'); ?>
                </button>
            </div>
        </div>

    <?php else: ?>
        <div class="api-message api-error">
            <?php esc_html_e('Unable to load community data. Your API key might be invalid.', 'wp_fce'); ?>
        </div>
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
                // Manual API Key Form
                $('#community-api-form').on('submit', (e) => {
                    e.preventDefault();
                    this.saveManualApiKey();
                });

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