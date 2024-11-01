<?php
/**
 * Plugin Name: woo-bootpay
 * Plugin URI: https://www.bootpay.co.kr
 * Description: 우커머스에 PG를 손쉽게 붙일 수 연동 플러그인 ( 이니시스 / 다날 / KCP / LGU+ 모두 쉽게 붙이는 Woo-bootpay )
 * Version: 1.1.15
 * Author: Gosomi
 * Author URI: https://docs.bootpay.co.kr
 * License: A "Slug" license name e.g. GPL2
 */

require_once('bootpay-api.php');

add_action('init', function () {
    register_post_status('wc-wating-bank', [
        'label' => __('입금대기', 'bootpay-with-woocommerce'),
        'label_count' => _n_noop('입금대기 <span class="count">(%s)</span>', '입금대기 <span class="count">(%s)</span>'),
        'show_in_admin_all_list' => true,
        'public' => true,
        'show_in_admin_status_list' => true,
        'exclude_from_search' => false
    ]);
});
add_action('wc_order_statuses', function ($order_statuses) {
    $order_statuses['wc-wating-bank'] = __('입금대기', 'bootpay-with-woocommerce');

    return $order_statuses;
});
add_filter('woocommerce_payment_gateways', function ($methods) {
    $methods[] = 'WC_Gateway_Bootpay';
    return $methods;
});

add_action('plugins_loaded', 'init_bootpay_plugin', 0);
load_plugin_textdomain('bootpay-with-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages');

function init_bootpay_plugin()
{
    if (!class_exists('WC_Payment_Gateway') || class_exists('WC_Gateway_Bootpay')) {
        return;
    }

    class WC_Gateway_Bootpay extends WC_Payment_Gateway
    {

        const CURRENT_VERSION = '2.0.10';
        const BOOTPAY_WC_DOMAIN = 'bootpay-with-woocommerce';
        const PAYMENT_SUBMIT = 'BOOTPAY_PAYMENT_SUBMIT';
        const VBANK_NOTIFICATION = 'BOOTPAY_VBANK_NOTI';

        static $loadActions = false;

        public function __construct()
        {
            $this->id = 'bootpay_gateway';
            $this->method_title = __('부트페이', self::BOOTPAY_WC_DOMAIN);
            $this->method_description = __('* 부트페이를 이용하여 결제를 할 수 있습니다.', self::BOOTPAY_WC_DOMAIN);
            $this->has_fields = true;
            $this->supports = ['products', 'refunds'];
            $this->enabled = $this->is_valid_currency();

            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            if (!static::$loadActions) {
                $this->load_actions();
            }

        }

        public function init_form_fields()
        {
            $this->form_fields = [
                'enabled' => [
                    'title' => __('Enable/Disable', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('부트페이 결제 활성화', self::BOOTPAY_WC_DOMAIN),
                    'default' => 'yes'
                ],
                'title' => [
                    'title' => __('Title', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('결제 정보 수단 이름', self::BOOTPAY_WC_DOMAIN),
                    'default' => __('부트페이 [ 휴대폰 소액결제 / 카드 결제 ]', self::BOOTPAY_WC_DOMAIN),
                    'desc_tip' => true
                ],
                'description' => [
                    'title' => __('Customer Message', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('구매 고객에게 결제 정보에 대한 설명을 보여줍니다.', self::BOOTPAY_WC_DOMAIN),
                    'default' => __('주문확정 버튼을 누르면 결제를 진행할 수 있습니다.', self::BOOTPAY_WC_DOMAIN)
                ],
                'notification' => [
                    'title' => __('가상계좌 입금 통지 URL', self::BOOTPAY_WC_DOMAIN),
                    'type' => 'text',
                    'custom_attributes' => ['readonly' => 'true'],
                    'description' => __('URL을 복사 한 후 부트페이 관리자에서 결제연동 > Private key관리 > 웹 ( FeedbackUrl )에 붙여넣기 한 후 저장해주세요.', self::BOOTPAY_WC_DOMAIN),
                    'placeholder' => add_query_arg('wc-api', self::VBANK_NOTIFICATION, site_url()),
                    'default' => add_query_arg('wc-api', self::VBANK_NOTIFICATION, site_url())
                ],
                'js_api_key' => [
                    'title' => __('Javascript Application ID', self::BOOTPAY_WC_DOMAIN),
                    'type' => 'text',
                    'description' => __('부트페이 관리자에서 자바스크립트 Application ID를 복사한 후 붙여넣기 해주세요.', self::BOOTPAY_WC_DOMAIN),
                    'placeholder' => __('자바스크립트 Application ID를 입력해주세요.', self::BOOTPAY_WC_DOMAIN)
                ],
                'rest_api_key' => [
                    'title' => __('REST Application ID', self::BOOTPAY_WC_DOMAIN),
                    'type' => 'text',
                    'description' => __('부트페이 관리자에서 REST Application ID를 복사한 후 붙여넣기 해주세요. 결제 취소 / 검증 시에 반드시 필요합니다.', self::BOOTPAY_WC_DOMAIN),
                    'placeholder' => __('REST Application ID를 입력해주세요.', self::BOOTPAY_WC_DOMAIN)
                ],
                'private_key' => [
                    'title' => __('PRIVATE KEY', self::BOOTPAY_WC_DOMAIN),
                    'type' => 'text',
                    'description' => __('서버 인증에 필요한 PRIVATE KEY를 관리자에서 복사한 후 붙여넣기 해주세요.', self::BOOTPAY_WC_DOMAIN),
                    'placeholder' => __('PRIVATE KEY를 입력해주세요.', self::BOOTPAY_WC_DOMAIN)
                ],
                'agree_window' => [
                    'title' => __('동의창 보이기 여부', self::BOOTPAY_WC_DOMAIN),
                    'type' => 'checkbox',
                    'description' => __('부트페이 약관 동의창을 보이게 하려면 체크해주세요.', self::BOOTPAY_WC_DOMAIN)
                ]
            ];
        }

        /**
         * 지원하는 결제 리스트
         * @return bool
         */
        public function is_valid_currency()
        {
            if ($this->isSupportCurrency()) {
                $this->msg = sprintf("부트페이는 %s 화폐 결제를 지원하지 않습니다.", get_woocommerce_currency());
                return false;
            }

            return true;
        }

        /**
         * admin option 호출
         * 관리자 환경설정에서 option 및 타이틀 기능을 커스텀 한다.
         */
        public function admin_options()
        {
            $this->renderFile('header.php', [
                'title' => __('부트페이 설정', self::BOOTPAY_WC_DOMAIN)
            ]);
        }

        /**
         * Action Gateway를 호출하여 Override한다.
         */
        public function load_actions()
        {
            add_filter('login_redirect', [$this, 'bootpay_login_redirect'], 30, 3);
            add_action('woocommerce_api_' . strtolower(self::PAYMENT_SUBMIT), [$this, 'payment_validation']);
            add_action('woocommerce_api_' . strtolower(self::VBANK_NOTIFICATION), [$this, 'vbank_notification']);
            add_action('woocommerce_order_details_after_order_table', [$this, 'bootpay_order_table'], 10, 1);
            add_action("woocommerce_update_options_payment_gateways_{$this->id}", [$this, 'process_admin_options']);
            add_action("wp_ajax_bootpay_payment_response", [$this, 'pre_bootpay_payment_response']);
            add_action("admin_enqueue_scripts", [$this, 'enqueue_inject_admin_bootpay_script'], 10000);
            add_action("wp_enqueue_scripts", [$this, 'enqueue_inject_bootpay_script'], 10000);
            add_filter('wc_checkout_params', [$this, 'inject_checkout_params']);
            add_action('wp_head', [$this, 'insert_meta_tag']);
            wp_register_script('bootpay-script', plugins_url('/assets/js/bootpay.js', plugin_basename(__FILE__)));
            wp_register_script('bootpay-cdn-script', "https://cdn.bootpay.co.kr/js/bootpay-{$this->currentVersion()}.min.js");
            wp_enqueue_script('jquery');
            wp_enqueue_script('bootpay-script');
            wp_enqueue_script('bootpay-cdn-script');
            if (is_product()) {
                wp_register_script('bootpay-analystics-script', plugins_url('/assets/js/bootpay-analystics.js', plugin_basename(__FILE__)));
                wp_enqueue_script('bootpay-analystics-script');
            }
            static::$loadActions = true;
        }

        public function insert_meta_tag()
        {
            echo "\t<meta name='bootpay-application-id' content='{$this->get_option('js_api_key')}' />\n";
        }

        public function bootpay_order_table($order)
        {
            return $this->renderFile('order_detail.php', [
                'pg_name' => $order->get_meta('bootpay_pg_name'),
                'method_name' => $order->get_meta('bootpay_method_name'),
                'bankname' => $order->get_meta('bootpay_bankname'),
                'holder' => $order->get_meta('bootpay_holder'),
                'account' => $order->get_meta('bootpay_account'),
                'username' => $order->get_meta('bootpay_username'),
                'expire' => $order->get_meta('bootpay_expire'),
                'price' => number_format((int)$order->get_total())
            ]);
        }

        public function pre_bootpay_payment_response()
        {
            if (!empty($_POST['order_key'])) {
                $order_id = wc_get_order_id_by_order_key($_POST['order_key']);
                $this->renderJson($this->bootpay_payment_response($order_id));
            } else {
                $this->renderJson([
                    'result' => 'failure',
                    'messages' => __('해당되는 Order Key 정보를 찾지 못했습니다.', self::BOOTPAY_WC_DOMAIN)
                ]);
            }
        }

        /**
         * Bootpay로 주문한 데이터 결과를 가져온다.
         */
        public function bootpay_payment_response($order_id)
        {
            $order = new WC_Order($order_id);
            $checkout_url = $order->has_status([
                'processing',
                'completed'
            ]) ? $order->get_checkout_order_received_url() : $order->get_checkout_payment_url(false);
            $order_items = $order->get_items();
            if (is_array($order_items)) {
                $items = [];
                foreach ($order_items as $item_id => $item) {
                    $cats = [];
                    $terms = get_the_terms($item['product_id'], 'product_cat');
                    foreach ($terms as $term) {
                        $cats[] = $term->slug;
                    }
                    $line_price = wc_get_order_item_meta($item_id, '_line_total', true);
                    array_push($items, [
                        'item_name' => $item['name'],
                        'qty' => $item['qty'],
                        'unique' => (string)$item_id,
                        'price' => (int)($line_price / $item['qty']),
                        'cat1' => empty($cats[0]) ? '' : $cats[0],
                        'cat2' => empty($cats[1]) ? '' : $cats[1],
                        'cat3' => empty($cats[2]) ? '' : $cats[2],
                    ]);
                }
            }

            $order_data = [
                'name' => sizeof($items) > 1 ? sprintf("%s 외 %s 개", $items[0]['item_name'], sizeof($items) - 1) : $items[0]['item_name'],
                'price' => $order->get_total(),
                'items' => $items,
                'order_id' => $order_id,
                'user_info' => [
                    'username' => $order->billing_last_name . $order->billing_first_name,
                    'phone' => (empty($order->get_billing_phone()) ? '010-0000-0000' : $order->get_billing_phone()),
                    'email' => $order->billing_email,
                    'addr' => strip_tags(implode(' ', [
                        $order->get_billing_city(),
                        $order->get_billing_address_1(),
                        $order->get_billing_address_2()
                    ]))
                ]
            ];

            return [
                'result' => 'success',
                'order_key' => $order->order_key,
                'order_id' => $order_id,
                'order_data' => $order_data,
                'checkout_url' => add_query_arg([
                    'wc-api' => self::PAYMENT_SUBMIT,
                    'order_id' => $order_id
                ], $checkout_url),
            ];
        }

        /**
         * Bootpay Script 추가
         */
        public function enqueue_inject_bootpay_script()
        {
            wp_register_script('bootpay-cdn-script', "https://cdn.bootpay.co.kr/js/bootpay-{$this->currentVersion()}.min.js");
            wp_register_script('bootpay-script', plugins_url('/assets/js/bootpay.js', plugin_basename(__FILE__)));
            wp_register_script('bootpay-order-script', plugins_url('/assets/js/bootpay-order.js', plugin_basename(__FILE__)));
            // 일단 주석 checkout을 주석처리할 경우 woocommerce의 wc_checkout_params의 값을 사용 못함
//			wp_dequeue_script( 'wc-checkout' );
            wp_enqueue_script('bootpay-script');
            wp_enqueue_script('bootpay-order-script');
            wp_enqueue_script('bootpay-cdn-script', "https://cdn.bootpay.co.kr/js/bootpay-{$this->currentVersion()}.min.js", ['boot-app-id' => $this->get_option('js_app_key')]);
        }

        /**
         *
         */
        public function enqueue_inject_admin_bootpay_script()
        {
            wp_register_script('bootpay-cdn-script', "https://cdn.bootpay.co.kr/js/bootpay-{$this->currentVersion()}.min.js");
            wp_register_script('bootpay-script', plugins_url('/assets/js/bootpay.js', plugin_basename(__FILE__)));
            wp_register_script('bootpay-admin-script', plugins_url('/assets/js/bootpay-admin.js', plugin_basename(__FILE__)));
            wp_enqueue_script('bootpay-script');
            wp_enqueue_script('bootpay-admin-script');
            wp_enqueue_script('bootpay-cdn-script');
        }

        /**
         * 결제 요청시에 wc_checkout_params 값에 추가적인 field
         *
         * @param $params
         *
         * @return array
         */
        public function inject_checkout_params($params)
        {
            $params['js_api_key'] = $this->get_option('js_api_key');
            $params['show_agree_window'] = $this->get_option('agree_window');

            return $params;
        }

        public function process_payment($order_id)
        {
            $order = new WC_Order($order_id);
            $checkout_url = $order->has_status([
                'processing',
                'completed'
            ]) ? $order->get_checkout_order_received_url() : $order->get_checkout_payment_url(false);
            $order_items = $order->get_items();
            if (is_array($order_items)) {
                $items = [];
                foreach ($order_items as $item_id => $item) {
                    $cats = [];
                    $terms = get_the_terms($item['product_id'], 'product_cat');
                    foreach ($terms as $term) {
                        $cats[] = $term->slug;
                    }
                    $line_price = wc_get_order_item_meta($item_id, '_line_total', true);
                    array_push($items, [
                        'item_name' => $item['name'],
                        'qty' => $item['qty'],
                        'unique' => (string)$item_id,
                        'price' => (int)($line_price / $item['qty']),
                        'cat1' => empty($cats[0]) ? '' : $cats[0],
                        'cat2' => empty($cats[1]) ? '' : $cats[1],
                        'cat3' => empty($cats[2]) ? '' : $cats[2],
                    ]);
                }
            }
            $order_data = [
                'name' => sizeof($items) > 1 ? sprintf("%s 외 %s 개", $items[0]['item_name'], sizeof($items) - 1) : $items[0]['item_name'],
                'price' => $order->get_total(),
                'items' => $items,
                'order_id' => $order_id,
                'user_info' => [
                    'username' => $order->billing_last_name . $order->billing_first_name,
                    'phone' => (empty($order->get_billing_phone()) ? '010-0000-0000' : $order->get_billing_phone()),
                    'email' => $order->billing_email,
                    'addr' => strip_tags(implode(' ', [
                        $order->get_billing_city(),
                        $order->get_billing_address_1(),
                        $order->get_billing_address_2()
                    ]))
                ]
            ];

            return [
                'result' => 'success',
                'order_key' => $order->get_order_key(),
                'order_id' => $order_id,
                'order_data' => $order_data,
                'checkout_url' => add_query_arg([
                    'wc-api' => self::PAYMENT_SUBMIT,
                    'order_id' => $order_id
                ], $checkout_url),
            ];
        }

        public function bootpay_login_redirect($redirect_to, $request, $user)
        {
//			exit;
            return $redirect_to;
        }

        /**
         * 결제 취소 관련 로직
         * 부트페이 서버로 부터 결제가 취소가 실패한 경우엔
         * alert으로 메세지를 보이도록 wp_error를 리턴한다.
         *
         * @param int $order_id
         * @param null $amount
         * @param string $reason
         *
         * @return bool|WP_Error
         */

        public function process_refund($order_id, $amount = null, $reason = '')
        {
            $order = new WC_Order($order_id);
            $receipt_id = $order->get_meta('bootpay_receipt_id');
            BootpayApi::setConfig($this->get_option('rest_api_key'), $this->get_option('private_key'));
            $response = BootpayApi::cancel([
                'receipt_id' => $receipt_id,
                'name' => __('판매자', self::BOOTPAY_WC_DOMAIN),
                'reason' => '판매자 취소',
                'price' => $amount
            ]);

            if ($response->status == 200) {
                $result = $response->data;
                // 모두 결제 취소가 된 경우
                if ((int)$result->remain_price == 0) {
                    $order->add_order_note(sprintf(__("%s원 모두 취소 됨", self::BOOTPAY_WC_DOMAIN), number_format((int)$amount)));
                    $order->update_status('refunded');
                } else {
                    $order->add_order_note(sprintf(__(" %s원 부분 취소 됨", self::BOOTPAY_WC_DOMAIN), number_format((int)$amount)));
                }

                return true;
            } else {
                $message = sprintf(__("결제 취소 실패 %s", self::BOOTPAY_WC_DOMAIN), $response->message);
                $order->add_order_note($message);

                return new WP_Error('error', $message);
            }
        }

        public function ajax_bootpay_payment_info()
        {

        }

        /**
         * 결제 완료후 Validation을 한다.
         * Bootpay Rest를 이용하여 결제 정보를 검증한다.
         * @return WP_Error
         */
        public function payment_validation()
        {
            $receipt_id = $_GET['receipt_id'];
            $order_id = $_GET['order_id'];

            if (!empty($receipt_id) && !empty($order_id)) {
                $order = new WC_Order($order_id);
                if (in_array($order->get_status(), ['processing', 'completed'])) {
                    $order->add_order_note(__('이미 결제 처리가 완료되었습니다.', self::BOOTPAY_WC_DOMAIN));

                    return new WP_Error('error', __('이미 결제 처리가 완료되었습니다.', self::BOOTPAY_WC_DOMAIN));
                }

                BootpayApi::setConfig($this->get_option('rest_api_key'), $this->get_option('private_key'));
                try {
                    $response = BootpayApi::confirm([
                        'receipt_id' => $receipt_id
                    ]);
                } catch (Exception $e) {
                    $order->update_status('failed');
                    $order->add_order_note(__('결제가 원활히 진행되지 못했습니다.', self::BOOTPAY_WC_DOMAIN));

                    return new WP_Error('error', __('결제가 원활히 진행되지 못했습니다.', self::BOOTPAY_WC_DOMAIN));
                }
                $result = $response->data;
                if ($response->status == 200 && $result) {
                    if ($result->status == 1 || ($result->method == 'vbank' && $result->status == 2)) {
                        if ((int)$result->price != (int)$order->get_total()) {
                            $order->add_order_note(__('결제된 금액이 일치하지 않습니다.', self::BOOTPAY_WC_DOMAIN));

                            return new WP_Error('error', __('결제된 금액이 일치하지 않습니다.', self::BOOTPAY_WC_DOMAIN));
                        }
                        if ($order_id != $result->order_id) {
                            $order->add_order_note(__('결제 정보가 일치하지 않습니다.', self::BOOTPAY_WC_DOMAIN));

                            return new WP_Error('error', __('결제 정보가 일치하지 않습니다.', self::BOOTPAY_WC_DOMAIN));
                        }
                        $transaction_id = $order->get_transaction_id();
                        if ($result->method == 'vbank') {
                            $order->update_status('wc-wating-bank', __('입금대기', self::BOOTPAY_WC_DOMAIN));
                            $order->set_payment_method($this->id);
                            $order->set_payment_method_title($this->get_payment_method_title($result));
                            $order->add_order_note($this->get_payment_method_title($result) . __(' 입금대기', self::BOOTPAY_WC_DOMAIN));
                            add_post_meta($order_id, 'bootpay_receipt_id', $result->receipt_id);
                            add_post_meta($order_id, 'bootpay_pg_name', $result->pg_name);
                            add_post_meta($order_id, 'bootpay_method_name', $result->method_name);
                            add_post_meta($order_id, 'bootpay_pg', $result->pg);
                            add_post_meta($order_id, 'bootpay_method', $result->method);
                            add_post_meta($order_id, 'bootpay_bankname', $result->payment_data->bankname);
                            add_post_meta($order_id, 'bootpay_holder', $result->payment_data->accountholder);
                            add_post_meta($order_id, 'bootpay_account', $result->payment_data->account);
                            add_post_meta($order_id, 'bootpay_username', $result->payment_data->username);
                            add_post_meta($order_id, 'bootpay_expire', $result->payment_data->expiredate);
                        } else {
                            // TODO: 나중에 Float형으로 변경해야 한다.

                            $order->payment_complete($transaction_id);
                            $order->set_payment_method($this->id);
                            $order->set_payment_method_title($this->get_payment_method_title($result));
                            $order->add_order_note($this->get_payment_method_title($result) . __(' 로 지불됨', self::BOOTPAY_WC_DOMAIN));
                            add_post_meta($order_id, 'bootpay_receipt_id', $result->receipt_id);
                            add_post_meta($order_id, 'bootpay_pg_name', $result->pg_name);
                            add_post_meta($order_id, 'bootpay_method_name', $result->method_name);
                            add_post_meta($order_id, 'bootpay_pg', $result->pg);
                            add_post_meta($order_id, 'bootpay_method', $result->method);
                        }
                        // 구매가 완료되었으므로 카트를 지운다.
                        wc_empty_cart();
                        wp_redirect($order->get_checkout_order_received_url());
                    }
                } else {
                    $order->add_order_note($result->message);

                    return new WP_Error('error', $result->message);
                }
            } else {
                return new WP_Error('error', __('잘못된 결제 접근 입니다.', self::BOOTPAY_WC_DOMAIN));
            }
        }

        /**
         * 가상계좌 입금시 Notification 처리하는 곳
         */
        public function vbank_notification()
        {
            $receipt_id = $_POST['receipt_id'];
            $order_id = $_POST['order_id'];
            $price = (int)$_POST['price'];
            $private_key = $_POST['private_key'];
            // 가상 계좌만 처리하고 나머지는 처리하지 않는다.
            // 서버에 질의하여 검증하는 로직이 있으므로 다른 결제는 유효성 검사를 하지 않는다. ( 중복 방지 )
            if ($_POST['method'] == 'vbank') {
                $order = new WC_Order($order_id);
                if (in_array($order->get_status(), ['processing', 'completed'])) {
                    $order->add_order_note(__('이미 결제 처리가 완료되었습니다.', self::BOOTPAY_WC_DOMAIN));
                    echo 'error 1';
                    exit;
                }
                if ($this->get_option('private_key') != $private_key) {
                    $order->add_order_note(__('결제 비밀키가 일치하지 않아 가상계좌 결제가 승인되지 않았습니다.', self::BOOTPAY_WC_DOMAIN));
                    echo 'error 2';
                    exit;
                }
                if ((int)$price != (int)$order->get_total()) {
                    $order->add_order_note(__('결제된 금액이 일치하지 않아 가상계좌 결제가 승인이 되지 않았습니다.', self::BOOTPAY_WC_DOMAIN));
                    echo 'error 3';
                    exit;
                }
                $transaction_id = $order->get_transaction_id();
                $order->payment_complete($transaction_id);
                $order->update_status('processing');
                $order->set_payment_method($this->id);
                $order->set_payment_method_title(sprintf("%s - %s", $_POST['pg_name'], $_POST['method_name']));
                $order->add_order_note(sprintf("%s - %s", $_POST['pg_name'], $_POST['method_name']) . __(' 로 지불됨', self::BOOTPAY_WC_DOMAIN));
                add_post_meta($order_id, 'bootpay_receipt_id', $receipt_id);
                add_post_meta($order_id, 'bootpay_pg_name', $_POST['pg_name']);
                add_post_meta($order_id, 'bootpay_method_name', $_POST['method_name']);
                add_post_meta($order_id, 'bootpay_pg', $_POST['pg']);
                add_post_meta($order_id, 'bootpay_method', $_POST['method']);
            }
            echo 'OK';
            exit;
        }

        private function get_payment_method_title($result)
        {
            return sprintf("부트페이 [%s - %s]", $result->pg_name, $result->method_name);
        }

        private function currentVersion()
        {
            return self::CURRENT_VERSION;
        }

        /**
         * @param      $file - Template 파일명
         * @param null $data - Template에서 사용할 Local Variable
         *                   View Template 기본 엔진 기능
         */
        private function renderFile($file, $data = null)
        {
            $template_path = __DIR__ . '/templates/' . $file;
            if (file_exists($template_path)) {
                if (!is_null($data) && is_array($data)) {
                    foreach ($data as $key => $value) {
                        $$key = $value;
                    }
                }
                ob_start();
                include($template_path);
                ob_end_flush();
            } else {
                $this->renderError(__("$template_path 파일이 없습니다.", self::BOOTPAY_WC_DOMAIN));
            }

            return;
        }

        /**
         * 에러가 났을 경우 에러를 뿌리고 바로 나간다.
         * javascript console.error도 함께 출력한다.
         *
         * @param $msg
         */
        private function renderError($msg, $console = true)
        {
            $this->renderFile('error.php', [
                'errorMsg' => $msg,
                'console' => $console
            ]);
            exit;
        }

        private function isSupportCurrency()
        {
            return !(in_array(get_woocommerce_currency(), ['KRW']));
        }

        private function renderJson($response)
        {
            header('Content-type: application/json');
            echo json_encode($response);
            wp_die();
        }
    }

    new WC_Gateway_Bootpay();
}