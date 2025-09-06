(function ($) {
    'use strict';

    function wpfce_handle_click_delete_access_rule_btn() {
        $(".wpfce_delete_access_rule_btn").click(function () {
            const $btn = $(this);
            const $row = $btn.closest('tr');

            var dataItem = $row.data('value');
            var metaData = wpfce_queryToJSON(dataItem);

            WPFCE_Modal.open({
                message: wp_fce.msg_confirm_delete_access_rule,
                context: {
                    row: $row,
                    dataItem: dataItem,
                    metaData: metaData
                },
                onConfirm: function (ctx) {
                    var dataJSON = {
                        action: 'wp_fce_handle_admin_ajax_callback',
                        func: 'delete_access_rule',
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

    function wpfce_handle_click_edit_access_rule_btn() {
        $(".wpfce_edit_access_rule_btn").click(function () {
            const $btn = $(this);
            const $row = $btn.closest('tr');

            // Felder holen
            const $modeField = $row.find('select[name^="fce_rule_mode"]');
            const $validField = $row.find('input[name^="valid_until"]');
            const $commentField = $row.find('input[name^="comment"]');

            if (!$btn.hasClass("active")) {
                // Bearbeitungsmodus aktivieren
                $modeField.prop('disabled', false);
                $validField.prop('disabled', false);
                $commentField.prop('disabled', false);
                $btn.addClass("active").text(wp_fce.label_save);
            } else {
                // Speichern und Felder deaktivieren
                const dataItem = $row.data('value');
                const metaData = wpfce_queryToJSON(dataItem);

                metaData.mode = $modeField.val();
                metaData.valid_until = $validField.val();
                metaData.comment = $commentField.val();

                const dataJSON = {
                    action: 'wp_fce_handle_admin_ajax_callback',
                    func: 'update_access_rule',
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
                            $modeField.prop('disabled', true);
                            $validField.prop('disabled', true);
                            $commentField.prop('disabled', true);
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

    // Document ready - nur Access Rules
    $(document).ready(function () {
        wpfce_handle_click_delete_access_rule_btn();
        wpfce_handle_click_edit_access_rule_btn();
    });

})(jQuery);