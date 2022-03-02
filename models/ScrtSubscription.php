<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class ScrtSubscription {
  protected $attrs, $order_attrs, $defaults, $count_orders;

  public function __construct($obj = null) {
    $this->initialize(
      // sub only keys
      array(
        'id'                => 0,
        'subscription_id'   => uniqid(),
        'status'            => self::$pending_str,
        'sub_status'        => self::$pending_str,
        'first_order'       => 0,
        'order_log'         => null,
        'amount'            => 0.00,
        'tax_amount'        => 0.00,
        'sub_amount'        => 0,
        'sub_discount'      => 0,
        'sub_discount_duration' => null,
        'sub_item_name'     => null,
        'sub_installments'  => null,
        'sub_interval'      => null,
        'sub_frequency'     => null,
        'sub_next_bill_date'=> null,
        'sub_end_date'      => null,
        'cancel_at'         => null,
        'free_trial_days'   => 0,
        'sign_up_fee'       => 0,
        'order_bump_subs'   => null, // deprecated      
        'main_product_sub'  => null, // deprecated      
        'cancel_date'       => null
      ),
      // shared order keys
      array(
        'firstname'         => null, // backwards compatibility
        'lastname'          => null, // backwards compatibility
        'first_name'        => null,
        'last_name'         => null,
        'customer_name'     => null,
        'customer_id'       => null,
        'custom_fields_post_data' => null,
        'custom_fields'     => null,
        'custom'            => null,
        'email'             => null,
        'phone'             => null,
        'country'           => null,
        'address1'          => null,
        'address2'          => null,
        'city'              => null,
        'state'             => null,
        'zip'               => null,
        'product_id'        => null,
        'product_name'      => null,
        'page_id'           => null,
        'page_url'          => null,
        'item_name'         => null,
        'plan_id'           => null,
        'option_id'         => null,
        'ip_address'        => null,
        'tax_rate'          => 0.00,
        'tax_desc'          => '',
        'tax_data'          => '',
        'tax_type'          => 'tax',
        'stripe_tax_id'     => '',
        'user_account'      => 0,
        'auto_login'        => null,          
        'coupon'            => null,
        'coupon_id'         => null,
        'on_sale'           => 0,
        'pay_method'        => null,
        'gateway_mode'      => null,
        'currency'          => 'USD',
        'main_offer'        => null,
        'main_offer_amt'    => null,
        'us_parent'         => null,
        'ds_parent'         => null,
        'vat_number'        => '',
      ),
      $obj
    );
  }    
        
  public function initialize($defaults, $order_defaults, $obj=null) {
    $this->defaults = $defaults;  
    $this->attrs = array_merge(array_keys($defaults), array_keys($order_defaults));
    $this->order_attrs = array_keys($order_defaults);
      
    if(is_null($obj)) {
      foreach ($defaults as $key => $value) {
        $this->$key = $value;
      }
      foreach ($order_defaults as $key => $value) {
        $this->$key = $value;
      }
    } else if(is_numeric($obj) && $obj > 0) {
      if (get_post_type($obj) != 'sc_subscription') {
        $this->id = false;
        return;
      }
      $meta = get_post_custom( $obj );
      foreach ($defaults as $key => $value) {
        if($key == 'id') {
            $this->$key = $obj;
        } else if(isset($meta['_sc_'.$key])) {
          $value = array_shift($meta['_sc_'.$key]);
          $this->$key = maybe_unserialize($value);
        } else {
          $this->$key = $value;
        }
      }
      foreach ($order_defaults as $key => $value) {
        if($key == 'id') {
            $this->$key = $obj;
        } else if(isset($meta['_sc_'.$key])) {
          $value = array_shift($meta['_sc_'.$key]);
          $this->$key = maybe_unserialize($value);
        } else {
          $this->$key = $value;
        }
      }
    }
  }    

  // Statuses
  public static $pending_str    = 'pending-payment';
  public static $active_str     = 'active';
  public static $canceled_str   = 'canceled';
  public static $completed_str  = 'completed';
  public static $past_due_str   = 'past_due';

  // Static Gateways
  public static $free_gateway_str  = 'free';
  public static $cod_gateway_str   = 'cod';
  public static $stripe_gateway_str = 'stripe';
  public static $paypal_gateway_str = 'paypal';
    
  public static function create($sub) {
    // create order
    $post_id = wp_insert_post(array('post_title'=> $sub->product_name , 'post_type'=>'sc_subscription', 'post_status'=>$sub->status), FALSE );

    wp_update_post( array( 
        'ID' => $post_id, 
        'post_title' => "#" . $post_id . " " . $sub->customer_name
    ),
    false );
      
    $keys = $sub->attrs;
    foreach($keys as $key) {
        if(isset($sub->$key) && $sub->$key){
            update_post_meta( $post_id , '_sc_'.$key , $sub->$key );
        }
    }
      
    return $post_id;
  }
    
  public static function update($sub) {
    if(get_post_type($sub->id) != 'sc_subscription') {
        return false;
    }
    $keys = $sub->attrs;
    foreach($keys as $key) {
        if(isset($sub->$key) && $sub->$key){
            update_post_meta( $sub->id , '_sc_'.$key , $sub->$key );
        }
    }
    wp_update_post( array( 'ID'   =>  $sub->id, 'post_status'   =>  $sub->status ) );
    return $sub->id;
  }
    
  public function store() {
    $og_sub = new self($this->id);
    
    // set first order ID if missing
    if(!$this->first_order) {
        $this->first_order();
    }

    if(isset($this->id) && !is_null($this->id) && (int)$this->id > 0) {
      $this->id = self::update($this);
    }
    else {
      $this->id = self::create($this);
    }

    //do actions now
    if(!$og_sub->id && $this->id || ($og_sub->status != $this->status)) {
        sc_log_entry($this->id, __('Subscription status updated to '. $this->status, 'ncs-cart'));
        if($this->status != self::$pending_str) {
          sc_trigger_integrations($this->status, $this->id);
        }
    }
      
    return $this->id;
  } 
    
  public static function from_order($order=false) {
      
    global $scp, $sc_currency;
      
    if ($order===false) {
        return false;
    }
      
    $sub = new self();
    $keys = $sub->order_attrs;
    foreach($keys as $key) {
        if(isset($order->$key) && $order->$key){
            $sub->$key = $order->$key;
        }
    }
      
    $plan = $order->plan;
      
    if($plan->type == 'recurring') {
        $sub->plan_id              = $plan->stripe_id;
        $sub->option_id            = $plan->option_id;
        $sub->item_name            = $plan->name;
        $sub->amount               = $plan->price;
        $sub->sub_amount           = $plan->price;
        $sub->sub_item_name        = $plan->name;
        $sub->sub_installments     = $plan->installments;
        $sub->sub_interval         = $plan->interval;
        $sub->sub_frequency        = $plan->frequency;
        $sub->sub_next_bill_date   = $plan->next_bill_date;
                
        if($plan->db_cancel_at){
            $sub->sub_end_date     = $plan->db_cancel_at;
            $sub->cancel_at        = $plan->cancel_at;
        }

        if($order->coupon && !in_array($order->coupon['type'], array('cart-percent', 'cart-fixed')) ) {
            $discount = $order->coupon['amount'];
            if ($order->coupon['type'] == 'percent') {
                $discount = $sub->sub_amount * ($discount / 100);
            } else if ( $_option_type == 'recurring' && !empty($coupon['amount_recurring']) ) {
                $discount = $coupon['amount_recurring'];
            }
            
            $sub->sub_discount = $discount;
            $sub->sub_amount -= $sub->sub_discount;

            if( $order->coupon['duration'] ) {
                $sub->sub_discount_duration = $order->coupon['duration'];
            } else {
                $sub->amount = $sub->sub_amount;
            }
            
        }
    }

    if(!empty($plan->trial_days)){
        $sub->free_trial_days = $plan->trial_days;
    }
      
    if(!empty($plan->fee)){
        $sub->sign_up_fee = $plan->fee;
    }
      
    // process order bumps
    if(is_array($order->order_bumps)) {
      foreach($order->order_bumps as $k=>$bump) {
          
        // does this order bump have a subscription?
        if(isset($bump['plan'])) {
        
          // add bump plan info if main product purchase isn't a subscription
          if($plan->type != 'recurring') {
            $plan = $bump['plan'];
            $sub->product_id           = $bump['id'];
            $sub->product_name         = $bump['name'];
            $sub->plan_id              = $plan->stripe_id;
            $sub->option_id            = $plan->option_id;
            $sub->item_name            = $plan->name;
            $sub->sub_amount           = $plan->price;
            $sub->amount               = $plan->price;
            $sub->sub_item_name        = $plan->name;
            $sub->sub_installments     = $plan->installments;
            $sub->sub_interval         = $plan->interval;
            $sub->sub_frequency        = $plan->frequency;
            $sub->sub_next_bill_date   = $plan->next_bill_date;
              
            if($plan->db_cancel_at){
                $sub->sub_end_date     = $plan->db_cancel_at;
                $sub->cancel_at        = $plan->cancel_at;   
            }
            if(!empty($plan->trial_days)){
                $sub->free_trial_days = $plan->trial_days;
            }

            if(!empty($plan->fee)){
                $sub->sign_up_fee = $plan->fee;
            }
          } else {
              // Add sign up fee
              if ( isset($bump['plan']->fee) ) {
                  $sub->sign_up_fee += $bump['plan']->fee;
              }
              // Add to sub amount
              $sub->sub_amount += $bump['plan']->price;
          }
        }
      }
    }
    if($sub->tax_data){
      if($sub->tax_data->type=='inclusive'){	
          $sub->tax_amount = $sub->tax_rate*$sub->amount/(100+$sub->tax_rate);	
      } else {	
          $sub->sign_up_fee = sc_format_number($sub->sign_up_fee + ($sub->sign_up_fee*$sub->tax_rate/100));
          $sub->tax_amount = $sub->tax_rate*$sub->amount/100;	
          $sub->sub_amount += $sub->tax_amount;	
      }	        
  }
    return $sub;
  }    
      
  public function cancel_at($time='period_end') {
      
    if($time == 'period_end') {
        $time = $this->sub_next_bill_date;
    } 
      
    if (!is_numeric($time)) {
        $time = new DateTime($time);
        $time = $time->format('U');
    }
      
    if (is_numeric($time)) {
        wp_schedule_single_event($time, 'sc_cancel_subscription_event', array($this));
    }
  }
    
  public static function get_by_sub_id($sub_id) {
    $args = array(
        'post_type'  => 'sc_subscription',
        'post_status' => 'any',
        'posts_per_page' => 1,
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => '_sc_stripe_subscription_id',
                'value' => $sub_id,
            ),
            array(
                'key' => '_sc_subscription_id',
                'value' => $sub_id,
            )
        )
    );
    $args = apply_filters('sc_get_sub_args',$args,$sub_id);
    $posts = get_posts($args);
    if (empty($posts)) {
        return false;
    } else {
        $post_id = $posts[0]->ID;
        return new self($post_id);
    }
  }
    
  public function get_data() {
      $data = array();
      $data['ID'] = $this->id;
      foreach ($this->attrs as $key) {
        $data[$key] = $this->$key;
      }
      
      $data['status'] = (in_array(get_post_status( $this->status ),['pending-payment','initiated'])) ? 'pending' : $this->status;
      
      if($data['free_trial_days']){
        $start_date =  date("M j, Y", strtotime(get_the_time( 'Y-m-d', $this->id ) ."+".$data['free_trial_days']." day"));
      }else{
        $start_date =  get_the_time( 'M j, Y', $this->id );   
      }
      $data['start_date'] = $start_date;
      
      $data['sub_payment'] = '<span class="sc-Price-amount amount">'.sc_format_price($data['sub_amount']).'</span> / ';

      // payment without html around currency symbol
      $data['sub_payment_plain'] = sc_format_price($data['sub_amount'], false).' / ';
      if($data['sub_frequency'] > 1) {
        $data['sub_payment'] .= ($data['sub_frequency'] . ' ' . sc_pluralize_interval($data['sub_interval']));
        $data['sub_payment_plain'] .= ($data['sub_frequency'] . ' ' . sc_pluralize_interval($data['sub_interval']));
      } else {
        $data['sub_payment'] .= $data['sub_interval'];
        $data['sub_payment_plain'] .= $data['sub_interval'];
      }

      $data['sub_payment_terms'] = $data['sub_payment'];
      if ($data['sub_installments'] > 1) {
        $data['sub_payment_terms'] .= ' x ' . $data['sub_installments'];
      } else if (isset($data['sub_end_date'])){
        delete_post_meta($data['ID'], '_sc_sub_end_date');
        unset($data['sub_end_date']);
      }

      // terms without html around currency symbol
      $data['sub_payment_terms_plain'] = $data['sub_payment_plain'];
      if ($data['sub_installments'] > 1) {
        $data['sub_payment_terms_plain'] .= ' x ' . $data['sub_installments'];
      }
      
      return $data;
  }
    
  public function first_order() {
    if(!$this->id) {
        return false;
    }
      
    $first_order = false;
        
    if(!$this->first_order) {
        $first_order = $this->orders(1);
        if(is_array($first_order)){
            $first_order = $first_order[0];
            $this->first_order = $first_order->id;
        }
    } else {
        $first_order = new ScrtOrder($this->first_order);
        if(!$first_order) {
            $first_order = $this->orders(1);
            if(is_array($first_order)){
                $first_order = $first_order[0];
                $this->first_order = $first_order->id;
            }
        }
    }
    return $first_order;
  }
    
  public function new_order() {
    $new_order = $this->first_order();
    if($new_order) {
        $new_order->pay_method = $this->pay_method;
        $new_order->gateway_mode = $this->gateway_mode;
        $new_order->invoice_total = $this->amount;
        $new_order->invoice_subtotal = $this->amount - $this->tax_amount;
        $new_order->amount = $this->amount;
        $new_order->main_offer_amt = $this->amount;
        $new_order->tax_amount = $this->tax_amount;
            
        $children = array(
        'id'                => 0,
        'transaction_id'    => null,
        'status'            => self::$pending_str,
        'payment_status'    => self::$pending_str,
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
        'order_type'        => null,
        );
        foreach($children as $k=>$v){
            $new_order->$k = $v;
        }
    }
    return $new_order;
  }
    
  public function orders($limit=-1, $order='ASC') {
    $orders = array();
    $args = array(
        'post_type'  => 'sc_order',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'order' => $order,
        'meta_query' => array(
            array(
                'key' => '_sc_subscription_id',
                'value' => $this->id
            )
        )
    );
    $posts = get_posts($args);
    if($posts){
      
      foreach($posts as $post) {
        $orders[] = new ScrtOrder($post->ID);
      }
    } 
    return $orders;
  }
    
  public function order_count() {
      if(!$this->count_orders) {
          $this->count_orders = count($this->orders());
      }
      return (int) $this->count_orders;
  }

} //End class
