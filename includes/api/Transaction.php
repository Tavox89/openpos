<?php
if(!class_exists('OP_REST_API_Transaction'))
{
    class OP_REST_API_Transaction extends OP_REST_API{
        public function register_routes() {
            register_rest_route( $this->namespace, '/transaction/transactions', array(
                'methods' => 'GET',
                'callback' => array($this,'transactions'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/transaction/create', array(
                'methods' =>  WP_REST_Server::CREATABLE,
                'callback' => array($this,'save'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/transaction/get/(?P<transaction_id>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this,'get_transaction'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
        }
        public function transactions(WP_REST_Request $request = null){
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
                $order_id = $request->get_param('order_id');
                $register_id = $request->get_param('register_id');
                if($order_id)
                {
                    $result['data']['result'] =  $this->transaction_class->getOrderTransactions($order_id);
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
                $register = isset($this->session_data['register']) ? $this->session_data['register'] : [];
                if(!isset($register['id']))
                {
                    throw new Exception(__('Register not found','openpos'));
                }
                $warehouse_id = $register['outlet_id'];

                $raw_json = $request->get_body();
                $by_data = json_decode($raw_json, true); 
                if(!$by_data)
                {
                    throw new Exception(__('Please enter transaction data','openpos'));
                }
                $local_id = isset($by_data['id']) ? $by_data['id'] : 0;
                if(!$local_id)
                {
                    throw new Exception(__('Transaction ID is required','openpos'));
                }
                $exist_transaction = $this->transaction_class->get_by_local_id($local_id);
                if($exist_transaction)
                {
                    throw new Exception(__('Transaction ID already exists','openpos'));
                }
                $transaction_id = $this->transaction_class->add($by_data);
                if(!$transaction_id)
                {
                    throw new Exception(__('Transaction not saved','openpos'));
                }
                $transaction = $this->transaction_class->get($transaction_id);
                if(!$transaction)
                {
                    throw new Exception(__('Transaction not found','openpos'));
                }
                $result['data']['result'] = $transaction;
               
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
        public function get_transaction(WP_REST_Request $request = null){
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $transaction_id = $request->get_param('transaction_id');
                if(!$transaction_id)
                {
                    throw new Exception(__('Transaction ID is required','openpos'));
                }
                $register = isset($this->session_data['register']) ? $this->session_data['register'] : [];
                if(!isset($register['id']))
                {
                    throw new Exception(__('Register not found','openpos'));
                }
                $warehouse_id = $register['outlet_id'];
                $transaction = $this->transaction_class->get($transaction_id);
                if(!$transaction)
                {
                    throw new Exception(__('Transaction not found','openpos'));
                }
                $result['data']['result'] = $transaction;
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