<?php
/*
Plugin Name:  Tạo hóa đơn điện tử etax cho openpos
Plugin URI: http://wpos.app
Description: Tạo hóa đơn điện tử etax cho openpos. Xuất hóa đơn dạng xml . Theo format của chi cuc thuế viet nam
Author: anhvnit@gmail.com
Author URI: http://wpos.app
Version: 1.1
WC requires at least: 2.6
Text Domain: openpos-vn-etax
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

use PhpParser\Node\Stmt\Foreach_;

define('OPENPOS_VNETAX_DIR',plugin_dir_path(__FILE__));
define('OPENPOS_VNETAX_URL',plugins_url('/',__FILE__));
require_once( OPENPOS_VNETAX_DIR.'class-vn-etax.php' );




add_action('op_add_order_after',function($order,$order_parse_data){
    global $OPENPOS_SETTING;
    $addition_information = isset($order_parse_data['addition_information']) ? $order_parse_data['addition_information'] : array();
    $tax_fields = array(
        'enable_etax' => '',
        'tax_code' => '',
        'tax_buyer_name' => '',
        'tax_buyer_address' => '',
    );
    foreach($addition_information as $info)
    {
        $key = $info['key'];
        if(isset($tax_fields[$key]))
        {
            $tax_fields[$key] = $info['value'];
        }
    }
    if($tax_fields['enable_etax'] == 'yes')
    {
        $etax_hoadon_index = get_option('etax_hoadon_index',0);
        $etax_hoadon_length = $OPENPOS_SETTING->get_option('etax_hoadon_length','openpos_addon');
        $next_index = $etax_hoadon_index + 1;
        $SHDon = str_pad($next_index, $etax_hoadon_length, '0', STR_PAD_LEFT);
        $invoice = new OP_Vn_Etax();
        $THDon = 'Hóa đơn bán hàng';
        $hTTT = array();
        $payment_method = $order_parse_data['payment_method'];
        foreach($payment_method as $p)
        {
            if($p['code'] =='cash')
            {
                $hTTT[] = 'Tiền mặt';
            }else{
                $hTTT[] = $p['name'];
            }
        }

        $invoice->setThongTinChung([
            'THDon' => $THDon,//'1',
            'KHMSHDon' => $OPENPOS_SETTING->get_option('etax_khmshdon','openpos_addon'),
            'KHHDon' => $OPENPOS_SETTING->get_option('etax_khhdon','openpos_addon'),
            'SHDon' => $SHDon,
            'NLap' => (new DateTime('now', wp_timezone()))->format('Y-m-d'),
            'DVTTe' => 'VND',
            'TGia' => '1',
            'HTTToan' => implode(',', $hTTT),
            'TTCKhac' => array(
                array(
                    'TTruong' => 'Đơn hàng',
                    'KDLieu' => 'string',
                    'DLieu' => $order_parse_data['order_number']
                ),
                
            )
        ]);
        
        $invoice->setNguoiBan([
            'MST' => $OPENPOS_SETTING->get_option('etax_mst','openpos_addon'),
            'Ten' => $OPENPOS_SETTING->get_option('etax_name','openpos_addon'),
            'DChi' => $OPENPOS_SETTING->get_option('etax_address','openpos_addon'),
            'SDThoai' => $OPENPOS_SETTING->get_option('etax_phone','openpos_addon'),
            'Email' => $OPENPOS_SETTING->get_option('etax_email','openpos_addon'),
        ]);
        
        $invoice->setNguoiMua([
            'Ten' => $tax_fields['tax_buyer_name'],
            'MST' =>  $tax_fields['tax_code'],
            'DChi' =>  $tax_fields['tax_buyer_address'],
            //'Email' =>  $tax_fields['tax_buyer_email'],
        ]);
        $items = $order_parse_data['items'];
        $count = 1;

        foreach($items as $item)
        {
            $dvt = 'chiec';
            $_product = isset($item['product']) ? $item['product'] : 0;
            $product_id  = $item['product_id'];
            if(isset($_product['parent_id']) && $_product['parent_id'] > 0)
            {
                $product_id = $_product['parent_id'];
            }
            
            
            $product_unit = get_post_meta($product_id, '_product_unit', true);
            if($product_unit)
            {
                $dvt = $product_unit;
            }

            $tax_details = isset($item['tax_details']) ? $item['tax_details'] : array();
            $tsuat = 0;
            foreach($tax_details as $t)
            {
                if($t['rate'] > 0)
                {
                    $tsuat += $t['rate'];
                }
            }
            $tax_amount = $item['total_tax'];
            $total_incl_tax = $item['total_incl_tax'];
            if($item['qty'] <= 0)
            {
                $invoice->addHangHoa([
                    'TChat' => '4',
                    'STT' => $count,
                    'Ten' => $item['name'],
                    'DVT' => $dvt,
                    'SLuong' => $item['qty'],
                    'DGia' => $item['final_price'],
                    'ThTien' => $item['total'],
                    'TSuat' => $tsuat,
                    'TGTGTang' => $tax_amount,
                    'TCong' => $total_incl_tax,
                ]);
            }else{
                $invoice->addHangHoa([
                    'TChat' => '1',
                    'STT' => $count,
                    'Ten' => $item['name'],
                    'DVT' => $dvt,
                    'SLuong' => $item['qty'],
                    'DGia' => $item['final_price'],
                    'ThTien' => $item['total'],
                    'TSuat' => $tsuat,
                    'TGTGTang' => $tax_amount,
                    'TCong' => $total_incl_tax,
                ]);
            }
            $count++;
        }
        
        
        $invoice->setThanhToan([
            'TgTCThue' =>($order_parse_data['grand_total'] - $order_parse_data['tax_amount']),
            'TgTThue' => $order_parse_data['tax_amount'],
            'TgTTTBSo' => $order_parse_data['grand_total']
        ]);
        $file_path = wp_upload_dir()['basedir'].'/etax';
        if(!file_exists($file_path))
        {
            mkdir($file_path, 0777, true);
        }
       
        $file_name = 'invoice_'.$order->get_id().'.xml';
        $file_path = $file_path.'/'.$file_name;
        $invoice->generate($file_path);
        $order->add_meta_data('etax_file', $file_name);
        $order->add_order_note(__('Hóa đơn điện tử đã được tạo thành công.','openpos'),true);
        $order->save();
        update_option('etax_hoadon_index',$next_index);
    }
},100,2);


add_filter('op_get_login_cashdrawer_data',function($session_response_data){
    $addition_checkout_fields = isset($session_response_data['setting']['pos_addition_checkout_fields']) ? $session_response_data['setting']['pos_addition_checkout_fields'] : array();
    $addition_checkout_fields[] = array(
        'code' => 'enable_etax',
        'type' => 'select',
        'label' => 'Xuất hóa đơn điện tử',
        'description' => '',
        'require' => 'yes',
        'default' => 'no',
        'options' => array(
            ['value' => 'no','label' => 'Không'],
            ['value' => 'yes','label' => 'Có'],
        ),
    );
    $addition_checkout_fields[] = array(
        'code' => 'tax_code',
        'type' => 'text',
        'label' => 'Mã số thuế',
        'description' => '',
        'require' => 'yes',
        'default' => '',
        'condition'=> array(
            'action' => 'show', // show - hide
            'groups' => array(
                array(
                    'operator' => 'and', // or / and
                    'items' => array(
                        [
                            'operator' => '', // or / and
                            'option_id' => 'enable_etax',
                            'relation' => 'is', // is ,is_not 
                            'value' => 'yes'
                            ]
                    )
                )
            )
        )
    );
    $addition_checkout_fields[] = array(
        'code' => 'tax_buyer_name',
        'type' => 'text',
        'label' => 'Tên người mua',
        'description' => '',
        'require' => 'yes',
        'default' => '',
        'condition'=> array(
            'action' => 'show', // show - hide
            'groups' => array(
                array(
                    'operator' => 'and', // or / and
                    'items' => array(
                        [
                            'operator' => '', // or / and
                            'option_id' => 'enable_etax',
                            'relation' => 'is', // is ,is_not 
                            'value' => 'yes'
                            ]
                    )
                )
            )
        )
    );
    $addition_checkout_fields[] = array(
        'code' => 'tax_buyer_address',
        'type' => 'text',
        'label' => 'Địa chỉ người mua',
        'description' => '',
        'require' => 'yes',
        'default' => '',
        'condition'=> array(
            'action' => 'show', // show - hide
            'groups' => array(
                array(
                    'operator' => 'and', // or / and
                    'items' => array(
                        [
                            'operator' => '', // or / and
                            'option_id' => 'enable_etax',
                            'relation' => 'is', // is ,is_not 
                            'value' => 'yes'
                            ]
                    )
                )
            )
        )
    );
    $session_response_data['setting']['pos_addition_checkout_fields'] = $addition_checkout_fields;
    return $session_response_data;
},20,1);
add_filter('op_addon_setting',function($addition_general_setting){
    global $OPENPOS_SETTING;
    
    $addition_general_setting[] =     array(
        'name'    => 'etax_hoadon_length',
        'label'   => __( 'Độ dài số hóa đơn', 'openpos' ),
        'desc'    => 'Độ dài số hóa đơn. VD: số hóa đơn 0000001 => độ dài 7',
        'type'    => 'text',
        'default' => '7',
    );
    $addition_general_setting[] =     array(
        'name'    => 'etax_khmshdon',
        'label'   => __( 'Ký hiệu mẫu số hóa đơn', 'openpos' ),
        'desc'    => 'Ký hiệu mẫu số hóa đơn. VD: 01GTKT0/001',
        'type'    => 'text',
        'default' => '01GTKT0/001',
    );
    $addition_general_setting[] =     array(
        'name'    => 'etax_khhdon',
        'label'   => __( 'Ký hiệu hóa đơn', 'openpos' ),
        'desc'    => 'Ký hiệu hóa đơn. VD: AA/22E',
        'type'    => 'text',
        'default' => 'AA/22E',
    );
    $addition_general_setting[] =     array(
        'name'    => 'etax_mst',
        'label'   => __( 'Mã Số Thuế', 'openpos' ),
        'desc'    => 'mã số thuế người bán',
        'type'    => 'text',
        'default' => '',
    );
    
    $addition_general_setting[] =     array(
        'name'    => 'etax_name',
        'label'   => __( 'Tên', 'openpos' ),
        'desc'    => 'Tên trên mã số thuế',
        'type'    => 'text',
        'default' => '',
    );
    $addition_general_setting[] =     array(
        'name'    => 'etax_address',
        'label'   => __( 'Địa chỉ', 'openpos' ),
        'desc'    => 'địa chỉ đăng ký mã số thuế người bán',
        'type'    => 'text',
        'default' => '',
    );
    $addition_general_setting[] =     array(
        'name'    => 'etax_phone',
        'label'   => __( 'Số điện thoại', 'openpos' ),
        'desc'    => 'Số điện thoại đăng ký mã số thuế người bán',
        'type'    => 'text',
        'default' => '',
    );
    $addition_general_setting[] =     array(
        'name'    => 'etax_email',
        'label'   => __( 'Email', 'openpos' ),
        'desc'    => 'mail đăng ký mã số thuế người bán',
        'type'    => 'text',
        'default' => '',
    );
    return $addition_general_setting;
},100,1);

add_action('woocommerce_product_options_inventory_product_data', function() {
    woocommerce_wp_text_input( array(
        'id'          => '_product_unit',
        'label'       => __('Đơn vị tính', 'openpos'),
        'desc_tip'    => true,
        'description' => __('Nhập đơn vị tính cho sản phẩm (ví dụ: chiếc, hộp, kg, ...)', 'openpos'),
        'type'        => 'text',
    ));
});

add_action('woocommerce_process_product_meta', function($post_id) {
    if (isset($_POST['_product_unit'])) {
        update_post_meta($post_id, '_product_unit', sanitize_text_field($_POST['_product_unit']));
    }
});

add_action('woocommerce_order_details_after_order_table', function($order){
    // Chỉ hiển thị cho khách đã đăng nhập và đơn hàng thuộc về họ
    if (!is_user_logged_in()) return;
    if (!$order instanceof WC_Order) return;
    if ($order->get_customer_id() != get_current_user_id()) return;

    $etax_file = $order->get_meta('etax_file');
    if ($etax_file) {
        $upload_dir = wp_upload_dir();
        $file_url = $upload_dir['baseurl'] . '/etax/' . $etax_file;
        echo '<p><a class="button" href="' . esc_url($file_url) . '" download target="_blank">Tải hóa đơn điện tử (XML)</a></p>';
    }
});

add_action('woocommerce_admin_order_data_after_order_details', function($order){
    if (!$order instanceof WC_Order) return;
    $etax_file = $order->get_meta('etax_file');
    if ($etax_file) {
        $upload_dir = wp_upload_dir();
        $file_url = $upload_dir['baseurl'] . '/etax/' . $etax_file;
        echo '<p ><a style="    margin-top: 20px;" class="button button-primary" href="' . esc_url($file_url) . '" download target="_blank">Tải hóa đơn điện tử (XML)</a></p>';
    }
});

function op_get_online_order_data_pdf_invoice($order_data){
    global $op_session;
    $session_id = isset($_REQUEST['session']) ? $_REQUEST['session'] : '';

    $session_data = $op_session->data($session_id);
    if(!empty($session_data))
    {
        $invoice_url = admin_url('admin-ajax.php?action=op_generate_wpo_wcpdf&document_type=invoice&order_ids='.$order_data['id'].'&sid='.$session_id); //&_wpnonce=91229371c8
        $packing_url = admin_url('admin-ajax.php?action=op_generate_wpo_wcpdf&document_type=packing-slip&order_ids='.$order_data['id'].'&sid='.$session_id); 
        $order_data['extra_html'] = '<a  target="_blank" href="'.$invoice_url .'" class="order-pdf-invoice-btn">'.__('PDF Invoice','woocommerce-penpos-pdf-invoice').'</a>&nbsp;';
        $order_data['extra_html'] .= '<a target="_blank" href="'.$packing_url .'" class="order-pdf-invoice-btn">'.__('PDF Packing Slip','woocommerce-penpos-pdf-invoice').'</a>';
    }
    
    return $order_data;
}
add_filter('op_get_online_order_data',function($order_data){
    $order_id = $order_data['id'];
    $order = wc_get_order($order_id);
    
    if($order)
    {
        if(!isset($order_data['extra_html']))
        {
            $order_data['extra_html'] = '';
        }
        $etax_file = $order->get_meta('etax_file');
        if ($etax_file) {
            $upload_dir = wp_upload_dir();
            $file_url = $upload_dir['baseurl'] . '/etax/' . $etax_file;
            $order_data['extra_html'] .= '<p ><a style="    margin-top: 20px;" class="button button-primary" href="' . esc_url($file_url) . '" download target="_blank">Tải hóa đơn điện tử (XML)</a></p>';
        }
    }
    return $order_data;
},100,1);