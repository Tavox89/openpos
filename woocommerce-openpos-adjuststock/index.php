<?php
/*
Plugin Name: Woocommerce + Openpos +  Adjust stock app 
Plugin URI: http://openswatch.com
Description: enable adjust stock inside app of pos panel
Author: anhvnit@gmail.com
Author URI: http://openswatch.com/
Version: 1.2
WC requires at least: 2.6
Text Domain: woocommerce-openpos-adjust-stock
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/
define('OPENPOS_APP_STOCK_ADJUST_DIR',plugin_dir_path(__FILE__));
define('OPENPOS_APP_STOCK_ADJUST_URL',plugins_url('woocommerce-openpos-adjuststock'));
require_once( OPENPOS_APP_STOCK_ADJUST_DIR.'/lib/class_core_openpos.php' );
global $openpos_ajust_stock;
$openpos_ajust_stock = new Core_Openpos(OPENPOS_APP_STOCK_ADJUST_DIR.'/view');
add_action( 'init', 'op_adjust_stock_app_registerScripts' ,11 );
function op_adjust_stock_app_registerScripts(){
            
    $in_app = isset($_GET['app']) && $_GET['app'] == 'openpos_app_stock_adjust' ? true : false;
    if($in_app)
    {
        wp_enqueue_script('openpos-adjust-stock.app.datables', '//cdn.datatables.net/2.0.3/js/dataTables.min.js',array('jquery'));


        wp_enqueue_script('openpos-book.app.responsive.bootstrap', OPENPOS_APP_STOCK_ADJUST_URL.'/assets/js/responsive.bootstrap.js',array('jquery'));
        wp_enqueue_script('openpos-book.app.datables.responsive', OPENPOS_APP_STOCK_ADJUST_URL.'/assets/js/dataTables.responsive.js',array('jquery'));

        wp_enqueue_script('openpos-adjust-stock.app.bootstrap', OPENPOS_APP_STOCK_ADJUST_URL.'/assets/js/bootstrap.js',array('jquery'));
        wp_enqueue_script('openpos.adjust.stock.app.script', OPENPOS_APP_STOCK_ADJUST_URL.'/assets/js/app.js',array('jquery','jquery-ui-dialog','openpos-adjust-stock.app.datables','openpos-adjust-stock.app.bootstrap','openpos-book.app.datables.responsive','openpos-book.app.responsive.bootstrap'));
        wp_add_inline_script('openpos.adjust.stock.app.script',"
                    
        ");

        wp_register_style( 'jquery-datables', '//cdn.datatables.net/2.0.3/css/dataTables.dataTables.min.css' );
         wp_enqueue_style('openpos.stocktransfer.app.style', OPENPOS_APP_STOCK_ADJUST_URL.'/assets/css/app.css',array('jquery-datables'));

    }
    
    
}

function op_adjust_stock_app_stocks(){
    global $op_session;
    global $op_warehouse;
    global $OPENPOS_CORE;
    $session = isset($_REQUEST['session']) ? $_REQUEST['session'] : '';
    $result = array(
        'draw' => 1,
        'recordsTotal' => 1,
        'recordsFiltered' => 1,
        'data' => array()
    );
    $rows = array();
    if($session)
    {
        $session_data = $op_session->data($session);
        $warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : -1;

        
        
        $sort  = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : false;
        $search  = isset($_REQUEST['search']) ? $_REQUEST['search'] : array();
        $searchPhrase  = isset($search['value']) ? sanitize_text_field($search['value']) : '';
        $sortBy = 'date';
        $order = 'DESC';
        if($sort && is_array($sort))
        {
            $key = array_keys($sort);

            $sortBy = end($key);
            if($sortBy == 'id')
            {
                $sortBy = 'ID';
            }
            $order = end($sort);
        }

        $start =  isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 1;
        $length =  isset($_REQUEST['length']) ? intval($_REQUEST['length']) : 1;
       
        $current = $start / $length + 1;
        $offet = isset($_REQUEST['start']) ? intval($_REQUEST['start']) : ($current -1) * $length;
        $ignores = array();
        $args = array(
            'posts_per_page'   => $length,
            'offset'           => $offet,
            'current_page'           => $current,
            'category'         => '',
            'category_name'    => '',
            'orderby'          => $sortBy,
            'order'            => $order,
            'exclude'          => $ignores,
            'post_type'        => $OPENPOS_CORE->getPosPostType(),
            'post_status'      => $OPENPOS_CORE->getDefaultProductPostStatus(),
            'suppress_filters' => false
        );

        $search_product_ids = array();
        if($searchPhrase)
        {

            $args['s'] = $searchPhrase;
            $tmp = (int)$searchPhrase;
            if($tmp)
            {
               $tmp_product = wc_get_product($tmp);
               if($tmp_product)
               {
                   $tmp_post = $tmp_product->get_type();
                   if($tmp_post != 'variable')
                   {
                       $search_product_ids[] = $tmp_product->get_id();
                   }

               }
            }
            if(empty($search_product_ids))
            {
                $product_id = wc_get_product_id_by_sku( $searchPhrase );
                if($product_id)
                {
                    $tmp_product = wc_get_product($product_id);
                    $tmp_post = $tmp_product->get_type();
                    if($tmp_post == 'variable')
                    {
                        $search_product_ids = $tmp_product->get_children();
                        
                    }
                }
            }

        }
        $posts_array = array();
        if(!empty($search_product_ids))
        {
            
            foreach($search_product_ids as $search_product_id){
                $posts_array[] = get_post($search_product_id);
            }
            $total = count($posts_array);

        }else{
            $args = apply_filters('op_load_stock_product_args',$args);
            
            $posts = $OPENPOS_CORE->getProducts($args);
            $posts_array = $posts['posts'];

            $total = $posts['total'];
        }

        $result['draw'] = isset($_REQUEST['draw']) ? intval($_REQUEST['draw']) : 0;
        $result['recordsTotal'] =  $total;
        $result['recordsFiltered'] =  $total ;

        
        $warehouses = array();
        $warehouses[] = $op_warehouse->get($warehouse_id);
        $fields = array('post_title');
        foreach($posts_array as $post)
        {
            if(is_a($post, 'WP_Post'))
            {
                $product_id = $post->ID;
            }else{
                $product_id = $post->get_id();
                $post = get_post($product_id);
            }
            $_product = wc_get_product($product_id);
            if(!$_product)
            {
                continue;
            }
            $type = $_product->get_type();
            $allow_types = $OPENPOS_CORE->getPosProductTypes();

            if(in_array($type,$allow_types))
            {
                $tmp = array();
                $thumb = '';
                if( wc_placeholder_img_src() ) {
                    $thumb = wc_placeholder_img();
                }

                foreach($fields as $field)
                {
                    $tmp[$field] = $post->$field;
                }

                $tid = get_post_thumbnail_id($post->ID);
                if($tid)
                {
                    $props = wc_get_product_attachment_props( get_post_thumbnail_id($product_id), $post );
                    $thumb = get_the_post_thumbnail( $post->ID, 'shop_thumbnail', array(
                        'title'  => $props['title'],
                        'alt'    => $props['alt'],
                    ) );
                }
                $tmp['action'] = '<a href="javascript:void(0)" data-action="increase" class="update-row increase" data-id="'.$product_id.'">'.__('Increase','woocommerce-openpos-adjust-stock').'</a><a href="javascript:void(0)" class="update-row decrease"  data-action="decrease" data-id="'.$product_id.'">'.__('Decrease','woocommerce-openpos-adjust-stock').'</a><a href="javascript:void(0)" class="update-row update"  data-action="update" data-id="'.$product_id.'">'.__('Update','woocommerce-openpos-adjust-stock').'</a>';

                if($type == 'variation')
                {
                    $parent_id = $post->post_parent;
                    $parent_product = wc_get_product($parent_id);
                    if(!$tid)
                    {
                        if($tid = get_post_thumbnail_id($parent_id))
                        {
                            $props = wc_get_product_attachment_props( get_post_thumbnail_id($parent_id), $parent_product );
                            $thumb = get_the_post_thumbnail( $parent_id, 'shop_thumbnail', array(
                                'title'  => $props['title'],
                                'alt'    => $props['alt'],
                            ) );
                        }
                    }
                }

                $tmp['action'] = '<div class="action-row">'.$tmp['action'].'</div>';
                $tmp['regular_price'] = $_product->get_regular_price();
                $tmp['sale_price'] = $_product->get_sale_price();
                $price = $_product->get_price();
                $tmp['price'] = $price;
                $barcode = $OPENPOS_CORE->getBarcode($product_id);
                $tmp['barcode'] = $barcode;
                $sub_title = '';
                if($_product->get_type() == 'variation')
                {
                    $variation_attributes = $_product->get_attributes();
                    $sub_title = '<p>'.implode(',',$variation_attributes).'</p>';
                }
                $tmp['post_title'] .= $sub_title;
                $tmp['post_title'] = '<div class="product-name">'. $tmp['post_title']. '</div>';
                if(!$price)
                {
                    $price = 0;
                }

                $tmp['formatted_price'] = '<div class="vna-row-price row"><div class="col-md-8 text-right"><input type="text" value="'.$price.'" class="row-price-input form-control" /><div class="row-price-span">'.wc_price($price).'</div> </div><div class="col-md-4 text-left"> <a href="javascript:void(0)" data-id="'.$product_id.'" class="click-edit-price-a"><span class="glyphicon glyphicon-pencil"></span><span class="glyphicon glyphicon-saved"></span></a></div></div>';
                $qty = $_product->get_stock_quantity();

                $stock_manager = $_product->get_manage_stock() ? 'yes' : 'no';
                
                if($stock_manager != 'yes')
                {
                    $tmp['action'] = '';
                }

                $tmp['qty_html'] = '<form id="product-row-'.$product_id.'">';
                $tmp['total_qty'] = 0;

                $no_stock_text = __('No manage','openpos');

                foreach($warehouses as $w)
                {
                    $tmp_qty_html = '';
                    $read_only = '';
                    $checked = 'checked';
                    if(!$op_warehouse->is_instore($w['id'],$product_id))
                    {
                        $checked = '';
                        $read_only = 'readonly';
                    }
                    if($warehouse_id == -1)
                    {

                        $qty = $op_warehouse->get_qty($w['id'],$product_id);
                        if(!$qty){
                            $qty = 0;
                        }

                        $tmp['total_qty']  += 1 * $qty;
                        $tmp['qty']  = 1 * $qty;


                        if($w['id'] > 0)
                        {
                            if($qty === false)
                            {
                                $read_only = 'readonly';
                            }
                            if($stock_manager == 'yes'){
                                $tmp_qty_html .= '<div class="warehouse-product-qty"> <p>'.$w['name'].': </p><p><a class="reduct-qty" id="reduct-qty-data-id="'.$product_id.'" ">-</a><input '.$read_only.'  class="form-text-input input-control product-qty-warehouse" name="qty['.$product_id.']['.$w['id'].']" type="number" min="0" value="0"/><a class="plus-qty" id="plus-qty-data-id="'.$product_id.'" ">+</a></p></div>';
                            }else{
                                $tmp_qty_html .= '<div class="warehouse-product-qty"> <p>'.$w['name'].': </p><p><input '.$read_only.'  class="form-text-input input-control product-qty-warehouse" name="qty['.$product_id.']['.$w['id'].']" type="number"  disabled placeholder="'.$no_stock_text .'" /></p></div>';
                            }
                            
                        }else{
                            if($stock_manager == 'yes')
                            {
                                $tmp_qty_html .= '<div class="warehouse-product-qty"> <p>'.$w['name'].': </p><p><a class="reduct-qty" id="reduct-qty-data-id="'.$product_id.'" ">-</a><input data-id="'.$product_id.'" class="form-text-input input-control product-qty-warehouse" name="qty['.$product_id.']['.$w['id'].']" type="number" min="0"  value="0"/><a class="plus-qty" id="plus-qty-data-id="'.$product_id.'" ">+</a></p></div>';
                            }else{
                                $tmp_qty_html .= '<div class="warehouse-product-qty"> <p>'.$w['name'].': </p><p><input data-id="'.$product_id.'"  class="form-text-input input-control product-qty-warehouse"  disabled placeholder="'.$no_stock_text .'"/></p></div>';
                            
                            }
                            
                        }

                    }else{
                        if($w['id'] == $warehouse_id)
                        {
                            $qty = $op_warehouse->get_qty($w['id'],$product_id);

                            $tmp['total_qty']  += $qty;
                            $tmp['qty']  = $qty;

                            if($w['id'] > 0)
                            {
                                if($qty === false)
                                {
                                    $read_only = 'readonly';
                                }
                                if($stock_manager == 'yes')
                                {
                                    $tmp_qty_html .= '<div class="warehouse-product-qty"> <p><a href="javascript:void(0);" class="reduct-qty" data-id="'.$product_id.'" ">-</a><input '.$read_only.' class="form-text-input input-control product-qty-warehouse"  id="input-qty-'.$product_id.'"  name="qty['.$product_id.']" type="number"  min="0"  value="0"/><a href="javascript:void(0);" class="plus-qty" data-id="'.$product_id.'" ">+</a></p></div>';
                                }else{
                                    $tmp_qty_html .= '<div class="warehouse-product-qty"> <p><input '.$read_only.' class="form-text-input input-control product-qty-warehouse"  id="input-qty-'.$product_id.'"  name="qty['.$product_id.']"  disabled placeholder="'.$no_stock_text .'" /></p></div>';
                                }
                                
                            }else{
                            if($stock_manager == 'yes')
                            {
                                $tmp_qty_html .= '<div class="warehouse-product-qty"><p><a href="javascript:void(0);" class="reduct-qty" data-id="'.$product_id.'" ">-</a><input data-id="'.$product_id.'"  class="form-text-input input-control product-qty-warehouse" id="input-qty-'.$product_id.'" name="qty['.$product_id.']" type="number" min="0"  value="0"/><a  href="javascript:void(0);" class="plus-qty" data-id="'.$product_id.'" ">+</a></p></div>';
                            }else{
                                $tmp_qty_html .= '<div class="warehouse-product-qty"> <p><input data-id="'.$product_id.'"  class="form-text-input input-control product-qty-warehouse"  id="input-qty-'.$product_id.'"  disabled placeholder="'.$no_stock_text .'" /></p></div>';
                            }
                                
                            }
                        }
                    }
                    $tmp['qty_html'] .= apply_filters('op_warehouse_stock_qty_html_input',$tmp_qty_html,$tmp,$w);


                }
                $tmp['qty_html'] .= '</form>';
                $tmp['id'] = $product_id;

                $tmp['product_thumb'] = '<div class="vna-cell-image"><a href="javascript:void(0);" class="upload-a" data-id="'.$product_id.'"><span class="glyphicon glyphicon-camera"></span></a>'.$thumb.'</div>';

                $_tmp_product =  $tmp;
                if($_tmp_product && !empty($_tmp_product))
                {
                        $rows[] = array(
                            $_tmp_product['id'],
                            $_tmp_product['post_title'].'<p class="product-barcode">'.$_tmp_product['barcode'].'</p>',                     
                            wc_price($_tmp_product['price']),
                            $_tmp_product['qty'],
                            $_tmp_product['qty_html'],
                            $_tmp_product['action'],
                        );
                }

                
                $result['data'] = $rows;

            }

           
        }
    }
    echo json_encode($result);
    exit;
}
add_action( 'wp_ajax_app_get_adjust_stocks', 'op_adjust_stock_app_stocks' );
add_action( 'wp_ajax_nopriv_app_get_adjust_stocks', 'op_adjust_stock_app_stocks' );


function op_app_stock_update(){
    $result = array();

    global $op_session;
    global $op_warehouse;
    global $OPENPOS_CORE;
    $session = isset($_REQUEST['session']) ? $_REQUEST['session'] : '';
    $update_action = isset($_REQUEST['update_action']) ? $_REQUEST['update_action'] : '';
    $product_id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
    $qty = isset($_REQUEST['qty']) ? 1*$_REQUEST['qty'] : 0;
   
    if($session && $product_id)
    {
        $session_data = $op_session->data($session);
        $warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : -1;
        $product = wc_get_product($product_id);
        if($warehouse_id >= 0 && $product)
        {
            switch($update_action)
            {
                case 'increase':
                    $current_qty = $op_warehouse->get_qty($warehouse_id,$product_id);
                    $new_qty = 1 * $current_qty + $qty;
                    $op_warehouse->set_qty($warehouse_id,$product_id ,$new_qty);
                    break;
                case 'decrease':
                    $current_qty = $op_warehouse->get_qty($warehouse_id,$product_id);
                    $new_qty = 1 * $current_qty - $qty;
                    $op_warehouse->set_qty($warehouse_id,$product_id ,$new_qty);
                    break;
                case 'update':
                    $op_warehouse->set_qty($warehouse_id,$product_id ,$qty);
                    break;
            }
            do_action('op_app_stock_update',$product,$qty,$warehouse_id,$update_action,$session_data);
        }

    }

    echo json_encode($result);
    exit;
}
add_action( 'wp_ajax_op_app_stock_update', 'op_app_stock_update' );
add_action( 'wp_ajax_nopriv_op_app_stock_update', 'op_app_stock_update' );



if(!class_exists('OP_App_Abstract'))
{
        $openpos_dir = dirname(rtrim(OPENPOS_APP_STOCK_ADJUST_DIR , '/') ).'/woocommerce-openpos';
        require_once $openpos_dir.'/lib/abtract-op-app.php';
}
require_once OPENPOS_APP_STOCK_ADJUST_DIR.'/stock-adjust-app.php';

$myApp = new OP_Stock_Adjust_App();