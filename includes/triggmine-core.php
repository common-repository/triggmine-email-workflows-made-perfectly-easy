<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

function is_triggmine_enabled() {
    $settings = get_option( 'triggmine_settings' );
    return ( $settings['plugin_enabled'] && !empty( $settings['api_key'] ) && class_exists( 'WooCommerce' ) ) ? true : false;
}

function triggmine_export_enabled() {
    $settings = get_option( 'triggmine_settings' );
    return $settings['order_export_enabled'] ? true : false;
}

function triggmine_customer_export_enabled() {
    $settings = get_option( 'triggmine_settings' );
    return $settings['customer_export_enabled'] ? true : false;
}

function triggmine_get_device_id() {
    $res = isset( $_COOKIE['device_id'] ) ? $_COOKIE['device_id'] : "";
    return $res;
}

function triggmine_get_device_id_1() {
    $res = isset( $_COOKIE['device_id_1'] ) ? $_COOKIE['device_id_1'] : "";
    return $res;
}

function triggmine_is_bot() {
    preg_match( '/bot|curl|spider|google|baidu|facebook|yandex|bing|aol|duckduckgo|teoma|yahoo|twitter^$/i', $_SERVER['HTTP_USER_AGENT'], $matches );
	
	return ( empty( $matches ) ) ? false : true;
}

function triggmine_get_product_image( $prod_id ) {
    $res = "";
    if ( has_post_thumbnail( $prod_id ) ) {
        $attachment_ids[0] = get_post_thumbnail_id( $prod_id );
        $attachment = wp_get_attachment_image_src( $attachment_ids[0], 'full' );
        $res = $attachment[0];
    }
    return $res;
}

function triggmine_get_billing_info( $order_id, $billing_info ) {
    global $wpdb;

    $table = $wpdb->prefix . 'postmeta';
    $sql   = 'SELECT * FROM `' . $table . '` WHERE post_id = ' . $order_id; 

    $postmeta = $wpdb->get_results( $sql );
    foreach ( $postmeta as $pm ) {
        if ( $pm->meta_key == $billing_info ) {
           $res = $pm->meta_value;
        }
    }
        
    return $res;

        // Values you can get
        // _billing_phone
        // _billing_first_name
        // _billing_last_name
        // _billing_email
        // _billing_country
        // _billing_address_1
        // _billing_address_2
        // _billing_postcode
        // _billing_state

        // _customer_ip_address
        // _customer_user_agent

        // _order_currency
        // _order_key
        // _order_total
        // _order_shipping_tax
        // _order_tax

        // _payment_method_title
        // _payment_method

        // _shipping_first_name
        // _shipping_last_name
        // _shipping_postcode
        // _shipping_state
        // _shipping_city
        // _shipping_address_1
        // _shipping_address_2
        // _shipping_company
        // _shipping_country
}

function triggmine_get_variable_product_attributes( $item, $product = null ) {
    // the variable attributes are stored among all item attributes
    // so we loop through item attrs and detect which ones are variable options
    $attributes = "";
    
    if ( $product == null ) {
        $product = new WC_Product_Variable( (int)$item['product_id'] );
    }
    
    $attributes_arr = $product->get_variation_attributes();
    
    foreach ( $item as $key => $value ) {
        if ( array_key_exists( $key, $attributes_arr ) ) {
            $attributes .= ' ' . $value;
        }
    }
    
    return $attributes;
}

function triggmine_get_orders_between_dates( $date_from, $date_to ) {
    global $wpdb;

    $res = $wpdb->get_results( "SELECT * FROM $wpdb->posts 
                WHERE post_type = 'shop_order'
                AND post_date BETWEEN '{$date_from}  00:00:00' AND '{$date_to} 23:59:59'
            ");
            
    return $res;
}

function triggmine_get_customers_between_dates( $date_from, $date_to ) {
    global $wpdb;

    $res = $wpdb->get_results( "SELECT * FROM $wpdb->users 
                WHERE deleted = 0
                AND user_registered BETWEEN '{$date_from}  00:00:00' AND '{$date_to} 23:59:59'
            ");
            
    return $res;
}

function triggmine_api_client( $data, $method, $url = false ) {
    $settings = get_option( 'triggmine_settings' );
    
    // if additional url (e.g. diagnostic) not specified, send to cabinet url from settings
    // if some diagnostic is to be sent to TriggMine, the url will be specified
    if ( $url == false ) {
        $url = $settings['api_url'];
    }
    $token = $settings['api_key'];
    
    if ( $url == "" ) {
        $res = array(
            "status" => 0,
            "body"   => ""
        );
    }
    else {
        $target = "https://" . $url . "/" . $method;

        $data_string = json_encode( $data );
            
        $ch = curl_init();
    
        curl_setopt( $ch, CURLOPT_URL, $target );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );           
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(                  
            'Content-Type: application/json',
            'ApiKey: ' . $token,
            'Content-Length: ' . strlen( $data_string ) )
        );
            
        $res_json = curl_exec ( $ch );
        
        $res = array(
            "status"    => curl_getinfo ($ch, CURLINFO_HTTP_CODE),
            "body"      => $res_json ? json_decode ($res_json, true) : curl_error ($ch)
        );
            
        curl_close ( $ch );
    
        //$res = json_decode( $res_json, true );
    }
        
    return $res;
}

function triggmine_soft_check() {
    global $wp_version;
    
    $wc_version = class_exists( 'WooCommerce' ) ? WC()->version : 'not found';
    $datetime   = date( 'Y-m-d\TH:i:s' );
    $status     = is_triggmine_enabled() ? "1" : "0";
        
    $data = array(
        'dateCreated'       => $datetime,
        'diagnosticType'    => "InstallPlugin",
        'description'       => "Wordpress " . $wp_version . " Woocommerce " . $wc_version . " Plugin " . TRIGGMINE_VERSION,
        'status'            => $status
    );

    return $data;
}

function triggmine_get_diagnostic_info( $diagnostic_type = 'InstallPlugin' ) {
    global $wp_version;
    $settings = get_option( 'triggmine_settings' );
    
    $wc_version = class_exists( 'WooCommerce' ) ? WC()->version : 'not found';
    $datetime   = date( 'Y-m-d\TH:i:s' );
    $remarks    = class_exists( 'WooCommerce' ) ? 'WooCommerce active' : 'WooCommerce not found';

    $data = array(
        'DateCreated'                   => $datetime,
        'DiagnosticType'                => $diagnostic_type,
        'Description'                   => 'Wordpress ' . $wp_version . ' Woocommerce ' . $wc_version . ' Plugin ' . TRIGGMINE_VERSION,
        'Remarks'                       => $remarks,
        'Host'                          => get_site_url(),
        'EmailAdmin'                    => get_option( 'admin_email' ),
        'StatusEnableTriggmine'         => $settings['plugin_enabled'],
        'StatusEnableOrderExport'       => $settings['order_export_enabled'],
        'StatusEnableCustomerExport'    => $settings['customer_export_enabled'],
        'ApiUrl'                        => $settings['api_url'],
        'ApiKey'                        => $settings['api_key'],
        'OrderExportDateFrom'           => $settings['order_export_date_from'],
        'OrderExportDateTo'             => $settings['order_export_date_to'],
        'CustomerExportDateFrom'        => $settings['customer_export_date_from'],
        'CustomerExportDateTo'          => $settings['customer_export_date_to']
    );

    return $data;
}

function triggmine_get_customer_register_data( $user_id ) {
    
    $user = get_userdata( (int)$user_id );
    
    $data = array(
        "device_id"             => triggmine_get_device_id(),
        "device_id_1"           => triggmine_get_device_id_1(),
        "customer_id"           => (string)$user_id,
        "customer_first_name"   => $user->user_firstname ? $user->user_firstname : null,
        "customer_last_name"    => $user->user_lastname  ? $user->user_lastname : null,
        "customer_email"        => $user->user_email,
        "customer_date_created" => $user->user_registered
    );
        
    return $data;
    
}

function triggmine_get_customer_login_data( $username = null, $user_id = null ) {
    
    if ( $username ) {
        $current_user = get_user_by( 'login', $username );
        $user_id = (int)$current_user->ID;
    }
    
    $user = get_userdata( (int)$user_id );
    
    $data = array(
        "device_id"             => triggmine_get_device_id(),
        "device_id_1"           => triggmine_get_device_id_1(),
        "customer_id"           => (string)$user_id,
        "customer_first_name"   => $user->user_firstname ? $user->user_firstname : null,
        "customer_last_name"    => $user->user_lastname  ? $user->user_lastname : null,
        "customer_email"        => $user->user_email,
        "customer_date_created" => $user->user_registered
    );
        
    return $data;
}

function triggmine_get_customer_logout_data() {
    global $current_user;
    
    $customer_data = wp_get_current_user();

    $data = array(
        "device_id"             => triggmine_get_device_id(),
        "device_id_1"           => triggmine_get_device_id_1(),
        "customer_id"           => (string)$customer_data->ID,
        "customer_first_name"   => $current_user->user_firstname ? $current_user->user_firstname : null,
        "customer_last_name"    => $current_user->user_lastname ? $current_user->user_lastname : null,
        "customer_email"        => $current_user->user_email,
        "customer_date_created" => $customer_data->user_registered
    );
        
    return $data;
    
}

function triggmine_page_init() {
    global $product, $current_user;
    
    $useragent = $_SERVER['HTTP_USER_AGENT'];
        
    $product_arr = array();

    $item = $product;
    $id   = $item->id;

    $categories_str   = strip_tags( wc_get_product_category_list( $id ) );
    $categories       = explode( ', ', $categories_str );

    $product_arr = array (
        "product_id"            => $id,
        "product_name"          => get_post( $id )->post_title,
        "product_desc"          => get_post( $id )->post_excerpt,
        "product_sku"           => $item->get_sku(),
        "product_image"         => triggmine_get_product_image( $id ),
        "product_url"           => get_permalink( $id ),
        "product_qty"           => 1,
        "product_price"         => wc_get_price_including_tax( $item ),
        "product_total_val"     => wc_get_price_including_tax( $item ),
        "product_categories"    => $categories
    );
    
    if ( triggmine_get_device_id() && triggmine_get_device_id_1() ) {
        $customer_data      = wp_get_current_user();
        $customer_id        = $customer_data->ID == "" ? null : (string)$customer_data->ID;
        $customer_firstname = $current_user->user_firstname ? $current_user->user_firstname : null;
        $customer_lastname  = $current_user->user_lastname ? $current_user->user_lastname : null;
        $customer_email     = $current_user->user_email ? $current_user->user_email : null;
        $date_created       = $customer_id ? $customer_data->user_registered : null;
        
        $customer = array(
            "device_id"             => triggmine_get_device_id(),
            "device_id_1"           => triggmine_get_device_id_1(),
            "customer_id"           => $customer_id,
            "customer_first_name"   => $customer_firstname,
            "customer_last_name"    => $customer_lastname,
            "customer_email"        => $customer_email,
            "customer_date_created" => $date_created
        );
            
        $products  = array( $product_arr );
            
        $data = array(
          "user_agent"      => $useragent,
          "customer"        => $customer,
          "products"        => $products
        );
    }
    else {
        $data = false;
    }

    return $data;
}

function triggmine_get_cart_data() {
    global $woocommerce, $current_user;
    
    $cart       = $woocommerce->cart;
    $products   = $cart->cart_contents;
    
    $customer_data      = wp_get_current_user();
    $customer_id        = $customer_data->ID == "" ? null : (string)$customer_data->ID;
    $customer_firstname = $current_user->user_firstname ? $current_user->user_firstname : null;
    $customer_lastname  = $current_user->user_lastname ? $current_user->user_lastname : null;
    $customer_email     = $current_user->user_email ? $current_user->user_email : null;
    $date_created       = $customer_id ? $customer_data->user_registered : null;
            
    $customer = array(
        "device_id"             => triggmine_get_device_id(),
        "device_id_1"           => triggmine_get_device_id_1(),
        "customer_id"           => $customer_id,
        "customer_first_name"   => $customer_firstname,
        "customer_last_name"    => $customer_lastname,
        "customer_email"        => $customer_email,
        "customer_date_created" => $date_created
    );

    $data = array(
        'customer'    => $customer,
        'order_id'    => null,
        'price_total' => sprintf( '%01.2f', $cart->subtotal ),
        'qty_total'   => $cart->cart_contents_count,
        'products'    => array()
    );
        
    foreach ( $products as $item ) {
        $attributes = "";
        
        if ( $item['variation'] ) {
            foreach ( $item['variation'] as $attribute ) {
                $attributes .= ' ' . $attribute;
            }
        }
        
        $product = new WC_Product( (int)$item['product_id'] );

        $categories_str   = strip_tags( wc_get_product_category_list( (int)$item['product_id'] ) );
        $categories       = explode( ', ', $categories_str );
        
        $product_id          = (string)$item['product_id'];
        $product_name        = get_post( $product_id )->post_title . $attributes; // to see size and color, triggminetodo - test for ordinary product
        $product_desc        = $item['variation'] ? get_post( $item['data']->get_parent_id() )->post_excerpt : get_post( $product_id )->post_excerpt;
        $product_qty         = $item['quantity'];
        $product_image       = triggmine_get_product_image( $product_id );
        $product_price       = wc_get_price_including_tax( $product );
        $product_total_val   = $item['line_subtotal'];
        $product_categories  = $categories;
                
        $item_data = array();
        $item_data['product_id'] = $product_id;
        $item_data['product_name'] = $product_name;
        $item_data['product_desc'] = $product_desc;
        $item_data['product_sku'] = $product->get_sku();
        $item_data['product_image'] = $product_image;
        $item_data['product_url'] = get_permalink( $product_id );
        $item_data['product_qty'] = round( $product_qty );
        $item_data['product_price'] = $product_price;
        $item_data['product_total_val'] = $product_total_val;
        $item_data['product_categories'] = $product_categories;
                
        $data['products'][] = $item_data;
    }

    return $data;
}

function triggmine_get_order_data( $order_id ) {

    $order    = new WC_Order( $order_id );
    $products = $order->get_items();
    
    $customer_user      = triggmine_get_billing_info( $order_id, '_customer_user' );
    $customer_id        = $customer_user == "" || $customer_user == "0" ? null : (string)$customer_user;
    $customer_firstname = triggmine_get_billing_info( $order_id, '_billing_first_name' );
    $customer_lastname  = triggmine_get_billing_info( $order_id, '_billing_last_name' );
    $customer_email     = triggmine_get_billing_info( $order_id, '_billing_email' );
    $customer_data      = $customer_id ? new WP_User( (int)$customer_id ) : null;
    $date_created       = $customer_data ? $customer_data->user_registered : null;
            
    $customer = array(
        "device_id"             => triggmine_get_device_id(),
        "device_id_1"           => triggmine_get_device_id_1(),
        "customer_id"           => $customer_id,
        "customer_first_name"   => $customer_firstname,
        "customer_last_name"    => $customer_lastname,
        "customer_email"        => $customer_email,
        "customer_date_created" => $date_created
    );
        
    $data = array(
        'customer'    => $customer,
        'order_id'    => $order_id,
        'status'      => $order->get_status(),
        'price_total' => sprintf( '%01.2f', $order->get_subtotal() ),
        'qty_total'   => $order->get_item_count(),
        'products'    => array()
    );

    foreach ( $products as $item ) {
        $attributes = "";
        
        if ( $item['variation_id'] == 0 ) {
            $product = new WC_Product( (int)$item['product_id'] );
        } else {
            $product = new WC_Product_Variable( (int)$item['product_id'] );
            $attributes = triggmine_get_variable_product_attributes( $item, $product );
        }

        $categories_str   = strip_tags( wc_get_product_category_list( (int)$item['product_id'] ) );
        $categories       = explode( ', ', $categories_str );

        $product_id          = $item['product_id'];
        $product_name        = $item['name'] . $attributes; // to see size, color etc.
        $product_desc        = get_post( $product_id )->post_excerpt;
        $product_qty         = $item['qty'];
        $product_image       = triggmine_get_product_image( $product_id );
        $product_price       = wc_get_price_including_tax( $product );
        $product_total_val   = $item['line_subtotal'];
        $product_categories  = $categories;
                
        $item_data = array();
        $item_data['product_id'] = $product_id;
        $item_data['product_name'] = $product_name;
        $item_data['product_desc'] = $product_desc;
        $item_data['product_sku'] = $product->get_sku();
        $item_data['product_image'] = $product_image;
        $item_data['product_url'] = get_permalink( $product_id );
        $item_data['product_qty'] = round( $product_qty );
        $item_data['product_price'] = $product_price;
        $item_data['product_total_val'] = $product_total_val;
        $item_data['product_categories'] = $product_categories;
                
        $data['products'][] = $item_data;
    }
        
    return $data;
}

function triggmine_get_admin_order_data( $order_id ) {
    $order    = new WC_Order( $order_id );
    $products = $order->get_items();
    
    $data = array(
        'order_id'    => $order_id,
        'status'      => $order->get_status(),
        'price_total' => sprintf( '%01.2f', $order->get_subtotal() ),
        'qty_total'   => $order->get_item_count(),
        'products'    => array()
    );

    foreach ( $products as $item ) {
        $attributes = "";
        
        if ( $item['variation_id'] == 0 ) {
            $product = new WC_Product( (int)$item['product_id'] );
        } else {
            $product = new WC_Product_Variable( (int)$item['product_id'] );
            $attributes = triggmine_get_variable_product_attributes( $item, $product );
        }

        $categories_str   = strip_tags( wc_get_product_category_list( (int)$item['product_id'] ) );
        $categories       = explode( ', ', $categories_str );

        $product_id          = $item['product_id'];
        $product_name        = $item['name'] . $attributes; // to see size, color etc.
        $product_desc        = get_post( $product_id )->post_excerpt;
        $product_qty         = $item['qty'];
        $product_image       = triggmine_get_product_image( $product_id );
        $product_price       = wc_get_price_including_tax( $product );
        $product_total_val   = $item['line_subtotal'];
        $product_categories  = $categories;
                
        $item_data = array();
        $item_data['product_id'] = $product_id;
        $item_data['product_name'] = $product_name;
        $item_data['product_desc'] = $product_desc;
        $item_data['product_sku'] = $product->get_sku();
        $item_data['product_image'] = $product_image;
        $item_data['product_url'] = get_permalink( $product_id );
        $item_data['product_qty'] = round( $product_qty );
        $item_data['product_price'] = $product_price;
        $item_data['product_total_val'] = $product_total_val;
        $item_data['product_categories'] = $product_categories;
                
        $data['products'][] = $item_data;
    }
        
    return $data;
}

function triggmine_get_order_history() {
    $settings = get_option( 'triggmine_settings' );
    
    $date_from = $settings['order_export_date_from'];
    $date_to   = $settings['order_export_date_to'];
    
    $orders = triggmine_get_orders_between_dates( $date_from, $date_to );
    
    foreach ( $orders as $order_item ) {

        $order_id = (int)$order_item->ID;
        $order    = new WC_Order( $order_id );
        $products = $order->get_items();

        $customer_user      = triggmine_get_billing_info( $order_id, '_customer_user' );
        $customer_id        = $customer_user == "" || $customer_user == "0" ? null : (string)$customer_user;
        $customer_firstname = triggmine_get_billing_info( $order_id, '_billing_first_name' );
        $customer_lastname  = triggmine_get_billing_info( $order_id, '_billing_last_name' );
        $customer_email     = triggmine_get_billing_info( $order_id, '_billing_email' );
        $customer_data      = $customer_id ? new WP_User( (int)$customer_id ) : null;
        $date_created       = $customer_data ? $customer_data->user_registered : null;
    
        $customer = array(
            'customer_id'           => $customer_id,
            'customer_first_name'   => $customer_firstname,
            'customer_last_name'    => $customer_lastname,
            'customer_email'        => $customer_email,
            'customer_date_created' => $date_created
        );
    
        $orders_export = array(
            'customer'      => $customer,
            'order_id'      => $order_id,
            'date_created'  => $order_item->post_date,
            'status'        => $order->get_status(),
            'price_total'   => sprintf( '%01.2f', $order->get_subtotal() ),
            'qty_total'     => $order->get_item_count(),
            'products'      => array()
        );
   
        foreach ( $products as $item ) {
            $attributes = "";
            
            if ( $item['variation_id'] == 0 ) {
                $product = new WC_Product( (int)$item['product_id'] );
            } else {
                $product = new WC_Product_Variable( (int)$item['product_id'] );
                $attributes = triggmine_get_variable_product_attributes( $item, $product );
            }
    
            $categories_str   = strip_tags( wc_get_product_category_list( (int)$item['product_id'] ) );
            $categories       = explode( ', ', $categories_str );
    
            $product_id          = $item['product_id'];
            $product_name        = $item['name'] . $attributes; // to see size, color etc.
            $product_desc        = get_post( $product_id )->post_excerpt;
            $product_qty         = $item['qty'];
            $product_image       = triggmine_get_product_image( $product_id );
            $product_price       = $product->get_price_including_tax();
            $product_total_val   = $item['line_subtotal'];
            $product_categories  = $categories;
    
            $item_data = array();
            $item_data['product_id'] = $product_id;
            $item_data['product_name'] = $product_name;
            $item_data['product_desc'] = $product_desc;
            $item_data['product_sku'] = $product->get_sku();
            $item_data['product_image'] = $product_image;
            $item_data['product_url'] = get_permalink( $product_id );
            $item_data['product_qty'] = round( $product_qty );
            $item_data['product_price'] = $product_price;
            $item_data['product_total_val'] = $product_total_val;
            $item_data['product_categories'] = $product_categories;
     
            $orders_export['products'][] = $item_data;
        }
     
        $data_export['orders'][] = $orders_export;
    }
        
    return $data_export;
}

function triggmine_get_customer_history() {
    $settings = get_option( 'triggmine_settings' );
    
    $date_from = $settings['customer_export_date_from'];
    $date_to   = $settings['customer_export_date_to'];
    
    $customers = triggmine_get_customers_between_dates( $date_from, $date_to );
    
    foreach ( $customers as $customer ) {
        $user = get_userdata((int)$customer->ID);

        $customer_data = array(
            'customer_id'              => $customer->ID,
            'customer_first_name'      => $user->first_name ? $user->first_name : null,
            'customer_last_name'       => $user->last_name ? $user->last_name : null,
            'customer_email'           => $user->user_email,
            'customer_date_created'    => $user->user_registered,
            'customer_last_login_date' => $user->last_login ? $user->last_login : null
          );
        
        $data_export['prospects'][] = $customer_data;
    }
    
    return $data_export;
}

function triggmine_build_price_item( $product )
{
    $priceId = "";
    $priceValue = $product->get_sale_price();
    $priceActiveFrom = $product->get_date_on_sale_from() ? $product->get_date_on_sale_from()->date( 'Y-m-d' ) : "";
    $priceActiveTo = $product->get_date_on_sale_from() ? $product->get_date_on_sale_to()->date( 'Y-m-d' ) : "";
    $priceCustomerGroup = "";
    $priceQty = "";

    $productPrice = array(
                'price_id'             => $priceId,
                'price_value'          => $priceValue,
                'price_priority'       => "",
                'price_active_from'    => $priceActiveFrom,
                'price_active_to'      => $priceActiveTo,
                'price_customer_group' => $priceCustomerGroup,
                'price_quantity'       => $priceQty
            );
        
    return $productPrice;
}

function triggmine_get_product_edit_data( $post_id ) {
    $dataExport = array();
    
    $product = wc_get_product( $post_id );
            
    $productPrices = array();
    if ( $product->get_sale_price() ) {
        $productPrice = triggmine_build_price_item( $product );
        $productPrices[] = $productPrice;
    }
            
    $productRelations = array();
    if ( $product->get_upsell_ids() ) {
        $upsellIds = $product->get_upsell_ids();
        foreach ( $upsellIds as $upsellId ) {
            $productRelations[] = array( 'relation_product_id' => $upsellId );
        }
    }
    if ( $product->get_cross_sell_ids() ) {
        $crosssellIds = $product->get_cross_sell_ids();
        foreach ( $crosssellIds as $crosssellId ) {
            $productRelations[] = array( 'relation_product_id' => $crosssellId );
        }
    }
            
    $productCategories = array();
    $categoriesIds    = $product->get_category_ids();
    foreach ( $categoriesIds as $catId ) {
        $term = get_term_by( 'id', $catId, 'product_cat' );
        $productCategories[] = array(
                'product_category_type' => array(
                        'category_id'   => $catId,
                        'category_name' => $term->name,
                    )
            );
    }
            
    $dataProduct = array (
        'product_id'               => $post_id,
        'parent_id'                => $product->get_parent_id() ? $product->get_parent_id() : "",
        'product_name'             => $product->get_name(),
        'product_desc'             => $product->get_short_description(),
        'product_create_date'      => $product->get_date_created() ? $product->get_date_created()->date( 'Y-m-d' ) : "",
        'product_sku'              => $product->get_sku(),
        'product_image'            => triggmine_get_product_image( $post_id ),
        'product_url'              => get_permalink( $post_id ),
        'product_qty'              => $product->get_stock_quantity() ? $product->get_stock_quantity() : 0,
        'product_default_price'    => $product->get_regular_price(),
        'product_prices'           => $productPrices,
        'product_categories'       => $productCategories,
        'product_relations'        => $productRelations,
        'product_is_removed'       => "",
        'product_is_active'        => $product->get_status() == 'publish' ? true : "",
        'product_active_from'      => "",
        'product_active_to'        => "",
        'product_show_as_new_from' => "",
        'product_show_as_new_to'   => ""
    );
    
    $dataExport['products'][] = $dataProduct;
            
    return $dataExport;
}