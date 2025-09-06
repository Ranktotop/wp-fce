(function ($) {
    'use strict';

    /**
     * Tests the Community API connection
     */
    function testCommunityAPIConnection() {
        const dataJSON = {
            action: 'wp_fce_handle_admin_ajax_callback',
            func: 'test_community_api_connection',
            data: {
                url: $('#community_api_url').val(),
                port: $('#community_api_port').val(),
                ssl: $('#community_api_ssl').is(':checked'),
                master_token: $('#community_api_master_token').val(),
                service_token: $('#community_api_service_token').val()
            },
            meta: {},
            _nonce: FCE_CommunityAPI.nonce
        };
        $('#community-api-test-result').html('<span style="color:blue;">' + FCE_CommunityAPI.messages.testing + '</span>');

        $.ajax({
            url: FCE_CommunityAPI.ajaxUrl,
            type: 'POST',
            data: dataJSON,
            success: function (response) {
                const result = JSON.parse(response);
                if (result.state) {
                    $('#community-api-test-result').html('<span style="color:green;">✓ ' + result.message + '</span>');
                } else {
                    $('#community-api-test-result').html('<span style="color:red;">✗ ' + result.message + '</span>');
                }
            },
            error: function () {
                $('#community-api-test-result').html('<span style="color:red;">✗ ' + FCE_CommunityAPI.messages.connection_failed + '</span>');
            }
        });
    }

    // Make function globally available
    window.testCommunityAPIConnection = testCommunityAPIConnection;

})(jQuery);