<?php
/**
 * Created by PhpStorm.
 * User: anhvnit
 * Date: 3/20/19
 * Time: 11:51
 */
class WpOpApp extends OP_App_Abstract implements OP_App {
    public $key = 'wp-admin-app'; // unique
    public $name = 'WP Admin';
    public $thumb = 'app.png';
    public function __construct(){
        $this->thumb = OPENPOS_WP_APP_URL.'app.png';    
    }
    public function render()
    {
        
        header('X-Frame-Options: allow-from *');
        global $in_pos_app;
        $in_pos_app = true;
        $session = $this->get_session();
        $email = $session['email'];
        $session_id = $session['session'];
        $user = wp_signon( array('user_login' => $email,'user_password' => $session_id) );
        require_once OPENPOS_WP_APP_DIR.'/view/view.php';
    }

}