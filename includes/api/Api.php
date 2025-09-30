<?php
if(!class_exists('OP_REST_API'))
{
    class OP_REST_API extends WC_REST_CRUD_Controller{
        protected $namespace = 'op/v1';
        public $session_data = false;

        protected $session_class;
        protected $register_class;
        protected $warehouse_class;
        protected $core_class;
        protected $setting_class;
        protected $order_class;
        protected $woo_class;
        protected $table_class;
        protected $transaction_class;
        protected $cart_class;
        protected $report_class;
        protected $receipt_class;
        protected $exchange_class;

        public function __construct($core_class = array()){
            if(isset($core_class['op_register']))
            {
                $this->register_class = $core_class['op_register'];
            }
            if(isset($core_class['op_session']))
            {
                $this->session_class = $core_class['op_session'];
            }
            if(isset($core_class['op_warehouse']))
            {
                $this->warehouse_class = $core_class['op_warehouse'];
            }
            if(isset($core_class['core']))
            {
                $this->core_class = $core_class['core'];
            }
            if(isset($core_class['settings']))
            {
                $this->setting_class = $core_class['settings'];
            }
            if(isset($core_class['op_woo']))
            {
                $this->woo_class = $core_class['op_woo'];
            }
            if(isset($core_class['op_woo_order']))
            {
                $this->order_class = $core_class['op_woo_order'];
            }
            if(isset($core_class['op_table']))
            {
                $this->table_class = $core_class['op_table'];
            }
            if(isset($core_class['op_transaction']))
            {
                $this->transaction_class = $core_class['op_transaction'];
            }
            if(isset($core_class['op_woo_cart']))
            {
                $this->cart_class = $core_class['op_woo_cart'];
            }
            if(isset($core_class['op_report']))
            {
                $this->report_class = $core_class['op_report'];
            }
            if(isset($core_class['op_receipt']))
            {
                $this->receipt_class = $core_class['op_receipt'];
            }
            if(isset($core_class['op_exchange']))
            {
                $this->exchange_class = $core_class['op_exchange'];
            }
            
        }
        public function permission_callback(WP_REST_Request $request = null){
            
            if ($request && !$this->check_auth_header($request)) {
                return new WP_Error('rest_forbidden', __('Unauthorized', 'openpos'), array('status' => 401));
            }
            return true;
        }
        protected function check_auth_header(WP_REST_Request $request) {
            $session_id = $request->get_param('session', '');
            if($session_id)
            {
                $session_data = $this->session_class->data($session_id);
                if($session_data)
                {
                    $this->session_data = $session_data;
                    return true;
                }
                return false;
            }else{
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
            }
            
            return false;
        }
        
        public function rest_ensure_response($result){
            $warehouse_id = false;
            $login_warehouse_id = isset($this->session_data['login_warehouse_id']) ? $this->session_data['login_warehouse_id'] : 0;
            $login_cashdrawer_id = isset($this->session_data['login_cashdrawer_id']) ? $this->session_data['login_cashdrawer_id'] : 0;
            if($login_cashdrawer_id)
            {
                $warehouse_id = $login_warehouse_id;
            }
            if($warehouse_id !== false)
            {
                $database_version = get_option('_openpos_product_version_'.$warehouse_id,0);
                $result['response']['database_version'] = 1*$database_version;
            }
            
            return rest_ensure_response( $result );
        }
        public function _convertToCent($price,$decimal)
        {
            if(!$price)
            {
                return 0;
            }
            
            $pow = pow(10,$decimal);
            $number =  1 * $price* $pow ;
            return floor($number);
        }
        

    }
}