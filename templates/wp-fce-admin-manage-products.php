<?php
// templates/wp-fce-admin-manage-products.php

if (!defined('ABSPATH') || !is_user_logged_in() || !current_user_can('manage_options')) {
    wp_redirect(home_url());
    exit;
}

$helper_product = new WP_FCE_Helper_Product();
$products = $helper_product->get_all_products();
?>

<div class="wrap fce-admin-page">
    <h1 class="wp-heading-inline"><?php esc_html_e('Produktverwaltung', 'wp-fce'); ?></h1>

    <div class="fce-admin-sections">
        <!-- Bestehende Produkte -->
        <div class="fce-section fce-section-list">
            <h2><?php esc_html_e('Bestehende Produkte', 'wp-fce'); ?></h2>
            <p class="description">
                <?php esc_html_e('Verwalte hier bestehende Produkte und bearbeite Titel oder Beschreibung.', 'wp-fce'); ?>
            </p>

            <div id="fce-products-list">
                <?php if (empty($products)) : ?>
                    <p><?php esc_html_e('Keine Produkte vorhanden.', 'wp-fce'); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Produkt-ID', 'wp-fce'); ?></th>
                                <th><?php esc_html_e('Titel', 'wp-fce'); ?></th>
                                <th><?php esc_html_e('Beschreibung', 'wp-fce'); ?></th>
                                <th><?php esc_html_e('Aktion', 'wp-fce'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product) : ?>
                                <tr data-value="product_id=<?php echo esc_attr($product->get_id()); ?>">
                                    <td><?php echo esc_html($product->get_product_id()); ?></td>
                                    <td>
                                        <input
                                            type="text"
                                            name="fce_product_edit_title[<?php echo (int) $product->get_product_id(); ?>]"
                                            value="<?php echo esc_attr($product->get_title()); ?>"
                                            class="regular-text"
                                            disabled>
                                    </td>
                                    <td>
                                        <textarea
                                            name="fce_product_edit_description[<?php echo (int) $product->get_product_id(); ?>]"
                                            rows="2"
                                            class="large-text"
                                            disabled><?php echo esc_textarea($product->get_description()); ?></textarea>
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="button button-primary wpfce_edit_product_btn">
                                            <?php esc_html_e('Bearbeiten', 'wp-fce'); ?>
                                        </button>
                                        <button
                                            type="button"
                                            class="button button-primary delete wpfce_delete_product_btn">
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
            <h2><?php esc_html_e('Neues Produkt hinzufügen', 'wp-fce'); ?></h2>
            <p class="description">
                <?php esc_html_e('Lege ein neues Produkt an, das mit einem Zahlungsanbieter verknüpft werden kann.', 'wp-fce'); ?>
            </p>

            <form method="post" action="">
                <input type="hidden" name="wp_fce_form_action" value="create_product">
                <?php wp_nonce_field('wp_fce_create_product', 'wp_fce_nonce'); ?>

                <h2><?php esc_html_e('Neues Produkt anlegen', 'wp-fce'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="fce_new_product_id"><?php esc_html_e('Externe Produkt-ID', 'wp-fce'); ?></label></th>
                        <td><input name="fce_new_product_id" type="text" id="fce_new_product_id" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="fce_new_product_title"><?php esc_html_e('Titel', 'wp-fce'); ?></label></th>
                        <td><input name="fce_new_product_title" type="text" id="fce_new_product_title" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="fce_new_product_description"><?php esc_html_e('Beschreibung', 'wp-fce'); ?></label></th>
                        <td><textarea name="fce_new_product_description" id="fce_new_product_description" class="large-text" rows="3"></textarea></td>
                    </tr>
                </table>

                <p><button type="submit" class="button button-primary"><?php esc_html_e('Produkt anlegen', 'wp-fce'); ?></button></p>
            </form>
        </div>
    </div>
</div>