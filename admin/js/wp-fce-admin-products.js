(function ($) {
    $(document).ready(function () {
        const container = $('#fce-products-app');

        // Funktion zum Rendern der Produktliste
        function renderProducts(list) {
            const table = $('<table class="widefat striped"><thead>'
                + '<tr><th>ID</th><th>Externe ID</th><th>Titel</th><th>Beschreibung</th></tr>'
                + '</thead><tbody></tbody></table>');
            list.forEach(prod => {
                const row = $('<tr>');
                row.append($('<td>').text(prod.id));
                row.append($('<td>').text(prod.product_id));
                row.append($('<td>').text(prod.title));
                row.append($('<td>').text(prod.description));
                table.find('tbody').append(row);
            });
            container.empty().append(table);
        }

        // AJAX: Produkte holen
        function loadProducts() {
            $.post(
                FCE_Products.ajaxUrl,
                { action: 'fce_get_products', nonce: FCE_Products.nonce },
                function (response) {
                    if (response.success) {
                        renderProducts(response.data);
                    } else {
                        container.html('<div class="notice notice-error"><p>Fehler beim Laden der Produkte.</p></div>');
                    }
                }
            );
        }

        // Initiales Laden
        loadProducts();
    });
})(jQuery);
