<?php
class OP_Stock_Adjust_App extends OP_App_Abstract implements OP_App {
    public $key = 'openpos_app_stock_adjust'; // unique
    public $name = 'Stock Adjustment';
    public $thumb = OPENPOS_APP_STOCK_ADJUST_URL.'/assets/images/app.png';
    
    public function render()
    {
        global $openpos_ajust_stock;
        $session = $this->get_session();
        $user_id = isset($session['user_id']) ? $session['user_id'] : 0;
        $allow = false;
        if($user_id)
        {
            $allow = true;
            if(user_can( $user_id, 'op_adjust_stock'))
            {
                $allow = true;
            }
        }
        $_allow = apply_filters('op_adjust_stock_app_permisison',$allow,$user_id);
        
        if($_allow)
        {
            echo  $openpos_ajust_stock->op_get_template('view.php',array(
                'session' => $session,
            ));
        }else{
            echo __('You do not permission to access this page','woocommerce-openpos-adjust-stock');
        }
        
    }

}