<?php
/*
Plugin Name: Table waiter lock
Plugin URI: http://wpos.app
Description:  When John has started an order on a table 1, nobody else(except John and administrator/shopmanager ) should have access to the table 1.
Author: anhvnit@gmail.com
Author URI: http://wpos.app/
Version: 1.0
WC requires at least: 3.0
WC tested up to: 8.7.0
Text Domain: openpos-waiter-lock
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

add_action('op_before_api_return',function($action){
    if($action == 'upload-desk')
    {
        global $op_session_data;
        global $op_table;
        $user_id = isset($_REQUEST['seller']) ? $_REQUEST['seller'] : 0;
        if(!$user_id)
        {
            $user_id = isset($op_session_data['user_id']) ? $op_session_data['user_id'] : 0;
        }
        if($user_id)
        {
            $user = get_user_by( 'id', $user_id );
            $roles = $user->roles;
            $allow = false;
            if(in_array('administrator',$roles) || in_array('shop_manager',$roles))
            {
                $allow = true;
            }
            if(!$allow)
            {
                $tables = array();
                if(isset($_REQUEST['tables']))
                {
                    $tables = json_decode(stripslashes($_REQUEST['tables']),true);
                }
                
                $seller_id = 0;
                foreach($tables as $table_key => $table_data)
                {
                    if(strpos($table_key,'desk') !== false )
                    {
                        $table_id = $table_data['id'];
                        $old_table_data = $op_table->bill_screen_data($table_id,$type='dine_in');
                        $items = isset($old_table_data['items']) ? $old_table_data['items'] : array();
                        if(!empty($items))
                        {
                            $first_item = $items[0];
                            if(isset($first_item['seller_id']))
                            {
                                $seller_id = $first_item['seller_id'];
                            }
                        }
                    }
                    
                    
                }
                if($seller_id && $seller_id != $user_id)
                {
                    $seller_user = get_user_by( 'id', $seller_id );
                    $username = $seller_user->user_login;
                    $result = array(
                        "status" => 0,
                        "message" => "This table has been manage by '".$username."'",
                        "data" => array(),
                        "database_version" => 0
                    );
                    echo json_encode($result);
                    exit;
                }
            }
            
            
           
        }
        
    }
});