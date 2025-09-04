(function ($) {
	'use strict';

	var callbacks = {
		wpfce_reloadPage: function (result, dialogId) {
			location.reload();
		}
	};

	/**
	 * All of the code for your public-facing JavaScript source should reside in
	 * this file.
	 * 
	 * Note: It has been assumed you will write jQuery code here, so the $
	 * function reference has been prepared for usage within the scope of this
	 * function.
	 * 
	 * This enables you to define handlers, for when the DOM is ready:
	 * 
	 * $(function() {
	 * 
	 * });
	 * 
	 * When the window is loaded: $( window ).load(function() {
	 * 
	 * });
	 * 
	 * ...and/or other possibilities.
	 * 
	 * Ideally, it is not considered best practise to attach more than a single
	 * DOM-ready or window-load handler for a particular page. Although scripts
	 * in the WordPress core, Plugins and Themes may be practising this, we
	 * should strive to set a better example in our own work.
	 */

	// Load Listener
	$(document).ready(function () {
	});
})(jQuery);

/**
 * Loads an external Javascript-File
 * 
 * @param url
 * @param callback
 * @param data
 * @returns
 */
function wpfce_loadExternalScript(url, callback, data = false) {
	var script = document.createElement('script');
	script.onload = function () {
		callback(data);
	};
	script.src = url;
	document.head.appendChild(script); // or something of the likes
}

/**
 * Converts a query String to JSON Array
 * @param dataItem
 * @returns
 */
function wpfce_queryToJSON(dataItem) {
	// Convert to JSON array
	return dataItem ? JSON.parse('{"'
		+ dataItem.replace(/&/g, '","').replace(/=/g, '":"') + '"}',
		function (key, value) {
			return key === "" ? value : decodeURIComponent(value);
		}) : {};
}

// UTILITY FUNKTIONEN GLOBAL (nach der Closure):
function wpfce_show_notice(message, type = 'success') {
	const $area = jQuery('#wpfce-notice-area');  // ← jQuery statt $
	const className = type === 'success' ? 'wpfce-notice wpfce-notice-success' : 'wpfce-notice wpfce-notice-error';

	const $notice = jQuery(`
        <div class="${className}">
            <p>${message}</p>
        </div>
    `);

	$area.html($notice);

	setTimeout(() => {
		$notice.fadeOut(500, function () {
			jQuery(this).remove();  // ← jQuery statt $(this)
		});
	}, 1500);
}

function wpfce_show_modal(modalId) {
	jQuery('#wpfce-modal-' + modalId).removeClass('hidden').show();  // ← jQuery statt $
}

function wpfce_close_modal(modalId) {
	jQuery('#wpfce-modal-' + modalId).addClass('hidden').hide();     // ← jQuery statt $
}