jQuery(function ($) {

    function getParameterByName(name, url) {
        if (!url) url = window.location.href;
        name = name.replace(/[\[\]]/g, "\\$&");
        var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, " "));
    }

    var bootpayGateways = [
        'bootpay_gateway'
    ];
    $('form[name=checkout]').on('checkout_place_order_bootpay_gateway', function (e) {
        var paymentGatewayName = $('#order_review input[name=payment_method]:checked').val();
        if (bootpayGateways.indexOf(paymentGatewayName) === -1) return true;
        var jsApiKey = wc_checkout_params.js_api_key;
        if (jsApiKey === undefined || jsApiKey.length === 0) {
            alert('Javascript Application ID를 입력해주세요.');
            return false;
        }

        $.ajax({
            type: 'POST',
            url: wc_checkout_params.checkout_url,
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.result !== 'success') {
                    try { var message = response.messages.replace(/<(?:.|\n)*?>/gm, '').trim(); } catch (e) { var message = ""; }
                    if (message.length > 0) {
                        alert(message);
                    } else {
                        alert('결제가 실패하였습니다.');
                    }
                    return;
                }

                var orderData = response.order_data;
                BootPay.request({
                    application_id: jsApiKey,
                    price: orderData.price,
                    name: orderData.name,
                    items: orderData.items,
                    phone: orderData.user_info.phone,
                    user_info: orderData.user_info,
                    order_id: orderData.order_id,
                    show_agree_window: wc_checkout_params.show_agree_window === 'yes' ? 1 : 0,
                    extra: {
                        vbank_result: 0
                    }
                }).error(function (data) {
                    console.log(data);
                    alert('결제가 실패하였습니다');
                }).cancel(function (data) {
                    console.log(data);
                    alert('결제가 진행도중 취소되었습니다. 다시 시도해주세요.');
                }).ready(function (data) {
                    if (data.receipt_id !== undefined) {
                        location.href = response.checkout_url + '&receipt_id=' + data.receipt_id;
                    } else {
                        alert('결제가 정상적으로 처리 되지 않았습니다.');
                    }
                }).confirm(function (data) {
                    // TODO: 재고 처리 관리 로직을 넣어야 한다.
                    this.transactionConfirm(data);
                }).done(function (data) {
                    // 결제 완료된 이후 처리
                    if (data.receipt_id !== undefined) {
                        location.href = response.checkout_url + '&receipt_id=' + data.receipt_id;
                    } else {
                        alert('결제가 정상적으로 처리 되지 않았습니다.');
                    }
                }).close(function (data) {

                });
            },
            error: function (error) {
                console.log(error);
            }
        });
        return false;
    });

    $("form#order_review:not([name=\"checkout\"])").on('submit', function (e) {
        var paymentGatewayName = $('#order_review input[name=payment_method]:checked').val();
        if (bootpayGateways.indexOf(paymentGatewayName) === -1) return true;

        var jsApiKey = wc_checkout_params.js_api_key;
        if (jsApiKey === undefined || jsApiKey.length === 0) {
            alert('Javascript API KEY를 입력해주세요.');
            return false;
        }

        $.ajax({
            type: 'POST',
            url: wc_checkout_params.ajax_url,
            data: {
                action: 'bootpay_payment_response',
                pay_for_order: getParameterByName('pay_for_order'),
                order_key: getParameterByName('key')
            },
            dataType: 'json',
            success: function (response) {
                if (response.result !== 'success') {
                    try { var message = response.messages.replace(/<(?:.|\n)*?>/gm, '').trim(); } catch (e) { var message = ""; }
                    if (message.length > 0) {
                        alert(message);
                    } else {
                        alert('결제가 실패하였습니다.');
                    }
                    return;
                }

                var orderData = response.order_data;
                BootPay.request({
                    application_id: jsApiKey,
                    price: orderData.price,
                    name: orderData.name,
                    items: orderData.items,
                    phone: orderData.user_info.phone,
                    user_info: orderData.user_info,
                    order_id: orderData.order_id,
                    show_agree_window: wc_checkout_params.show_agree_window === 'yes' ? 1 : 0,
                    extra: {
                        vbank_result: 0
                    }
                }).error(function (data) {
                    console.log(data);
                    alert('결제가 실패하였습니다.');
                }).cancel(function (data) {
                    console.log(data);
                    alert('결제가 진행도중 취소되었습니다. 다시 시도해주세요.');
                }).ready(function (data) {
                    if (data.receipt_id !== undefined) {
                        location.href = response.checkout_url + '&receipt_id=' + data.receipt_id;
                    } else {
                        alert('결제가 정상적으로 처리 되지 않았습니다.');
                    }
                }).confirm(function (data) {
                    // TODO: 재고 처리 관리 로직을 넣어야 한다.
                    this.transactionConfirm(data);
                }).done(function (data) {
                    // 결제 완료된 이후 처리
                    if (data.receipt_id !== undefined) {
                        location.href = response.checkout_url + '&receipt_id=' + data.receipt_id;
                    } else {
                        alert('결제가 정상적으로 처리 되지 않았습니다.');
                    }
                }).close(function (data) {

                });
            },
            error: function (error) {
                console.log(error);
            }
        });
        return false;
    });
});