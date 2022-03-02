<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class ScrtOrder {
  protected $attrs, $customer_attrs, $defaults;

  public function __construct($obj = null) {
    $this->initialize(
      array(
        'id'                => 0,
        'transaction_id'    => null,
        'status'            => self::$pending_str,
        'payment_status'    => self::$pending_str,
        'custom_fields_post_data' => null,
        'custom_fields'     => null,
        'custom_prices'     => null,
        'product_id'        => null,
        'product_name'      => null,
        'page_id'           => null,
        'page_url'          => null,
        'item_name'         => null,
        'plan'              => null,
        'plan_id'           => null,
        'option_id'         => null,
        'invoice_total'     => 0.00,
        'invoice_subtotal'  => 0.00,
        'amount'            => 0.00,
        'main_offer_amt'    => 0.00,
        'pre_tax_amount'    => 0.00,
        'tax_amount'        => 0.00,
        'auto_login'        => null,
        'coupon'            => null,
        'coupon_id'         => null,
        'on_sale'           => 0,
        'accept_terms'      => null,
        'accept_privacy'    => null,
        'consent'           => null,
        'order_log'         => null,
        'order_bumps'       => null,
        'us_parent'         => null,
        'ds_parent'         => null,
        'order_parent'      => null,
        //'order_child'       => array(), // ['id'=>$int, 'type'=>$type]
        'order_type'        => null,
        'subscription_id'   => 0,
      ),
      // shared child order keys
      array(
        'firstname'         => null, // backwards compatibility
        'lastname'          => null, // backwards compatibility
        'first_name'        => null,
        'last_name'         => null,
        'customer_name'     => null,          
        'customer_id'       => null,          
        'email'             => null,
        'phone'             => null,
        'country'           => null,
        'address1'          => null,
        'address2'          => null,
        'city'              => null,
        'state'             => null,
        'zip'               => null,          
        'ip_address'        => null,
        'user_account'      => 0,
        'pay_method'        => 'cod',
        'gateway_mode'      => null,
        'currency'          => 'USD',
        'tax_rate'          => 0.00,
        'tax_desc'          => '',
        'tax_data'          => '',
        'tax_type'          => 'tax',
        'vat_number'        => '',
        'stripe_tax_id'         => '',
      ),
      $obj
    );
  }
    
  public function initialize($defaults, $customer_defaults, $obj=null) {
    $customer_defaults = apply_filters('sc_customer_defaults',$customer_defaults);
    $this->defaults = $defaults;
    $this->attrs = array_merge(array_keys($defaults), array_keys($customer_defaults));      
    $this->customer_attrs = array_keys($customer_defaults);
      
    $keysets = array($defaults,$customer_defaults);
      
    foreach($keysets as $set) {

        if(is_null($obj)) {
          foreach ($set as $key => $value) {
            $this->$key = $value;
          }
        } else if(is_numeric($obj) && $obj > 0) {
          if (get_post_type($obj) != 'sc_order') {
            $this->id = false;
            return;
          }
          $meta = get_post_custom( $obj );
          foreach ($set as $key => $value) {
            if($key == 'id') {
                $this->$key = $obj;
            } else if(isset($meta['_sc_'.$key])) {
                if ($key != 'order_child') {
                    $value = array_shift($meta['_sc_'.$key]);
                    $this->$key = maybe_unserialize($value);
                }             
            } else {
              $this->$key = $value;
            }
          }
        }
    }
  }    

  // Statuses
  public static $pending_str    = 'pending-payment';
  public static $failed_str     = 'failed';
  public static $paid_str       = 'paid';
  public static $completed_str  = 'completed';
  public static $uncollect_str  = 'uncollectible';
  public static $refunded_str   = 'refunded';

  // Static Gateways
  public static $free_gateway_str  = 'free';
  public static $cod_gateway_str   = 'cod';
  public static $stripe_gateway_str = 'stripe';
  public static $paypal_gateway_str = 'paypal';
    
  public static function create($order) {
    // create order
    $post_id = wp_insert_post(array('post_title'=> $order->product_name , 'post_type'=>'sc_order', 'post_status'=>$order->status), FALSE );

    wp_update_post( array( 
        'ID' => $post_id, 
        'post_title' => "#" . $post_id . " " . $order->customer_name
    ),
    false );
      
    $keys = $order->attrs;
    foreach($keys as $key) {
        if(isset($order->$key) && $order->$key){
            update_post_meta( $post_id , '_sc_'.$key , $order->$key );
        }
    }
      
    if($order->order_parent > 0) {
        add_post_meta( $order->order_parent, '_sc_order_child', ['id' => $post_id, 'type' => $order->order_type] );
    }
    
    return $post_id;
  }
    
  public static function update($order) {
    if(get_post_type($order->id) != 'sc_order') {
        return false;
    }
    $keys = $order->attrs;
    foreach($keys as $key) {
        if(isset($order->$key) && $order->$key){
            update_post_meta( $order->id , '_sc_'.$key , $order->$key );
        }
    }
    wp_update_post( array( 'ID'   =>  $order->id, 'post_status'   =>  $order->status ) );
    return $order->id;
  }
    
  public function store() {
    $og_order = new self($this->id);
      
    if($this->subscription_id) {
        $this->check_first_order();
        if(isset($this->renewal)) {
            $this->main_offer_amt = $this->amount;
        }
    }

    if(isset($this->id) && $this->id) {
      $this->id = self::update($this);
      $status = __('Order status updated to '. $this->status, 'ncs-cart');
    } else {
      $this->id = self::create($this);
      $status = __('Creating order.', 'ncs-cart');
    }
      
    if(isset($this->renewal)) {
        update_post_meta($this->id,'_sc_renewal_order', 1);
    }
    
    if( !$og_order->id && $this->id || ($og_order->id && $og_order->status != $this->status) ) {
      sc_log_entry($this->id, $status);
      sc_trigger_integrations($this->status, $this->id);
    }
      
    return $this->id;
  }
    
  private function check_first_order() {
    $sub = new ScrtSubscription($this->subscription_id);
    if ($sub->id && $sub->order_count() >= 1) {
      $first_order = $sub->first_order();
      if ($sub->order_count() == 1 && $first_order && !in_array($first_order->status, ['paid','refunded'])) {
        // first and only transaction was never updated to paid, make this the first order so that new order integrations can run 
        $this->id = $first_order->id;
      }
      if($this->id != $first_order->id){
          $this->renewal = true;
      }
    }
  }
    
  public function load_from_post() {
      
    global $scp, $sc_currency;
      
    $_amount = 0;
    $sc_product_id = intval($_POST['sc_product_id']);
      
    if(!$scp) {
        $scp = sc_setup_product($sc_product_id);
    }
      
    $email = sanitize_email( $_POST['email'] );
    $customer_id = isset($_POST['customerId']) ? sanitize_text_field( $_POST['customerId'] ) : (isset($_POST['customer_id']) ? sanitize_text_field( $_POST['customer_id'] ) : '');
    $first_name = sanitize_text_field( $_POST['first_name'] ); 
    $last_name = sanitize_text_field( $_POST['last_name'] );
    $sc_option_id = sanitize_text_field($_POST['sc_product_option']);
    $phone = (isset($_POST['phone']) && !empty($_POST['phone'])) ? sanitize_text_field( $_POST['phone'] ): null;
    $pay_method = sanitize_text_field( $_POST['pay-method'] );
    $vat_number = sanitize_text_field( $_POST['vat-number']??"" );
    $name = $first_name . ' ' . $last_name;
    $sc_accept_terms = (isset($_POST['sc_accept_terms'])) ? sanitize_text_field(__('accepted', "ncs-cart")) : null;
    $sc_accept_privacy = (isset($_POST['sc_accept_privacy'])) ? sanitize_text_field(__('accepted', "ncs-cart")) : null;      
    $ipaddress = $_SERVER['REMOTE_ADDR']; //client IP
    $curr_user_id = get_current_user_id();
    
    // If user is not logged in, try to get user it by email
    if(!$curr_user_id ){
      $user = get_user_by('email',$email);
     if($user){
        $curr_user_id = $user->ID;
      }
    }
      
    // Apply sale pricing?
    $sale = ( isset($_POST['on-sale']) && sc_is_prod_on_sale() ) ? 1 : 0;
    $plan = studiocart_plan($sc_option_id, $sale);    
      
    $this->firstname         = $first_name; // backwards compatibility
    $this->lastname          = $last_name; // backwards compatibility
    $this->first_name        = $first_name;
    $this->last_name         = $last_name;
    $this->customer_name     = $name;
    $this->customer_id       = $customer_id;
    $this->email             = $email;
    $this->phone             = $phone;
    $this->pay_method        = $pay_method;
    $this->currency          = $sc_currency;
    $this->accept_terms      = $sc_accept_terms;
    $this->accept_privacy    = $sc_accept_privacy;
    $this->ip_address        = $ipaddress;
    $this->user_account      = $curr_user_id;
    $this->on_sale           = $sale;
    $this->item_name         = $plan->name;
    $this->plan              = $plan;
    $this->plan_id           = $plan->stripe_id;
    $this->option_id         = $sc_option_id;
    $this->product_id        = $sc_product_id;
    $this->product_name      = sc_get_public_product_name($sc_product_id);
    $this->vat_number        = $vat_number;
    
    if(isset($_POST['sc-auto-login'])) {
        $this->auto_login = 1;
    }
      
    if(isset($_POST['sc_page_id'])){
        $this->page_id = intval($_POST['sc_page_id']);
        $sc_page_url = $_POST['sc_page_url']??get_permalink($_POST['sc_page_id']);
        $this->page_url = sanitize_text_field($sc_page_url);
    }
      
    if( $scp->show_optin_cb ) {
        $this->consent = (isset($_POST['sc_consent'])) ? 'Yes' : null;
    }

    $address_info = array('address1', 'address2', 'city', 'state', 'zip', 'country');
    foreach ($address_info as $info) {
        if (isset($_POST[$info])){
            $this->$info = sanitize_text_field( $_POST[$info] ); // deprecated
        }
    }

    if($this->plan->price == 'free')
        $_amount = 0;
    elseif(isset($_POST['pwyw_amount']) && !empty($_POST['pwyw_amount']))
        $_amount = $_POST['pwyw_amount'];
    else
        $_amount = $plan->initial_payment;
   
    if ( isset($_POST['coupon_id']) && $_POST['coupon_id'] != '' && ($_amount > 0 || $plan->type == 'recurring')){                    
        $user_info = array(
            'email' => $email,
            'ip' => $ipaddress
        );
        $coupon = sc_get_coupon( sanitize_text_field($_POST['coupon_id']), $sc_product_id, $user_info );
        if ( !isset($coupon['error']) && (!$coupon['plan'] || in_array($sc_option_id, $coupon['plan'])) ) {
            $this->coupon = $coupon;
            $this->coupon_id = $coupon['code'];

            if ($coupon['type'] == 'percent' || $coupon['type'] == 'fixed') {
                $discount = $coupon['amount'];

                if ($coupon['type'] == 'percent') {
                    $discount = $_amount * ($discount / 100);
                    $discount_text = $coupon['amount'].'%';
                } else if ( $_option_type == 'recurring' && !empty($coupon['amount_recurring']) ) {
                    $discount = $coupon['amount_recurring'];
                    $discount_text = sc_format_price($coupon['amount']);
                }

                $this->coupon['discount_amount'] = $discount;
                $this->coupon['description'] = sprintf(__('(%s off)', 'ncs-cart'), $discount_text);

                $_amount -= $discount;

                if($_amount < 0) {
                    $_amount = 0;
                }
            }
        }
    }
      
    $this->main_offer_amt = $_amount;
      
    if (isset($scp->custom_fields)) {
        $NCS_Cart_Public = new NCS_Cart_Public();
        $custom_fields = $NCS_Cart_Public->get_custom_fields_from_post($scp);
        if (!empty($custom_fields)) {
            $this->custom_fields = $custom_fields;
        }

        // Store all custom field data for possible user creation in webhook
        $custom_fields_post = $NCS_Cart_Public->get_custom_fields_post_data($sc_product_id);
        if($custom_fields_post){    
            $this->custom_fields_post_data = $custom_fields_post;
        }

        // process pricing fields
        foreach($scp->custom_fields as $field) {
            $posted = $_POST['sc_custom_fields'];
            if ($field['field_type']=='quantity' && isset($field['qty_price']) && isset($posted[$field['field_id']]) && !empty($posted[$field['field_id']])) {
                $qty = intval($_POST['sc_custom_fields'][$field['field_id']]);
                $qty_price = floatval($field['qty_price']) * $qty;
                $this->custom_prices[$field['field_id']] = sc_format_number($qty_price);
                $_amount += $qty_price;
            }
        }
    }

    if ( isset($_POST['sc-orderbump'])) {
        
        $this->order_bumps = array();
        
        foreach($_POST['sc-orderbump'] as $i=>$v){
            
            $ob_id = intval($_POST['sc-orderbump'][$i]);

            // Process main order bump
            if ( $i == 'main' && isset($scp->order_bump)) {
                
                $bump_plan = isset($scp->ob_type) && $scp->ob_type == 'plan';
                if (intval($scp->ob_product) == $ob_id){

                    // if bump replaces main purchase
                    if(isset($scp->ob_replace) || ($scp->ob_type == 'plan' && $this->plan->type == 'recurring')) {
                        $ob_price = $scp->ob_price ?? '';
                        $this->replaced_product  = $this->product_id;
                        $this->product_id        = $ob_id;
                        $this->product_name      = sc_get_public_product_name($ob_id);
                        $this->on_sale           = 0;
                        $this->coupon            = null;
                        $this->coupon_id         = null;
                        $this->item_name         = __('Order Bump', 'ncs-cart');
                        $this->plan_id           = 'bump';
                        $this->option_id         = 'bump';
                        $this->main_offer_amt    = $ob_price;
                        $this->plan              = (object) array(
                                                        'type'               => 'one-time',
                                                        'option_id'          => $this->option_id,
                                                        'name'               => $this->item_name,
                                                        'price'              => $ob_price,
                                                        'initial_payment'    => $ob_price,
                                                    );
                        
                        $_amount = $ob_price;
                        
                        // is bump recurring?
                        if ($bump_plan) {
                            $bump_plan_id = sanitize_text_field($scp->ob_plan);
                            $plan = studiocart_plan($bump_plan_id, '', $ob_id);                            
                            $this->plan              = $plan;
                            $this->item_name         = $plan->name;
                            $this->plan_id           = $plan->stripe_id;
                            $this->option_id         = $bump_plan_id;
                            $this->main_offer_amt    = $plan->initial_payment;
                            $_amount                 = $plan->initial_payment;
                        }               
                    } else {
                        $this->order_bumps['main'] = array();
                        $this->order_bumps['main']['id'] = $ob_id;
                        $this->order_bumps['main']['name'] = sc_get_public_product_name($ob_id);

                        // is bump recurring?
                        if ($bump_plan) {
                            $bump_plan_id = sanitize_text_field($scp->ob_plan);
                            $plan = studiocart_plan($bump_plan_id, '', $ob_id);                            
                            $this->order_bumps['main']['plan'] =  $plan;
                            $this->order_bumps['main']['amount'] = $plan->initial_payment;
                        } else {
                            $this->order_bumps['main']['amount'] = $scp->ob_price;
                        }

                        $_amount += $this->order_bumps['main']['amount'];
                    }
                }
            }
              
            // process repeater bumps
            if( is_numeric($i) && intval($scp->order_bump_options[$i]['ob_product']) == $ob_id && is_countable($scp->order_bump_options)){
                $this->order_bumps[$i]['id'] = $ob_id;
                $this->order_bumps[$i]['amount'] = $scp->order_bump_options[$i]['ob_price'];
                $this->order_bumps[$i]['name'] = sc_get_public_product_name($ob_id);

                if ($scp->order_bump_options[$i]['ob_type'] == 'plan') { // deprecated
                    $sc_option_id = sanitize_text_field($scp->order_bump_options[$i]['ob_plan']);
                    $plan = studiocart_plan($sc_option_id, '', $ob_id);                            
                    $this->order_bumps[$i]['plan'] =  $plan;
                    $this->order_bumps[$i]['amount'] = $plan->initial_payment;
                    $_amount += $plan->initial_payment;
                } else {
                    $_amount += $scp->order_bump_options[$i]['ob_price'];
                }
            }
        }
    }
            
    if($this->coupon && ($this->coupon['type'] == 'cart-fixed' || $this->coupon['type'] == 'cart-percent')){
        
        $coupon = $this->coupon;

        $discount = $coupon['amount'];

        if ($coupon['type'] == 'cart-percent') {
            $discount = $_amount * ($discount / 100);
            $discount_text = $coupon['amount'].'%';
        }
        

        $this->coupon['discount_amount'] = $discount;
        $this->coupon['description'] = sprintf(__('(%s off)', 'ncs-cart'), $discount_text);

        $_amount -= $discount;
        
        if($_amount < 0) {
            $_amount = 0;
        }
    }
      
    $this->invoice_subtotal = sc_format_number($_amount);

    if($scp->product_taxable){	
        
        $this->tax_data = NCS_Cart_Tax::get_order_tax_data($this);
        $this->tax_rate = $this->tax_data->rate;	
        $this->tax_desc = $this->tax_data->title;
        $this->stripe_tax_id = $this->tax_data->stripe_tax_rate;
              
        if($scp->tax_type=='inclusive_tax'){	
            $this->tax_data->type = 'inclusive';
            $this->tax_amount = $this->tax_rate*$_amount/(100+$this->tax_rate);	
            $this->pre_tax_amount = $_amount - $this->tax_amount;
        } else {	
            $this->tax_data->type = 'exclusive';
            $this->tax_amount = $this->tax_rate*$_amount/100;	
            $this->pre_tax_amount = $_amount;
            $_amount += $this->tax_amount;	
        }	
    }

    $_amount = apply_filters('sc_charge_amount', $_amount, $scp);
    $this->amount = sc_format_number($_amount);
    $this->invoice_total = $this->amount;

    do_action('sc_after_load_from_post',$this,$_POST);
  }    

  public static function get_by_trans_id($transaction_id) {
    $args = array(
        'post_type'  => 'sc_order',
        'post_status' => 'any',
        'posts_per_page' => 1,
        'meta_query' => array(
            array(
                'key' => '_sc_transaction_id',
                'value' => $transaction_id,
            ),
        )
    );
    $posts = get_posts($args);
    if (empty($posts)) {
        return false;
    } else {
        $post_id = $posts[0]->ID;
        return new self($post_id);
    }
  }
    
  public static function child_of($id, $type='upsell') {
    $parent = new self($id);
    $order = new self();
    if ($parent===false) {
        return false;
    }
      
    $product_info = sc_setup_product($parent->product_id);
      
    $keys = $order->customer_attrs;
    foreach($keys as $key) {
        if(isset($parent->$key) && $parent->$key){
            $order->$key = $parent->$key;
        }
    }
    
    if($type == 'upsell') {
        $amount = $product_info->us_price;
        $product_id = $product_info->us_product;
        $prod_type = 'us_prod_type';
        $oto_plan = $product_info->us_plan;
        $parent_key = 'us_parent';
    } else { $type == 'downsell';
        $amount = $product_info->ds_price;
        $product_id = $product_info->ds_product;
        $prod_type = 'ds_prod_type';
        $oto_plan = $product_info->ds_plan;
        $parent_key = 'ds_parent';
    }
      
    $order->main_offer_amt = $amount;
      
    $is_sub = ($product_info->$prod_type == 'plan');
    $plan = studiocart_plan($oto_plan, '', $product_id);
    
    $order->amount          = sc_format_number($amount);
    $order->product_id      = $product_id;
    $order->product_name    = sc_get_public_product_name($product_id);

    $order->$parent_key     = $id;
    $order->order_parent    = $id;
    $order->plan_id         = $type;
    $order->order_type      = $type;
      
    if($is_sub) {
        $order->main_offer_amt = $plan->initial_payment;
        $order->amount      = $plan->initial_payment;
        $order->item_name   = $plan->name;
        $order->plan        = $plan;
        $order->plan_id     = $plan->stripe_id;
        $order->option_id   = $plan->option_id;
    }  
      
    if($product_info->product_taxable){	
        if($product_info->tax_type=='inclusive_tax'){	
            $order->tax_data->type = 'inclusive';
            $order->tax_amount = $order->tax_rate*$order->amount/(100+$order->tax_rate);	
        } else {	
            $order->tax_amount = $order->tax_rate*$order->amount/100;	
            $order->amount += $order->tax_amount;	
        }	
    }
      
    $order->amount = sc_format_number($order->amount);
    return $order;
  }
    
  public function trigger_integrations() {
    $order = $this->get_data();
    sc_trigger_integrations($this->status, $order );
  }
    
  function find_user_id() {
    
    if($this->user_account) {
      return $this->user_account;
    }
      
    if($user_id = email_exists($this->email)) {
      return $user_id;
    }
      
    return false;
}
    
  public function get_data() {
      $data = array();
      $data['ID'] = $this->id;
      $data['date'] = get_the_date('', $this->id);
      
      foreach ($this->attrs as $key) {
        $data[$key] = $this->$key;
      }
      
      $data['invoice_subtotal'] = $data['main_offer_amt'];      
      
      if (!empty($data['order_bumps']) && is_array($data['order_bumps'])) {
        foreach($data['order_bumps'] as $order_bump) {
            $data['invoice_subtotal'] += $order_bump['amount'];
        }
      }
      
      if ($data['coupon']) {
        if (!isset($data['coupon_id']) || !$data['coupon_id']) {
            $data['coupon_id'] = $data['coupon'];
        }
      }
                        
      if ( isset($data['custom_prices']) ) {
        $fields = $data['custom_fields'];
        foreach($data['custom_prices'] as $k=>$v) {
            $custom[$k]['label'] = $fields[$k]['label'];
            $custom[$k]['qty'] = $fields[$k]['value'];
            $custom[$k]['price'] = $v;
        }
        $data['custom_prices'] = $custom;
      }
      
      $data['invoice_total'] = $data['invoice_subtotal'];
      
      if($data['tax_amount']) {
        $data['invoice_tax_amount'] = $data['tax_amount'];      
        if($data['tax_data'] && (!isset($data['tax_data']->type) || $data['tax_data']->type!='inclusive')){
            $data['invoice_total'] += $data['invoice_tax_amount'];
        }
      }
      if ($children = get_post_meta($this->id, '_sc_order_child')) {
        $data['order_child'] = array();
        foreach($children as $child) {
            
          if(is_numeric($child)) {
              $child = array('id' => $child);
          }
            
          $child = new ScrtOrder($child['id']);
          $data['order_child'][] = $child->get_data();
          $data['invoice_subtotal'] += $child->main_offer_amt;
          if($child->tax_data){
            $data['invoice_tax_amount'] += $child->tax_amount;
            if(!isset($child->tax_data->type) || $child->tax_data->type != 'inclusive'){
              $data['invoice_total'] += $child->tax_amount;
            }
          }
        }
      }
      
      return $data;
  }  

} //End class
