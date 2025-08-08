<?php
if(!class_exists('OP_REST_API_Auth'))
{
    class OP_REST_API_Auth extends OP_REST_API{
       
        public function register_routes() {
            
                // staff  route         
                register_rest_route( $this->namespace, '/auth/login', array(
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => array($this,'login'),
                    'permission_callback' => '__return_true',
                    ) 
                );
                register_rest_route( $this->namespace, '/auth/login-register/(?P<register_id>\d+)', array(
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => array($this,'login_register'),
                    'permission_callback' => '__return_true',
                    ) 
                );
                
                register_rest_route( $this->namespace, '/auth/login-session', array(
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => array($this,'login_session'),
                    'permission_callback' => '__return_true',
                    ) 
                );
                register_rest_route( $this->namespace, '/auth/logout', array(
                    'methods' =>  WP_REST_Server::CREATABLE,
                    'callback' => array($this,'logout'),
                    'permission_callback' => array($this,'permission_callback'),
                    ) 
                );
                
                register_rest_route( $this->namespace, '/auth/logoff', array(
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => array($this,'logoff'),
                    'permission_callback' => array($this,'permission_callback'),
                    ) 
                );
                register_rest_route( $this->namespace, '/auth/logon', array(
                    'methods' =>  WP_REST_Server::CREATABLE,
                    'callback' => array($this,'logon'),
                    'permission_callback' => array($this,'permission_callback'),
                    ) 
                );
                register_rest_route( $this->namespace, '/pos-state', array(
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => array($this,'pos_state'),
                    'permission_callback' => array($this,'permission_callback'),
                    ) 
                );
        }
        private function _getAllSetting($warehouse_id){
            $setting_sections = array(
                array(
                    'id'    => 'openpos_general',
                    'title' => __( 'General', 'openpos' )
                ),
                array(
                    'id'    => 'openpos_payment',
                    'title' => __( 'Payment', 'openpos' )
                ),
                array(
                    'id'    => 'openpos_shipment',
                    'title' => __( 'Shipping', 'openpos' )
                ),
                array(
                    'id'    => 'openpos_label',
                    'title' => __( 'Barcode Label', 'openpos' )
                ),
                array(
                    'id'    => 'openpos_receipt',
                    'title' => __( 'Receipt', 'openpos' )
                ),
                array(
                    'id'    => 'openpos_pos',
                    'title' => __( 'POS Layout', 'openpos' )
                )
            );
            
            $setting = array();
            $ignore = array(
                'stripe_public_key',
                'stripe_secret_key'
            );
            
            foreach($setting_sections as $section)
            {
                $options = $this->setting_class->get_options($section['id']);
                foreach($options as $field => $value)
                {
                    $option = $field;
                    if(in_array($option,$ignore))
                    {
                        continue;
                    }
                    switch ($option)
                    {
                        case 'shipping_methods':
                            $setting_methods = $value;
                            $shipping_methods =   $this->woo_class->get_setting_shipping_methods();// WC()->shipping()->get_shipping_methods();
                            $shipping_result = array();
                            if(!is_array($setting_methods))
                            {
                                $setting_methods = array();
                            }
                            foreach ($setting_methods as $shipping_method_code)
                            {
                                foreach($shipping_methods as $shipping_method)
                                {
                                    $instance_id = $shipping_method->instance_id ? $shipping_method->instance_id : 0;

                                    $code = $shipping_method->id.':'.$instance_id;
                                
                                    if($code == $shipping_method_code)
                                    {
                                        $title = $shipping_method->title;
                                        if(!$title)
                                        {
                                            $title = $shipping_method->method_title;
                                        }
                                        if(!$title)
                                        {
                                            $title = $code;
                                        }
                                        $taxes = array();
                                        $cost = isset($shipping_method->cost) ? $shipping_method->cost : 0;
                                        
                                        $tmp = array(
                                            'code' => $code,
                                            'title' => $title,
                                            'cost' => $cost,
                                            'cost_online' => 'yes',
                                            'inclusive_tax' => 'yes',
                                            'tax_details' => $taxes
                                        );
                                        $shipping_result[] = apply_filters('op_setting_shipping_method_data',$tmp);
                                    }
                                }
                            }
                            $shipping_methods =  apply_filters('op_shipping_methods',$shipping_result);
                            $setting[$option] = $shipping_methods;
                            break;
                        case 'payment_methods':
                            $payment_gateways = WC()->payment_gateways->payment_gateways();
                            $addition_payment_gateways = $this->core_class->additionPaymentMethods();
                            $payment_gateways = array_merge($payment_gateways,$addition_payment_gateways);
                            $payment_options = $value;
                            foreach ($payment_gateways as $code => $p)
                            {
                                if($p)
                                {
                                    if(isset( $payment_options[$code]))
                                    {
                                        if(!is_object($p))
                                        {
                                            $title = $p;
                                            $payment_options[$code] = $title;
                                        }else{
                                            $title = $p->title;
                                            $payment_options[$code] = $title;
                                        }

                                    }
                                }
                            }
                            $setting[$option] = $payment_options;
                            break;
                        default:
                            $setting[$option] = $value;
                            if($option == 'receipt_template_header' || $option == 'receipt_template_footer')
                            {
                                $setting[$option] = balanceTags($setting[$option],true);
                            }
                            break;
                    }
                }
            
            }
            $setting['pos_allow_online_payment'] = $this->core_class->allow_online_payment(); // yes or no

            $setting['openpos_tables'] = array();
            $setting['payment_methods'] = $this->core_class->formatPaymentMethods($setting['payment_methods']);

            $setting['shipping_methods'] = $this->woo_class->getStoreShippingMethods($warehouse_id,$setting);
            if(isset($setting['pos_enable_weight_barcode']) && $setting['pos_enable_weight_barcode'] == 'yes')
            {
                $setting['pos_weight_barcode_prefix'] = '20';
            }

            $incl_tax_mode = $this->woo_class->inclTaxMode() == 'yes' ? true : false;
            $setting = $this->woo_class->_formatSetting($setting);
            $setting = $this->core_class->formatReceiptSetting($setting,$incl_tax_mode);
            
            return $setting;
        }
        public function login(WP_REST_Request $request){
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            
            try{
                $username = $request->get_param( 'username' );
                $password = $request->get_param( 'password' );
                $login_mode = $request->get_param( 'login_mode' );
                $location = $request->get_param( 'location' );
                $lang = $request->get_param('lang');
                $time_stamp = $request->get_param('time_stamp');
                
                
                if($login_mode == 'pin')
                {
                    $user = \Op\Models\User::login_pin($password);
                }else{
                    $user = \Op\Models\User::login($username,$password);
                }

                if(is_wp_error($user))
                {
                   $message = strip_tags($user->get_error_message());
                   throw new Exception($message);
                  
                }
                $registers = $this->register_class->registers();
                if(empty($registers))
                {
                    throw new Exception("You not assign to any register");
                }
                $allow_registers = array();
                $user_id = $user->ID;
                foreach($registers as $register)
                {
                    $warehouse_id = $register['warehouse'];
                    
                    if($register['status'] == 'publish')
                    {
                        $warehouse = $this->warehouse_class->get($warehouse_id);
                        $state = '';
                        $country = '';
                        if($warehouse['country'])
                        {
                            if(strpos($warehouse['country'],':') !== false)
                            {
                                $tmp = explode(':',$warehouse['country']);
                                $state = end($tmp);
                                $country = $tmp[0];
                            }
                        }
                        $warehouse_address_data = array(
                            'address_1'  => $warehouse['address'],
                            'address_2'  => $warehouse['address_2'],
                            'city'       => $warehouse['city'],
                            'state'      => $state,
                            'postcode'   => $warehouse['postal_code'],
                            'country'    => $country,
                        );
                        
                        $cashiers = $register['cashiers'];
                        if(in_array($user_id,$cashiers))
                        {
                            if(!empty($warehouse))
                            {
                                $allow_registers[] = array(
                                    'id' => $register['id'],
                                    'name' => $register['name'],
                                    'mode' => $register['register_mode'],
                                    'outlet_id' => $warehouse['id'],
                                    'outlet_name' => $warehouse['name'],
                                    'address' => WC()->countries->get_formatted_address( $warehouse_address_data,',' )
                                );
                            }
                            
                        }
                    }
                }

                $ip = $this->core_class->getClientIp();

                $avatar = rtrim(OPENPOS_URL,'/').'/assets/images/default_avatar.png';

                $avatar_args = get_avatar_data( $user_id);
                if($avatar_args && isset($avatar_args['url']))
                {
                    $avatar = $avatar_args['url'];
                }
                $prefix = 'api-'.sanitize_title($user->user_login);
                $session_id = $this->session_class->generate_session_id($prefix);
                
                $result['data']['result']['session'] = $session_id;
                $result['data']['result']['user'] = array(
                    'user_id' => $user_id,
                    'username' => $user->data->user_login,
                    'avatar' => $avatar,
                    'name' => $user->data->display_name,
                    'email' => $user->data->user_email,
                    'phone' => '',
                    'role' => $user->roles,
                    'login_location' => $location,
                    'login_ip' => $ip,
                    'login_time' => current_time('Y-m-d H:i:s',true),
                );
                $result['data']['result']['registers'] = $allow_registers;
                $this->session_class->save($session_id,$result['data']['result'] );
                $result['data']['status'] = 1;
                $result['code'] = 200;
                
            }catch(Exception $e){
                $result['message'] = $e->getMessage();
                $result['code'] = 400;
                $result['data']['status'] = 0;
            }
            return $this->rest_ensure_response( $result );
        }
        public function login_register(WP_REST_Request $request){
            
            $response_code = 200;
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $session_id = $request->get_param( 'session_id' );
                
                $register_id = $request->get_param( 'register_id' );
                $app_ver  = $request->get_param( 'app_ver' );
                $client_time_offset = $request->get_param( 'client_time_offset' );

                if(!$session_id)
                {
                    throw new Exception('Session not found');
                }
                $session = $this->session_class->data($session_id);
                if(!$session)
                {
                    throw new Exception('Session not found');
                }
                $user = isset($session['user']) ? $session['user'] : false;
                if(!$user)
                {
                    throw new Exception('Session not found');
                }
                $registers = isset($session['registers']) ? $session['registers'] : array();
                if(empty($registers))
                {
                    throw new Exception('You not assign to any register');
                }
                $register = false;
                foreach($registers as $re)
                {
                    if($re['id'] == $register_id)
                    {
                        $register = $re;
                        break;
                    }
                }
                if(!$register)
                {
                    throw new Exception('Register not found');
                }
                

                $currency_pos = get_option( 'woocommerce_currency_pos' );
                $default_currency  = array(
                    'decimal' => wc_get_price_decimals(),
                    'decimal_separator' => wc_get_price_decimal_separator(),
                    'thousand_separator' => wc_get_price_thousand_separator(),
                    'currency_pos' => $currency_pos,// symbol position
                    'code' => get_woocommerce_currency(), 
                    'symbol' => html_entity_decode(get_woocommerce_currency_symbol()), 
                    'rate' => 1
                );
                
                $warehouse_id = $register['outlet_id'];
                $pos_available_taxes =  $this->woo_class->getAvailableTaxes($warehouse_id);
                $pos_type = $this->setting_class->get_option('openpos_type','openpos_pos') == 'grocery' ? 'grocery': 'restaurant';
                $pos_tables = array();
                if($pos_type == 'restaurant')
                {
                    $pos_tables = $this->table_class->tables($warehouse_id,true);
                }
                $default_display =  ( $this->setting_class->get_option('openpos_type','openpos_pos') == 'grocery'  && $this->setting_class->get_option('dashboard_display','openpos_pos') =='table' ) ? 'product': $this->setting_class->get_option('dashboard_display','openpos_pos');
                
                
               

                $all_setting = $this->_getAllSetting($warehouse_id);
                
                $time_frequency = $all_setting['time_frequency'] ? (int)$all_setting['time_frequency'] : 3000 ;
                $product_sync = $all_setting['pos_auto_sync'] ;
                $pos_clear_product = $all_setting['pos_clear_product'] ;
                $pos_display_outofstock = $all_setting['pos_display_outofstock'] ;
                $accept_negative_checkout = $all_setting['accept_negative_checkout'] ;
                $pos_change_price = $all_setting['pos_change_price'] ;
                $pos_require_customer_mode = $all_setting['pos_require_customer_mode'] ;
                $pos_customer_autocomplete = $all_setting['pos_customer_autocomplete'] ;
                $pos_search_product_auto = $all_setting['pos_search_product_auto'] ;
                $pos_search_product_online = $all_setting['pos_search_product_online'] ;
                $pos_enable_weight_barcode = $all_setting['pos_enable_weight_barcode'] ;
                $pos_weight_barcode_format = $all_setting['pos_weight_barcode_format'] ;
                $pos_weight_barcode_prefix = $all_setting['pos_weight_barcode_prefix'];
                $search_result_total = $all_setting['search_result_total'];

                $pos_incl_tax_mode = $all_setting['pos_incl_tax_mode'];
                $pos_prices_include_tax = $all_setting['pos_prices_include_tax'];
                $pos_tax_included_discount = $all_setting['pos_tax_included_discount'];
                $pos_cart_discount_calc = $all_setting['pos_cart_discount'];
                $pos_item_discount_calc = isset($all_setting['pos_item_discount']) ? $all_setting['pos_item_discount'] : $pos_cart_discount_calc;
                $pos_fee_tax_class = $all_setting['pos_fee_tax_class'];
                $pos_tax_class = $all_setting['pos_tax_class'];
                $pos_default_checkout_mode = $all_setting['pos_default_checkout_mode'];
                $pos_laybuy = $all_setting['pos_laybuy'];
                $pos_allow_tip = $all_setting['pos_allow_tip'];
                $pos_tip_amount = $all_setting['pos_tip_amount'];

                $pos_money = $all_setting['pos_money'] ? $all_setting['pos_money'] : array();
                $pos_custom_item_discount_amount = $all_setting['pos_custom_item_discount_amount'] ? $all_setting['pos_custom_item_discount_amount'] : array();
                $pos_custom_cart_discount_amount = $all_setting['pos_custom_cart_discount_amount'] ? $all_setting['pos_custom_cart_discount_amount'] : array();
                $openpos_customer_addition_fields = $all_setting['openpos_customer_addition_fields'] ? $all_setting['openpos_customer_addition_fields'] : array();
                $takeaway_number = $this->table_class->getTakeawayNumber($register_id,$warehouse_id);
                
                $order_sequential_number = array(
                    'prefix' => $all_setting['pos_sequential_number_prefix'],
                    'format' => 'DDIIIIEEEEEEWWWWDDDDD',
                    'appenfix' => '',
                    'start' => 1,
                    'length' => 5,
                    'increment' => 1,
                );
                $order_takaway_number = array(
                        'prefix' => '20',
                        'format' => 'DDIIIIEEEEEEWWWWDDDDD',
                        'appenfix' => '00000',
                        'start' => $takeaway_number,
                        'length' => 5,
                        'increment' => 1,
                );
                $setting = array(
                    'pos_type' => $pos_type,
                    'allow_offline_order_number' => 'no',
                    'order_sequential_number' => $order_sequential_number,
                    'order_takaway_number' => $order_takaway_number,
                    'auto_sync' => $product_sync,
                    'stock_manager' => $all_setting['pos_stock_manage'],
                    'order_status' => $all_setting['pos_order_status'],
                    'continue_order_status' => $all_setting['pos_continue_checkout_order_status'],
                    'cart_discount_cal' => $pos_cart_discount_calc,
                    'item_discount_cal' => $pos_item_discount_calc,
                    'taxes' => $pos_available_taxes,
                    'pos_tax_class' => $pos_tax_class,
                    'discount_class' => 'op_product_tax',
                    'fee_tax_class' => $pos_fee_tax_class,
                    'payment_methods' => $all_setting['payment_methods'],
                    'shipping_methods' => $all_setting['shipping_methods'],
                    'currency' => $default_currency,
                    'dashboard_display' => $default_display,
                    'date_format' => $this->core_class->convert_to_js_date_format(get_option( 'date_format' )),
                    'time_format' => $this->core_class->convert_to_js_date_format(get_option( 'time_format' )),
                    'languages' => array(),
                    'grid_size' => array(
                        'product' => array(
                            'col' => $all_setting['pos_product_grid_column'],
                            'row' => $all_setting['pos_product_grid_row']
                        ),
                        'category' => array(
                            'col' => $all_setting['pos_cat_grid_column'],
                            'row' => $all_setting['pos_cat_grid_row'],
                        ),
                        'table' => array(
                            'col' => $all_setting['pos_product_grid_column'],
                            'row' => $all_setting['pos_product_grid_row']
                        ),  
                        'takeaway' => array(
                            'col' => $all_setting['pos_product_grid_column'],
                            'row' => $all_setting['pos_product_grid_row']
                        ),  
                    ),
                    'clear_product' => $pos_clear_product,
                    'time_frequency' => $time_frequency,
                    'accept_nagative_checkout' => $accept_negative_checkout,
                    'display_out_of_stock' => $pos_display_outofstock,
                    'money_list' => $pos_money ,
                    'custom_cart_discounts' => $pos_custom_cart_discount_amount,
                    'custom_item_discounts' => $pos_custom_item_discount_amount,
                    'tip' => array(
                        'enable' => $pos_allow_tip,
                        'amounts' => $pos_tip_amount,
                        'allow_custom' => 'yes',
                    ),
                    'checkout_mode' => $pos_default_checkout_mode,
                    'product_autocomplete' => $pos_search_product_auto,
                    'product_search_online' => $pos_search_product_online,
                    'customer_autocomplete' => $pos_customer_autocomplete,
                    'customer_addition_fields' => $openpos_customer_addition_fields,
                    'require_customer' => $pos_require_customer_mode,
                    'product_search_result_total' => $search_result_total,
                    'weight_barcode' => array(
                        'enable' => $pos_enable_weight_barcode,
                        'prefix' => $pos_weight_barcode_prefix,
                        'format' => $pos_weight_barcode_format,
                    ),
                    'customer_barcode' => array(
                        'enable' => 'yes',
                        'prefix' => '20',
                        'format' => 'DDIIIIEEEEEEWWWWDDDDD',
                    ),
                    'product_barcode' => array(
                        'enable' => 'no',
                        'prefix' => '20',
                        'format' => 'DDIIIIEEEEEEWWWWDDDDD',
                    ),
                    'qrcode_prefix' => array(
                        'customer-' => 'customer',
                        'gift-' => 'gift',
                        'voucher-' => 'voucher',
                        'table-' => 'table',
                        'takeaway-' => 'takeaway',
                    ),
                    'receipts' => array(
                        'customer' => '',
                        'kitchen' => '',
                        'product_decal' => '',
                        'xreport' => ''
                    )
                );
                $permisions = array(
                    'allow_tip' => 'yes',
                    'allow_refund' => 'yes',
                    'allow_exchange' => 'yes',
                    'allow_custom_item' => $all_setting['pos_allow_custom_item'],
                    'allow_custom_price' => $pos_change_price,
                    'allow_cart_discount' => 'yes',
                    'allow_item_discount' => 'yes',
                    'allow_custom_tax' => 'yes',
                    'allow_custom_note' => $all_setting['pos_allow_custom_note'],
                );
                
                $user['permision'] = $permisions;
                $sellers =  $this->register_class->getCashierList($register_id);;
                $tables = $pos_tables;
                $categories =  $this->woo_class->get_pos_categories($register_id);
                $pos_balance = $this->register_class->cash_balance($register_id);

                $data = array(
                    'session_id' => $session_id,
                    'user' => $user,
                    'registers' => $registers,
                    'register' => $register,
                    'pos_balance' => $pos_balance,
                    'sellers' => $sellers,
                    'tables' => $tables,
                    'categories' => $categories,
                    'setting' => $setting,
                    'app_ver' => $app_ver, 
                    'client_time_offset' => $client_time_offset, 
                );
                $session_data = apply_filters('op_op_login_session_data',$data);
                $this->session_class->clean($session_id);
                $this->session_class->save($session_id,$session_data);
                $result['data']['result'] = $data;
                $result['data']['status'] = 1;
                $result['code'] = 200;

            }catch(Exception $e){
                $result['message'] = $e->getMessage();
                $result['code'] = 400;
                $result['data']['status'] = 0;
            }

            return $this->rest_ensure_response( $result );
            

        }
        public function logon(WP_REST_Request $request){
            
            $result = array(
                'code' => 'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $session_id = $request->get_param( 'session_id' );
                $logon_mode =  $request->get_param( 'logon_mode' ) ? stripslashes($request->get_param( 'logon_mode' )) : 'default';
                $result['data']['status'] = 1;
                $result['code'] = 200;
            }catch(Exception $e){
                $result['message'] = $e->getMessage();
                $result['data']['status'] = 0;
                $result['code'] = 400;
            }
            return $this->rest_ensure_response($result);
        }
        public function logoff(WP_REST_Request $request){
            $response_code = 200;
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{

                $session_id = $request->get_param( 'session_id' );
                $result['data']['status'] = 1;
                $result['code'] = 200;
            }catch(Exception $e){
                $result['message'] = $e->getMessage();
                $result['data']['status'] = 0;
                $result['code'] = 400;
            }
            return $this->rest_ensure_response( $result );
        }
        public function login_session(WP_REST_Request $request){
            $response_code = 200;
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $session_id = $request->get_param( 'session_id' );
                $result['data']['status'] = 1;
                $result['code'] = 200;
            }catch(Exception $e){
                $result['message'] = $e->getMessage();
                $result['data']['status'] = 0;
                $result['code'] = 400;
            }
            return rest_ensure_response( $result );
        }
        public function logout(WP_REST_Request $request){
            
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $session_id = $request->get_param( 'session_id' );
                
                $result['data']['status'] = 1;
                $result['code'] = 200;
            }catch(Exception $e){
                $result['message'] = $e->getMessage();
                $result['data']['status'] = 0;
                $result['code'] = 400;
            }

            return $this->rest_ensure_response( $result );
        }
        public function pos_state(WP_REST_Request $request = null){
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{

                $result['data']['result'] = array(
                    'deleted_takeaway' => array(),
                    'tables' => array(),
                    'ready_dish' => array(),
                    'desk_message' => array(),
                    'notifications' => array(),
                );
                $result['data']['status'] = 1;
                $result['code'] = 200;
            }catch(Exception $e)
            {
                $result['code'] = 400;
                $result['data']['status'] = 0;
                $result['message'] = $e->getMessage();
                
            }
            return $this->rest_ensure_response($result);
        }
        
    }
}