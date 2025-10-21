<?php 
if(!class_exists('Core_Openpos'))
{
    class Core_Openpos{
        public $template_path = '';
        public function __construct($template_path)
        {
            $this->template_path = $template_path;
        }
        public function op_get_template($template,$args = array()){
            if ( ! empty( $args ) && is_array( $args ) ) {
                extract( $args );
            }
            $message = '';
            $validate = true;
            $purchase_code = get_option('_op_purchase_code',false);
            $purchase_time = 1* get_option('_op_purchase_code_time',false);
            $current_time = time();
            $recheck = false;
            $time_duration = 3600*24*30;
            if(!$purchase_code)
            {
                $validate = false;
            }else{
                
                if(($current_time - $purchase_time) > $time_duration){
                    $recheck = true;
                }
            }
            $post_barcode = isset($_POST['purchase_code']) ? trim($_POST['purchase_code']) : '';
            if($post_barcode || $recheck)
            {
                $result = $this->getPurchaseCode($post_barcode);
                if($result['status'] == 0)
                {
                    $message = $result['message'];
                }
                if($result['status'] == 1)
                {
                    $purchase_data = $result['data'];
                    $item = $purchase_data->item;
                    if($item->id == '22613341')
                    {
                        update_option('_op_purchase_code',$post_barcode,false);
                        update_option('_op_purchase_code_time',$current_time,false);
                        $validate = true;
                    }
                }
            }
            ob_start();
            if(!$validate)
            {
                $html = '<form method="post" style="text-align: center;margin-top:  20px;"><p style="color:red;">'.$message.'</p><p>OpenPOS Purchase code: <input type="text" name="purchase_code" style="border:  solid 1px #999;border-radius: 0;font-weight: bold;" ><input type="submit" value="Submit" style="border:  solid 1px #999;padding:  3px;margin-left:  5px;text-transform: uppercase;font-weight: bold;" /></p><p>Please enter your OpenPos purchase code to use this function</p><p><a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-" target="_blank">How to get Purchase code ? </a></p></form>';
                return $html;
            }
            include $this->template_path.'/'.$template;
            return ob_get_clean();
        }
        function getPurchaseCode($code) {
            $result = array(
                'status' => 0,
                'message' => '',
                'data' => array(),
            );
            try{
                $personalToken = site_url();
                $code = trim($code);
                if (!preg_match("/^([a-f0-9]{8})-(([a-f0-9]{4})-){3}([a-f0-9]{12})$/i", $code)) {
                    throw new Exception("Invalid purchase code");
                }
                
                $url = "\x68\x74\x74\x70\163\72\57\57\163\x75\x70\160\157\x72\164\x2e\x6f\160\x65\x6e\163\167\x61\164\143\x68\x2e\143\157\155\x2f\x76\x65\162\x69\x66\171\57\x76\145\162\x69\146\x79\x2e\x70\x68\160\x3f\x72\x65\x73\165\154\164\x5f\164\x79\160\145\75\x6a\x73\157\156\46\160\x75\x72\143\150\x61\x73\x65\x5f\143\157\x64\145\x3d{$code}";
                $response = wp_remote_get($url, array(
                    "headers" => array(
                        "Authorization" => "Bearer {$personalToken}",
                        "User-Agent" => "Purchase code verification from Openpos"
                    )
                ));
                if (is_wp_error($response)) {
                    throw new Exception("Failed to look up purchase code");
                }
                $responseCode = wp_remote_retrieve_response_code($response);
                switch ($responseCode) {
                    case 404: throw new Exception("Invalid purchase code");
                    case 403: throw new Exception("The personal token is missing the required permission for this script");
                    case 401: throw new Exception("The personal token is invalid or has been deleted");
                }
                if ($responseCode !== 200) {
                    throw new Exception("Got status {$responseCode}, try again shortly");
                }
                $body = @json_decode(wp_remote_retrieve_body($response));
                if ($body === false && json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Error parsing response, try again");
                }
                $result['status'] = 1;
                $result['data'] = $body;
            }catch(Exception $e){
                $result['message'] = $e->getMessage();
                $result['status'] = 0;
            }
            return $result;
        }
        public function purchase_code_page(){
    
        }
        public function get_openpos_global(){
            
        }
    }
    
}


?>