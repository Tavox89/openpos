<?php
/*
Plugin Name: OpenPOS + FOX - Currency Switcher Professional for WooCommerce
Plugin URI: http://openswatch.com
Description: Integrate "FOX - Currency Switcher Professional for WooCommerce Version: 1.4.1.2" For OpenPOS. https://currency-switcher.com/
Author: anhvnit@gmail.com
Author URI: http://openswatch.com/
Version: 1.0
WC requires at least: 2.6
Text Domain: openpos-dev
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/


add_filter('op_cashdrawer_login_session_data',function ($session_data){
    
    
    $currencies = $session_data['setting']['currencies'];
    $all_currency = get_woocommerce_currencies();
    $all_currency_symbol = get_woocommerce_currency_symbols();
    global $WOOCS;

    if (is_object($WOOCS)) {
        $current_currencies = $WOOCS->get_currencies();
        foreach($current_currencies as $code => $currency)
        {
            $currencies[$code] = array(
                'decimal' => wc_get_price_decimals(),
                'decimal_separator' => wc_get_price_decimal_separator(),
                'thousand_separator' => wc_get_price_thousand_separator(),
                'code' => $code, 
                'symbol' => $currency['symbol'], 
                'rate' => $currency['rate']
            );
        }
       
    }

    $session_data['setting']['currencies'] = $currencies;

    return $session_data;
},301,1);