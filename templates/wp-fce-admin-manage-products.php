<?php
// templates/wp-fce-admin-manage-products.php

if (!defined('ABSPATH') || !is_user_logged_in() || !current_user_can('manage_options')) {
    wp_redirect(home_url());
    exit;
}

$helper_product = new WP_FCE_Helper_Product();
$products = $helper_product->get_all();
?>

<div class="wrap fce-admin-page">
    <h1 class="wp-heading-inline"><?php esc_html_e('Product Management', 'wp-fce'); ?></h1>

    <div class="fce-admin-sections">
        <!-- Bestehende Produkte -->
        <div class="fce-section fce-section-list">
            <h2><?php esc_html_e('Existing Products', 'wp-fce'); ?></h2>
            <p class="description">
                <?php esc_html_e('Manage existing products and change their details.', 'wp-fce'); ?>
            </p>

            <div id="fce-products-list">
                <?php if (empty($products)) : ?>
                    <p><?php esc_html_e('No products found.', 'wp-fce'); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Product ID', 'wp-fce'); ?></th>
                                <th><?php esc_html_e('Name', 'wp-fce'); ?></th>
                                <th><?php esc_html_e('Description', 'wp-fce'); ?></th>
                                <th><?php esc_html_e('Action', 'wp-fce'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product) : ?>
                                <tr data-value="product_id=<?php echo esc_attr($product->get_id()); ?>">
                                    <td><?php echo esc_html($product->get_sku()); ?></td>
                                    <td>
                                        <input
                                            type="text"
                                            name="fce_product_edit_product_name[<?php echo (int) $product->get_id(); ?>]"
                                            value="<?php echo esc_attr($product->get_name()); ?>"
                                            class="regular-text"
                                            disabled>
                                    </td>
                                    <td>
                                        <textarea
                                            name="fce_product_edit_product_description[<?php echo (int) $product->get_id(); ?>]"
                                            rows="2"
                                            class="large-text"
                                            disabled><?php echo esc_textarea($product->get_description()); ?></textarea>
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="button button-primary wpfce_edit_product_btn">
                                            <?php esc_html_e('Edit', 'wp-fce'); ?>
                                        </button>
                                        <button
                                            type="button"
                                            class="button button-primary delete wpfce_delete_product_btn">
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
            <h2><?php esc_html_e('Add Products', 'wp-fce'); ?></h2>
            <p class="description">
                <?php esc_html_e('Create a new product that can be linked with a payment provider.', 'wp-fce'); ?>
            </p>

            <form method="post" action="">
                <input type="hidden" name="wp_fce_form_action" value="create_product">
                <?php wp_nonce_field('wp_fce_create_product', 'wp_fce_nonce'); ?>

                <h2><?php esc_html_e('Add new product', 'wp-fce'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="fce_new_product_sku"><?php esc_html_e('External SKU', 'wp-fce'); ?></label></th>
                        <td><input name="fce_new_product_sku" type="text" id="fce_new_product_sku" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="fce_new_product_name"><?php esc_html_e('Name', 'wp-fce'); ?></label></th>
                        <td><input name="fce_new_product_name" type="text" id="fce_new_product_name" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="fce_new_product_description"><?php esc_html_e('Description', 'wp-fce'); ?></label></th>
                        <td><textarea name="fce_new_product_description" id="fce_new_product_description" class="large-text" rows="3"></textarea></td>
                    </tr>
                </table>

                <p><button type="submit" class="button button-primary"><?php esc_html_e('Add Product', 'wp-fce'); ?></button></p>
            </form>
        </div>
    </div>
</div>