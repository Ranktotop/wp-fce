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
    /** Credentials Table */
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

    /* Slide Animation Styles */
    #transactions-table-wrapper {
        position: relative;
        overflow: hidden;
        min-height: 300px;
    }

    .transactions-slide {
        width: 100%;
        transition: transform 0.3s ease-in-out;
        position: relative;
    }

    .transactions-slide.sliding-out {
        transform: translateX(-100%);
    }

    .transactions-slide.slide-in {
        transform: translateX(100%);
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
    }

    .transactions-slide.slide-in.active {
        transform: translateX(0);
    }

    .transactions-slide.slide-in-left {
        transform: translateX(-100%);
    }

    /* Loading Overlay */
    .transactions-loading-overlay {
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 8px 15px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: #007bff;
        border: 1px solid #e3f2fd;
        z-index: 10;
        opacity: 0;
        transition: opacity 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .transactions-loading-overlay.show {
        opacity: 1;
    }

    .loading-spinner {
        width: 16px;
        height: 16px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #007bff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Disable buttons during loading */
    .pagination-navbar.loading .page-btn:not(.disabled) {
        pointer-events: none;
        opacity: 0.7;
        transition: opacity 0.2s ease;
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
        <!-- Transactions Container (wird per AJAX aktualisiert) -->
        <div id="transactions-container">
            <?php
            // Initial laden der ersten Seite
            $transaction_response = $community_api_helper->fetch_transactions(page: 1, page_size: 10);
            $transactions = $transaction_response["transactions"] ?? [];
            $has_next = $transaction_response["has_next"] ?? false;
            $has_prev = $transaction_response["has_prev"] ?? false;
            $total_pages = $transaction_response["total_pages"] ?? 1;
            $current_page = $transaction_response["page"] ?? 1;
            ?>

            <div id="transactions-table-wrapper">
                <!-- Loading Overlay -->
                <div id="transactions-loading-overlay" class="transactions-loading-overlay">
                    <div class="loading-spinner"></div>
                    <span>Lade...</span>
                </div>

                <div id="transactions-slide-current" class="transactions-slide">
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
                            <tbody id="transactions-tbody">
                                <?php foreach ($transactions as $tx): ?>
                                    <tr>
                                        <td><?php echo esc_html(date('d.m.Y H:i', strtotime($tx['created_at'] ?? ''))); ?></td>
                                        <td><?php echo esc_html(ucfirst($tx['transaction_type'] ?? 'N/A')); ?></td>
                                        <td><?php echo esc_html($tx['amount_credits'] ?? '0'); ?></td>
                                        <td><?php echo get_transaction_details_link($tx['detail_url'] ?? ''); ?></td>
                                        <td><?php echo esc_html($tx['description'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p><?php esc_html_e('You do not have any transactions yet.', 'wp_fce'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pagination Navigation -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination-navbar" id="pagination-nav">
                        <!-- Wird durch JavaScript generiert -->
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <script>
            (function($) {
                'use strict';

                // Pagination State
                let currentPage = <?php echo isset($current_page) ? intval($current_page) : 1; ?>;
                let totalPages = <?php echo isset($total_pages) ? intval($total_pages) : 1; ?>;
                const userId = <?php echo $user->get_id(); ?>;

                /**
                 * Lädt eine spezifische Seite via AJAX mit Slide-Animation
                 */
                function loadTransactionsPage(page) {
                    if (page < 1 || page > totalPages || page === currentPage) {
                        return;
                    }

                    // Bestimme Slide-Richtung
                    const slideDirection = page > currentPage ? 'right' : 'left';

                    // Loading anzeigen
                    showTransactionsLoading();

                    // AJAX Request mit Ihrem bestehenden System
                    const dataJSON = {
                        action: 'wp_fce_handle_public_ajax_callback',
                        func: 'load_community_api_transactions_page_for_user',
                        data: {
                            user_id: userId
                        },
                        meta: {
                            page: page,
                            page_size: 10
                        },
                        _nonce: wp_fce._nonce
                    };

                    $.ajax({
                        cache: false,
                        type: "POST",
                        url: wp_fce.ajax_url,
                        data: dataJSON,
                        success: function(response) {
                            const result = typeof response === 'string' ? JSON.parse(response) : response;

                            if (result.state) {
                                // Slide-Animation mit neuen Daten
                                slideToNewPage(result.transactions || [], slideDirection);

                                // Pagination State aktualisieren
                                currentPage = result.page || page;
                                totalPages = result.total_pages || 1;

                                // Navigation aktualisieren
                                updatePaginationNav();
                            } else {
                                wpfce_show_notice(result.message || 'Fehler beim Laden der Transaktionen', 'error');
                                hideTransactionsLoading();
                            }
                        },
                        error: function(xhr) {
                            wpfce_show_notice('Netzwerkfehler beim Laden der Transaktionen', 'error');
                            hideTransactionsLoading();
                        }
                    });
                }

                /**
                 * Zeigt Loading-Overlay
                 */
                function showTransactionsLoading() {
                    $('#transactions-loading-overlay').addClass('show');
                    $('#pagination-nav').addClass('loading');
                }

                /**
                 * Versteckt Loading-Overlay
                 */
                function hideTransactionsLoading() {
                    $('#transactions-loading-overlay').removeClass('show');
                    $('#pagination-nav').removeClass('loading');
                }

                /**
                 * Slide-Animation zu neuer Seite
                 */
                function slideToNewPage(transactions, direction) {
                    const $wrapper = $('#transactions-table-wrapper');
                    const $currentSlide = $('#transactions-slide-current');

                    // Neue Tabelle erstellen
                    const newTableHTML = createTableHTML(transactions);

                    // Neue Slide erstellen
                    const newSlideId = 'transactions-slide-new';
                    const slideClass = direction === 'right' ? 'slide-in' : 'slide-in slide-in-left';

                    const $newSlide = $(`<div id="${newSlideId}" class="transactions-slide ${slideClass}">${newTableHTML}</div>`);
                    $wrapper.append($newSlide);

                    // Animation starten
                    setTimeout(() => {
                        // Aktuelle Slide raussliden
                        const currentSlideClass = direction === 'right' ? 'sliding-out' : 'sliding-out';
                        $currentSlide.addClass(currentSlideClass);

                        // Neue Slide reinsliden
                        $newSlide.addClass('active');

                        // Nach Animation aufräumen
                        setTimeout(() => {
                            $currentSlide.remove();
                            $newSlide.attr('id', 'transactions-slide-current')
                                .removeClass('slide-in slide-in-left active');
                            hideTransactionsLoading();
                        }, 300); // Match CSS transition duration

                    }, 10); // Kurze Verzögerung für DOM-Update
                }

                /**
                 * Erstellt HTML für Transaktions-Tabelle
                 */
                function createTableHTML(transactions) {
                    if (!transactions || transactions.length === 0) {
                        return '<p><?php esc_html_e('You do not have any transactions yet.', 'wp_fce'); ?></p>';
                    }

                    let tableHTML = `
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
        `;

                    transactions.forEach(function(tx) {
                        const date = new Date(tx.created_at);
                        const formattedDate = date.toLocaleDateString('de-DE', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });

                        const detailsLink = tx.detail_url ?
                            `<a href="${tx.detail_url}" target="_blank" rel="noopener"><?php esc_html_e('View Details', 'wp_fce'); ?></a>` :
                            '<?php esc_html_e('No details available', 'wp_fce'); ?>';

                        tableHTML += `
                <tr>
                    <td>${formattedDate}</td>
                    <td>${tx.transaction_type ? tx.transaction_type.charAt(0).toUpperCase() + tx.transaction_type.slice(1) : 'N/A'}</td>
                    <td>${tx.amount_credits || '0'}</td>
                    <td>${detailsLink}</td>
                    <td>${tx.description || ''}</td>
                </tr>
            `;
                    });

                    tableHTML += '</tbody></table>';
                    return tableHTML;
                }

                /**
                 * Aktualisiert die Pagination Navigation
                 */
                function updatePaginationNav() {
                    const $paginationNav = $('#pagination-nav');
                    if (!$paginationNav.length) return;

                    let navHTML = '';

                    // << Erste Seite
                    if (currentPage > 1) {
                        navHTML += '<button class="page-btn nav-btn" data-page="1" title="Erste Seite">&laquo;</button>';
                    } else {
                        navHTML += '<button class="page-btn nav-btn disabled" title="Erste Seite">&laquo;</button>';
                    }

                    // < Vorherige Seite
                    if (currentPage > 1) {
                        navHTML += `<button class="page-btn nav-btn" data-page="${currentPage - 1}" title="Vorherige Seite">&lt;</button>`;
                    } else {
                        navHTML += '<button class="page-btn nav-btn disabled" title="Vorherige Seite">&lt;</button>';
                    }

                    // Seitenzahlen berechnen
                    const startPage = Math.max(1, currentPage - 3);
                    const endPage = Math.min(totalPages, currentPage + 3);

                    // Vorherige Seiten anzeigen
                    for (let i = startPage; i < currentPage; i++) {
                        navHTML += `<button class="page-btn" data-page="${i}">${i}</button>`;
                    }

                    // Aktuelle Seite
                    navHTML += `<button class="page-btn current">${currentPage}</button>`;

                    // Nächste Seiten anzeigen
                    for (let i = currentPage + 1; i <= endPage; i++) {
                        navHTML += `<button class="page-btn" data-page="${i}">${i}</button>`;
                    }

                    // > Nächste Seite
                    if (currentPage < totalPages) {
                        navHTML += `<button class="page-btn nav-btn" data-page="${currentPage + 1}" title="Nächste Seite">&gt;</button>`;
                    } else {
                        navHTML += '<button class="page-btn nav-btn disabled" title="Nächste Seite">&gt;</button>';
                    }

                    // >> Letzte Seite
                    if (currentPage < totalPages) {
                        navHTML += `<button class="page-btn nav-btn" data-page="${totalPages}" title="Letzte Seite">&raquo;</button>`;
                    } else {
                        navHTML += '<button class="page-btn nav-btn disabled" title="Letzte Seite">&raquo;</button>';
                    }

                    $paginationNav.html(navHTML);

                    // Event Handler für Pagination-Buttons
                    $paginationNav.find('.page-btn:not(.disabled):not(.current)').on('click', function() {
                        const page = parseInt($(this).data('page'));
                        loadTransactionsPage(page);
                    });
                }

                // Pagination initialisieren
                $(document).ready(function() {
                    if (totalPages > 1) {
                        updatePaginationNav();
                    }
                });

            })(jQuery);
        </script>
    <?php endif; ?>

<?php endif; ?>

<?php if ($community_api_helper->get_make_plugin_url() || $community_api_helper->get_n8n_plugin_url() || $community_api_helper->get_documentation_url()): ?>
    <h3><?php esc_html_e('Links/Resources', 'wp_fce'); ?></h3>
<?php endif; ?>
<div class="widgets-stats-container">
    <?php if ($community_api_helper->get_make_plugin_url()): ?>
        <div class="widgets-stat-item-info">
            <span class="widgets-stat-item-number" style="font-size: 1.2em;"><?= esc_html_e('Make.com Plugin', 'wp_fce'); ?></span>
            <span class="widgets-stat-item-label"><?= get_transaction_details_link($community_api_helper->get_make_plugin_url()); ?></span>
        </div>
    <?php endif; ?>
    <?php if ($community_api_helper->get_n8n_plugin_url()): ?>
        <div class="widgets-stat-item-info">
            <span class="widgets-stat-item-number" style="font-size: 1.2em;"><?= esc_html_e('n8n Plugin', 'wp_fce'); ?></span>
            <span class="widgets-stat-item-label"><?= get_transaction_details_link($community_api_helper->get_n8n_plugin_url()); ?></span>
        </div>
    <?php endif; ?>
    <?php if ($community_api_helper->get_documentation_url()): ?>
        <div class="widgets-stat-item-info">
            <span class="widgets-stat-item-number" style="font-size: 1.2em;"><?= esc_html_e('Documentation/Help', 'wp_fce'); ?></span>
            <span class="widgets-stat-item-label"><?= get_transaction_details_link($community_api_helper->get_documentation_url()); ?></span>
        </div>
    <?php endif; ?>
</div>