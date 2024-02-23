<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class ScrtOrder extends stdClass {
  protected $attrs, $customer_attrs, $defaults;

  public function __construct($obj = null) {
    $this->initialize(
      array(
        'id'                => 0,
        'date'              => '',
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
        'purchase_note'     => null,
        'order_log'         => null,
        'order_bumps'       => null,
        'us_parent'         => null,
        'ds_parent'         => null,
        'us_offer'          => null,
        'order_parent'      => null,
        'refund_log'        => null,
        //'order_child'       => array(), // ['id'=>$int, 'type'=>$type]
        'order_type'        => null,
        'subscription_id'   => 0,
        'quantity'          => 1,
        'shipping_amount'   => 0.00,
        'shipping_tax'      => 0.00,
      ),
      // shared child order keys
      array(
        'firstname'         => null, // backwards compatibility
        'lastname'          => null, // backwards compatibility
        'first_name'        => null,
        'last_name'         => null,
        'customer_name'     => null,          
        'customer_id'       => null,          
        'company'           => null,
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
        'stripe_tax_id'     => '',
        'invoice_number'    => null,
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

        if(is_numeric($obj) && $obj > 0) {
          if (get_post_type($obj) != 'sc_order') {
            $this->id = false;
            return;
          }
          $meta = get_post_custom( $obj );
          foreach ($set as $key => $value) {
            if($key == 'id') {
                $this->$key = $obj;
            } else if($key == 'date') {
                $this->$key = get_the_date('', $this->id);
            } else if(isset($meta['_sc_'.$key])) {
                if ($key != 'order_child') {
                    $value = array_shift($meta['_sc_'.$key]);
                    $this->$key = maybe_unserialize($value);
                }             
            } else {
              $this->$key = $value;
            }
          }
        } else {
          foreach ($set as $key => $value) {
            $this->$key = $value;
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

  public function setup_atts_from_post() {
      
    global $scp, $sc_currency;
      
    $this->product_id = intval($_POST['sc_product_id']);
    $this->email = (isset($_POST['email'])) ? sanitize_email( $_POST['email'] ) : null;

    $scp = ($scp) ? $scp : sc_setup_product($this->product_id);
    $curr_user_id = get_current_user_id();
      
    // If user is not logged in, try to get user it by email
    if(!$curr_user_id ){
      $user = get_user_by('email', $this->email);
      if($user){
        $curr_user_id = $user->ID;
      }
    }
      
    $this->first_name        = sanitize_text_field( $_POST['first_name'] ?? '');
    $this->last_name         = sanitize_text_field( $_POST['last_name'] ?? '');
    $this->firstname         = $this->first_name; // backwards compatibility
    $this->lastname          = $this->last_name; // backwards compatibility
    $this->customer_name     = $this->first_name. ' ' . $this->last_name;
    $this->customer_id       = sanitize_text_field( $_POST['customerId'] ?? '' );
    $this->phone             = sanitize_text_field( $_POST['phone'] ?? '');
    $this->company           = sanitize_text_field( $_POST['company'] ?? '');
    $this->pay_method        = sanitize_text_field( $_POST['pay-method'] ?? '');
    $this->currency          = $sc_currency;
    $this->accept_terms      = (isset($_POST['sc_accept_terms'])) ? sanitize_text_field(__('accepted', "ncs-cart")) : null;
    $this->accept_privacy    = (isset($_POST['sc_accept_privacy'])) ? sanitize_text_field(__('accepted', "ncs-cart")) : null;
    $this->ip_address        = $_SERVER['REMOTE_ADDR'];
    $this->user_account      = $curr_user_id;
    $this->on_sale           = ( isset($_POST['on-sale']) && sc_is_prod_on_sale() ) ? 1 : 0;
    $this->option_id         = sanitize_text_field($_POST['sc_product_option'] ?? '');
    $this->plan              = apply_filters('sc_plan_at_checkout', studiocart_plan($this->option_id, $this->on_sale), $this->product_id);
    $this->plan_id           = $this->plan->stripe_id;
    $this->item_name         = $this->plan->name;
    $this->product_name      = sc_get_public_product_name($this->product_id);
    $this->vat_number        = sanitize_text_field( $_POST['vat-number'] ?? "" );
    $this->purchase_note     = $scp->purchase_note ?? null;
    $this->quantity          = intval($_POST['sc_qty'] ?? 1);
    $this->main_offer_amt    = 0;
    
    if(isset($_POST['sc-auto-login'])) {
        $this->auto_login = 1;
    }
      
    if(isset($_POST['sc_page_id'])){
        $this->page_id = intval($_POST['sc_page_id']);
        $this->page_url = sanitize_text_field($_POST['sc_page_url']??get_permalink($_POST['sc_page_id']));
    }
      
    if( $scp->show_optin_cb ) {
        $this->consent = (isset($_POST['sc_consent'])) ? 'Yes' : null;
    }

    $address_info = array('address1', 'address2', 'city', 'state', 'zip', 'country');
    foreach ($address_info as $info) {
        if (isset($_POST[$info])){
            $this->$info = sanitize_text_field( $_POST[$info] );
        }
    }

    $this->load_coupon_from_post();
  }

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
      
    $order->id = $post_id;

    self::store_items($order);
    do_action( 'studiocart_order_created', $order ); 
    
    return $post_id;
  }
    
  public static function update($order) {

    $keys = $order->attrs;
    foreach($keys as $key) {
      if($key == 'order_log'){
        continue;
      } else if(isset($order->$key) && $order->$key){
        update_post_meta( $order->id , '_sc_'.$key , $order->$key );
      } else {
        delete_post_meta( $order->id , '_sc_'.$key );
      }
    }

    wp_update_post( array( 'ID'   =>  $order->id, 'post_status'   =>  $order->status ) );
    self::store_items($order);
    do_action( 'studiocart_order_updated', $order ); 
    
    return $order->id;
  }

  public function output_invoice($download) {
    $this->get_invoice($stream=true, $download);          
    //echo $this->get_invoice();          
  }

  public function get_invoice($stream=false, $download=false) {
    $order = $this;
    $file = plugin_dir_path( __FILE__ ) . '../public/partials/invoice-pdf.php';
    if (!$stream) {
      return require_once($file);
    } else {
      require_once($file);
    }
  }

  public function set_invoice_number(){
    if(get_option('_sc_enable_invoice_number',false)){
      $invoice_number = get_option('_sc_invoice_start_number',1);
      $invoice_format = get_option('_sc_invoice_format',"sc_pns");
      $invoice_sufix = get_option('_sc_invoice_sufix',"");
      $invoice_prefix = get_option('_sc_invoice_prefix',"");
      $order_number_length = (int)get_option('_sc_invoice_length',0);
      if ( $order_number_length) {
        $invoice_number = sprintf( "%0{$order_number_length}d", $invoice_number );
      }

      switch($invoice_format){
        case "sc_ns":
          $formatted = $invoice_number.$invoice_sufix;
          break;
        case "sc_pn":
          $formatted = $invoice_prefix.$invoice_number;
          break;
        case "sc_n":
          $formatted = $invoice_number;
          break;
        default:
          $formatted = $invoice_prefix.$invoice_number.$invoice_sufix;
          break;
      }
      $replacements = array(
        '{D}'    => date_i18n( 'j' ),
        '{DD}'   => date_i18n( 'd' ),
        '{M}'    => date_i18n( 'n' ),
        '{MM}'   => date_i18n( 'm' ),
        '{YY}'   => date_i18n( 'y' ),
        '{YYYY}' => date_i18n( 'Y' ),
        '{H}'    => date_i18n( 'G' ),
        '{HH}'   => date_i18n( 'H' ),
        '{N}'    => date_i18n( 'i' ),
        '{S}'    => date_i18n( 's' ),
      );
  
      // Return $replacements as case insensitive.
      $formatted_order_number = str_ireplace( array_keys( $replacements ), $replacements, $formatted );
      $this->invoice_number = $formatted_order_number;
      $invoice_number++;
      update_option('_sc_invoice_start_number',$invoice_number);
    }
  }
  public function store() {

    global $sc_debug_logger;

    $bt = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($bt);

    $sc_debug_logger->log_debug("ScrtOrder->store() called id: {$this->id}, status: {$this->status}", 0); 
    $sc_debug_logger->log_debug("File: {$caller['file']}:: {$caller['line']}", 0); 

    $og_order = new self($this->id);
    $sc_debug_logger->log_debug("ScrtOrder copy set id: {$og_order->id}, status: {$og_order->status}", 0); 

    if($this->subscription_id) {
        $this->check_first_order();
        if(isset($this->renewal)) {
          $this->main_offer_amt = $this->amount;
        }
    }

    if(isset($this->id) && $this->id) {
      $this->id = self::update($this);
      $status = __('Order status updated from ' .$og_order->status. ' to '. $this->status, 'ncs-cart');
      $sc_debug_logger->log_debug("updating ScrtOrder {$this->id}", 0); 
    } else {
      if(!$this->invoice_number){
        $this->set_invoice_number();
      }
      $this->id = self::create($this);
      $status = __('Creating order.', 'ncs-cart');
      $sc_debug_logger->log_debug("saved ScrtOrder, new id: {$this->id}", 0); 
    }
      
    if(isset($this->renewal)) {
        update_post_meta($this->id,'_sc_renewal_order', 1);
    }
    
    if( !$og_order->id && $this->id || ($og_order->id && $og_order->status != $this->status) ) {
      
      $sc_debug_logger->log_debug("triggering {$this->status} integrations for ScrtOrder {$this->id}", 0); 

      sc_log_entry($this->id, $status);
      sc_trigger_integrations($this->status, $this->id);

    }

    $sc_debug_logger->log_debug("ScrtOrder->store() finished {$this->id}", 0); 
      
    return $this->id;
  }
    
  public static function log($id, $status) {
      sc_log_entry($id, $status);
  }
    
  function check_first_order() {
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

    $this->setup_atts_from_post(); 
    $this->add_main_item_from_post();

    if ($this->coupon) {
        $args = $this->apply_plan_coupon_to_items();
    }

    if (isset($scp->custom_fields)) {
        $this->add_line_item_from_post();
    }

    if ( isset($_POST['sc-orderbump'])) {
        $this->order_bumps = array();
        $this->add_bump_items_from_post($_POST['sc-orderbump']);
    }

    if ($this->coupon) {
        $args = $this->apply_cart_coupon_to_items();
    }

    $this->calculate_pre_tax_amount_from_items();
    $this->calculate_tax_amount_from_items();
    $this->calculate_total_amount_from_items();

    $this->invoice_subtotal = $this->pre_tax_amount;

    $this->amount = apply_filters('sc_charge_amount', $this->amount, $scp);
    $this->non_formatted_amount = $this->amount;
    $this->amount = $this->amount;
    $this->invoice_total = $this->amount;

    // setup order_summary_items for checkout form
    $this->calculate_final_amounts();

    // end order setup   
    do_action('sc_after_load_from_post',$this,$_POST);
  }

  public function calculate_final_amounts() {

    $this->order_summary_items = [];

    foreach($this->items as $item) {

        if($item['item_type'] != 'bundled') {
          $this->order_summary_items[] = array(
              'name'          => apply_filters('sc_order_summary_item_name', $item['product_name'], $item),
              'total_amount'  => (float) $item['subtotal'], 
              'subtotal'      => (float) $item['subtotal'], 
              'type'          => $item['item_type'],
              'quantity'      => $item['quantity'],
          );
        }
    }

    if ($this->coupon) {
        if (!$this->coupon['discount_amount']) {
            $this->coupon = null;
            $this->coupon_id = null;
        } else {
            $this->order_summary_items[] = array(
                'name'          => apply_filters('sc_order_summary_coupon_text', sprintf(__('Coupon %s', 'ncs-cart'), '<span class="sc-badge">'. $this->coupon_id .'</span>'), $this->coupon),
                'total_amount'  => $this->coupon['discount_amount'], 
                'subtotal'      => $this->coupon['discount_amount'], 
                'type'          => 'discount'
            );
        }
    }

    if ($this->tax_desc) {
      $title = $this->tax_desc;
      if($this->tax_data->type == 'inclusive') {
          if(!isset($this->tax_data->redeem_vat) || !$this->tax_data->redeem_vat){
            $title .= ' (' . __('Included in price', "ncs-cart") . ')'; 
          } else if(isset($this->tax_data->redeem_vat)) {
            unset($this->tax_data->redeem_vat);
          }
      }

      $this->order_summary_items[] = array(
          'name'          => $title,
          'total_amount'  => $this->tax_amount, 
          'subtotal'      => $this->tax_amount, 
          'type'          => 'tax'
      );
    }
  }

  
  public function add_main_item_from_post() {
        
    $product_name   = $this->product_name;
    $product_id     = $this->product_id;
    $price_id       = $this->option_id;
    $price_name     = $this->plan->name;
    $quantity       = $this->quantity;

    if($this->plan->type == 'pwyw' && isset($_POST['pwyw_amount'][$this->option_id]) && $_POST['pwyw_amount'][$this->option_id] >= $this->plan->initial_payment ) {
        $this->plan->initial_payment = (float) $_POST['pwyw_amount'][$this->option_id];
    }
    
    $unit_price     = $this->plan->initial_payment;
    $total          = $this->plan->initial_payment;

    if ($total > 0 && $this->quantity > 1) {
        $total *= $this->quantity;
    }

    $args = array(
        'product_id'     => $product_id,
        'price_id'       => $price_id,
        'product_name'   => $product_name,
        'price_name'     => $price_name,
        'unit_price'     => $unit_price,
        'item_type'      => 'main',
        'quantity'       => $quantity,
        'subtotal'       => $total,
        'total_amount'   => $total,
    );

    if (!empty($this->plan->trial_days)) {
        $args['trial_days'] = $this->plan->trial_days;
    }

    if (!empty($this->plan->fee)) {
        $args['sign_up_fee'] = $this->plan->fee;
    }

    if ($this->purchase_note) {
        $args['purchase_note'] = $this->purchase_note;
    }

    $this->main_offer_amt += $total;
    $this->add_item($args);
  }

  public function add_line_item_from_post() {
    global $scp, $studiocart;

    $custom_fields = $studiocart->get_custom_fields_from_post($scp);
    if (!empty($custom_fields)) {
        $this->custom_fields = $custom_fields;
    }

    // Store all custom field data for possible user creation in webhook
    $custom_fields_post = $studiocart->get_custom_fields_post_data($scp->ID);
    if($custom_fields_post){    
        $this->custom_fields_post_data = $custom_fields_post;
    }

    // process pricing fields
    foreach($scp->custom_fields as $field) {
      $posted = $_POST['sc_custom_fields'] ?? array();
      if ($field['field_type']=='quantity' && isset($field['qty_price']) && isset($posted[$field['field_id']]) && !empty($posted[$field['field_id']])) {
        $qty = intval($_POST['sc_custom_fields'][$field['field_id']]);
        $qty_price = $field['qty_price'] * $qty;
        $this->custom_prices[$field['field_id']] = $qty_price;

        $this->add_item([
            'product_id'     => $this->product_id,
            'price_id'       => $field['field_id'],
            'item_type'      => 'line item',
            'product_name'   => $this->product_name,
            'price_name'     => $field['field_name'],
            'unit_price'     => $field['qty_price'],
            'quantity'       => $qty,
            'subtotal'       => $qty_price,
            'total_amount'   => $qty_price,
        ]);
      }
    }
  }

  public function load_coupon_from_post() {   
    if ( isset($_POST['coupon_id']) && $_POST['coupon_id'] != '' ) {
        $user_info = array(
            'email' => $this->email,
            'ip' => $this->ip_address
        );
        $coupon = sc_get_coupon( sanitize_text_field($_POST['coupon_id']), $this->product_id, $user_info );
        if ( !isset($coupon['error']) && (!$coupon['plan'] || in_array($this->option_id, $coupon['plan'])) ) {
            
            $this->coupon_id = sanitize_text_field($_POST['coupon_id']);
            $this->coupon = $coupon;

            return true;
        }
    }
    return false;
  }

  public function apply_plan_coupon_to_items() {

    if ($this->coupon['type'] == 'percent' || $this->coupon['type'] == 'fixed') {
        foreach($this->items as $k=>$item) {

            if ($item['item_type'] != 'main') {
                continue;
            }
                    
            $discount = $this->coupon['amount'];

            if ($this->coupon['type'] == 'percent') {
                $discount = $item['total_amount'] * ($discount / 100);
                $discount_text = $this->coupon['amount'].'%';
            } else if ( $this->plan->type == 'recurring' && !empty($this->coupon['amount_recurring']) ) {
                $discount = $this->coupon['amount_recurring'];
                $discount_text = sc_format_price($this->coupon['amount']);
            }
                    
            $this->coupon['discount_amount'] = $discount * $this->quantity;
            $item['discount_amount'] = $this->coupon['discount_amount'];

            if ( $this->plan->type != 'recurring' || (!isset($this->plan->trial_days) || !$this->plan->trial_days) ) {
                $item['total_amount'] = $item['subtotal'] - $discount;
            }

            if($item['total_amount'] < 0) {
                $item['total_amount'] = 0;
            }

            $this->main_offer_amt = $item['total_amount'];

            $this->items[$k] = $this->maybe_apply_tax_to_item($item);

            // Apply only to THE (1) main offer 
            break;
        }
    }
  }
  
  public function add_bump_items_from_post($bumps) {
        
    global $scp;

    foreach($bumps as $i=>$bump) {
            
        $ob_id = intval($_POST['sc-orderbump'][$i]);
        $price_name = __('Order Bump', 'ncs-cart');
        $type = $price_id = 'bump';
        $purchase_note = get_post_meta($ob_id , '_sc_purchase_note', true);

        // Process main order bump
        if ( $i == 'main' && isset($scp->order_bump) && intval($scp->ob_product) == $ob_id){
            
            $bump_plan = isset($scp->ob_type);

            if ($bump_plan) {
                $bump_plan_id = sanitize_text_field($scp->ob_plan);
                $plan = apply_filters('sc_plan_at_checkout', studiocart_plan($bump_plan_id, '', $ob_id), $ob_id);
                $price_id = $bump_plan_id;
            }

            // Process bump replace
            if(isset($scp->ob_replace) || ($bump_plan && $this->plan->type == 'recurring' && $plan->type == $this->plan->type)) {
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
                $this->purchase_note     = $purchase_note;
                $this->plan              = (object) array(
                                                'type'               => 'one-time',
                                                'option_id'          => $this->option_id,
                                                'name'               => $this->item_name,
                                                'price'              => $ob_price,
                                                'initial_payment'    => $ob_price,
                                            );

                //remove "main" items
                if($this->items) {
                    $items = array();
                    foreach($this->$items as $item) {
                        if($item['item_type'] != 'main') {
                            $items[] = $item; 
                        }
                    }
                    $this->items = $items;
                }
                
                $bump_amount = $ob_price;
                $type = 'main';
                    
                // is bump recurring?
                if ($plan->type == 'recurring') {                            
                    $this->plan              = $plan;
                    $this->item_name         = $plan->name;
                    $this->plan_id           = $plan->stripe_id;
                    $this->option_id         = $bump_plan_id;
                    $this->main_offer_amt    = $plan->initial_payment;
                    $bump_amount             = $plan->initial_payment;
                    $price_name = $this->item_name;
                }                     
                
            } else {
                $this->order_bumps['main'] = array();
                $this->order_bumps['main']['id'] = $ob_id;
                $this->order_bumps['main']['name'] = sc_get_public_product_name($ob_id);
                $this->order_bumps['main']['amount'] = $scp->ob_price;
                $this->order_bumps['main']['purchase_note'] = $purchase_note;
                
                // is bump recurring?
                if ($bump_plan) {
                    $this->order_bumps['main']['plan'] =  $plan;
                    $this->order_bumps['main']['amount'] = $plan->initial_payment;
                    $price_name = $plan->name;
                }

                $bump_amount = $this->order_bumps['main']['amount'];
            }

            $args = [
                'product_id'     => $ob_id,
                'price_id'       => $price_id,
                'item_type'      => $type,
                'product_name'   => sc_get_public_product_name($ob_id),
                'price_name'     => $price_name,
                'unit_price'     => $bump_amount,
                'quantity'       => 1,
                'subtotal'       => $bump_amount,
                'total_amount'   => $bump_amount,
            ];

            if($bump_plan && !empty($plan->trial_days)){
                $args['trial_days'] = $plan->trial_days;
            }

            if($bump_plan && !empty($plan->fee)){
                $args['sign_up_fee'] = $plan->fee;
            }

            if($purchase_note) {
                $args['purchase_note'] = $purchase_note;
            }

            $this->add_item($args);
        }
            
        // process repeater bumps
        else if( is_numeric($i) && intval($scp->order_bump_options[$i]['ob_product']) == $ob_id && is_countable($scp->order_bump_options)){
            $this->order_bumps[$i]['id'] = $ob_id;
            $this->order_bumps[$i]['amount'] = $scp->order_bump_options[$i]['ob_price'];
            $this->order_bumps[$i]['name'] = sc_get_public_product_name($ob_id);
            $this->order_bumps[$i]['purchase_note'] = $purchase_note;

            if ($scp->order_bump_options[$i]['ob_type'] == 'plan') {
                $price_id = sanitize_text_field($scp->order_bump_options[$i]['ob_plan']);
                $plan = studiocart_plan($price_id, '', $ob_id);     
                $price_name = $plan->name;                       
                $this->order_bumps[$i]['plan'] =  $plan;
                $this->order_bumps[$i]['amount'] = $plan->initial_payment;
                $bump_amount = $this->order_bumps[$i]['amount'];
            } else {
                $bump_amount = $scp->order_bump_options[$i]['ob_price'];
            }

            $args = [
                'product_id'     => $this->order_bumps[$i]['id'],
                'price_id'       => $price_id,
                'item_type'      => 'bump',
                'product_name'   => $this->order_bumps[$i]['name'],
                'price_name'     => $price_name,
                'unit_price'     => $bump_amount,
                'quantity'       => 1,
                'subtotal'       => $bump_amount,
                'total_amount'   => $bump_amount,
            ];

            if(isset($plan) && !empty($plan->trial_days)){
                $args['trial_days'] = $plan->trial_days;
            }

            if(isset($plan) && !empty($plan->fee)){
                $args['sign_up_fee'] = $plan->fee;
            }

            $this->add_item($args);
        }
    }
  }

  public function calculate_pre_tax_amount_from_items() {
    $amount = 0;
    $count = 0;
    foreach($this->items as $item) {
        $amount += (float) $item['subtotal'];
        $count++;
    }
    $this->pre_tax_amount = $amount;
    return $count;
  }

  public function calculate_total_amount_from_items() {
    $amount = 0;
    $count = 0;
    foreach($this->items as $item) {
        $amount += (float) $item['total_amount'];
        $count++;
    }
    $this->amount = $amount;
    return $count;
  }

  public function calculate_tax_amount_from_items() {
    $amount = 0;
    $count = 0;
    foreach($this->items as $item) {
        if(!isset($item['tax_amount']) || !$item['tax_amount']) {
            continue;
        }
        $amount +=  (float) $item['tax_amount'];
        $count++;
    }
    $this->tax_amount = $amount;
    return $count;
  }
  
  public function apply_cart_coupon_to_items() {      
      if($this->coupon && ($this->coupon['type'] == 'cart-fixed' || $this->coupon['type'] == 'cart-percent')){
          
          $coupon = $this->coupon;
          $item_count = $this->calculate_pre_tax_amount_from_items();
  
          $discount = $coupon['amount'];
  
          if ($coupon['type'] == 'cart-percent') {
              $discount = $this->pre_tax_amount * ($discount / 100);
              $discount_text = $coupon['amount'].'%';
          }
  
          $this->coupon['discount_amount'] = $discount;
          $this->coupon['description'] = $coupon['description'] ?? sprintf(__('(%s off)', 'ncs-cart'), $discount_text);
  
          // Handle 100% off
          if($discount >= $this->pre_tax_amount) {
            $discount = $this->pre_tax_amount;
              foreach($this->items as $k=>$item) {
                  $item['discount_amount'] = $item['subtotal'];
                  $item['total_amount'] = $item['subtotal'] - $item['discount_amount'];
                  $item = $this->maybe_apply_tax_to_item($item);
                  $this->items[$k] = $item;
              }
          } else {

              $item_discounts = $this->divide_money_evenly($discount, $item_count);

              $i = 0;
              foreach($this->items as $k=>$item) {
                  $item['discount_amount'] = $item_discounts[$i];
                  $item['total_amount'] = $item['subtotal'] - $item['discount_amount'];
                  $item = $this->maybe_apply_tax_to_item($item);
                  $this->items[$k] = $item;
                  $i++;
              }
          }
      }
  }

  public function setup_tax($scp) {
    $this->tax_data = NCS_Cart_Tax::get_order_tax_data($this);
    $this->tax_rate = $this->tax_data->rate ?? 0.00;	
    $this->tax_desc = $this->tax_data->title ?? '';
    $this->stripe_tax_id = $this->tax_data->stripe_tax_rate ?? '';

    if($scp->tax_type=='inclusive_tax'){
        $this->tax_data->type = 'inclusive';
    } else {
        $this->tax_data->type = 'exclusive';
    }

    if(isset($this->tax_data->redeem_vat) && $this->tax_data->redeem_vat){    
      $this->tax_rate = 0;
      $this->tax_desc = get_option('_sc_vat_reverse_charge',"VAT Reversal");
    }
  }

  public function calculate_tax($_amount, $product_id) {

    $scp = sc_setup_product($product_id);

    if(!$this->tax_data) {
        $this->setup_tax($scp);
    }

    $tax_amount = 0;
    if($this->tax_data->type == 'inclusive'){	
        $tax_amount = $this->tax_rate*$_amount/(100+$this->tax_rate);
        if(isset($this->tax_data->redeem_vat) && $this->tax_data->redeem_vat){
            $tax_amount = 0;
        }
    } else {
        $tax_amount = $this->tax_rate*$_amount/100;	
        if(!isset($this->tax_data->redeem_vat) || !$this->tax_data->redeem_vat) {
          $_amount += $tax_amount;
        }
    }

    return ['tax'=> $tax_amount, 'total'=>$_amount];
  }

  public function maybe_apply_tax_to_item($item) {

    global $scp;

    if(!isset($item['product_id']) || !$item['product_id'] || !$item['total_amount']) {
        return $item;
    }

    $pid = $item['product_id'];
    $tax = get_post_meta($pid, '_sc_product_taxable', true);
    if ($tax =='tax') {	

        $_amount = $item['total_amount'];
        
        $res = $this->calculate_tax($_amount, $pid);
        $tax_amount = $res['tax'];
        $_amount = $res['total'];

        if($this->tax_desc) {
            $title = $this->tax_desc;
            if($this->tax_data->type == 'inclusive') {
                if(!isset($this->tax_data->redeem_vat) || !$this->tax_data->redeem_vat){
                  $title .= ' (' . __('included in price', "ncs-cart") . ')'; 
                }
            }

            $item['tax_amount'] = $tax_amount;
            $item['tax_rate'] = $this->tax_rate;
            $item['tax_desc'] = $this->tax_desc;
            $item['total_amount'] = $_amount;
        }
    }

    return $item;
} 

public function add_item($arr) {
    $this->items = $this->items ?? array();
    $this->items[] = $this->maybe_apply_tax_to_item($arr);
}

public function get_items() {
    if($items = ScrtOrderItem::get_order_items($this->id)) {
        return $items;
    } else {
        return false;
    }
}

public static function store_items($order) {
    
    if(!isset($order->items) || !is_countable($order->items)) {
        return false;
    }

    foreach ($order->items as $i) {
        $item = new ScrtOrderItem();

        $i['order_id'] = $i['order_id'] ?? $order->id;
        
        foreach($i as $key=>$val) {
            $item->$key = $val;
        }
        $item->store();
    }

    unset($order->items);
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
      
    if($product_info->upsell_path) {
        $path = $product_info->upsell_path;
        $offer = $_POST['step'] ?? get_post_meta($id, 'current_upsell_offer', true);
        $offer = (!$offer) ? 1 : intval($offer);
        
        if($type == 'upsell') {
            $amount = $path['us_price_'.$offer];
            $product_id = $path['us_product_'.$offer];
            $prod_type = 'us_prod_type_'.$offer;
            $oto_plan = $path['us_plan_'.$offer];
            $parent_key = 'us_parent';
        } else { $type == 'downsell';
            $amount = $path['ds_price_'.$offer];
            $product_id = $path['ds_product_'.$offer];
            $prod_type = 'ds_prod_type_'.$offer;
            $oto_plan = $path['ds_plan_'.$offer];
            $parent_key = 'ds_parent';
        }
                
        $is_plan = false;
        if ($path[$prod_type]) {
            $is_plan = true;
            $plan = studiocart_plan($oto_plan, '', $product_id);
        }   

    }
          
    $order->product_id      = $product_id;
    $order->product_name    = sc_get_public_product_name($product_id);

    $order->$parent_key     = $id;
    $order->order_parent    = $id;
    $order->item_name       = $type;
    $order->plan_id         = $type;
    $order->option_id       = $type;
    $order->main_offer_amt  = $amount;   
    $order->purchase_note   = get_post_meta($product_id , '_sc_purchase_note', true); 
    $order->order_type      = $type;
    $order->us_offer        = $offer;
      
    if($is_plan) {
        $amount             = $plan->initial_payment;
        $order->plan        = $plan;
        $order->item_name   = $plan->name;
        $order->plan_id     = $plan->stripe_id;
        $order->option_id   = $plan->option_id;
        $order->main_offer_amt = $plan->initial_payment;
    }

    $args = [
        'product_id'     => $order->product_id,
        'price_id'       => $order->option_id,
        'item_type'      => 'main',
        'product_name'   => $order->product_name,
        'price_name'     => $order->item_name,
        'unit_price'     => $amount,
        'quantity'       => 1,
        'subtotal'       => $amount,
        'total_amount'   => $amount,
    ];

    if($is_plan && !empty($plan->trial_days)){
        $args['trial_days'] = $plan->trial_days;
    }

    if($is_plan && !empty($plan->fee)){
        $args['sign_up_fee'] = $plan->fee;
    }

    if($order->purchase_note) {
        $args['purchase_note'] = $order->purchase_note;
    }

    $order->add_item($args);

    $order->calculate_pre_tax_amount_from_items();
    $order->calculate_tax_amount_from_items();
    $order->calculate_total_amount_from_items();

    $order->invoice_subtotal = $order->pre_tax_amount;

    $order->amount = apply_filters('sc_charge_amount', $order->amount, $product_info);
    $order->non_formatted_amount = $order->amount;
    $order->amount = $order->amount;
    $order->invoice_total = $order->amount;

    // setup order_summary_items for checkout form
    $order->calculate_final_amounts();

    do_action('sc_after_load_from_post', $order, false);

    return $order;
  }
    
  public function trigger_integrations() {
    $order = $this->get_data();
    sc_trigger_integrations($this->status, $order );
  }
    
  public function find_user_id() {
    
    if($this->user_account) {
      return $this->user_account;
    } else if($user_id = email_exists($this->email)) {
      return $user_id;
    } else {
      return false;
    }

  }
    
  static function get_current_user_orders($limit=1, $product_id=0) {
        $args = array(
          'post_type' => 'sc_order',
          'post_status' => array('paid','completed'),
          'posts_per_page' => $limit,
        );
        
        $meta_query = array('relation' => 'OR');
        
        if (is_user_logged_in()) {
            $meta_query[] = array(
                                'key' => '_sc_user_account',
                                'value' => get_current_user_id(),
                            );
        }

        if (isset($_POST['email'])) {
            $meta_query[] = array(
                        'key' => '_sc_email',
                        'value' => sanitize_email($_POST['email']),
                    );
        }
      
        $meta_query = apply_filters('sc_current_user_orders_meta_query_args', $meta_query, $product_id);
              
        if (count($meta_query) > 1) {
            if($product_id) {
                $args['meta_query'] = array(
                        'relation' => 'AND',
                        array(
                          'key' => '_sc_product_id',
                          'value' => $product_id,
                        ),
                        $meta_query,
                );
            } else {
                $args['meta_query'] = $meta_query;
            }

            $posts = get_posts($args);
            return $posts;
        } else {
            return false;
        }
  }
    
  static function get_meta_value($order_id, $key) {
      return get_post_meta($order_id, '_sc_'.$key, true);
  }

  public function get_children($return_obj = false) {
      $data = false;
      if ($children = get_post_meta($this->id, '_sc_order_child')) {
        $data = array();
        foreach($children as $child) {
            
          if(is_numeric($child)) {
              $child = array('id' => $child);
          }
            
          $child = new ScrtOrder($child['id']);
          $child = apply_filters('studiocart_order', $child);
          
          if($return_obj) {
            $data[] = $child;
          } else {
            $data[] = $child->get_data();
          }
        }
      }
      return $data;
  }
    
  public function get_upsell($offer='') {
      if($children = $this->get_children()) {
          foreach ($children as $child) {
              if($child['us_parent'] && (!$offer || $child['us_offer'] == $offer) ) {
                  return $child;
              }
          }
      }
      return false;
  }
    
  public function get_downsell() {
      $children = $this->get_children();
      if($children) {
          foreach ($children as $child) {
              if($child['ds_parent']) {
                  return $child;
              }
          }
      }
      return false;
  }  

  public function get_subscription() {
    if($this->subscription_id) {
      $sub = new ScrtSubscription($this->subscription_id);
      if($sub->id) {
          return $sub;
      }
    }
    return false;
  }
    
  public function invoice_link($download = true) {
    if($this->status != 'pending-payment' || $this->pay_method=='cod') {
        $invoice_id = $this->id;
        $download = (integer) $download;
        if (isset($this->ob_parent) || isset($this->us_parent) || isset($this->ds_parent)) {
            if (isset($this->ob_parent)) {
                $invoice_id = $this->ob_parent;
            } else if (isset($this->ds_parent)) {
                $invoice_id = $this->ds_parent;
            } else {
                $invoice_id = $this->us_parent;
            }
        }
        return home_url().'?sc-invoice='.$invoice_id.get_post_timestamp($invoice_id).'&id='.$invoice_id.'&dl='.$download;
    } else {
        return false;
    }
  }
    
  public function invoice_link_html($label = false) {
    if($link = $this->invoice_link()) {
        if ($label === false) {
            $label = esc_html__('Download Invoice', 'ncs-cart');
        }
        return '<a href="'.$link.'" target="_blank" rel="noopener noreferrer">'.$label.'</a>';
    } else {
        return false;
    }
  }

  public function get_status() {
    global $wp_post_statuses;
    return $wp_post_statuses[$this->status]->label;
  }
    
  public function set_date_from_timestamp( $gmt_timestamp ) {
	$iso_date = date( 'Y-m-d H:i:s', $gmt_timestamp );
	return $this->set_date($iso_date);
  }
  
  public function set_date($date_time) {
      if(!$this->id) {
          return false;
      }
      
      wp_update_post( array( 
        'ID' => $this->id, 
        'post_date' => get_gmt_from_date( $date_time ),
        'post_date_gmt' => get_gmt_from_date( $date_time )
      ),
      false );
	  
	  return get_gmt_from_date( $date_time );
  }

  public function refund_log($amount,$refundID){       
    $log_entries = get_post_meta( $this->id, '_sc_refund_log', true);
    
    if(!is_array($log_entries)) {
        $log_entries = array(); 
    }

    $log_entries[]= array(
      'refundID' => $refundID,
      'date' => date('Y-m-d H:i'),
      'amount' => $amount 
    );

    update_post_meta( $this->id, '_sc_refund_log', $log_entries );
    
    $this->refund_log = $log_entries;
  }
    
  public function get_data() {

      if($this->id===false) {
          return false;
      }
      
      $data = array();
      $data['ID'] = $this->id;
      $data['date'] = get_the_date('', $this->id);
      
      foreach ($this->attrs as $key) {
        $data[$key] = $this->$key;
      }

      $data['status_label'] = $this->get_status();
            
      $data['invoice_link'] = $this->invoice_link();
      $data['invoice_link_html'] = $this->invoice_link_html();
      
      if($data['user_account']) {
          $data['user'] = get_user_by( 'id', $data['user_account'] );
      }
      
      if(!isset($data['invoice_subtotal'])){
        $data['invoice_subtotal'] = $data['main_offer_amt'];      
      
        if (!empty($data['order_bumps']) && is_array($data['order_bumps'])) {
          foreach($data['order_bumps'] as $order_bump) {
            $data['invoice_subtotal'] += $order_bump['amount'];
          }
        }
        $data['invoice_total'] = $data['invoice_subtotal'];
         
        if($data['tax_amount']>0) {
          if($data['tax_data'] && (!isset($data['tax_data']->type) || $data['tax_data']->type!='inclusive')){
            $data['invoice_total'] += $data['tax_amount'];
          }
        }
      }
      
      if($data['tax_data']) {
        $data['tax_rate'] .= '%';
        if($data['tax_amount']>0 && $data['tax_data'] && $data['tax_data']->type=='inclusive') {
          $data['tax_rate'] .= ' '.__('incl.', 'ncs-cart');
        }
      }
      
      if ($data['coupon']) {
        if (!isset($data['coupon_id']) || !$data['coupon_id']) {
            $data['coupon_id'] = $data['coupon'];
        }
      }
                        
      if ( is_countable($data['custom_prices']) ) {
        $fields = $data['custom_fields'];
        foreach($data['custom_prices'] as $k=>$v) {
            $custom[$k]['label'] = $fields[$k]['label'];
            $custom[$k]['qty'] = $fields[$k]['value'];
            $custom[$k]['price'] = $v;
        }
        $data['custom_prices'] = $custom;
      }
        
      $data['renewal_order'] = get_post_meta( $this->id, '_sc_renewal_order', true );
            
      if($data['tax_amount']) {
        $data['invoice_tax_amount'] = $data['tax_amount']; 
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
          $data['invoice_total'] += $child->main_offer_amt;
          if($child->tax_data){
            $data['invoice_tax_amount'] = $data['invoice_tax_amount'] ?? 0;
            $data['invoice_tax_amount'] += $child->tax_amount;
            if(!isset($child->tax_data->type) || $child->tax_data->type != 'inclusive'){
              $data['invoice_total'] += $child->tax_amount;
            }
          }
        }
      }

      $key = 'order_summary_items';
      $data['order_summary_items'] = $this->$key ?? [];
      
      return $data;
  }

  private function divide_money_evenly($dollar_amount, $num_parts, $dollar_amounts=[]) {
      $total = 0;
      
      for ($i = 0; $i < $num_parts; $i++) {
          if (abs($dollar_amount - $total) != 0) {
              $divided = $dollar_amount / $num_parts;
          
              if ($divided < 1) {
                  $rounded = 0;
              }
              
              else {
                  $rounded = floor($divided);
              }
              
              if ($rounded == 0) {
                  $total += 0.01;
                  
                  if (isset($dollar_amounts[$i])) {
                      $dollar_amounts[$i] = $dollar_amounts[$i] + 0.01;
                  }
                  
                  else{
                      $dollar_amounts[$i] = 0.01;
                  }
              }
              
              else {
                  $total += $rounded;
                  
                  if (isset($dollar_amounts[$i])) {
                      $dollar_amounts[$i] = $dollar_amounts[$i] + $rounded;
                  }
                  
                  else {
                      $dollar_amounts[$i] = $rounded;
                  }
              }
          }
      }
      
      $difference = $dollar_amount - $total;

      if ($difference > 0) {
          $dollar_amounts = $this->divide_money_evenly((float)(string) $difference, $num_parts, $dollar_amounts);
      }
  
      foreach ($dollar_amounts as &$dollar_amount) {
          $dollar_amount = strval($dollar_amount);
      }
  
      return $dollar_amounts;
  }

} //End class
