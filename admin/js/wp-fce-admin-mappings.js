(function ($) {
    'use strict';

    function wpfce_handle_click_edit_mapping_btn() {
        $('.wpfce_edit_mapping_btn').click(function () {
            const productId = $(this).data('product-id');
            const productTitle = $(this).data('product-title') || '';

            if (!productId) return;

            // Setze die Product-ID ins Hidden-Feld
            $('#wpfce-edit-product-id').val(productId);

            // Setze den Titel ins Modal
            $('#wpfce-edit-product-title').text(productTitle);

            // Lade Mappings via AJAX
            $.post(wp_fce.ajax_url, {
                action: 'wp_fce_handle_ajax_callback',
                func: 'get_product_mapping',
                data: { product_id: productId },
                meta: {},
                _nonce: wp_fce._nonce
            }, function (response) {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                console.log(result);
                if (!result.state || !result.mapping) return;

                // Hole NUR die IDs als Strings
                const mappedIds = result.mapping.map(entry => String(entry.id));

                // Alle Checkboxen zurücksetzen
                $('#wpfce-checkboxes-courses input[type="checkbox"], #wpfce-checkboxes-spaces input[type="checkbox"]').prop('checked', false);

                // Checkboxen mit gemappten IDs aktivieren
                $('#wpfce-checkboxes-courses input[type="checkbox"], #wpfce-checkboxes-spaces input[type="checkbox"]').each(function () {
                    if (mappedIds.includes($(this).val())) {
                        $(this).prop('checked', true);
                    }
                });

                wpfce_show_modal('edit-mapping');
            });
        });
    }

    function wpfce_handle_click_delete_mapping_btn() {
        $('.wpfce_delete_product_mapping_btn').click(function () {
            const $btn = $(this);
            const $row = $btn.closest('tr');
            const productId = $btn.data('product-id');

            if (!productId) return;

            WPFCE_Modal.open({
                message: wp_fce.msg_confirm_delete_product_mapping,
                context: {
                    row: $row,
                    product_id: productId
                },
                onConfirm: function (ctx) {
                    $.ajax({
                        method: 'POST',
                        url: wp_fce.ajax_url,
                        data: {
                            action: 'wp_fce_handle_ajax_callback',
                            func: 'delete_product_mapping',
                            data: {
                                product_id: ctx.product_id
                            },
                            meta: {},
                            _nonce: wp_fce._nonce
                        },
                        success: function (response) {
                            const result = typeof response === 'string' ? JSON.parse(response) : response;
                            if (result.state) {
                                wpfce_show_notice(wp_fce.notice_success, 'success');
                                setTimeout(() => {
                                    location.reload(); // ganze Seite neu laden
                                }, 800);
                            } else {
                                wpfce_show_notice(wp_fce.notice_error + ': ' + result.message, 'error');
                            }
                        },
                        error: function (xhr) {
                            console.error(xhr);
                            wpfce_show_notice(wp_fce.notice_error + ': ' + xhr.status, 'error');
                        }
                    });
                }
            });
        });
    }

    // Document ready - nur Mappings
    $(document).ready(function () {
        wpfce_handle_click_edit_mapping_btn();
        wpfce_handle_click_delete_mapping_btn();

        // Modal close handler (falls nur für mappings relevant)
        $('.wpfce-modal-close').on('click', function () {
            wpfce_close_modal('edit-mapping');
        });
    });

})(jQuery);