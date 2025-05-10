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

	function wpfce_show_notice(message, type = 'success') {
		const $area = $('#wpfce-notice-area');
		const className = type === 'success' ? 'wpfce-notice wpfce-notice-success' : 'wpfce-notice wpfce-notice-error';

		const $notice = $(`
		<div class="${className}">
			<p>${message}</p>
		</div>
	`);

		$area.html($notice);

		setTimeout(() => {
			$notice.fadeOut(500, function () {
				$(this).remove();
			});
		}, 1500);
	}
	function wpfce_show_modal(modalId) {
		$('#wpfce-modal-' + modalId).removeClass('hidden').show();
	}
	function wpfce_close_modal(modalId) {
		$('#wpfce-modal-' + modalId).addClass('hidden').hide();
	}



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
						action: 'wp_fce_handle_ajax_callback',
						func: 'delete_product',
						data: ctx.metaData,
						meta: {},
						_nonce: wp_fce._nonce
					};
					console.log(dataJSON);

					$.ajax({
						cache: false,
						type: "POST",
						url: wp_fce.ajax_url,
						data: dataJSON,
						success: function (response) {
							const result = JSON.parse(response);
							console.log(result);
							if (result.state) {
								wpfce_show_notice(wp_fce.notice_success, 'success');
								ctx.row.fadeOut(300, function () {
									$(this).remove();
								});
							} else {
								wpfce_show_notice(wp_fce.notice_error + ': ' + result.message, 'error');
								console.log(result.message);
							}
						},
						error: function (xhr, status, error) {
							console.log('Status: ' + xhr.status);
							console.log('Error: ' + xhr.responseText);
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

			// Felder holen
			const $titleField = $row.find('input[name^="fce_product_edit_title"]');
			const $descField = $row.find('textarea[name^="fce_product_edit_description"]');

			if ($btn.text().trim() === wp_fce.label_edit) {
				// Felder aktivieren
				$titleField.prop('disabled', false);
				$descField.prop('disabled', false);
				$btn.text(wp_fce.label_save);
			} else {
				// Felder deaktivieren & speichern
				const dataItem = $row.data('value');
				const metaData = wpfce_queryToJSON(dataItem);

				metaData.title = $titleField.val();
				metaData.description = $descField.val();

				const dataJSON = {
					action: 'wp_fce_handle_ajax_callback',
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
						console.log(result);
						if (result.state) {
							wpfce_show_notice(wp_fce.notice_success, 'success');
							$titleField.prop('disabled', true);
							$descField.prop('disabled', true);
							$btn.text(wp_fce.label_edit);
						} else {
							wpfce_show_notice(wp_fce.notice_error + ': ' + result.message, 'error');
							console.log(result.message);
						}
					},
					error: function (xhr) {
						console.log('Status: ' + xhr.status);
						console.log('Error: ' + xhr.responseText);
						wpfce_show_notice(wp_fce.notice_error + ': ' + xhr.status, 'error');
					},
					complete: function () {
						$btn.prop('disabled', false);
					}
				});
			}
		});
	}

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
							console.log(result);
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

	// Load Listener
	$(document).ready(function () {
		wpfce_handle_click_delete_product_btn();
		wpfce_handle_click_edit_product_btn();
		wpfce_handle_click_edit_mapping_btn();
		wpfce_handle_click_delete_mapping_btn();

		$('.wpfce-modal-close').on('click', function () {
			wpfce_close_modal('edit-mapping');
		});
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