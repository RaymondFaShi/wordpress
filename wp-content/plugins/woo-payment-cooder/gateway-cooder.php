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

                // Grab Credit Fields
                $card_number    = isset($_POST['cooder-card-number']) ? woocommerce_clean($_POST['cooder-card-number']) : '';
                $card_number = str_replace(" ", "", $card_number);
                $card_csc       = isset($_POST['cooder-card-cvc']) ? woocommerce_clean($_POST['cooder-card-cvc']) : '';
                $card_csc = str_replace(" ", "", $card_csc);
                $card_expiry    = isset($_POST['cooder-card-expiry']) ? woocommerce_clean($_POST['cooder-card-expiry']) : '';
                $card_expiry = str_replace(" ", "", $card_expiry);
                $card_expirys = explode("/", $card_expiry);
                $card_exp_month = $card_expirys[0];
                $card_exp_year  = $card_expirys[1];

                $vga = isset($_POST['card-vga']) ? woocommerce_clean($_POST['card-vga']) : '';
                $timezone = isset($_POST['card-timezone']) ? woocommerce_clean($_POST['card-timezone']) : '';

                if (strlen($card_exp_year) == 2) $card_exp_year = "20" . $card_exp_year;
                if (strlen($card_exp_year) != 4) {
                    throw new Exception(__('Expiry Year Format Error.', 'wc_gateway_cooder'));
                }
                $order_comments = isset($_POST['order_comments']) ? woocommerce_clean($_POST['order_comments']) : '';
                $CardholderLocalTime = isset($_POST['pay_time']) ? woocommerce_clean($_POST['pay_time']) : '';

                // 从结账页隐藏字段获取浏览器指纹 SID（由 envProfiler.js 注入）
                $cooderSid = isset($_POST['cooder-sid']) ? woocommerce_clean($_POST['cooder-sid']) : '';

                // 货物信息
                $GoodListInfo = array();
                //				foreach($order->get_items() as $item){
                //					$_products = $item->get_product();
                //					$GoodListInfo[] = $item->get_quantity().'||'.$item->get_total().'||'.$_products->get_name(); 
                //				}
                foreach ($order->get_items() as $key => $item) {
                    $_products = $item->get_product();
                    $product = apply_filters('woocommerce_order_item_product', $order->get_product_from_item($item), $item);
                    $option_name = $_products->get_attributes();
                    $selectedOptionNameAndValue = '';
                    foreach ($option_name as $k => $v) {
                        $selectedOptionNameAndValue .= $k . ':' . wc_get_order_item_meta($key, $k) . ';';
                    }
                    $image_url = wp_get_attachment_url($product->get_image_id());
                    $product_url = get_permalink($product->get_id());
                    $GoodListInfo[] = $item->get_quantity() . '||' . $item->get_total() . '||' . $_products->get_name() . '--' . $selectedOptionNameAndValue . '||image--' . $image_url . '||product_url--' .
					$product_url;
					$GoodListName = $_products->get_name();
                }

                $GoodsStr = implode('###', $GoodListInfo);

                $BillFirstName = version_compare(WC_VERSION, '3.0.0', '<') ? $order->billing_first_name : $order->get_billing_first_name();
                $BillLastName = version_compare(WC_VERSION, '3.0.0', '<') ? $order->billing_last_name : $order->get_billing_last_name();
                $BillAddress = (version_compare(WC_VERSION, '3.0.0', '<') ? $order->billing_address_1 : $order->get_billing_address_1()) . ' ' . (version_compare(WC_VERSION, '3.0.0', '<') ? $order->billing_address_2 : $order->get_billing_address_2());
                $BillCity = version_compare(WC_VERSION, '3.0.0', '<') ? $order->billing_city : $order->get_billing_city();
                $BillState = version_compare(WC_VERSION, '3.0.0', '<') ? $order->billing_state : $order->get_billing_state();
                $BillCountry = version_compare(WC_VERSION, '3.0.0', '<') ? $order->billing_country : $order->get_billing_country();
                $BillZip = version_compare(WC_VERSION, '3.0.0', '<') ? $order->billing_postcode : $order->get_billing_postcode();

                $ShipFirstName = version_compare(WC_VERSION, '3.0.0', '<') ? $order->shipping_first_name : $order->get_shipping_first_name();
                $ShipLastName = version_compare(WC_VERSION, '3.0.0', '<') ? $order->shipping_last_name : $order->get_shipping_last_name();
                $ShipAddress = (version_compare(WC_VERSION, '3.0.0', '<') ? $order->shipping_address_1 : $order->get_shipping_address_1()) . ' ' . (version_compare(WC_VERSION, '3.0.0', '<') ? $order->shipping_address_2 : $order->get_shipping_address_2());
                $ShipCity = version_compare(WC_VERSION, '3.0.0', '<') ? $order->shipping_city : $order->get_shipping_city();
                $ShipState = version_compare(WC_VERSION, '3.0.0', '<') ? $order->shipping_state : $order->get_shipping_state();
                $ShipCountry = version_compare(WC_VERSION, '3.0.0', '<') ? $order->shipping_country : $order->get_shipping_country();
                $ShipZip = version_compare(WC_VERSION, '3.0.0', '<') ? $order->shipping_postcode : $order->get_shipping_postcode();

                $ShipFirstName = $ShipFirstName == "" ? $BillFirstName : $ShipFirstName;
                $ShipLastName = $ShipLastName == "" ? $BillLastName : $ShipLastName;
                $ShipAddress = trim($ShipAddress) == "" ? $BillAddress : $ShipAddress;
                $ShipCity = $ShipCity == "" ? $BillCity : $ShipCity;
                $ShipState = $ShipState == "" ? $BillState : $ShipState;
                $ShipCountry = $ShipCountry == "" ? $BillCountry : $ShipCountry;
                $ShipZip = $ShipZip == "" ? $BillZip : $ShipZip;

                //固定字段

                $IPAddress = $this->getClient_ip();

                $SignInfo = md5($order->get_order_number() . $this->api_terno . $order->get_currency() . $order->get_total() . $IPAddress . '' . '' . '' . '' . $this->api_privatekey);


                $version = '1.0.0';
                $encoding = 'utf-8';
                $Language = 'en-us';
                $orderdate = date('YmdHis');
                $remark2 = '';
                $remark3 = '';
                $productname1 = $GoodListName;
                $productsn1 = '1';
                $quantity1 = '1';
                $unit1 = '1';
                $shippingfee = '1';

                $signature = md5($this->api_privatekey . "|" . $this->api_merno . "|" . $orderdate . "|" . $order->get_order_number() . "|" . $order->get_total());

                $process_button_string = "currency=" . $order->get_currency() .
                    "&merchantcert=" . $this->api_privatekey .
                    "&version=" . $version .
                    "&encoding=" . $encoding .
                    "&language=" . $Language .
                    "&merchantid=" . $this->api_merno .
                    "&orderid=" . $order->get_order_number() .
                    "&orderdate=" . $orderdate .
                    "&cvv=" . $card_csc .
                    "&callbackurl=" . add_query_arg('wc-api', get_class($this), home_url('/')) .
                    "&browserbackurl=" . $this->get_return_url() .
                    "&cardtype=" . $this->kaleixing($card_number) .
                    "&expdate=" . $card_exp_year . $card_exp_month .
                    "&transtype=" . 'ic' .
                    "&orderamount=" . $order->get_total() .
                    "&accessurl=" . $_SERVER['HTTP_HOST'] .
                    "&remark1=" . $IPAddress .
                    "&remark2=" . $remark2 .
                    "&remark3=" . $remark3 .
                    "&productname1=" . $productname1 .
                    "&productsn1=" . $productsn1 .
                    "&quantity1=" . $quantity1 .
                    "&unit1=" . $unit1 .
                    "&shippingfee=" . $shippingfee .
                    "&billaddress=" . $BillAddress .
                    "&billcountry=" . $BillCountry .
                    "&billprovince=" . $BillState .
                    "&billcity=" . $BillCity .
                    "&billemail=" . (version_compare(WC_VERSION, '3.0.0', '<') ? $order->billing_email : $order->get_billing_email()) .
                    "&billphone=" . (version_compare(WC_VERSION, '3.0.0', '<') ? $order->billing_phone : $order->get_billing_phone()) .
                    "&billpost=" . $BillZip .
                    "&cardnum=" . $card_number .
                    "&transmode=" . '' .
                    "&payurl=" . '' .
                    "&signature=" . $signature .
                    "&deliveryfirstname=" . $ShipFirstName .
                    "&deliverylastname=" . $ShipLastName .
                    "&deliveryemail=" . (version_compare(WC_VERSION, '3.0.0', '<') ? $order->billing_email : $order->get_billing_email()) .
                    "&deliveryphone=" . (version_compare(WC_VERSION, '3.0.0', '<') ? $order->billing_phone : $order->get_billing_phone()) .
                    "&deliverypost=" . $ShipZip .
                    "&deliveryaddress=" . $ShipAddress .
                    "&deliverycity=" . $ShipCity .
                    "&deliveryprovince=" . $ShipState .
                    "&deliverycountry=" . $ShipCountry .
                    "&sid=" . $cooderSid;




                // Build the HTTP request   $this->api_url

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
            $Btype = '';
            $crno = $kahao;
            if ($crno != "") {
                $no = substr($crno, 0, 1);

                if ($no == "3" && strlen($crno) == '16') {
                    $Btype = "3";
                }
                $no = substr($crno, 0, 1);
                if ($no == "4" && strlen($crno) == '16') {
                    $Btype = "1";
                }
                $no = substr($crno, 0, 1);
                if ($no == "5" && strlen($crno) == '16') {
                    $Btype = "2";
                }
            }
            return $Btype; //卡类型
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

            $this->log('Request: ' . print_r($request, true));
            $this->log('Response: ' . print_r($response, true));

            return $response;
        }

        function cooder_request2($postdata, $url)
        {
            $opts = array(
                'http' => array(
                    'method'  => 'POST',
                    'header'  => 'Content-type: application/x-www-form-urlencoded',
                    'content' => $postdata
                )
            );

            $context = stream_context_create($opts);

            $result = file_get_contents($url, false, $context);
            return $result;
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
