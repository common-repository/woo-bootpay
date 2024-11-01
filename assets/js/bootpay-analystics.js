jQuery(function ($) {

    $.ajax({
        url: woocommerce_params.wc_ajax_url,
        type: 'POST',
        dataType: 'json',
        success: function (response) {
            console.log(response);
        }
    });
});