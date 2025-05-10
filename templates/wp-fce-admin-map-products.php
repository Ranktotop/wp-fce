<?php
if (! defined('ABSPATH') || ! is_user_logged_in() || ! current_user_can('manage_options')) {
    wp_redirect(home_url());
    exit;
}

$helper_product = new WP_FCE_Helper_Product();
$helper_fcom    = new WP_FCE_Helper_Fluent_Community_Entity();

$ungemappte_produkte = $helper_product->get_unmapped_products();
$mapped = $helper_product->get_all_mapped_products_with_entities();
$spaces              = $helper_fcom->get_all_spaces();
$courses             = $helper_fcom->get_all_courses();
?>

<div class="wrap">
    <h1><?php esc_html_e('Produktzuweisungen', 'wp-fce'); ?></h1>

    <h2><?php esc_html_e('Bestehende Zuweisungen', 'wp-fce'); ?></h2>

    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Produkt', 'wp-fce'); ?></th>
                <th><?php esc_html_e('Zugewiesene Gruppen/Kurse', 'wp-fce'); ?></th>
                <th><?php esc_html_e('Aktion', 'wp-fce'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (empty($mapped)) :
            ?>
                <tr>
                    <td colspan="3"><?php esc_html_e('Keine Zuweisungen gefunden.', 'wp-fce'); ?></td>
                </tr>
                <?php
            else :
                foreach ($mapped as $entry):
                    $product = $entry['product'];
                    $mapped_spaces  = $entry['spaces'];
                ?>
                    <tr>
                        <td><?php echo esc_html($product->get_title()); ?></td>
                        <td>
                            <ul style="margin: 0; padding-left: 1.2em;">
                                <?php foreach ($mapped_spaces as $space): ?>
                                    <li><?php echo esc_html($space->get_title()); ?> <small>(<?php echo esc_html($space->get_type()); ?>)</small></li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                        <td>
                            <button
                                type="button"
                                class="button wpfce_edit_mapping_btn"
                                data-product-id="<?php echo esc_attr($product->get_id()); ?>"
                                data-product-title="<?php echo esc_attr($product->get_title()); ?>">
                                <?php esc_html_e('Bearbeiten', 'wp-fce'); ?>
                            </button>
                            <button
                                type="button"
                                class="button button-primary delete wpfce_delete_product_mapping_btn"
                                data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                                ✕ <?php esc_html_e('Löschen', 'wp-fce'); ?>
                            </button>
                        </td>
                    </tr>
            <?php endforeach;
            endif; ?>
        </tbody>
    </table>

    <hr style="margin: 40px 0;">

    <h2><?php esc_html_e('Neues Mapping anlegen', 'wp-fce'); ?></h2>

    <form method="post" action="">
        <?php wp_nonce_field('wp_fce_map_product', 'wp_fce_nonce'); ?>
        <input type="hidden" name="wp_fce_form_action" value="create_product_mapping">

        <table class="form-table">
            <tr>
                <th><label for="fce_product_id"><?php esc_html_e('Produkt wählen', 'wp-fce'); ?></label></th>
                <td>
                    <select name="fce_product_id" id="fce_product_id" required>
                        <option value=""><?php esc_html_e('-- Bitte wählen --', 'wp-fce'); ?></option>
                        <?php foreach ($ungemappte_produkte as $product): ?>
                            <option value="<?php echo esc_attr($product->get_product_id()); ?>">
                                <?php echo esc_html($product->get_title()); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>

        <h3><?php esc_html_e('Zuweisung zu Kursen', 'wp-fce'); ?></h3>
        <div>
            <?php foreach ($courses as $course): ?>
                <label style="display: block; margin-bottom: 6px;">
                    <input type="checkbox" name="fce_spaces[]" value="<?php echo esc_attr($course->get_id()); ?>">
                    <?php echo esc_html($course->get_title()); ?>
                </label>
            <?php endforeach; ?>
        </div>

        <h3 style="margin-top: 20px;"><?php esc_html_e('Zuweisung zu Spaces', 'wp-fce'); ?></h3>
        <div>
            <?php foreach ($spaces as $space): ?>
                <label style="display: block; margin-bottom: 6px;">
                    <input type="checkbox" name="fce_spaces[]" value="<?php echo esc_attr($space->get_id()); ?>">
                    <?php echo esc_html($space->get_title()); ?>
                </label>
            <?php endforeach; ?>
        </div>

        <p><button type="submit" class="button button-primary"><?php esc_html_e('Zuweisung speichern', 'wp-fce'); ?></button></p>
    </form>
</div>
<div id="wpfce-modal-edit-mapping" class="wpfce-modal hidden" data-modal-id="edit-mapping">
    <div class="wpfce-modal-overlay"></div>
    <div class="wpfce-modal-content">
        <h2 id="wpfce-edit-product-title" style="margin-bottom: 10px;"></h2>
        <form id="wpfce-edit-mapping-form" method="post" action="">
            <input type="hidden" name="wp_fce_form_action" value="update_product_mapping">
            <input type="hidden" name="product_id" id="wpfce-edit-product-id" value="">
            <?php wp_nonce_field('wp_fce_update_product_mapping', 'wp_fce_nonce'); ?>
            <h4><?php esc_html_e('Zugewiesene Kurse', 'wp-fce'); ?></h4>
            <div id="wpfce-checkboxes-courses">
                <?php foreach ($courses as $entity): ?>
                    <label style="display: block; margin-bottom: 6px;">
                        <input type="checkbox"
                            name="fce_edit_entities[]"
                            value="<?php echo esc_attr($entity->get_id()); ?>"
                            data-entity-type="<?php echo esc_attr($entity->get_type()); ?>">
                        <?php echo esc_html($entity->get_title()); ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <h4 style="margin-top: 20px;"><?php esc_html_e('Zugewiesene Spaces', 'wp-fce'); ?></h4>
            <div id="wpfce-checkboxes-spaces">
                <?php foreach ($spaces as $entity): ?>
                    <label style="display: block; margin-bottom: 6px;">
                        <input type="checkbox"
                            name="fce_edit_entities[]"
                            value="<?php echo esc_attr($entity->get_id()); ?>"
                            data-entity-type="<?php echo esc_attr($entity->get_type()); ?>">
                        <?php echo esc_html($entity->get_title()); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="wpfce-modal-actions" style="margin-top: 20px;">
                <button type="submit" class="button button-primary"><?php esc_html_e('Speichern', 'wp-fce'); ?></button>
                <button type="button" class="button wpfce-modal-close"><?php esc_html_e('Abbrechen', 'wp-fce'); ?></button>
            </div>
        </form>
    </div>
</div>