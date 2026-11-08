<?php

/**
 * @wordpress-plugin
 * Plugin Name: 		Woo Payments with Cooder
 * Plugin URI: 			http://www.cooderpay.com
 * Description: 		Process credit cards in WooCommerce with a Cooder merchant account.
 * Version: 			1.0.0
 * Author: 				Cooder
 * Author URI: 			http://www.cooderpay.com
 *
 *************
 * Attribution
 *************
 * Woo Payment Processing - Cooder is a derivative work of the code from WooThemes / WebDevStudios,
 * which is licensed with GPLv3.  This code is also licensed under the terms
 * of the GNU Public License, version 3.
 */

add_action('plugins_loaded', 'woocommerce_cooder_init', 0);
function woocommerce_cooder_init()
{

    if (! class_exists('WC_Payment_Gateway')) return;

    load_plugin_textdomain('wc_gateway_cooder', false, dirname(plugin_basename(__FILE__)) . '/languages');

    /**
     * Gateway class
     **/
    class WC_Gateway_Cooder extends WC_Payment_Gateway_CC
    {

        private $logger = false;
        public $liveurl;
        public $debug;
        public $api_url;
        public $api_merno;
        public $api_terno;
        public $api_privatekey;

        function __construct()
        {

            // Setup our default vars
            $this->id                 = 'cooder';
            $this->method_title       = __('Cooder', 'wc_gateway_cooder');
            $this->method_description = __('Payment by credit card', 'wc_gateway_cooder');
            $this->icon               = plugins_url('/images/cards.png', __FILE__);
            $this->has_fields         = true;
            $this->supports           = array('products');
            $this->liveurl            = '';

            // Load the form fields
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();

            // Get setting values
            $this->title       = $this->settings['title'];
            $this->description = $this->settings['description'];
            $this->enabled     = $this->settings['enabled'];
            $this->debug       = $this->settings['debug'];


            $this->api_url      = $this->settings['api_uri'] != "" ? $this->settings['api_uri'] : $this->liveurl;
            $this->api_merno = $this->settings['api_merno'];
            $this->api_terno = $this->settings['api_terno'];
            $this->api_privatekey = $this->settings['api_privatekey'];

            // Hooks
            add_action('admin_notices', array(&$this, 'checks'));
            add_action('woocommerce_update_options_payment_gateways',              array(&$this, 'process_admin_options'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
            add_action('woocommerce_api_' . strtolower(get_class($this)), array(&$this, 'cooder_result'));
            add_filter('woocommerce_credit_card_form_fields', array(&$this, 'credit_card_form_fields'), 10, 2);
        }



        /**
         * Check if SSL is enabled and notify the user
         **/
        function checks()
        {
            global $woocommerce;

            if ($this->enabled == 'no')
                return;
        }

        /**
         * Mark card fields as required and expose a visible red required label.
         *
         * @param array  $fields Credit card fields.
         * @param string $gateway_id Gateway ID.
         * @return array
         */
        public function credit_card_form_fields($fields, $gateway_id)
        {
            if ($this->id !== $gateway_id) {
                return $fields;
            }

            foreach ($fields as $key => $field) {
                if (false === strpos((string) $field, 'cooder-card-')) {
                    continue;
                }
                $field = preg_replace('/<input /', '<input required="required" aria-required="true" ', (string) $field, 1);
                $field = str_replace('<span class="required">*</span>', '<span class="required">必須</span>', (string) $field);
                $fields[$key] = $field;
            }

            return $fields;
        }

        /**
         * Check if this gateway is enabled and available in the user's country
         */
        function is_available()
        {
            global $woocommerce;

            if ($this->enabled == 'yes') {



                return true;
            }

            return false;
        }

        /**
         * Initialise Gateway Settings Form Fields
         */
        function init_form_fields()
        {

            $this->form_fields = array(
                'enabled' => array(
                    'title'       => __('Enable/Disable', 'wc_gateway_cooder'),
                    'label'       => __('Enable Cooder', 'wc_gateway_cooder'),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => __('Title', 'wc_gateway_cooder'),
                    'type'        => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'wc_gateway_cooder'),
                    'default'     => __('Credit Card', 'wc_gateway_cooder')
                ),
                'description' => array(
                    'title'       => __('Description', 'wc_gateway_cooder'),
                    'type'        => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'wc_gateway_cooder'),
                    'default'     => 'Pay with your credit card.'
                ),
                'api_merno' => array(
                    'title'       => __('Merchant ID', 'wc_gateway_cooder'),
                    'type'        => 'text',
                    'description' => sprintf(__('Cooder Merchant No can be located in your Cooder <a href="%s">merchant interface.</a>', 'wc_gateway_cooder'), 'http://www.123456.com'),
                    'default'     => ''
                ),
                'api_terno' => array(
                    'title'       => __('TerminalNo ID', 'wc_gateway_cooder'),
                    'type'        => 'text',
                    'description' => sprintf(__('Cooder TerminalNo No can be located in your Cooder <a href="%s">TerminalNo interface.</a>', 'wc_gateway_cooder'), 'http://www.123456.com'),
                    'default'     => ''
                ),
                'api_privatekey' => array(
                    'title'       => __('API Transaction Key', 'wc_gateway_cooder'),
                    'type'        => 'text',
                    'description' => __('Your API credentials can be located in your Cooder merchant interface.', 'wc_gateway_cooder'),
                    'default'     => ''
                ),
                'api_uri' => array(
                    'title'       => __('API uri', 'wc_gateway_cooder'),
                    'type'        => 'text',
                    'description' => __('Your API credentials can be located in your Cooder uri.', 'wc_gateway_cooder'),
                    'default'     => 'http://www.123456.com'
                ),
                'debug' => array(
                    'title'       => __('Debug Log', 'wc_gateway_cooder'),
                    'label'       => __('Enable Logging', 'wc_gateway_cooder'),
                    'type'        => 'checkbox',
                    'description' => __('Log Cooder events inside <code>/wp-content/uploads/wc-logs/cooder-{tag}.log</code>', 'wc_gateway_cooder'),
                    'default'     => 'no'
                ),
            );
        }

        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis
         */
        function admin_options()
        {
?>
            <h3><?php echo $this->method_title; ?></h3>
            <p><?php echo $this->method_description; ?></p>
            <table class="form-table">
                <?php $this->generate_settings_html(); ?>
            </table><!--/.form-table-->
<?php

        }

        /**
         * Payment form on checkout page
         */
        function payment_fields()
        {
            global $woocommerce;
            // 输出支付描述
            if ($this->description) {
                echo '<div class="cooder-payment-description">' . wpautop(wptexturize($this->description)) . '</div>';
            }
            // 添加字体样式
            echo '<style>
                .payment_method_cooder .cooder-payment-description {
                    font-family: "SimHei", "Heiti SC", "黑体", sans-serif;
                    color: #000000;
                }
                .payment_method_cooder {
                    font-family: "SimHei", "Heiti SC", "黑体", sans-serif;
                    color: #000000;
                }
                .woocommerce-cooder-payment-form label {
                    font-family: inherit;
                    color: inherit;
                }
                
            </style>';
            // 包裹表单并应用样式类
            echo '<div class="woocommerce-cooder-payment-form">';
            // 调用父类的信用卡表单方法
            //parent::payment_fields();
            $this->form();
            echo '</div>';

            // 隐藏字段：用于在结账页收集浏览器指纹 SID 并随订单提交
            echo '<input type="hidden" name="cooder-sid" id="cooder-sid" value="" />';

            // 加载 envProfiler.js，调用 getBrowserInfoSid() 获取 SID
            echo '<script src="https://console.forallhk.com/envProfiler.js"></script>';
            echo '<script>
            (function(){
                var retryCount = 0;
                var maxRetry = 10;
                function fetchSid(){
                    if(typeof getBrowserInfoSid !== "function"){
                        retry();
                        return;
                    }
                    getBrowserInfoSid().then(function(res){
                        console.log("Cooder SID 接口返回：", res);
                        var sid = (res && res.sid) ? res.sid : "";
                        if(sid){
                            var input = document.getElementById("cooder-sid");
                            if(input){ input.value = sid; }
                        } else {
                            retry();
                        }
                    }).catch(function(err){
                        console.error("Cooder 获取 SID 失败：", err);
                        retry();
                    });
                }
                function retry(){
                    retryCount++;
                    if(retryCount > maxRetry){ return; }
                    setTimeout(fetchSid, 500);
                }
                setTimeout(fetchSid, 800);
            })();
            </script>';
        }

        /**
         * Process the payment
         */
        function process_payment($order_id)
        {
            global $woocommerce;

            // Setup our order object
            $order = new WC_Order($order_id);

            // Use Cooder CURL API for payment
            try {

                $card_data     = $this->get_posted_card_data();
                $request_fields = $this->build_gateway_request_fields($order, $card_data);
                $process_button_string = http_build_query($request_fields, '', '&', PHP_QUERY_RFC1738);

                $response = $this->cooder_request2($process_button_string, $this->api_url);

                $this->log($response);
                $this->log($this->api_url);

                // Handle response
                if (empty($response)) {
                    $this->log('empty');
                } else {
                    $this->log('no empty');
                }

                /*if( empty($response['body']) ) {
    				throw new Exception( __('Empty response from payment gateway.', 'wc_gateway_cooder') );
    			}*/

                //$payJson = json_decode($response['body'],true);
                $payJson = json_decode($response, true);

                $order_note = $payJson['orderid'] ?? 'TEST-TX-' . time(); // fallback value

                if ($payJson['responseData_code'] == 'require3D') {
                    //@session_start();
                    //$_SESSION['redirect_url'] = base64_decode($payJson['responseData_msg']);
                    $this->log('redirect_url:'.base64_decode($payJson['responseData_msg']));
                    $this->log('responseData_msg:'.$payJson['responseData_msg']);
                    return array(
                        'result'     => 'success',
                        'redirect'    => base64_decode($payJson['responseData_msg'])
                    );
                } else {

                    // $order_note = $payJson['orderid'];


                    if ($payJson['responseData_code'] == "0000") {
                        // Add order note
                        $order->add_order_note(sprintf(__('payment completed ', 'wc_gateway_cooder')));

                        // Payment complete
                        $order->payment_complete($order_note);

                        // Remove cart
                        $woocommerce->cart->empty_cart();

                        // Return thank you page redirect
                        return array(
                            'result'     => 'success',
                            'redirect'    => $this->get_return_url($order)
                        );
                    } else {
                        $order->update_status('failed', sprintf(__('Gateway returned error: %s', 'wc_gateway_cooder'), $order_note));

                        //throw new Exception( __($ResultMessage, 'wc_gateway_cooder') );
                        $woocommerce->cart->empty_cart();

                        // Return thank you page redirect
                        return array(
                            'result'     => 'failed',
                            'redirect'    => $this->get_return_url($order)
                        );
                    }
                }
            } catch (Exception $e) {
                $this->log('Payment exception: ' . $e->getMessage());
                wc_add_notice(__('Payment error: ' . $e->getMessage(), 'wc_gateway_cooder'), 'error');

                if (!$order->get_transaction_id()) {
                    $order->update_status('failed', sprintf(__('<strong>Error:</strong> %s', 'wc_gateway_cooder'), $e->getMessage()));
                }

                return [
                    'result' => 'failure',
                ];
            }
        }

        /**
         * Read and validate card fields posted by WooCommerce checkout.
         *
         * @return array
         * @throws Exception When a required card field is missing or malformed.
         */
        private function get_posted_card_data()
        {
            $card_number = isset($_POST['cooder-card-number'])
                ? preg_replace('/\D+/', '', wp_unslash($_POST['cooder-card-number']))
                : '';
            $card_csc = isset($_POST['cooder-card-cvc'])
                ? preg_replace('/\D+/', '', wp_unslash($_POST['cooder-card-cvc']))
                : '';
            $card_expiry = isset($_POST['cooder-card-expiry'])
                ? preg_replace('/\s+/', '', sanitize_text_field(wp_unslash($_POST['cooder-card-expiry'])))
                : '';

            if (!preg_match('/^\d{12,20}$/', $card_number)) {
                throw new Exception(__('カード番号を正しく入力してください。', 'wc_gateway_cooder'));
            }
            if (!preg_match('/^\d{3,5}$/', $card_csc)) {
                throw new Exception(__('セキュリティコードを正しく入力してください。', 'wc_gateway_cooder'));
            }
            if (!preg_match('/^(\d{1,2})\/(\d{2}|\d{4})$/', $card_expiry, $expiry_parts)) {
                throw new Exception(__('有効期限を正しく入力してください。', 'wc_gateway_cooder'));
            }

            $card_exp_month = (int) $expiry_parts[1];
            $card_exp_year = (int) $expiry_parts[2];
            if ($card_exp_year < 100) {
                $card_exp_year += 2000;
            }
            if ($card_exp_month < 1 || $card_exp_month > 12) {
                throw new Exception(__('有効期限の月を正しく入力してください。', 'wc_gateway_cooder'));
            }

            $expdate = (string) $card_exp_year . str_pad((string) $card_exp_month, 2, '0', STR_PAD_LEFT);
            if ($expdate < date('Ym')) {
                throw new Exception(__('カードの有効期限が切れています。', 'wc_gateway_cooder'));
            }

            return array(
                'number' => $card_number,
                'cvv' => $card_csc,
                'expdate' => $expdate,
                'type' => $this->kaleixing($card_number),
            );
        }

        /**
         * Build the DOOPSUN V2.0 form fields.
         *
         * Card values are sent only in the request body and are never logged or stored.
         *
         * @param WC_Order $order Order.
         * @param array    $card_data Validated card data.
         * @return array
         */
        private function build_gateway_request_fields($order, $card_data)
        {
            $order_id = (string) $order->get_id();
            if (!preg_match('/^[A-Za-z0-9]{1,20}$/', $order_id)) {
                throw new Exception(__('注文番号がDOOPSUNの形式に適合していません。', 'wc_gateway_cooder'));
            }

            $order_date = date('YmdHis');
            $order_amount = $this->format_gateway_amount($order->get_total());
            $shipping_fee = $this->format_gateway_amount(
                (float) $order->get_shipping_total() + (float) $order->get_shipping_tax()
            );
            $billing_email = (string) $order->get_billing_email();
            $billing_phone = (string) $order->get_billing_phone();
            $billing_address = trim($order->get_billing_address_1() . ' ' . $order->get_billing_address_2());
            $shipping_address = trim($order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2());
            $shipping_first_name = $order->get_shipping_first_name() ?: $order->get_billing_first_name();
            $shipping_last_name = $order->get_shipping_last_name() ?: $order->get_billing_last_name();

            $fields = array(
                'version' => '1.0.0',
                'encoding' => 'utf-8',
                'language' => 'ja-jp',
                'merchantid' => (string) $this->api_merno,
                'orderid' => $order_id,
                'orderdate' => $order_date,
                'currency' => strtoupper((string) $order->get_currency()),
                'orderamount' => $order_amount,
                'transtype' => 'ic',
                'callbackurl' => add_query_arg('wc-api', get_class($this), home_url('/')),
                'browserbackurl' => $this->get_return_url($order),
                'accessurl' => home_url('/'),
                'remark1' => mb_substr($this->getClient_ip(), 0, 20),
                'remark2' => mb_substr((string) $order->get_customer_note(), 0, 20),
                'remark3' => '',
                'shippingfee' => $shipping_fee,
                'merchantcert' => (string) $this->api_privatekey,
                'transmode' => '0',
            );

            $fields += $this->build_product_fields($order);
            $fields['billaddress'] = $billing_address ?: $shipping_address;
            $fields['billcountry'] = $order->get_billing_country() ?: $order->get_shipping_country();
            $fields['billprovince'] = $order->get_billing_state() ?: $order->get_shipping_state();
            $fields['billcity'] = $order->get_billing_city() ?: $order->get_shipping_city();
            $fields['billemail'] = $billing_email;
            $fields['billphone'] = $billing_phone;
            $fields['billpost'] = $order->get_billing_postcode() ?: $order->get_shipping_postcode();
            $fields['deliveryfirstname'] = $shipping_first_name;
            $fields['deliverylastname'] = $shipping_last_name;
            $fields['deliveryaddress'] = $shipping_address ?: $billing_address;
            $fields['deliverycountry'] = $order->get_shipping_country() ?: $order->get_billing_country();
            $fields['deliveryprovince'] = $order->get_shipping_state() ?: $order->get_billing_state();
            $fields['deliverycity'] = $order->get_shipping_city() ?: $order->get_billing_city();
            $fields['deliveryemail'] = $billing_email;
            $fields['deliveryphone'] = $billing_phone;
            $fields['deliverypost'] = $order->get_shipping_postcode() ?: $order->get_billing_postcode();
            $fields['signature'] = md5(
                $this->api_privatekey . '|' . $this->api_merno . '|' . $order_date . '|' . $order_id . '|' . $order_amount
            );
            $fields['cardnum'] = $card_data['number'];
            $fields['cardtype'] = $card_data['type'];
            $fields['cvv'] = $card_data['cvv'];
            $fields['expdate'] = $card_data['expdate'];

            $cooder_sid = isset($_POST['cooder-sid'])
                ? sanitize_text_field(wp_unslash($_POST['cooder-sid']))
                : '';
            if ('' !== $cooder_sid) {
                $fields['sid'] = $cooder_sid;
            }

            return $fields;
        }

        /**
         * Build product slots 1-3 from WooCommerce order items.
         *
         * @param WC_Order $order Order.
         * @return array
         */
        private function build_product_fields($order)
        {
            $items = array_values($order->get_items('line_item'));
            if (!$items) {
                throw new Exception(__('注文商品が見つかりません。', 'wc_gateway_cooder'));
            }

            $slots = array();
            foreach ($items as $item) {
                $quantity = max(1, (int) $item->get_quantity());
                $line_total = (float) $item->get_total() + (float) $item->get_total_tax();
                $product = $item->get_product();
                $product_id = $item->get_variation_id() ?: $item->get_product_id();
                $sku = $product ? $product->get_sku() : '';
                $slots[] = array(
                    'name' => $this->gateway_text($item->get_name(), 50),
                    'sku' => $this->gateway_text($sku ?: (string) $product_id, 100),
                    'quantity' => $quantity,
                    'unit' => $this->format_gateway_amount($line_total / $quantity),
                );
            }

            if (count($slots) > 3) {
                $remaining = array_slice($slots, 2);
                $quantity = array_sum(array_column($remaining, 'quantity'));
                $total = 0;
                foreach ($remaining as $slot) {
                    $total += (float) $slot['unit'] * (int) $slot['quantity'];
                }
                $slots = array_slice($slots, 0, 2);
                $slots[] = array(
                    'name' => $this->gateway_text(sprintf('その他商品（%d点）', $quantity), 50),
                    'sku' => 'MULTI',
                    'quantity' => $quantity,
                    'unit' => $this->format_gateway_amount($total / max(1, $quantity)),
                );
            }

            $fields = array();
            foreach ($slots as $index => $slot) {
                $slot_number = $index + 1;
                $fields['productname' . $slot_number] = $slot['name'];
                $fields['productsn' . $slot_number] = $slot['sku'];
                $fields['quantity' . $slot_number] = (string) $slot['quantity'];
                $fields['unit' . $slot_number] = $slot['unit'];
            }

            return $fields;
        }

        /**
         * Format a monetary value for the DOOPSUN request.
         *
         * @param mixed $amount Amount.
         * @return string
         */
        private function format_gateway_amount($amount)
        {
            $formatted = wc_format_decimal($amount, 2);
            $parts = explode('.', $formatted, 2);
            return ($parts[0] === '' ? '0' : $parts[0]) . '.' . str_pad($parts[1] ?? '', 2, '0');
        }

        /**
         * Trim text to a documented field length.
         *
         * @param string $value Value.
         * @param int    $max_length Maximum length.
         * @return string
         */
        private function gateway_text($value, $max_length)
        {
            return mb_substr(wp_strip_all_tags((string) $value), 0, $max_length);
        }


        function getIp()
        {
            if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
                $ip = getenv("HTTP_CLIENT_IP");
            else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
                $ip = getenv("HTTP_X_FORWARDED_FOR");
            else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
                $ip = getenv("REMOTE_ADDR");
            else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
                $ip = $_SERVER['REMOTE_ADDR'];
            else
                $ip = "unknown";
            return ($ip);
        }

        function cooder_result()
        {
            global $woocommerce;
            header("HTTP/1.1 200 OK");

            $responseData_code = $_REQUEST['responseData_code'];
            $Orderid = $_REQUEST['Orderid'];
            $responseData_msg = $_REQUEST['responseData_msg'];
            $order_note = $Orderid;
            $respMsg = $responseData_msg;

            //$order_note = sprintf( __( '<strong>Error:</strong> %1$s', 'wc_gateway_cooder' ), $respMsg);
            $orders = new WC_Order($Orderid);
            if ($responseData_code == "0000") {
                // Add order note
                $orders->add_order_note(sprintf(__('payment completed ', 'wc_gateway_cooder')));

                // Payment complete
                $orders->payment_complete();

                // Remove cart
                $woocommerce->cart->empty_cart();
                wc_add_notice($respMsg, 'success');
                //echo '<script>window.location.href="' . $this->get_return_url($orders) . '"</script>';
                //die(0);
                exit('success');
            } else {
                $order_note = sprintf(__('<strong>Error:</strong> %1$s', 'wc_gateway_cooder'), $respMsg);
                $orders->update_status('failed', $order_note);
                wc_add_notice($order_note, 'error');
                //echo '<script>window.location.href="' . $woocommerce->cart->get_checkout_url() . '"</script>';
                //die(0);
                exit('success');
            }
        }


        function kaleixing($kahao)
        {
            $card_number = preg_replace('/\D+/', '', (string) $kahao);
            if (preg_match('/^4/', $card_number)) {
                return '1';
            }
            if (preg_match('/^(5[1-5]|2[2-7])/', $card_number)) {
                return '2';
            }
            if (preg_match('/^35/', $card_number)) {
                return '3';
            }
            return '4';
        }

        /**
         * cooder_request function.
         *
         * @access public
         * @param mixed $post_data
         * @return void
         */
        function cooder_request($request, $orderUrl)
        {

            global $woocommerce;

            /*$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $orderUrl);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
			curl_setopt($curl, CURLOPT_TIMEOUT, 60);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		   if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) 
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
			
			$tmpInfo = curl_exec($curl);
			if (curl_errno($curl)) {
				return false;
			}
			curl_close($curl);
			return $tmpInfo;*/

            // send packet and receive response
            $response = wp_remote_post($orderUrl, array(
                'body'       => $request,
                'timeout'    => 70,
                'verbose'    => true,
                'headers'    => 'Referer: ' . home_url(),
                'user-agent' => $_SERVER['HTTP_USER_AGENT']
            ));

            $this->log('Cooder HTTP request completed.');

            return $response;
        }

        function cooder_request2($postdata, $url)
        {
            $response = wp_remote_post(
                $url,
                array(
                    'timeout' => 70,
                    'headers' => array(
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Referer' => home_url('/'),
                    ),
                    'body' => $postdata,
                )
            );

            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }

            return wp_remote_retrieve_body($response);
        }

        function log($message = '')
        {
            if ('no' == $this->debug) {
                return false;
            }

            if (false == $this->logger && class_exists('WC_Logger')) {
                $this->logger = new WC_Logger();
            }

            $this->logger->add('cooder', $message);
        }

        /**
         * Get http client ip
         **/
        function getClient_ip()
        {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $online_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $online_ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
                $online_ip = $_SERVER['HTTP_X_REAL_IP'];
            } else {
                $online_ip = $_SERVER['REMOTE_ADDR'];
            }
            $ips = explode(",", $online_ip);
            return $ips[0];
        }

        function get_language_code($language)
        {
            $languagecode = strtolower($language);
            if ($languagecode == "en" || $languagecode == "english") {
                $LangCode = "en";
            } elseif ($languagecode == "fr" || $languagecode == "france" || $languagecode == "french") {
                $LangCode = "fr";
            } elseif ($languagecode == "it" || $languagecode == "italy" || $languagecode == "italian") {
                $LangCode = "it";
            } elseif ($languagecode == "ja" || $languagecode == "jp" || $languagecode == "japanese") {
                $LangCode = "ja";
            } elseif ($languagecode == "de" || $languagecode == "ge" || $languagecode == "german") {
                $LangCode = "de";
            } elseif ($languagecode == "es" || $languagecode == "sp" || $languagecode == "spanish") {
                $LangCode = "sp";
            } elseif ($languagecode == "ru" || $languagecode == "rf" || $languagecode == "russian") {
                $LangCode = "ru";
            } elseif ($languagecode == "pt" || $languagecode == "po" || $languagecode == "portuguese") {
                $LangCode = "pt";
            } elseif ($languagecode == "nl" || $languagecode == "dutch") {
                $LangCode = "nl";
            } else {
                $LangCode = "en";
            }
            return $LangCode;
        }

        function getBrowser()
        {
            if (empty($_SERVER['HTTP_USER_AGENT'])) {
                return '';
            }
            if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'rv:11.0')) {
                return 'IE';
            }
            if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'rv:11.0') || false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 10.0')) {
                return 'IE';
            }
            if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 9.0')) {
                return 'IE';
            }
            if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 8.0')) {
                return 'IE';
            }
            if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 7.0')) {
                return 'IE';
            }
            if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.0')) {
                return 'IE';
            }
            if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
                return 'IE';
            }
            if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox')) {
                return 'Firefox';
            }
            if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome')) {
                return 'Chrome';
            }
            if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'Safari')) {
                return 'Safari';
            }
            if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') || false !== strpos($_SERVER['HTTP_USER_AGENT'], 'OPR')) {
                return 'Opera';
            }
            return '';
        }

        function getBrowserLang()
        {
            $acceptLan = '';
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $acceptLan = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
                $acceptLan = $acceptLan[0];
            }
            return $acceptLan;
        }

        function isMobile()
        {
            $UserAgent = $_SERVER['HTTP_USER_AGENT'];
            $IsMobile = false;
            if (stristr($UserAgent, 'mobile') && !stristr($UserAgent, 'ipad')) {
                $IsMobile = true;
            }
            return $IsMobile;
        }
    } // end woocommerce_cooder




    /**
     * Add the Gateway to WooCommerce
     **/
    function add_cooder_gateway($methods)
    {
        $methods[] = 'WC_Gateway_Cooder';
        return $methods;
    }

    function cooder_action_links($actions, $plugin_file, $plugin_data, $context)
    {
        $custom_actions = array(
            'configure' => sprintf('<a href="%s">%s</a>', admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_cooder'), __('Configure', 'wc_gateway_cooder')),
            'docs' => sprintf('<a href="%s" target="_blank">%s</a>', 'http://www.123456.com', __('Docs', 'wc_gateway_cooder')),

        );

        // add the links to the front of the actions list
        return array_merge($custom_actions, $actions);
    }



    add_filter('woocommerce_payment_gateways', 'add_cooder_gateway');

    $basename = plugin_basename(__FILE__);
    $prefix = is_network_admin() ? 'network_admin_' : '';
    add_filter("{$prefix}plugin_action_links_$basename", 'cooder_action_links', 10, 4);
}
