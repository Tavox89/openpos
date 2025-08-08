<?php
if(!class_exists('OP_REST_API_Table'))
{
    class OP_REST_API_Table extends OP_REST_API{
        public function register_routes() {
            register_rest_route( $this->namespace, '/table/tables', array(
                'methods' => 'GET',
                'callback' => array($this,'tables'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/table/takeaways', array(
                'methods' => 'GET',
                'callback' => array($this,'takeaways'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/table/upload-table/(?P<table_id>\d+)', array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this,'upload_table'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/table/upload-takeaway/(?P<takeaway_id>\d+)', array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this,'upload_takeaway'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/table/clean-table/(?P<table_id>\d+)', array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this,'clean_table'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/table/remove-takeaway/(?P<takeaway_id>\d+)', array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this,'remove_takeaway'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
        }
        public function tables(WP_REST_Request $request = null){
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $desk_ids = array();
                $register = isset($this->session_data['register']) ? $this->session_data['register'] : [];
                if(!isset($register['id']))
                {
                    throw new Exception(__('Register not found','openpos'));
                }
                $all_tables = $this->session_data['tables'] ? $this->session_data['tables'] : [];
                
                foreach($all_tables as $table)
                {
                    $desk_id = $table['id'];
                    $desk_data = $this->table_class->bill_screen_data($desk_id);
                    $items = isset($desk_data['items']) ? $desk_data['items'] : array();
                    $version = isset($desk_data['ver']) ? $desk_data['ver'] : 0;
                    $sys_version = isset($desk_data['system_ver']) ? $desk_data['system_ver'] : 0;
                    $start_time = isset($desk_data['start_time']) ? $desk_data['start_time'] : 0;
                    $parent = isset($desk_data['parent']) ? $desk_data['parent'] : 0;
                    $child_desks = isset($desk_data['child_desks']) ? $desk_data['child_desks'] : [];
                    $seller = isset($desk_data['seller']) ? $desk_data['seller'] : null;
                    $fee_item = isset($desk_data['fee_item']) ? $desk_data['fee_item'] : null;
                    if(!isset($seller['id']))
                    {
                        $seller = null;
                    }
                    $note = isset($desk_data['note']) ? $desk_data['note'] : '';
                    $customer =  isset($desk_data['customer']) ? $desk_data['customer'] : null;
                    
                    $result_data = array(
                        'table' => $table,
                        'items' => $items,
                        'version'  => $version,
                        'system_ver'  => $sys_version,
                        'start_time'  => $start_time,
                        'parent'  => $parent,
                        'child_desks'  => $child_desks,
                        'seller' => $seller,
                        'note' => $note,
                        'customer' => $customer,
                        'fee_item' => $fee_item,
                    );
                    $result['data']['result'][$desk_id] = apply_filters('api_op_pull_desk_data',$result_data,$desk_data);
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
        public function takeaways(WP_REST_Request $request = null){
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
                $desk_ids = array();
                $list = $this->table_class->takeawayJsonTables($warehouse_id,$desk_ids);
                $result['data']['result'] =  $list;
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
        public function upload_table(WP_REST_Request $request = null){
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $table_id = $request->get_param('table_id');
                if(!$table_id)
                {
                    throw new Exception(__('Table not found','openpos'));
                }
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
    
                    throw new Exception(__('Please enter table data','openpos'));
                }

                $_tables = array(
                    'desk-'.$table_id = $by_data
                );
                $result['data']['result'] = $this->table_class->update_bill_screen($_tables,true);
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
        public function upload_takeaway(WP_REST_Request $request = null){
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $takeaway_id = $request->get_param('takeaway_id');
                if(!$takeaway_id)
                {
                    throw new Exception(__('Takeaway not found','openpos'));
                }
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
    
                    throw new Exception(__('Please enter table data','openpos'));
                }

                $_tables = array(
                    'takeaway-'.$takeaway_id = $by_data
                );
                $result['data']['result'] = $this->table_class->update_bill_screen($_tables,true);

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
        public function clean_table(WP_REST_Request $request = null){
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $table_id = $request->get_param('table_id');
                if(!$table_id)
                {
                    throw new Exception(__('Table not found','openpos'));
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
        public function remove_takeaway(WP_REST_Request $request = null){
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $takeaway_id = $request->get_param('takeaway_id');
                $force = $request->get_param('force_remove') == 'yes' ? true : false;
                $allow = apply_filters('op_allow_remove_takeaway',true,$takeaway_id,$this->session_data);
                if(!$allow)
                {
                    throw new Exception(__('You are not allowed to remove this takeaway','openpos'));
                }
                if(!$takeaway_id)
                {
                    throw new Exception(__('Takeaway not found','openpos'));
                }
                $this->table_class->removeJsonTable($takeaway_id,$force);

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