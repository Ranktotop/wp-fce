<?php
// templates/partials/html-modal-confirm.php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="wpfce-confirm-modal" class="wpfce-modal hidden">
    <div class="wpfce-modal-overlay"></div>
    <div class="wpfce-modal-content">
        <h2><?php esc_html_e('Bist du sicher?', 'wp-fce'); ?></h2>
        <p></p>
        <div class="wpfce-modal-actions">
            <button class="button-secondary wpfce-cancel-btn"><?php esc_html_e('Abbrechen', 'wp-fce'); ?></button>
            <button class="button-primary wpfce-confirm-btn"><?php esc_html_e('Bestätigen', 'wp-fce'); ?></button>
        </div>
    </div>
</div>