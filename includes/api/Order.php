<?php
if(!class_exists('OP_REST_API_Order'))
{
    class OP_REST_API_Order extends OP_REST_API{
       
        
        public function register_routes() {
            
            register_rest_route( $this->namespace, '/order/orders', array(
                'methods' => 'GET',
                'callback' => array($this,'orders'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/order/get-by-order-number/(?P<order_number>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this,'get_order_by_order_number'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/order/get-by-local-id/(?P<local_id>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this,'get_order_by_local_id'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/order/get/(?P<order_id>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this,'get_order'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/order/get-notes/(?P<order_id>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this,'get_order_notes'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/order/generate-order-number', array(
                'methods' => 'GET',
                'callback' => array($this,'generate_order_number'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/order/create', array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this,'save'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/order/update/(?P<order_id>\d+)', array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this,'update_order'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );

           
        }
        public function orders($request)
        {
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(
                        'total_post' => 0,
                        'total_page' => 0,
                        'orders' => array()
                    )
                ),
                'message' => ''
            );
            
            try{
                $session_data = $this->session_data;
                $customer_id = $request->get_param('customer_id') ? (int)$request->get_param('customer_id') : 0;
                $page = $request->get_param('page') ? (int)$request->get_param('page') : 1;
                $list_type = $request->get_param('list_type') ? $request->get_param('list_type') : 'latest';
                $term = $request->get_param('term') ? $request->get_param('term') : '';
                $per_page = $request->get_param('per_page') ? $request->get_param('per_page') : 15;
                $per_page = apply_filters('op_latest_order_per_page',$per_page);
                $orders = array();

                $post_statuses =  array(
                    'wc-completed',
                    'wc-processing',
                    'wc-pending',
                    'wc-on-hold',
                    'wc-refunded',
                    'wc-cancelled'
                );
                $params = array(
                    'posts_per_page' => $per_page,
                    'paged' => $page,
                );
                if($customer_id > 0)
                {
                    $params['customer_id'] = $customer_id;
                    $list_type = 'customer_orders';
                }
                if($term)
                {
                    $params['search'] = esc_textarea($term);
                    $list_type = 'search';
                    $id_from_number = $this->order_class->get_order_id_from_number($term);
                    $id_from_number_format = $this->order_class->get_order_id_from_order_number_format($term);
                    $id_from_local_id = $this->order_class->get_order_id_from_local_id($term);
                    if($page == 1)
                    {
                        $order_from_term = wc_get_order($term);
                        $order_from_order_number = wc_get_order($id_from_number);
                        $order_from_order_number_format = wc_get_order($id_from_number_format);
                        $order_from_order_local_id = wc_get_order($id_from_local_id);
                        if($order_from_term instanceof WC_Order )
                        {
                            $order_id = $order_from_term->get_id();
                            $orders[$order_id] = $this->_formatApiOrder($order_id);
                        }
                        if($order_from_order_number instanceof WC_Order )
                        {
                            $order_id = $order_from_order_number->get_id();
                            $orders[$order_id] = $this->_formatApiOrder($order_id);
                        }
                        if($order_from_order_number_format instanceof WC_Order )
                        {
                            $order_id = $order_from_order_number_format->get_id();
                            $orders[$order_id] = $this->_formatApiOrder($order_id);
                        }
                        if($order_from_order_local_id instanceof WC_Order )
                        {
                            $order_id = $order_from_order_local_id->get_id();
                            $orders[$order_id] = $this->_formatApiOrder($order_id);
                        }
                    }
                }
                if($list_type == 'current_session')
                {
                    $time = time();
                    if(isset($session_data['logged_time']) && $session_data['logged_time'])
                    {
                        $time = strtotime($session_data['logged_time']);
                    }
                    $wc_date = new WC_DateTime();
                    if ( get_option( 'timezone_string' ) ) {
                        $wc_date->setTimezone( new DateTimeZone( wc_timezone_string() ) );
                    } else {
                        $wc_date->set_utc_offset( wc_timezone_offset() );
                    }
                    $wc_date->setTimestamp($time);
                    $date_string = $wc_date->date("Y-m-d 00:00:00");
                    $params['date']['from'] = $date_string;
                }
                $post_orders_query = $this->core_class->getOrdersByDate($params,$post_statuses);
                $post_orders = $post_orders_query['posts'];
                $total_page = $post_orders_query['total_page'];
                $total_post = $post_orders_query['total_post'];


                $result['data']['result']['total_post']  = $total_post;
                $result['data']['result']['total_page']  = $total_page;
                if(count($post_orders) > 0)
                {
                    foreach($post_orders as $_order)
                    {
                        if($_order)
                        {
                            $order_id = 0;
                            if($_order instanceof WC_Order )
                            {
                                $order_id = $_order->get_id();
                                $formatted_order = $this->_formatApiOrder($order_id);
                             }else{
                                $order_id = $_order->ID;
                                $formatted_order = $this->_formatApiOrder($order_id);
                             }
                            
                            if(!$formatted_order || empty($formatted_order))
                            {
                                continue;
                            }
                            $orders[$order_id] = $formatted_order;
                        }
                       
                    }
                    
                }
                $result['data']['result']['orders'] = array_values($orders);
                
                $result['code'] = 200;
                $result['data']['status'] = 1;

            }catch(Exception $e)
            {
                $result['code'] = 400;
                $result['data']['status'] = 0;
                $result['message'] = $e->getMessage();
                
            }
            return $this->rest_ensure_response($result);
        }
        public function get_order($request)
        {
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $order_id = $request->get_param('order_id');
                if(!$order_id)
                {
                    throw new Exception(__('Order Id is required','openpos'));
                }
                $order = wc_get_order($order_id);
                if(!$order)
                {
                    throw new Exception(__('Order not found','openpos'));
                }
                $formatted_order = $this->_formatApiOrder($order->get_id());
                if(!$formatted_order || empty($formatted_order))
                {
                    throw new Exception(__('Order not found','openpos'));
                }
                $result['data']['result'] = $formatted_order;
                $result['code'] = 200;
                $result['data']['status'] = 1;
            }catch(Exception $e)
            {
                $result['code'] = 400;
                $result['data']['status'] = 0;
                $result['message'] = $e->getMessage();
                
            }
            return $this->rest_ensure_response($result);
        }
        public function get_order_by_order_number($request)
        {
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $order_number = $request->get_param('order_number');
                if(!$order_number)
                {
                    throw new Exception(__('Order number is required','openpos'));
                }
                $order_id = $this->order_class->get_order_id_from_number($order_number);
                if(!$order_id)
                {
                    throw new Exception(__('Order not found','openpos'));
                }
                $order = wc_get_order($order_id);
                if(!$order)
                {
                    throw new Exception(__('Order not found','openpos'));
                }
                
                $formatted_order = $this->_formatApiOrder($order->get_id());
                if(!$formatted_order || empty($formatted_order))
                {
                    throw new Exception(__('Order not found','openpos'));
                }
                $result['data']['result'] = $formatted_order;
                $result['code'] = 200;
                $result['data']['status'] = 1;

            }catch(Exception $e)
            {
                $result['code'] = 400;
                $result['data']['status'] = 0;
                $result['message'] = $e->getMessage();
                
            }
            return $this->rest_ensure_response($result);
        }
        public function get_order_by_local_id($request)
        {
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $local_id = $request->get_param('local_id');
                if(!$local_id)
                {
                    throw new Exception(__('Local ID is required','openpos'));
                }
                $order_id = $this->order_class->get_order_id_from_local_id($local_id);
                
                if(!$order_id)
                {
                    throw new Exception(__('Order not found','openpos'));
                }
                $order = wc_get_order($order_id);
                if(!$order)
                {
                    throw new Exception(__('Order not found','openpos'));
                }
                
                $formatted_order = $this->_formatApiOrder($order->get_id());
                if(!$formatted_order || empty($formatted_order))
                {
                    throw new Exception(__('Order not found','openpos'));
                }
                
                $result['data']['result'] = $formatted_order;
                $result['code'] = 200;
                $result['data']['status'] = 1;

            }catch(Exception $e)
            {
                $result['code'] = 400;
                $result['data']['status'] = 0;
                $result['message'] = $e->getMessage();
                
            }
            return $this->rest_ensure_response($result);
        }
        public function generate_order_number($request)
        {
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $allow_hpos = $this->core_class->enable_hpos();
                $order_number_json = array();
                $session_data = $this->session_data;
                if($allow_hpos){
                    $order_number_json = $this->order_class->hpos_get_order_number();
                }else{
                    $order_number_json = $this->order_class->default_get_order_number($session_data);
                }
                if(!$order_number_json['status'])
                {
                    throw new Exception($order_number_json['message']);
                }
                $result['data']['result'] = $order_number_json['data'];
                $result['code'] = 200;
                $result['data']['status'] = 1;

            }catch(Exception $e)
            {
                $result['code'] = 400;
                $result['data']['status'] = 0;
                $result['message'] = $e->getMessage();
                
            }
            return $this->rest_ensure_response($result);
        }
        public function get_order_notes($request)
        {
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $order_id = $request->get_param('order_id');
                if(!$order_id)
                {
                    throw new Exception(__('Order Id is required','openpos'));
                }
                
            
                $order = wc_get_order($order_id);
                
                if(!$order)
                {
                    $order_number = $this->order_class->get_order_id_from_local_id($order_id);
                    $order = wc_get_order($order_number);
                }

                
                if(!$order)
                {
                    throw new Exception(__('Order not found','openpos'));
                }
                $notes = $this->order_class->getOrderNotes($order->get_id());

                $result['data']['result']['notes'] = $notes;
                $result['data']['result']['order_status'] = $order->get_status();
                $order_note_allow_status = array();

                //$order_note_allow_status[] = array('code' => 'completed','label' => 'Completed');
                
                $result['data']['result']['allow_status'] =  apply_filters('op_order_note_allow_status',$order_note_allow_status,$order,$this);
                $result['code'] = 200;
                $result['data']['status'] = 1;

            }catch(Exception $e)
            {
                $result['code'] = 400;
                $result['data']['status'] = 0;
                $result['message'] = $e->getMessage();
                
            }
            return $this->rest_ensure_response($result);
        }
        /**
         * Save order
         *
         * @param WP_REST_Request $request
         * @return WP_REST_Response
         */
        public function save($request)
        {
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $raw_json = $request->get_body();
                $by_data = json_decode($raw_json, true); 
                $logger = wc_get_logger();
                $logger->info( $raw_json, array( 'source' => 'app-json-orders' ) );
                $logger->info( wc_print_r( $by_data, true ), array( 'source' => 'app-orders' ) );

                $result['code'] = 200;
                $result['data']['result'] = array();
                $result['data']['status'] = 1;
            }catch(Exception $e)
            {
                $result['code'] = 400;
                $result['data']['status'] = 0;
                $result['message'] = $e->getMessage();
                
            }
            return $this->rest_ensure_response($result);
        }
        public function update_order($request)
        {
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $order_id = $request->get_param('order_id');
                if(!$order_id)
                {
                    throw new Exception(__('Order Id is required','openpos'));
                }
                
                $result['data']['result'] = $order_id;
                $result['code'] = 200;
                $result['data']['status'] = 1;
            }catch(Exception $e)
            {
                $result['code'] = 400;
                $result['data']['status'] = 0;
                $result['message'] = $e->getMessage();
                
            }
            return $this->rest_ensure_response($result);
        }
        
        public function _formatApiOrderItem($item)
        {
            //$currency = $session_data['setting']['currency'];
            //$decimal = $currency['decimal'];
            $item = array(
                'id' => null, // Optional, for SQLite row id
                'itemType' => '',
                'itemKey' => '', // Unique key for the item, use check new item or existing item

                'variantId' => null,
                'productId' => null,
                'productName' => '',
                'productSku' => '',
                'productImage' => '',
                'barcode' => '',
                'barcodeDetails' => null,
                'productPrice' => 0,
                'productPriceInclTax' => 0,
                'product' => null, // Product data if available, can be null if not set
                'customPrice' => null, // Custom price if applicable, can be null if not set
                'price' => 0, // product price or variation price
                'priceInclTax' => 0, // Price including tax
                'finalPrice' => 0, // price with option and bundle
                'finalPriceInclTax' => 0, // price with option and bundle incl tax
                'options' => '',
                'bundles' => '',
                'groupItems' => '',
                'variations' => '',
                'optionPrice' => 0, // Total price of options
                'bundlePrice' => 0, // Total price of bundles
                'groupItemPrice' => 0, // Total price of group items
                'note' => '',
                'qty' => 0,

                'discounts' => array(),
                'discount' => 0,
                'discountTax' => 0,
                'discountInclTax' => 0,

                'discountRules' => null,
                'discountRuleTotal' => 0, // Total discount rules amount, can be 0 if no rules applied
                'discountRuleTax' => 0, // Total discount rules tax amount, can be 0 if no rules applied
                'discountRuleInclTax' => 0, // Total discount rules amount including tax, can be 0 if no rules applied

                'shipping' => 0,
                'shippingTax' => 0,
                'shippingInclTax' => 0,

                'subtotal' => 0,
                'subtotalInclTax' => 0,
                'tax' => 0,
                'taxDetails' => array(),

                'total' => 0,
                'totalInclTax' => 0,

                'isShipping' => false,
                'isSync' => false,
                'createdAt' => 0,
                'updatedAt' => 0,
            );
            return $item;
        }
        public function _formatApiOrder($order_id,$currency = null)
        {
            $formatted_order = $this->woo_class->formatWooOrder($order_id);
            return $formatted_order;
            // $currency_pos = wc_get_price_decimals(); 
            // $decimal = isset($currency['decimal']) ? $currency['decimal'] : $currency_pos;
            // $register = null;
            // $outlet = null;
            // $cashier = null;
            // $seller = null;
            // $order = array(
            //     'id' => $formatted_order['id'], // Optional, for SQLite row id
            //     'localId' => $formatted_order['order_id'], // Optional, for SQLite row id
            //     'sessionId' => '',
            //     'cartType' => '',
            //     'cartSource' => '',
            //     'cartSourceDetails' => '',
            
            //     'orderNumber' => $formatted_order['order_number'], // Order number, can be empty if not assigned
            //     'orderId' => $formatted_order['order_id'], // Order ID, can be null if not assigned
            //     'orderNumberFormatted' => $formatted_order['order_number_format'], // Formatted order number, can be empty if not assigned
            
            //     'label' => $formatted_order['title'],
            //     'customerId' => 0,
            //     'customer' => $formatted_order['customer'], // Customer|null
            //     'seller' => $seller,   // User|null
            //     'cashier' => $cashier,  // User|null
            //     'register' => $register, // User|null
            //     'outlet' => $outlet,   // User|null
            
            //     'coupons' => [],
            //     'coupounTotal' => 0, // Total coupon amount, can be 0 if no coupons applied
            //     'couponTax' => 0,    // Total coupon tax amount, can be 0 if no coupons applied
            //     'couponInclTax' => 0, // Total coupon amount including tax, can be 0 if no coupons applied
            
            //     'discountRules' => null,
            //     'discountRuleTotal' => 0, // Total discount rules amount, can be 0 if no rules applied
            //     'discountRuleTax' => 0,   // Total discount rules tax amount, can be 0 if no rules applied
            //     'discountRuleInclTax' => 0, // Total discount rules amount including tax, can be 0 if no rules applied
            
            //     'discounts' => '', // manual discount
            //     'discount' => 0,
            //     'discountTax' => 0,
            //     'discountInclTax' => 0,
            
            //     'pickupDate' => '', // Optional, for pickup date if applicable
            //     'pickupTime' => '', // Optional, for pickup time if applicable
            //     'pickupLocation' => '', // Optional, for pickup location if applicable
            //     'pickupNote' => '', // Optional, for pickup note if applicable
            
            //     'shipping' => '',
            //     'shippingNote' => '',
            //     'shippingCost' => 0,
            //     'shippingTax' => 0,
            //     'shippingTotal' => 0,
            //     'shippingInclTax' => 0, // Total shipping cost including tax
            
            //     'subTotal' => 0,
            //     'itemTax' => 0,
            //     'subtotalInclTax' => 0,
            
            //     'feeTotal' => 0, // Total fee amount excl tax
            //     'feeTax' => 0,   // Total fee tax amount
            //     'feeInclTax' => 0, // Total fee amount including tax
            
            //     'taxDetails' => '',
            //     'tax' => $this->_convertToCent($formatted_order['grand_total'],$decimal), // Total tax amount
            //     'grandTotal' => $this->_convertToCent($formatted_order['tax_amount'],$decimal), // grand total with tax
            
            //     'state' => $formatted_order['state'], // Order state, can be 'pending', 'processing', 'completed', 'cancelled', etc.
            //     'status' => $formatted_order['status'], // Order status, can be 'pending', 'processing', 'completed', 'cancelled', etc.
            //     'totalPaid' => $this->_convertToCent($formatted_order['total_paid'],$decimal), // total paid amount
            //     'paymentTransactions' => null, // Array of payment transactions, can be null if no transactions
            
            //     'isGift' => false,
            //     'isShipping' => false,
            //     'isSync' => true,
            //     'isPaid' => false,
            //     'isPrinted' => false,
            //     'isSyncCart' => false,
            
            //     'feeItems' => array(), // Optional, for fee items if any
            //     'items' => array(),     // You can replace 'any' with your CartItem[] interface if available
            //     'currency' => null,
            //     'note' => '', // Optional, for pickup note if applicable
            
            //     'additionalData' => $formatted_order['addition_information'], // Optional, for any additional data related to the order
            
            //     'createdAt' => 0,
            //     'updatedAt' => 0,
            // );
            // return $order;
        }
    }
}