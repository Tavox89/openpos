<?php
if(!class_exists('OP_REST_API_Product'))
{
    class OP_REST_API_Product extends OP_REST_API{
       
        
        public function register_routes(){
            
            register_rest_route( $this->namespace, '/product/products', array(
                'methods' => 'GET',
                'callback' => array($this,'products'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/product/update-products', array(
                'methods' => 'GET',
                'callback' => array($this,'update_products'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/product/add-product', array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this,'save'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/product/stock-overview', array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this,'stock_overview'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/product/search-product', array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this,'search_product'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
            register_rest_route( $this->namespace, '/product/scan-product', array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this,'scan_product'),
                'permission_callback' => array($this,'permission_callback'),
                ) 
            );
        }
        
        private function _formatApiProduct($product_data,$session_data){
            
            $currency = $session_data['setting']['currency'];
            $decimal = $currency['decimal'];
            $price = $this->_convertToCent($product_data['price'],$decimal);
            $priceInclTax = $this->_convertToCent($product_data['price_incl_tax'],$decimal);
            $specialPrice = $this->_convertToCent($product_data['special_price'],$decimal);
            $product = array(
                'id' => $product_data['id'],
                'parentId' => $product_data['parent_id'],
                'parentProduct' => null, // Reference to parent product if this is a variation or bundle
                'name' => $product_data['name'],
                'description' => isset($product_data['description']) ? $product_data['description'] : '',
                'sku' => $product_data['sku'],
                'barcode' => $product_data['barcode'],
                'image' => $product_data['image'],
                'groupItems' => array(),
                'bundles' => $product_data['bundles'],
                'options' => $product_data['options'],
                'variations' => $product_data['variations'],
                'customNotes' => $product_data['custom_notes'],
                'taxes' =>  isset($product_data['tax']) ? $product_data['tax'] : array(),
                'price' => $price,
                'priceInclTax' => $priceInclTax,
                'isPriceInclTax' => isset($product_data['price_included_tax']) && $product_data['price_included_tax'] == 1 ? true : false,
                'specialPrice' => $specialPrice,
                'specialStartAt' => isset($product_data['special_start_at']) ? $product_data['special_start_at'] : 0,
                'specialEndAt' => isset($product_data['special_end_at']) ? $product_data['special_end_at'] : 0, 
                'discountRules' => isset($product_data['discount_rules']) ? $product_data['discount_rules'] : array(),
                'stockStatus' => $product_data['stock_status'],
                'stockUnit' => isset($product_data['stock_unit']) ? $product_data['stock_unit'] : '',
                'stockQty' => $product_data['qty'],
                'decimalQty' => $product_data['decimal_qty'], // number of decimal places for quantity 
                'manageStock' => $product_data['manage_stock'],
                'minQty' =>  isset($product_data['min_qty']) ? $product_data['min_qty'] : 0, // Minimum quantity for purchase
                'maxQty' =>  isset($product_data['max_qty']) ? $product_data['max_qty'] : 0, // Maximum quantity for purchase
                'allowDecal' => isset($product_data['allow_decal']) && $product_data['allow_decal'] == 'yes' ? true : false, 
                'allowChangePrice' => $product_data['allow_change_price'], 
                'productType' => isset($product_data['product_type']) ? $product_data['product_type'] : '', // product type, simple, variable, bundle, group
                'type' => $product_data['type'], // display type
                'isFeatured' => isset($product_data['is_featured']) ? $product_data['is_featured'] : false, // is featured product
                'isDisplay' => $product_data['display'], 
                'isSearchable' => isset($product_data['is_searchable']) ? $product_data['is_searchable'] : true, // is searchable product
                'isSync' => false,
                'createdAt' => 0,
                'updatedAt' => 0,
                'productCategories' => $product_data['categories'], 
                'kitchenArea' => isset($product_data['kitchen_area']) ? $product_data['kitchen_area'] : array(), // kitchen area
                'additionInfo' => isset($product_data['addition_info']) ? $product_data['addition_info'] : array(), // kitchen area
            );

            return $product;
        }
        public function products($request)
        {
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $session_data = $this->session_data;
                $term = $request->get_param( 'term' ) ? $request->get_param( 'term' ) : '';
                if($term)
                {
                    $term = sanitize_text_field($term);
                }
                $per_page = $request->get_param( 'per_page' );
                $page = $request->get_param( 'page' ) ? $request->get_param( 'page' ) : 1;
                $rowCount = $per_page ? $per_page : 10;
                $current = $page;
                $offet = ($current -1) * $rowCount;
                $sortBy = 'title';
                $order = 'ASC';
        
                $args = array(
                    'posts_per_page'   => $rowCount,
                    'offset'           => $offet,
                    'current_page'           => $current,
                    'category'         => '',
                    'category_name'    => '',
                    'orderby'          => $sortBy,
                    'order'            => $order,
                    'post_type'        => $this->core_class->getPosPostType(),
                    'post_status'      => $this->core_class->getDefaultProductPostStatus(),
                    'suppress_filters' => false
                );
                $args = apply_filters('op_load_product_args',$args);
               
                $products = $this->core_class->getProducts($args,true);
               
                if(isset($products['total_page']))
                {
                    $total_page = $products['total_page'];
                }else{
                    $total_page = 1;//$this->getTotalPageProduct();
                }
                
                $data = array('total_page' => $total_page, 'page' => $current,'term'=>$term);
        
                $data['products'] = array();
                
                $show_out_of_stock = false;
                $warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : 0;
                $show_out_of_stock_setting = $this->setting_class->get_option('pos_display_outofstock','openpos_pos');
                if($show_out_of_stock_setting == 'yes')
                {
                    $show_out_of_stock = true;
                }
                foreach($products['posts'] as $_product)
                {
                    if(is_a($_product, 'WP_Post'))
                    {
                        $product_id = $_product->ID;
                        $product = wc_get_product($product_id );
                    }else{
                        $product_id = $_product->get_id();
                        $product = $_product;
                    }
        
                    $product_data = $this->woo_class->get_product_formatted_data($_product,$warehouse_id);
                    
                    $allow = $this->warehouse_class->_allowProduct($product_data,$warehouse_id);
                    
                    if(!$allow || !$product_data)
                    {
                        continue;
                    }
                   
                    if(!$show_out_of_stock)
                    {
                        if( $product_data['manage_stock'] &&  is_numeric($product_data['qty']) && $product_data['qty'] <= 0)
                        {
                            $product_data['display'] = false;
                            if(!empty($data['product']))
                            {
                                continue;
                            }
                        }
                        if($warehouse_id == 0)
                        {
                            if($product->get_type() == 'variable' && $product_data['stock_status'] == 'outofstock')
                            {
                                continue;
                            }
                            if( !$product_data['manage_stock'] &&  $product_data['stock_status'] == 'outofstock' )
                            {
                                continue;
                            }
                        }
                        
                    }
                   
                    $data['products'][] = $this->_formatApiProduct($product_data,$session_data);
                }
                $result['code'] = 200;
                $result['data']['status'] = 1;
                $result['data']['result'] = $data;
            }catch(Exception $e)
            {
                $result['code'] = 400;
                $result['data']['status'] = 0;
                $result['message'] = $e->getMessage();
                
            }
            
            
            return $this->rest_ensure_response($result);
        }
        public function update_products($request){
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $session_data = $this->session_data;
                $login_warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : 0;
                $local_db_version = $request->get_param( 'local_db_version' ) ? $request->get_param( 'local_db_version' ) : 0; 
                $database_version = get_option('_openpos_product_version_'.$login_warehouse_id,0);
                if($local_db_version > 0)
                {
                    $product_changed_data = $this->woo_class->getProductChanged($local_db_version,$login_warehouse_id);

                    $product_ids = array();
                    foreach($product_changed_data['data'] as $product_id => $qty)
                    {
                        $product_ids[] = $product_id;
                    }

                    $data = array('total_page' => 0,'page' => 0,'version' => $product_changed_data['current_version']);

                    $data['product'] = array();
                    $data['delete_product'] = array();
                    $login_cashdrawer_id = isset($session_data['login_cashdrawer_id']) ?  $session_data['login_cashdrawer_id'] : 0;
                    
                    
                    $show_out_of_stock = true;
                    
                    $allow_status = $this->core_class->getDefaultProductPostStatus();
                    foreach($product_ids as $product_id)
                    {
                        $_product = get_post($product_id);
                        $status = get_post_status( $product_id );
                        if(!in_array($status,$allow_status))
                        {
                            $data['delete_product'][] = $product_id;
                            continue;
                        }
                        $warehouse_id = 0;
                        if($login_cashdrawer_id > 0)
                        {
                            $warehouse_id = $session_data['login_warehouse_id'];

                        }

                        $product_data = $this->woo_class->get_product_formatted_data($_product,$warehouse_id);

                        if(!$product_data)
                        {
                            $data['delete_product'][] = $product_id;
                            continue;
                        }
                        if(empty($product_data))
                        {
                            $data['delete_product'][] = $product_id;
                            continue;
                        }
                        if(!$show_out_of_stock)
                        {
                            if( $product_data['manage_stock'] &&  is_numeric($product_data['qty']) && $product_data['qty'] <= 0)
                            {
                                $data['delete_product'][] = $product_id;
                                continue;
                            }
                        }

                        $data['product'][] = $this->_formatApiProduct($product_data,$session_data);

                    }
                    $version = $product_changed_data['current_version'];
                
                    if(empty($data['product']) &&  $version == 0)
                    {
                        $version = $database_version;
                    }

                    $result['data']['result'] = array(
                        'product' => $data['product'],
                        'version' => $version
                    );
                }else{
                    $result['data']['result'] = array(
                        'product' => array(),
                        'version' => $database_version
                    );
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
        public function save($request)
        {
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{

                $session_data = $this->session_data;
                $warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : 0;
                $product_data = json_decode(stripslashes($request->get_param('product')),true);
                $barcode = isset($product_data['barcode']) ? trim($product_data['barcode']) : '';
                $name = isset($product_data['name']) ? trim($product_data['name']) : '';
                $qty = isset($product_data['qty']) ? trim($product_data['qty']) : 0;
                $price = isset($product_data['price']) ? 1 * $product_data['price'] : 0;
                $tax_amount = isset($product_data['tax_amount']) ? 1 * $product_data['tax_amount'] : 0;
                $entered_price = isset($product_data['entered_price']) ? 1 * $product_data['entered_price'] : 0;
                $description = isset($product_data['description']) ?  $product_data['description'] : '';
                $final_price = $price + $tax_amount;
                if($entered_price)
                {
                    $final_price = $entered_price;
                }

                if(!$barcode)
                {
                    throw new Exception(__('Please enter product barcode','openpos'));
                }
                $product_id = $this->_core->getProductIdByBarcode($barcode);
                if(!$product_id)
                {
                    $objProduct = new WC_Product();
                    $objProduct->set_price($final_price);
                    $objProduct->set_regular_price($final_price);
                    $objProduct->set_name($name);
                    $objProduct->set_description($description);
                    $objProduct->set_stock_quantity(0);
                    $objProduct->set_sku($barcode);
                    $product_id = $objProduct->save();
                    $this->warehouse_class->set_qty($warehouse_id,$product_id,$qty);
                    $barcode_field = $this->setting_class->get_option('barcode_meta_key','openpos_label');
                    update_post_meta($product_id,$barcode_field,$barcode);
                    $status = 'pending';
                    $post = array( 'ID' => $product_id, 'post_status' => $status );
                    wp_update_post($post);
                }

                do_action( 'openpos_after_add_custom_product',$product_id,$product_data );

                $product_post = get_post($product_id);
                $data = $this->woo_class->get_product_formatted_data($product_post,$warehouse_id);
                $result['data']['result'] = $this->_formatApiProduct($data,$session_data);
                $result['code'] = 200;
                $result['data']['status'] = 1;
                $result = apply_filters('api_op_get_custom_item_data',$result,$session_data,$product_data);

            }catch(Exception $e)
            {
                $result['code'] = 400;
                $result['data']['status'] = 0;
                $result['message'] = $e->getMessage();
                
            }
            return $this->rest_ensure_response($result);
        }
        public function stock_overview($request){
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $term = $request->get_param( 'term' ) ? $request->get_param( 'term' ) : '';
                if(!$term)
                {
    
                    throw new Exception(__('Please enter term to search','openpos'));
                }
                $product_id = $this->core_class->getProductIdByBarcode($term);
                $warehouses = $this->warehouse_class->warehouses();
                if($product_id)
                {
    
                    $total_with_online = 0;
                    $total_no_online = 0;
                    $product = wc_get_product($product_id);
                    $product_data = array(
                        'name' => $product->get_name()
                    );
                    $stock_data = array();
                    foreach($warehouses as $w)
                    {
                        if($w['status'] == 'draft')
                        {
                            continue;
                        }
                        $qty = $this->warehouse_class->get_qty($w['id'],$product_id);
                        $total_with_online += $qty;
                        if($w['id'])
                        {
                            $total_no_online += $qty;
                        }
                        $stock_data[]  = array( 'warehouse' => $w['name'] , 'qty' => $qty );
                    }
                    $product_data['stock_overview'] = $stock_data;
                    $result['data']['result'][] = $product_data;
    
                }else{
                    $posts = $this->woo_class->searchProductsByTerm($term);
                    foreach($posts as $post)
                    {
                        $product_id = $post->ID;
                        $total_with_online = 0;
                        $total_no_online = 0;
                        $product = wc_get_product($product_id);
                        if(!$product)
                        {
                            continue;
                        }
                        if($product->get_type() == 'variable')
                        {
                            continue;
                        }
                        $product_data = array(
                            'name' => $product->get_name()
                        );
                        $stock_data = array();
                        foreach($warehouses as $w)
                        {
                            if($w['status'] == 'draft')
                            {
                                continue;
                            }
                            $qty = $this->warehouse_class->get_qty($w['id'],$product_id);
                            $total_with_online += $qty;
                            if($w['id'])
                            {
                                $total_no_online += $qty;
                            }
                            $stock_data[]  = array( 'warehouse' => $w['name'] , 'qty' => $qty );
                        }
                        $product_data['stock_overview'] = $stock_data;
                        $result['data']['result'][] = $product_data;
                    }
                }
                if(empty($result['data']['result']))
                {
                    $result['code'] = 200;
                    $result['data']['status'] = 2;
                    $result['status'] = 2;
                    $result['message'] = __('No product found. Please check your barcode !','openpos');
                }else{
                    $result['code'] = 200;
                    $result['data']['status'] = 1;
                }
            }catch(Exception $e)
            {
                $result['code'] = 400;
                $result['data']['status'] = 0;
                $result['message'] = $e->getMessage();
                
            }
            return $this->rest_ensure_response($result);
        }
        public function search_product($request)
        {
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $term = $request->get_param( 'term' ) ? $request->get_param( 'term' ) : '';
                if(!$term)
                {
    
                    throw new Exception(__('Please enter term to search','openpos'));
                }
                $session_data = $this->session_data;
                $warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : 0;
                $product_id_by_barcode = $this->core_class->getProductIdByBarcode($term,false);

                
                if($product_id_by_barcode)
                {
                    $product_post = get_post($product_id_by_barcode);
                    $tmp_product = $this->woo_class->get_product_formatted_data($product_post,$warehouse_id,false,true);
                    if($tmp_product)
                    {
                        $products[$product_id_by_barcode] = $this->_formatApiProduct($tmp_product,$session_data);
                    }
                }
                if(empty($products)){
                    $data_store = new OP_WC_Product_Data_Store_CPT();//WC_Data_Store::load( 'product' );
                    $search_result_total = $this->setting_class->get_option('search_result_total','openpos_pos');
                    $result_number = apply_filters('op_get_online_search_total_result',$search_result_total);

                    $include_variable = apply_filters('search_product_include_variable',false);
                    $ids        = $data_store->search_products( $term, '', $include_variable , false, $result_number );
                   
                    foreach ( $ids as $product_id ) {
                        // if(function_exists('icl_object_id'))
                        // {
                        //     $post_type = get_post_type( $product_id ) ;
                        //     $product_id = icl_object_id( $product_id, $post_type, false, ICL_LANGUAGE_CODE );
                            
                        // }
                        if($product_id)
                        {
                                $product_post = get_post($product_id);
                                if($product_post)
                                {
                                    $tmp_product = $this->woo_class->get_product_formatted_data($product_post,$warehouse_id,false,true);

                                    $allow = $this->warehouse_class->_allowProduct($tmp_product,$warehouse_id);

                                    if($allow)
                                    {
                                        $products[$product_id] = $this->_formatApiProduct($tmp_product,$session_data);
                                    }
                                    
                                }
                        }
                    }
                }
                
                if(!empty($products))
                {
                    $products = array_values($products);
                }
                $result['data']['result']['term'] = $term;
                $result['data']['result']['products'] = $products;
                $result['code'] = 200;
                $result['data']['status'] = 1;
                $result = apply_filters('api_op_get_search_product_result_data',$result,$this->session_data,$term);
            }catch(Exception $e)
            {
                $result['code'] = 400;
                $result['data']['status'] = 0;
                $result['message'] = $e->getMessage();
                
            }
            return $this->rest_ensure_response($result);
        }
        public function scan_product($request)
        {
            $result = array(
                'code' =>'unknown_error',
                'data' => array(
                    'status' => 0,
                    'result' => array(),
                ),
                'message' => ''
            );
            try{
                $term = $request->get_param( 'term' ) ? $request->get_param( 'term' ) : '';
                if(!$term)
                {
                    throw new Exception(__('Please enter barcode to scan','openpos'));
                }
                $session_data = $this->session_data;
                $warehouse_id = isset($session_data['login_warehouse_id']) ? $session_data['login_warehouse_id'] : 0;
                
                $products = array();
                if($term)
                {
                    $product_id_by_barcode = $this->core_class->getProductIdByBarcode($term);
                    if($product_id_by_barcode)
                    {
                        $product_post = get_post($product_id_by_barcode);

                        $tmp_product = $this->woo_class->get_product_formatted_data($product_post,$warehouse_id,false,true);
                        $allow = $this->warehouse_class->_allowProduct($tmp_product,$warehouse_id);
                        if($allow)
                        {
                            $products[] = $this->_formatApiProduct($tmp_product,$session_data);
                        }
                    }
                    if(!empty($products))
                    {
                        $result['data']['result'] = end($products);
                        $result['data']['status'] = 1;
                    }else{
                        $result['data']['status'] = 0;
                        $result['message'] = sprintf(__('Have no product with barcode "%s". Please check again!','openpos'),$term);
                    }
                    $result['code'] = 200;
                    $result = apply_filters('api_op_get_search_product_result_data',$result,$session_data);
                }
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