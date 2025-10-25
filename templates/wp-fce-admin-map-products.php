<?php
if (! defined('ABSPATH') || ! is_user_logged_in() || ! current_user_can('manage_options')) {
    wp_redirect(home_url());
    exit;
}

$unmapped_products = WP_FCE_Helper_Product::get_unmapped_products();
$mapped_products   = WP_FCE_Helper_Product::get_mapped_products();

$spaces = WP_FCE_Helper_Fcom::get_all_spaces();
$courses = WP_FCE_Helper_Fcom::get_all_courses();
?>

<div class="wrap">
    <h1><?php esc_html_e('Product Mappings', 'wp-fce'); ?></h1>

    <h2><?php esc_html_e('Existing Mappings', 'wp-fce'); ?></h2>
    <div class="wpfce-table-glass">
        <table>
            <thead>
                <tr>
                    <th><?php esc_html_e('Product', 'wp-fce'); ?></th>
                    <th><?php esc_html_e('Assigned Spaces', 'wp-fce'); ?></th>
                    <th><?php esc_html_e('Assigned Courses', 'wp-fce'); ?></th>
                    <th><?php esc_html_e('Action', 'wp-fce'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (empty($mapped_products)) :
                ?>
                    <tr>
                        <td colspan="4"><?php esc_html_e('No Mappings found', 'wp-fce'); ?></td>
                    </tr>
                    <?php
                else :
                    foreach ($mapped_products as $mapped_product):
                    ?>
                        <tr>
                            <td><?php echo esc_html($mapped_product->get_name()); ?></td>
                            <td>
                                <ul style="margin: 0; padding-left: 1.2em;">
                                    <?php foreach ($mapped_product->get_mapped_communities() as $community): ?>
                                        <li><?php echo esc_html($community->get_title()); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td>
                                <ul style="margin: 0; padding-left: 1.2em;">
                                    <?php foreach ($mapped_product->get_mapped_courses() as $course): ?>
                                        <li><?php echo esc_html($course->get_title()); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td>
                                <button
                                    type="button"
                                    class="button wpfce_edit_mapping_btn"
                                    data-product-id="<?php echo esc_attr($mapped_product->get_id()); ?>"
                                    data-product-title="<?php echo esc_attr($mapped_product->get_name()); ?>">
                                    <?php esc_html_e('Edit', 'wp-fce'); ?>
                                </button>
                                <button
                                    type="button"
                                    class="button button-primary delete wpfce_delete_product_mapping_btn"
                                    data-product-id="<?php echo esc_attr($mapped_product->get_id()); ?>">
                                    ✕ <?php esc_html_e('Delete', 'wp-fce'); ?>
                                </button>
                            </td>
                        </tr>
                <?php endforeach;
                endif; ?>
            </tbody>
        </table>
    </div>

    <hr style="margin: 40px 0;">

    <h2><?php esc_html_e('Add a new mapping', 'wp-fce'); ?></h2>

    <form method="post" action="">
        <?php wp_nonce_field('wp_fce_map_product', 'wp_fce_nonce'); ?>
        <input type="hidden" name="wp_fce_form_action" value="create_product_mapping">

        <table class="form-table">
            <tr>
                <th><label for="fce_product_id"><?php esc_html_e('Choose Product', 'wp-fce'); ?></label></th>
                <td>
                    <select name="fce_product_id" id="fce_product_id" required>
                        <option value="">-- <?php esc_html_e('Please select', 'wp-fce'); ?> --</option>
                        <?php foreach ($unmapped_products as $product): ?>
                            <option value="<?php echo esc_attr($product->get_id()); ?>">
                                <?php echo esc_html($product->get_name()); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>

        <h3><?php esc_html_e('Assigned Spaces', 'wp-fce'); ?></h3>
        <div>
            <?php foreach ($spaces as $space): ?>
                <label style="display: block; margin-bottom: 6px;">
                    <input type="checkbox" name="fce_spaces[]" value="<?php echo esc_attr($space->get_id()); ?>">
                    <?php echo esc_html($space->get_title()); ?>
                </label>
            <?php endforeach; ?>
        </div>

        <h3 style="margin-top: 20px;"><?php esc_html_e('Assigned Courses', 'wp-fce'); ?></h3>
        <div>
            <?php foreach ($courses as $course): ?>
                <label style="display: block; margin-bottom: 6px;">
                    <input type="checkbox" name="fce_spaces[]" value="<?php echo esc_attr($course->get_id()); ?>">
                    <?php echo esc_html($course->get_title()); ?>
                </label>
            <?php endforeach; ?>
        </div>

        <p><button type="submit" class="button button-primary"><?php esc_html_e('Save Mapping', 'wp-fce'); ?></button></p>
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
            <h4><?php esc_html_e('Assigned Courses', 'wp-fce'); ?></h4>
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

            <h4 style="margin-top: 20px;"><?php esc_html_e('Assigned Spaces', 'wp-fce'); ?></h4>
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
                <button type="submit" class="button button-primary"><?php esc_html_e('Save', 'wp-fce'); ?></button>
                <button type="button" class="button wpfce-modal-close"><?php esc_html_e('Cancel', 'wp-fce'); ?></button>
            </div>
        </form>
    </div>
</div>