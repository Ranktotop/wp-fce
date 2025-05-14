<?php
if (!defined('ABSPATH') || !is_user_logged_in() || !current_user_can('manage_options')) {
    wp_redirect(home_url());
    exit;
}

$users = WP_FCE_Helper_User::get_all();
$products = WP_FCE_Helper_Product::get_all();
$rules = WP_FCE_Helper_Access_Override::get_all();

//sort by login name
usort($rules, function ($a, $b) {
    $userA = WP_FCE_Model_User::load_by_id($a->get_user_id());
    $userB = WP_FCE_Model_User::load_by_id($b->get_user_id());

    return strcasecmp($userA->get_login(), $userB->get_login());
});
?>

<div class="wrap fce-admin-page">
    <h1 class="wp-heading-inline"><?php esc_html_e('Access Management', 'wp-fce'); ?></h1>

    <div class="fce-admin-sections">
        <!-- Bestehende Produkte -->
        <div class="fce-section fce-section-list">
            <h2><?php esc_html_e('Existing Rules', 'wp-fce'); ?></h2>
            <p class="description">
                <?php esc_html_e('Manage access rules for existing users and products', 'wp-fce'); ?>
            </p>

            <div id="fce-rules-list">
                <?php if (empty($rules)) : ?>
                    <p><?php esc_html_e('No rules found', 'wp-fce'); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('User', 'wp-fce'); ?></th>
                                <th><?php esc_html_e('Product', 'wp-fce'); ?></th>
                                <th><?php esc_html_e('Access/Type', 'wp-fce'); ?></th>
                                <th><?php esc_html_e('Valid until', 'wp-fce'); ?></th>
                                <th><?php esc_html_e('Comment', 'wp-fce'); ?></th>
                                <th><?php esc_html_e('Action', 'wp-fce'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rules as $rule) :
                                $product = $rule->get_product();
                                $user = $rule->get_user();
                            ?>
                                <tr data-value="rule_id=<?php echo esc_attr($rule->get_id()); ?>">
                                    <td><?php echo esc_html($user->get_login() . ' (' . $user->get_email() . ')'); ?></td>
                                    <td><?php echo esc_html($product->get_name()); ?></td>
                                    <td>
                                        <select name="fce_rule_mode[<?php echo (int) $rule->get_id(); ?>]" disabled>
                                            <option value="allow" <?php selected($rule->get_override_type(), 'allow'); ?>>
                                                <?php _e('Grant Access', 'wp-fce'); ?>
                                            </option>
                                            <option value="deny" <?php selected($rule->get_override_type(), 'deny'); ?>>
                                                <?php _e('Revoke Access', 'wp-fce'); ?>
                                            </option>
                                        </select>
                                    </td>
                                    <td>
                                        <?php
                                        $vu = $rule->get_valid_until();
                                        // Wenn kein Datum gesetzt, leer lassen
                                        $vu_str = $vu instanceof \DateTime
                                            ? $vu->format('Y-m-d\TH:i')
                                            : '';
                                        ?>
                                        <input
                                            type="datetime-local"
                                            name="valid_until[<?php echo (int) $rule->get_id(); ?>]"
                                            value="<?php echo esc_attr($vu_str); ?>"
                                            disabled
                                            required>
                                    </td>
                                    <td>
                                        <input
                                            type="text"
                                            name="comment[<?php echo (int) $rule->get_id(); ?>]"
                                            value="<?php echo esc_attr($rule->get_comment()); ?>"
                                            disabled>
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="button button-primary wpfce_edit_access_rule_btn">
                                            <?php esc_html_e('Edit', 'wp-fce'); ?>
                                        </button>
                                        <button
                                            type="button"
                                            class="button button-primary delete wpfce_delete_access_rule_btn">
                                            ✕ <?php esc_html_e('Delete', 'wp-fce'); ?>
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
            <h2><?php esc_html_e('Add Rule', 'wp-fce'); ?></h2>
            <p class="description">
                <?php esc_html_e('Manage access rules for existing users and products', 'wp-fce'); ?>
            </p>

            <form method="post" action="">
                <input type="hidden" name="wp_fce_form_action" value="create_override">
                <?php wp_nonce_field('wp_fce_create_override', 'wp_fce_nonce'); ?>

                <table class="form-table">
                    <tbody>
                        <tr>
                            <th><label for="user_id"><?php _e('User', 'wp-fce'); ?></label></th>
                            <td>
                                <select name="user_id" id="user_id" required>
                                    <option value="">-- <?php _e('Choose User', 'wp-fce'); ?> --</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?= esc_attr($user->get_id()); ?>">
                                            <?= esc_html($user->get_login() . ' (' . $user->get_email() . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="product_id"><?php _e('Product', 'wp-fce'); ?></label></th>
                            <td>
                                <select name="product_id" id="product_id" required>
                                    <option value="">-- <?php _e('Choose product', 'wp-fce'); ?> --</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?= esc_attr($product->get_id()); ?>">
                                            <?= esc_html($product->get_name()); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="mode"><?php _e('Access Mode', 'wp-fce'); ?></label></th>
                            <td>
                                <select name="mode" id="mode" required>
                                    <option value="allow"><?php _e('Grant Access', 'wp-fce'); ?></option>
                                    <option value="deny"><?php _e('Revoke Access', 'wp-fce'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="comment"><?php _e('Comment', 'wp-fce'); ?></label></th>
                            <td>
                                <input type="text" name="comment" id="comment">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="valid_until"><?php _e('Valid until', 'wp-fce'); ?></label></th>
                            <td>
                                <input type="datetime-local" name="valid_until" id="valid_until" required>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button(__('Save Rule', 'wp-fce')); ?>
            </form>
        </div>
    </div>
</div>