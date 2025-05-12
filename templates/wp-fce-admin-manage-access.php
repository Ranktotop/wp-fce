<?php
if (!defined('ABSPATH') || !is_user_logged_in() || !current_user_can('manage_options')) {
    wp_redirect(home_url());
    exit;
}

$user_helper = new WP_FCE_Helper_User();
$product_helper = new WP_FCE_Helper_Product();
$access_helper = new WP_FCE_Helper_Access_Override();

$users = $user_helper->get_all();
$products = $product_helper->get_all();
$rules = $access_helper->get_all();
//sort by login name
usort($rules, function ($a, $b) {
    $userA = WP_FCE_Model_User::load_by_id($a->get_user_id());
    $userB = WP_FCE_Model_User::load_by_id($b->get_user_id());

    return strcasecmp($userA->get_login(), $userB->get_login());
});
?>

<div class="wrap fce-admin-page">
    <h1 class="wp-heading-inline"><?php esc_html_e('Produktverwaltung', 'wp-fce'); ?></h1>

    <div class="fce-admin-sections">
        <!-- Bestehende Produkte -->
        <div class="fce-section fce-section-list">
            <h2><?php esc_html_e('Bestehende Regeln', 'wp-fce'); ?></h2>
            <p class="description">
                <?php esc_html_e('Verwalte hier bestehende Regeln zum Produkt-Zugang.', 'wp-fce'); ?>
            </p>

            <div id="fce-rules-list">
                <?php if (empty($rules)) : ?>
                    <p><?php esc_html_e('Keine Regeln vorhanden.', 'wp-fce'); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Benutzer', 'wp-fce'); ?></th>
                                <th><?php esc_html_e('Produkt', 'wp-fce'); ?></th>
                                <th><?php esc_html_e('Zugriffsmodus', 'wp-fce'); ?></th>
                                <th><?php esc_html_e('Gültig bis', 'wp-fce'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rules as $rule) :
                                $product = $rule->get_product();
                                $user = $rule->get_user();
                            ?>
                                <tr data-value="rule_id=<?php echo esc_attr($rule->get_id()); ?>">
                                    <td><?php echo esc_html($user->get_login() . ' (' . $user->get_email() . ')'); ?></td>
                                    <td><?php echo esc_html($product->get_title()); ?></td>
                                    <td>
                                        <select name="fce_rule_mode[<?php echo (int) $rule->get_id(); ?>]" disabled>
                                            <option value="grant" <?php selected($rule->get_mode(), 'grant'); ?>>
                                                <?php _e('Zugriff gewähren', 'wp-fce'); ?>
                                            </option>
                                            <option value="deny" <?php selected($rule->get_mode(), 'deny'); ?>>
                                                <?php _e('Zugriff sperren', 'wp-fce'); ?>
                                            </option>
                                        </select>
                                    </td>
                                    <td>
                                        <input
                                            type="datetime-local"
                                            name="valid_until[<?php echo (int) $rule->get_id(); ?>]"
                                            value="<?php echo esc_attr(date('Y-m-d\TH:i', $rule->get_valid_until())); ?>"
                                            disabled
                                            required>
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="button button-primary wpfce_edit_access_rule_btn">
                                            <?php esc_html_e('Bearbeiten', 'wp-fce'); ?>
                                        </button>
                                        <button
                                            type="button"
                                            class="button button-primary delete wpfce_delete_access_rule_btn">
                                            ✕ <?php esc_html_e('Löschen', 'wp-fce'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <hr>

        <!-- Neues Produkt anlegen -->
        <div class="fce-section fce-section-create">
            <h2><?php esc_html_e('Neue Regel hinzufügen', 'wp-fce'); ?></h2>
            <p class="description">
                <?php esc_html_e('Lege eine Regel an, die festlegt ob er Zugang erlaubt oder verboten ist.', 'wp-fce'); ?>
            </p>

            <form method="post" action="">
                <input type="hidden" name="wp_fce_form_action" value="create_override">
                <?php wp_nonce_field('wp_fce_create_override', 'wp_fce_nonce'); ?>

                <table class="form-table">
                    <tbody>
                        <tr>
                            <th><label for="user_id"><?php _e('Benutzer', 'wp-fce'); ?></label></th>
                            <td>
                                <select name="user_id" id="user_id" required>
                                    <option value=""><?php _e('Benutzer wählen…', 'wp-fce'); ?></option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?= esc_attr($user->get_id()); ?>">
                                            <?= esc_html($user->get_login() . ' (' . $user->get_email() . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="product_id"><?php _e('Produkt', 'wp-fce'); ?></label></th>
                            <td>
                                <select name="product_id" id="product_id" required>
                                    <option value=""><?php _e('Produkt wählen…', 'wp-fce'); ?></option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?= esc_attr($product->get_id()); ?>">
                                            <?= esc_html($product->get_title()); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="mode"><?php _e('Zugriffsmodus', 'wp-fce'); ?></label></th>
                            <td>
                                <select name="mode" id="mode" required>
                                    <option value="grant"><?php _e('Zugriff gewähren', 'wp-fce'); ?></option>
                                    <option value="deny"><?php _e('Zugriff sperren', 'wp-fce'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="valid_until"><?php _e('Gültig bis', 'wp-fce'); ?></label></th>
                            <td>
                                <input type="datetime-local" name="valid_until" id="valid_until" required>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button(__('Überschreibung speichern', 'wp-fce')); ?>
            </form>
        </div>
    </div>
</div>