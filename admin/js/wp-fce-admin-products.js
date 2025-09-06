(function ($) {
    'use strict';

    function wpfce_handle_click_delete_product_btn() {
        $(".wpfce_delete_product_btn").click(function () {
            const $btn = $(this);
            const $row = $btn.closest('tr');

            var dataItem = $row.data('value');
            var metaData = wpfce_queryToJSON(dataItem);

            WPFCE_Modal.open({
                message: wp_fce.msg_confirm_delete_product,
                context: {
                    row: $row,
                    dataItem: dataItem,
                    metaData: metaData
                },
                onConfirm: function (ctx) {
                    var dataJSON = {
                        action: 'wp_fce_handle_admin_ajax_callback',
                        func: 'delete_product',
                        data: ctx.metaData,
                        meta: {},
                        _nonce: wp_fce._nonce
                    };

                    $.ajax({
                        cache: false,
                        type: "POST",
                        url: wp_fce.ajax_url,
                        data: dataJSON,
                        success: function (response) {
                            const result = typeof response === 'string' ? JSON.parse(response) : response;
                            if (result.state) {
                                wpfce_show_notice(wp_fce.notice_success, 'success');
                                ctx.row.fadeOut(300, function () {
                                    $(this).remove();
                                });
                            } else {
                                wpfce_show_notice(wp_fce.notice_error + ': ' + result.message, 'error');
                            }
                        },
                        error: function (xhr, status, error) {
                            wpfce_show_notice(wp_fce.notice_error + ': ' + xhr.status, 'error');
                        }
                    });
                }
            });
        });
    }

    function wpfce_handle_click_edit_product_btn() {
        $(".wpfce_edit_product_btn").click(function () {
            const $btn = $(this);
            const $row = $btn.closest('tr');

            const $nameField = $row.find('input[name^="fce_product_edit_product_name"]');
            const $descField = $row.find('textarea[name^="fce_product_edit_product_description"]');

            if (!$btn.hasClass("active")) {
                // Bearbeitungsmodus aktivieren
                $nameField.prop('disabled', false);
                $descField.prop('disabled', false);
                $btn.addClass("active").text(wp_fce.label_save);
            } else {
                // Speichern und Felder deaktivieren
                const dataItem = $row.data('value');
                const metaData = wpfce_queryToJSON(dataItem);

                metaData.name = $nameField.val();
                metaData.description = $descField.val();

                const dataJSON = {
                    action: 'wp_fce_handle_admin_ajax_callback',
                    func: 'update_product',
                    data: metaData,
                    meta: {},
                    _nonce: wp_fce._nonce
                };

                $btn.prop('disabled', true).text('…');

                $.ajax({
                    cache: false,
                    type: "POST",
                    url: wp_fce.ajax_url,
                    data: dataJSON,
                    success: function (response) {
                        const result = typeof response === 'string' ? JSON.parse(response) : response;
                        if (result.state) {
                            wpfce_show_notice(wp_fce.notice_success, 'success');
                            $nameField.prop('disabled', true);
                            $descField.prop('disabled', true);
                            $btn.removeClass("active").text(wp_fce.label_edit);
                        } else {
                            wpfce_show_notice(wp_fce.notice_error + ': ' + result.message, 'error');
                        }
                    },
                    error: function (xhr) {
                        wpfce_show_notice(wp_fce.notice_error + ': ' + xhr.status, 'error');
                    },
                    complete: function () {
                        $btn.prop('disabled', false);
                    }
                });
            }
        });
    }

    // Document ready - nur Products
    $(document).ready(function () {
        wpfce_handle_click_delete_product_btn();
        wpfce_handle_click_edit_product_btn();
    });

})(jQuery);