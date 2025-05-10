(function ($) {
    'use strict';

    window.WPFCE_Modal = {
        active: null,
        context: {},
        open: function (options) {
            // Erwartete Struktur:
            // {
            //   message: 'Bist du sicher?',
            //   onConfirm: function (ctx) {},
            //   context: {} // optional
            // }

            this.context = options.context || {};
            this.active = options;

            const $modal = $('#wpfce-confirm-modal');
            $modal.find('.wpfce-modal-content p').text(options.message || 'Bist du sicher?');
            $modal.removeClass('hidden');
        },
        close: function () {
            $('#wpfce-confirm-modal').addClass('hidden');
            this.active = null;
            this.context = {};
        }
    };

    $(document).ready(function () {
        $('.wpfce-cancel-btn').on('click', function () {
            WPFCE_Modal.close();
        });

        $('.wpfce-confirm-btn').on('click', function () {
            if (typeof WPFCE_Modal.active?.onConfirm === 'function') {
                WPFCE_Modal.active.onConfirm(WPFCE_Modal.context);
            }
            WPFCE_Modal.close();
        });
    });
})(jQuery);
