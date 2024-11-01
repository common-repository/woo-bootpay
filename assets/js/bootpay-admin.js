(function () {
    jQuery(function ($) {
        $(function () {
            $('#bootpay-test-btn').click(function () {
                var applicationId = $('[name=woocommerce_bootpay_gateway_js_api_key]').val();
                if (!applicationId.length) {
                    alert('Javascript Application ID를 부트페이 관리자에서 복사한 후 붙여넣기 해주세요.');
                    return;
                }
                BootPay.request({
                    price: 1000,
                    application_id: applicationId,
                    name: '테스트 아이템 결제',
                    phone: '01000000000',
                    items: [
                        {
                            item_name: '테스트 아이템',
                            qty: 1,
                            unique: 'TEST',
                            price: 1000,
                            cat1: 'test1',
                            cat2: 'test2',
                            cat3: 'test3'
                        }
                    ],
                    order_id: (new Date()).getMilliseconds()
                }).error(function (data) {
                    alert('결제 에러가 났습니다.');
                    console.log(data);
                }).cancel(function (data) {
                    alert('결제가 중단되었습니다.');
                    console.log(data);
                }).confirm(function (data) {
                    if (confirm('정말로 결제 승인을 하시겠습니까?')) {
                        this.transactionConfirm(data);
                    } else {
                        alert('결제 승인이 취소되었습니다.');
                    }
                }).done(function (data) {
                    alert('결제가 완료되었습니다.');
                    console.log(data);
                });
            });
        });
    });
})();