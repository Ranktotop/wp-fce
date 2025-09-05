<style>
    #wpfce-notice-area {
        position: fixed;
        top: 32px;
        left: 0;
        width: 100%;
        z-index: 9999;
        display: flex;
        justify-content: center;
        pointer-events: none;
    }

    #wpfce-notice-area .wpfce-notice {
        width: 100%;
        max-width: 100%;
        margin: 0;
        border-radius: 0;
        pointer-events: auto;
        padding: 5px 10px 5px 10px;
        text-align: center;
    }

    #wpfce-notice-area .wpfce-notice p {
        color: #fff;
        font-weight: 600;
        font-size: 18px;
    }

    #wpfce-notice-area .wpfce-notice-success {
        background-color: #81d381;
    }
</style>
<div id="wpfce-notice-area"></div>
<script>
    // UTILITY FUNKTIONEN GLOBAL (nach der Closure):
    function wpfce_show_notice(message, type = 'success') {
        const $area = jQuery('#wpfce-notice-area'); // ← jQuery statt $
        const className = type === 'success' ? 'wpfce-notice wpfce-notice-success' : 'wpfce-notice wpfce-notice-error';

        const $notice = jQuery(`
        <div class="${className}">
            <p>${message}</p>
        </div>
    `);

        $area.html($notice);

        setTimeout(() => {
            $notice.fadeOut(500, function() {
                jQuery(this).remove(); // ← jQuery statt $(this)
            });
        }, 1500);
    }
    // if the url contains fce_success or fce_error, show the notice
    jQuery(document).ready(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const successMessage = urlParams.get('fce_success');
        const errorMessage = urlParams.get('fce_error');

        if (successMessage) {
            wpfce_show_notice(decodeURIComponent(successMessage), 'success');
            // Remove the parameter from the URL without reloading the page
            urlParams.delete('fce_success');
            const newUrl = window.location.pathname + '?' + urlParams.toString();
            window.history.replaceState({}, document.title, newUrl);
        }

        if (errorMessage) {
            wpfce_show_notice(decodeURIComponent(errorMessage), 'error');
            // Remove the parameter from the URL without reloading the page
            urlParams.delete('fce_error');
            const newUrl = window.location.pathname + '?' + urlParams.toString();
            window.history.replaceState({}, document.title, newUrl);
        }
    });
</script>