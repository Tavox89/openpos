<?php
if(!class_exists('OP_REST_API_Cart'))
{
    class OP_REST_API_Cart extends OP_REST_API{
        public function register_routes() {
            register_rest_route( $this->namespace, '/cart/carts', array(
                'methods' => 'GET',
                'callback' => array($this,'carts'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/cart/get/(?P<cart_number>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this,'get_cart'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/cart/current-cart', array(
                'methods' => 'GET',
                'callback' => array($this,'current_cart'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/cart/save', array(
                'methods' =>  WP_REST_Server::CREATABLE,
                'callback' => array($this,'save'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
        }
        public function carts(WP_REST_Request $request = null){
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $register = isset($this->session_data['register']) ? $this->session_data['register'] : [];
                if(!isset($register['id']))
                {
                    throw new Exception(__('Register not found','openpos'));
                }
                $warehouse_id = $register['outlet_id'];
                $warehouse_meta_key = $this->warehouse_class->get_order_meta_key();
                $post_type = 'shop_order';

                $today = getdate();
                
                $args = array(
                    'date_query' => array(
                        array(
                            'year'  => $today['year'],
                            'month' => $today['mon'],
                            'day'   => $today['mday'],
                        ),
                    ),
                    'post_type' => $post_type,
                    'post_status' => 'auto-draft',
                    'meta_query' => array(
                        array(
                            'key' => $warehouse_meta_key,
                            'value' => $warehouse_id,
                            'compare' => '=',
                        )
                    ),
                    'posts_per_page' => -1
                );
                $args = apply_filters('op_draft_orders_query_args',$args);

                
                $query = new WP_Query($args);
                $orders = $query->get_posts();
                
            
                
                $carts = array();
                if(count($orders) > 0)
                {
                    foreach($orders as $_order)
                    {
                        $order_number = $_order->ID;
                        $cart_data = get_post_meta($order_number,'_op_cart_data');
                        if($cart_data && is_array($cart_data) && !empty($cart_data))
                        {
                            $cart= end($cart_data);
                            $cart['allow_delete'] = 'yes';
                            $carts[] = $cart;
                        }else{
                            continue;
                        }
                    }

                    
                    
                }else{
                    throw new Exception(__('No cart found','openpos'));
                }
                $result['data']['result'] = $carts;
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
        public function get_cart(WP_REST_Request $request = null){
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $cart_number = $request->get_param('cart_number');
                if(!$cart_number)
                {
                    throw new Exception(__('Cart number is required','openpos'));
                }
                $register = isset($this->session_data['register']) ? $this->session_data['register'] : [];
                if(!isset($register['id']))
                {
                    throw new Exception(__('Register not found','openpos'));
                }
                $warehouse_id = $register['outlet_id'];
                
                if(is_numeric($cart_number))
                {
                    //cashier cart
                    $order = get_post((int)$cart_number);
                    if(!$order)
                    {
                        throw new Exception( __('Cart Not found','openpos') );
                    }
                    $cart_data = get_post_meta($order->ID,'_op_cart_data');
                    if($cart_data && is_array($cart_data) && !empty($cart_data))
                    {
                        $result['data']['result'] = $cart_data[0];
                        
                    }
                }else{
                    // online cart
                    $cart_type = 'website';
                    $result['data']['cart_type'] = $cart_type;
                    $cart_data = $this->cart_class->getCartBySessionId($cart_number);
                    if(!$cart_data || !is_array($cart_data) || empty($cart_data))
                    {
                        throw new Exception( __('Cart Not found','openpos')   );
                    }else{
                        $result['data']['result'] = $cart_data;
                        
                    }
                }
                
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
        public function save(WP_REST_Request $request = null){
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
                $cart_data = json_decode($raw_json, true); 
                
                if(!$cart_data || !is_array($cart_data) || empty($cart_data))
                {
                    throw new Exception(__('Cart data is required','openpos'));
                }

                $register = isset($this->session_data['register']) ? $this->session_data['register'] : [];
                if(!isset($register['id']))
                {
                    throw new Exception(__('Register not found','openpos'));
                }
                $warehouse_id = $register['outlet_id'];

                $order_number = isset($cart_data['order_number']) ? $cart_data['order_number'] : 0;
                $order_id = isset($cart_data['order_id']) ? $cart_data['order_id'] : 0;
                if(!$order_id && !$this->core_class->_enable_hpos)
                {
                    $order_id = $this->order_class->get_order_id_from_number($order_number);
                }
                
                $items = isset($cart_data['items']) ? $cart_data['items'] : array();
                if(empty($items))
                {
                    throw new Exception('Item not found.');
                }
                $order = get_post($order_id);
                if(!$order)
                {
                    throw new Exception(__('Cart not found','openpos'));
                }
                $warehouse_meta_key = $this->warehouse_class->get_order_meta_key();
                
                update_post_meta($order->ID,'_op_cart_data',$cart_data);
                update_post_meta($order->ID,$warehouse_meta_key,$warehouse_id);
                
                $result['data']['result'] = array('id' => $order_id);
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
        public function current_cart(WP_REST_Request $request = null){
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $register_id = $request->get_param('register_id') ;
                if(!$register_id)
                {
                    $register = isset($this->session_data['register']) ? $this->session_data['register'] : [];
                    if(!isset($register['id']))
                    {
                        throw new Exception(__('Register not found','openpos'));
                    }
                    $register_id = $register['id'];
                }
                $cart_data = $this->cart_class->getPosCart($register_id);
                $result['data']['result'] = $cart_data;
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