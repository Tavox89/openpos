<?php
if(!class_exists('OP_REST_API_Customer'))
{
    class OP_REST_API_Customer extends WC_REST_CRUD_Controller{
       
        protected $namespace = 'op/v1';
        protected $session_class;
        protected $register_class;
        protected $warehouse_class;
        protected $core_class;
        protected $setting_class;
        protected $woo_class;
        public $session_data = false;
        public function __construct($core_class = array())
        {
            $this->session_class = $core_class['op_session'];
            $this->register_class = $core_class['op_register'];
            $this->warehouse_class = $core_class['op_warehouse'];
            $this->core_class = $core_class['core'];
            $this->setting_class = $core_class['settings'];
            $this->woo_class = $core_class['op_woo'];
        }
        public function register_routes() {
            
            register_rest_route( $this->namespace, '/customer/customers', array(
                'methods' => 'GET',
                'callback' => array($this,'customers'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/customer/get-by-field', array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this,'get_by_field'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/customer/customer-fields', array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this,'customer_fields'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            
            register_rest_route( $this->namespace, '/customer/save-customer', array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this,'save'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
                

           
        }
        public function permission_callback(WP_REST_Request $request = null){
            
            if ($request && !$this->check_auth_header($request)) {
                return new WP_Error('rest_forbidden', __('Unauthorized', 'openpos'), array('status' => 401));
            }
            return true;
        }
        protected function check_auth_header(WP_REST_Request $request) {
            $headers = $request->get_headers();
            $auth_header = isset($headers['authorization']) ? $headers['authorization'][0] : '';
            // Ví dụ: Bearer <token>
            if ($auth_header && strpos($auth_header, 'Bearer ') === 0) {
                $token = substr($auth_header, 7);
                $session_data = $this->session_class->data($token);
                if($session_data)
                {
                    $this->session_data = $session_data;
                    return true;
                }
            }
            return false;
        }
        public function customers($request)
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
                $term = $request->get_param( 'term' ) ? $request->get_param( 'term' ) : '';
                
                $current_page = $request->get_param( 'page' ) ? $request->get_param( 'page' ) : 1;
                $customers = $this->woo_class->get_customers($term,$current_page);   
                $total_page = 1;
                
                
                $result_customers = array();
                if(is_array($customers) && !empty($customers))
                {
                    $result_customers = array_values($customers);
                }
                
                if(count($result_customers) == 1 )
                {
                    $result_customers[0]['auto_add'] = 'yes';
                }
                if(empty($result_customers))
                {
                     if($term)
                     {
                        throw new Exception(sprintf(__('No customer with search keyword: %s','openpos'),$term));
                     }else{
                        throw new Exception(sprintf(__('No customers','openpos'),$term));
                     }

                }
                
                $result['data']['result'] = array(
                    'customers' => $result_customers,
                    'total_page' => $total_page,
                    'current_page' => $current_page,
                );
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
        public function get_by_field($request)
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
                $by = $request->get_param( 'by' ) ? $request->get_param( 'by' ) : '';
                $search_data = $request->get_param( 'by_data' ) ? $request->get_param( 'by_data' ) : '';
                $multi = $request->get_param( 'multi' ) == 'no' ? false : true;
                if(!$by)
                {
                    throw new Exception(__('Please enter by field to search','openpos'));
                }
                if(!$search_data)
                {
    
                    throw new Exception(__('Please enter by term to search','openpos'));
                }
                $customers = $this->woo_class->get_customer_by($by,$search_data);

                if(count($customers) == 0 )
                {
                    throw new Exception(sprintf(__('No customer found with %s : "%s"','openpos'),$by,$term));
                }
                if(count($customers) > 1 && !$multi)
                {
                    throw new Exception(__('There are multi user with same term','openpos'));
                }
                if(!empty($customers))
                {
                    $result['status'] = 1;
                    if(!$multi)
                    {
                        $result['data']['result'] = end($customers);
                    }else{
                        $result['data']['result'] = $customers;
                    }
                    $result['code'] = 200;
                    $result['data']['status'] = 1;
                }
            }catch(Exception $e)
            {
                $result['code'] = 400;
                $result['data']['status'] = 0;
                $result['message'] = $e->getMessage();
                
            }
            return $this->rest_ensure_response($result);
        }
        public function customer_fields($request)
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
                if(!$by_data)
                {
    
                    throw new Exception(__('Please enter by field to search','openpos'));
                }
                
                $country = isset($by_data['country']) ? $by_data['country'] : array();
                $data = array();
                if($country )
                {
                    $countries_obj   = new WC_Countries();
                    $states = $countries_obj->get_states($country);
                    if(!$states || empty($states))
                    {
                        $data['state'] = array(
                            'type' => 'text',
                            'default' => ''
                        );
                    }else{
                        $state_options = array();
                        foreach($states as $key => $state)
                        {
                            $state_options[] = ['value' => $key,'label' => $state];
                        }
                        $data['state'] = array(
                            'type' => 'select',
                            'default' => '',
                            'options' => $state_options
                        );
                    }

                }
                $result['data']['result'] = apply_filters('op_get_customer_field',$data,$by_data);
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
                if(!$by_data)
                {
    
                    throw new Exception(__('Please enter customer data','openpos'));
                }
                
                $customer_request_data = apply_filters('op_new_customer_request',$by_data);
                $customer_id = isset($customer_request_data['id']) &&  $customer_request_data['id'] != 'null'  ? $customer_request_data['id'] : 0;

                $name = isset($customer_request_data['name']) ? $customer_request_data['name'] : '';
                if($name)
                {
                    $name = trim($name);
                    $tmp = explode(' ',$name);
                    $firstname = $tmp[0];
                    $lastname = trim(substr($name,(strlen($firstname))));
                }else{
                    $firstname = isset($customer_request_data['firstname']) ? $customer_request_data['firstname'] : '';
                    $lastname = isset($customer_request_data['lastname']) ? $customer_request_data['lastname'] : '';
                }
                $email = isset($customer_request_data['email']) &&  $customer_request_data['email'] != 'null' ? $customer_request_data['email'] : '';
                $phone = isset($customer_request_data['phone']) &&  $customer_request_data['phone'] != 'null'  ? $customer_request_data['phone'] : '';
                $address = isset($customer_request_data['address']) &&  $customer_request_data['address'] != 'null'  ? $customer_request_data['address'] : '';
                $company = isset($customer_request_data['company']) &&  $customer_request_data['company'] != 'null'  ? $customer_request_data['company'] : '';

                
                $address_2 = isset($customer_request_data['address_2']) && $customer_request_data['address_2'] != null ? esc_textarea($customer_request_data['address_2']):'';
                $state = isset($customer_request_data['state']) && $customer_request_data['state'] != null ? esc_textarea($customer_request_data['state']):'';
                $city = isset($customer_request_data['city']) && $customer_request_data['city'] != null ? esc_textarea($customer_request_data['city']):'';
                $country = isset($customer_request_data['country']) && $customer_request_data['country'] != null ? esc_textarea($customer_request_data['country']):'';
                $postcode = isset($customer_request_data['postcode']) && $customer_request_data['postcode'] != null ? esc_textarea($customer_request_data['postcode']):'';

                $session_data = $this->session_data;
                if($customer_id  > 0)
                {
                    $customer = new WC_Customer($customer_id);
                    if(!$customer || !$customer->get_id())
                    {
                        throw new Exception(__('Customer not found','openpos'));
                    }
                    $customer->set_billing_address($address);
                    $customer->set_billing_phone($phone);
                    $customer->set_display_name($name);
                    $customer->set_first_name(wc_clean($firstname));
                    $customer->set_last_name(wc_clean($lastname));

                    $customer->set_billing_first_name($firstname);
                    $customer->set_billing_last_name($lastname);

                    if($address_2 )
                    {
                        $customer->set_billing_address_2($address_2);
                    }
                    if($state)
                    {
                        $customer->set_billing_state($state);
                    }
                    if($city)
                    {
                        $customer->set_billing_city($city);
                    }
                    if($postcode)
                    {
                        $customer->set_billing_postcode($postcode);
                    }

                    if($country)
                    {
                        $customer->set_billing_country($country);
                    }

                    if($name)
                    {
                        $customer->update_meta_data('_op_full_name',$name);
                    }

                    $customer->save_data();

                    $user_obj = get_userdata( $customer_id);
                    clean_user_cache( $user_obj );
                    
                    do_action('op_update_customer_after',$customer_id,$session_data,$customer_request_data);
                    $customer_data = $this->woo_class->formatCustomer($customer_id);
                    $result['data']['result'] = $customer_data;
                    //update customer
                }else{
                    $create_user = isset($customer_request_data['create_customer']) ? $customer_request_data['create_customer'] : 1;
                    if(!$create_user)
                    {
                        $customer_data = array(
                            'id' => $customer_id,
                            'name' => $name,
                            'firstname' =>$firstname,
                            'lastname' => $lastname,
                            'company' => $company,	
                            'address' => $address,
                            'phone' => $phone,
                            'email' => $email,
                            'billing_address' =>array(),
                            'point' => 0,
                            'discount' => 0,
                            'badge' => '',
                            'shipping_address' => array()
                        );
                        
                        $tmp = apply_filters('op_guest_customer_data',$customer_data);
                        $result['data']['result'] = $tmp;
                        
                    }else{
                        $reponse_customer_data = $this->woo_class->_add_customer($customer_request_data,$session_data);
                        if($reponse_customer_data['status'] > 0)
                        {
                            $id = $reponse_customer_data['data'];
                            if($id)
                            {
                                do_action('op_add_customer_after',$id,$session_data,$customer_request_data);
                                $customer_data = $this->woo_class->formatCustomer($id);
                                $tmp = apply_filters('op_new_customer_data',$customer_data);
                                
                                $result['data']['result'] = $tmp;
                            }
                        }else{
                            throw new Exception($reponse_customer_data['message']);
                        }
                    }
                }
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
    }
}