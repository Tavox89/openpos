<?php
/*
Plugin Name: Wordpress Admin App for OpenPOS
Plugin URI: http://openswatch.com
Description: A Wordpress Admin App for OpenPOS + Woocommerce.
Author: anhvnit@gmail.com
Author URI: http://openswatch.com/
Version: 1.3
Requires Plugins: woocommerce-openpos
WC requires at least: 2.6
Text Domain: openpos-app
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

define('OPENPOS_WP_APP_DIR',plugin_dir_path(__FILE__));
define('OPENPOS_WP_APP_URL',plugins_url('/',__FILE__));
add_action( 'init', 'customer_wpadmin_registerScripts' ,10 );
add_action( 'admin_enqueue_scripts', 'customer_wpadmin_load_admin_styles' );
add_filter('openpos_pos_header_style','customer_wpadmin_openpos_pos_header_style',10,1);

add_action('authenticate','op_auth',10,3);
add_action('openpos_logout','op_auth_openpos_logout',10,1);
add_action('wp_logout','op_wp_logout',1000);


function customer_wpadmin_registerScripts(){
        wp_enqueue_style( 'wp.admin.app.openpos.styles', OPENPOS_WP_APP_URL.'/pos.css','');
}

function customer_wpadmin_openpos_pos_header_style($handles){
        $handles[] = 'wp.admin.app.openpos.styles';
        return $handles;
}

function customer_wpadmin_load_admin_styles() {
        session_start();
        if($_SESSION && isset($_SESSION['is_openpos_admin_login']) && $_SESSION['is_openpos_admin_login'] == 1)
        {
                wp_enqueue_style( 'wp.admin.admin_css', OPENPOS_WP_APP_URL. '/admin.css', false, '1.0.0' );
        }
        
        
}  

function op_auth($user,$username,$password){
        global $in_pos_app;
        if($user == null && $in_pos_app)
        {
                global $op_session;

                $user = get_user_by( 'email', $username );
                
                if ( ! $user ) {
                        return new WP_Error(
                                'invalid_email',
                                __( 'Unknown email address. Check again or try your username.' )
                        );
                }
               
                $sesion_data = $op_session->data($password);
                if(!isset($sesion_data['email']) || $sesion_data['email'] != $username)
                {
                        return new WP_Error(
                                'invalid_email',
                                __( 'Unknown email address. Check again or try your username.' )
                        );
                }
                session_start();
                $_SESSION['is_openpos_admin_login'] = 1;

        }
        return $user;
}


function op_auth_openpos_logout($session_id){
        global $op_session;
        global $op_logout;
        $sesion_data = $op_session->data($session_id);
        $op_logout = true;
        if(isset($sesion_data['email']) )
        {
                session_start();
                $_SESSION['is_openpos_admin_login'] = 0;
                //wp_logout();
		wp_destroy_current_session();
		wp_clear_auth_cookie();
		wp_set_current_user( 0 );
        }
}

function op_wp_logout(){
}
add_action( 'init', function(){
        if(is_plugin_active( 'woocommerce-openpos/woocommerce-openpos.php' ))
        {
        
                if(!class_exists('OP_App_Abstract'))
                {
                        $openpos_dir = dirname(rtrim(OPENPOS_WP_APP_DIR , '/') ).'/woocommerce-openpos';
                        require_once $openpos_dir.'/lib/abtract-op-app.php';
                }
                require_once OPENPOS_WP_APP_DIR.'/wp-op-app.php';
                $myApp = new WpOpApp();
        }
} );
