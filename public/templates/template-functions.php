<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

global $scp, $post;

$post_type = (array) apply_filters('sc_product_post_type', 'sc_product');
if(in_array(get_post_type(), $post_type)){
    $scp = ($scp) ? $scp : sc_setup_product( $post->ID );
    do_action('sc_after_product_setup', $scp);
}

if ( sc_fs()->is__premium_only() && sc_fs()->can_use_premium_code()) {
    add_action('sc_coupon_fields', 'sc_do_coupon_fields', 10);
    add_action('sc_coupon_status', 'sc_do_coupon_status', 10);
    add_action('sc_card_details_fields', 'sc_orderbumps', 15, 2); 
    add_action('sc_checkout_form_fields', 'sc_custom_fields', 10, 2);
    add_action('sc_after_buy_button', 'sc_do_form_footer_text', 10);
    add_action('sc_after_step_1_button', 'sc_do_step_1_footer_text', 10);
    
    function sc_do_form_footer_text() {
        global $scp;
        if(isset($scp->footer_text)) {
            echo '<div class="sc-footer-text">'.wpautop(wp_specialchars_decode($scp->footer_text, 'ENT_QUOTES' )).'</div>';
        }
    }
    
    function sc_do_step_1_footer_text() {
        global $scp;
        if(isset($scp->step_1_footer_text)) {
            echo '<p class="sc-footer-text">'.wp_specialchars_decode($scp->step_1_footer_text, 'ENT_QUOTES' ).'</p>';
        }
    }
    
    function sc_custom_fields($post_id, $hide_labels=false) {
        global $scp;
        $autologin = false;
        if (isset($scp->custom_fields)) {
            echo '<div class="row">';
            foreach($scp->custom_fields as $field) {
                
                $name = 'sc_custom_fields['.esc_attr($field['field_id']).']';
                
                // hide user cred fields if logged in
                if  ( ($field['field_type']=='text' && isset($field['field_username'])) || 
                    $field['field_type']=='password' ) {
                    
                    if (is_user_logged_in()) {
                        if ( ($field['field_type']=='password' || isset($field['field_username'])) && !current_user_can('administrator')) {
                            continue;
                        }
                        
                        if($field['field_type']!='password') {
                            global $current_user;
                            wp_get_current_user();
                            $field['default_value'] = $current_user->user_login;
                        }
                    }
                    
                    if (isset($field['field_username'])) {
                        $field['field_id'] = 'wpuserid';
                    }
                    
                    if ($field['field_type']=='password') {
                        if (isset($field['field_autologin'])) {
                            echo '<input type="hidden" name="sc-auto-login" value="1" />';
                            $autologin = true;
                        }
                    }
                }
               
                $f = array(
                    'id'=> 'sc-'.esc_attr($field['field_id']),
                    'name'=> $name,
                    'label'=> esc_attr($field['field_label']),
                    'hide_labels'=>$hide_labels,
                    'type'=>esc_attr($field['field_type']),
                    'cols' => 12
                );
                
                if (isset($field['field_required'])) {
                   $f['required'] = true;
                }
                
                if (isset($field['description'])) {
                   $f['description'] = $field['description'];
                }
                
                if ($field['field_type']=='quantity' && isset($field['qty_price'])) {
                   $f['qty_price'] = floatval($field['qty_price']);
                }

                if (isset($field['default_value'])) {
                   $f['value'] = esc_attr($field['default_value']);
                }
                
                if (isset($field['select_options'])) {
                    $choices = [];
                    $options = explode("\n", str_replace("\r", "", esc_attr($field['select_options'])));
                    for ($i=0;$i<count($options);$i++) {
                        $option = explode(':', $options[$i]);
                        if (count($option) == 1) {
                            $choices[trim($option[0])] = trim($option[0]);
                        } else {
                            $choices[trim($option[0])] = trim($option[1]);
                        }
                    }
                    $f['choices'] = $choices;
                }
               
                sc_do_field($f);
            }
            echo '</div>';
        }
        
        if (!$autologin && isset($scp->autologin)) {
            echo '<input type="hidden" name="sc-auto-login" value="1" />';
        }
    }
}

function sc_do_test_mode_message(){
    global $sc_stripe;
    if(!$sc_stripe || !isset($sc_stripe['mode'])) {
        return;
    }
    if($sc_stripe['mode'] == 'test') {
        echo '<p class="sc-stripe" id="test-mode-message">'.sprintf(__('TEST MODE ENABLED: To make a test (US) purchase, use Credit Card Number "4242424242424242" with any CVC and a valid expiration date. <a href="%s" target="_blank" rel="noopener noreferrer">Find a test card for another country</a>.','ncs-cart'), 'https://stripe.com/docs/testing#international-cards').'</p>';
    }
}
function sc_do_payment_confirmation($prod_id) { 
    
    $show_confirm   = ( isset($_GET['sc-order']) && $_GET['sc-order'] > 0 ) ? true : false;
    $orderID        = ($show_confirm) ? intval($_GET['sc-order']) : false;
    ?>
    
    <section class="studiocart pay-confirm">
         <div class="container">
            <div id="sc-payment-form" class="pay-confirmation">
                <div class="sc-section products">
                    <?php if ( $show_confirm ): ?>
                        <div>
                            <img src="<?php echo plugin_dir_url( __FILE__ ) . '../images/checkmark.png' ?>" style="display: inline-block;width: 80px;margin-left: 12px;">
                            <?php 
                                sc_do_confirmation_message();  
                                ncs_template('shortcodes/receipt', '', sc_get_item_list($orderID));
                            ?>
                        </div>
                    <?php else: ?>
                        <?php sc_do_cart_closed_message(); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php
}

function sc_do_cart_closed_message() {
    global $scp;
    $closed_msg = ($scp->cart_closed_message) ? $scp->cart_closed_message : __("Sorry, this product is no longer for sale.", "ncs-cart");
    echo '<h4 class="closed" style="margin:0">';
    echo wp_specialchars_decode($closed_msg, 'ENT_QUOTES' );
    echo '</h4>';
}

function sc_do_confirmation_message() {
    global $scp;
    $closed_msg = ($scp->confirmation_message) ? $scp->confirmation_message : __("Thank you. We've received your order.", "ncs-cart");
    echo '<h4 style="margin:0 0 20px">';
    echo wp_specialchars_decode($closed_msg, 'ENT_QUOTES' );
    echo '</h4>';
}

function sc_do_error_messages() {
    $errors = [];
    if (isset($_POST["sc_errors"]['messages'])) {
        $errors = $_POST["sc_errors"]['messages'];
    }
    $errors = apply_filters('sc_checkout_page_error',$errors);
    if(!empty($errors)){
        echo '<ul class="form-errors">';
        foreach ($errors as $msg) {
            echo '<li>'.esc_html($msg, 'ncs-cart').'</li>';
        }
        echo '</ul>';
    }
}

function sc_do_checkout_form_open($post_id) {
    global $scp;
    global $scuid;
    
    $scuid = uniqid();
    if (!$scp || !isset($scp->ID)) {
        $scp = sc_setup_product($post_id);
    }
    $scp->form_action = (string) $scp->form_action;
    $optin_class = isset($scp->show_optin) ? 'class="signup-form"' : '';
    echo '<div id="sc-payment-form-'.$scp->ID.'-'.$scuid.'" class="sc-form-wrap">';
    echo '<form id="sc-payment-form" '.$optin_class.' action="'.esc_attr($scp->form_action).'" method="post">';
}

function sc_do_checkout_form($post_id, $hide_labels, $plan=false) {    
    do_action('sc_card_details_fields', $post_id, $hide_labels, $plan);
    do_action('sc_order_summary', $post_id); 
}
        
function sc_payment_plan_options($post_id, $hide_labels, $plan=false) {
    global $scp;
    $hide_class = (isset($scp->hide_plans)) ? 'hidden' : '';
    
    if (isset($scp->show_coupon_field)) {
        $hide_class .= ' sc-show-coupon';
    }
    
    do_action('sc_orderform_before_payment_plans', $post_id);
    ?>

    <div class="sc-section products <?php echo $hide_class; ?>">

        <?php    
        $on_sale = sc_is_prod_on_sale();

        $name = (!$on_sale) ? 'option_name' : 'sale_option_name';
        $price = (!$on_sale) ? 'price' : 'sale_price';  
        $items = apply_filters('sc_checkout_form_pay_options', $scp->pay_options, $scp);
        $installments = (!$on_sale) ? 'installments' : 'sale_installments';
        $interval = (!$on_sale) ? 'interval' : 'sale_interval';
        $fee = (!$on_sale) ? 'sign_up_fee' : 'sale_sign_up_fee';
        $tax_data = array();
        $i = 0;
    
        $plan_heading = ( isset($scp->plan_heading) && $scp->plan_heading ) ? $scp->plan_heading : esc_html__("Payment Plan", "ncs-cart");
        $plan_heading = apply_filters('sc_plan_heading', $plan_heading, $scp->ID);
        ?>

        <h3 class="title"><?php echo esc_html($plan_heading); ?></h3>

        <?php if ( sc_fs()->is__premium_only() && sc_fs()->can_use_premium_code()) {
            do_action('sc_coupon_fields', $post_id); 
        } ?>
            
        <?php foreach ($items as $item): 
    
            $item['product_type'] = $item['product_type'] ?? false;

            if(isset($item['is_hidden'])) continue;
    
            if ($item['product_type'] == 'free') {
                $item[$price] = 0;
            } else if(isset($scp->show_optin)) {
                continue;
            }
            if ($item['product_type'] != 'recurring') {
                unset($item[$fee],$item['trial_days']);
            }
    
            $checked = '';
            if(!$plan && get_query_var('plan')) {
                $plan = sanitize_text_field(get_query_var('plan'));
            }
    
            if ( $plan ) {
                $checked = ( isset($item['url_slug']) && $item['url_slug'] == $plan ) ? 'checked' : '';
            } else if ($i==0) {
                $checked = 'checked';
            }
    
            $int = $item[$interval];
            if($item['frequency'] > 1) {
                $int = sc_pluralize_interval($int);
            }
            ?>

            <div class="item <?php if($item['product_type'] == 'pwyw'){ echo 'flex-wrap';}?>">
                <label>
                    <input id="option-<?php echo $item['option_id']; ?>" <?php echo $checked; ?> type="radio" name="sc_product_option" data-val="<?php echo $item['product_type']; ?>" data-price="<?php echo floatval($item[$price]); ?>" 
                    <?php if($item['product_type'] == 'recurring'): ?>  
                    data-installments="<?php echo $item[$installments]; ?>" data-interval="<?php echo $int; ?>" 
                        <?php if(isset($item['frequency'])): ?>    
                        data-frequency="<?php echo $item['frequency']; ?>"
                        <?php endif; ?>
                        <?php if(isset($item['trial_days'])): ?>    
                        data-trial-days="<?php echo $item['trial_days']; ?>"
                        <?php endif; ?>
                        <?php if(isset($item[$fee])): ?>    
                        data-signup-fee="<?php echo $item[$fee]; ?>" 
                        <?php endif; ?>
                    <?php endif; ?> 
                    <?php if($scp->product_taxable): ?>
                        data-taxable="yes"
                        data-tax-type="<?php echo $scp->tax_type; ?>"
                        data-tax-price-format="<?php echo $scp->price_show_with_tax; ?>"
                    <?php else: ?>
                        data-taxable="no" 
                    <?php endif; ?>
                    value="<?php echo $item['option_id']; ?>">
                    <span class="item-name">
                        <?php echo ($item[$name]) ?? $item['option_name'] ?? ''; ?>
                        <?php if($item['product_type'] == 'pwyw' && isset($item['name_your_own_price_text'])): ?>
                            <span class="pwyw-suggested"><?php echo $item['name_your_own_price_text'];?></span>
                        <?php endif;?>
                    </span>
                </label>

                <?php if(!isset($scp->hide_plan_price) && $item['product_type'] != 'pwyw'): ?>
                <span class="price">
                    <?php 
                    if ($on_sale && isset($scp->show_full_price) && $item['price'] > 0) {
                        echo '<s>'.sc_format_price($item['price']).'</s> ';
                    }
                    if ($item['product_type'] != 'free') {
                        sc_formatted_price($item[$price]);
                    } else if(!isset($scp->show_optin)) {
                        echo '<span class="price">'. esc_html_e("Free", "ncs-cart") .'</span>';
                    } ?>
                </span>
                <?php endif; ?>
                
                <?php if($item['product_type'] == 'pwyw'): ?>
                    <div class="w-100 my-4 pwyw-input" id="pwyw-input-block-<?php echo $item['option_id']; ?>" style="display: none;">
                        <?php
                        $pwyw_class = 'form-group mb-1';
                        $right_currency = (in_array(get_option( '_sc_currency_position' ), ['right', 'right-space'])) ? true : false;
                        if ($right_currency) {
                            $class .= ' right-currency';
                        }
                        ?>
                        <div class="<?php echo $pwyw_class; ?>">
                            <span class="sc-currency"><?php echo sc_get_currency_symbol(); ?></span>
                            <input id="pwyw-amount-input-<?php echo $item['option_id']; ?>" name="pwyw_amount[<?php echo $item['option_id']; ?>]" type="number" min="<?php echo floatval($item[$price]); ?>" class="form-control mb-0 required" placeholder="Amount">
                        </div>
                    </div>
                <?php endif;?>
            </div>
        <?php $i++; endforeach; ?>

        <?php if ( sc_fs()->is__premium_only() && sc_fs()->can_use_premium_code()) {
            do_action('sc_coupon_status', $post_id); 
        } ?>

    </div>
    <?php
}

function sc_do_checkout_form_close() {
    echo '</form></div>';
}

if ( sc_fs()->is__premium_only() && sc_fs()->can_use_premium_code()) {
    function sc_do_coupon_fields($post_id) {
        
        global $scp;

        ?>
        <input id="sc-coupon-id" type="hidden" name="coupon_id" value="">
        <div class="coupon-code">
            <?php if (isset($scp->show_coupon_field)): ?>
            <a id="sc-coupon-toggle" href="#"><?php esc_html_e("Got a coupon code?", "ncs-cart"); ?></a>
            <div id="sc-coupon-form" class="form-group" style="display: none;">
                <input id="discount-code" name="discount_code" type="text" class="form-control" placeholder="<?php esc_html_e("Enter discount code", "ncs-cart"); ?>" aria-label="<?php esc_html_e("Discount Code", "ncs-cart"); ?>" aria-describedby="basic-addon1">
                <input type="button" value="<?php esc_html_e("Apply", "ncs-cart"); ?>"/>            
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    function sc_do_coupon_status($post_id) {
        ?>
        <span id="coupon-status"><?php esc_html_e("coupon invalid", "ncs-cart"); ?></span>
        <div class="coupon-code applied">
            <span class="coup-confirm"><?php esc_html_e("Discount Applied!", "ncs-cart"); ?></span>
        </div>
        <?php
    }
}
function sc_do_field($args) {
    
    $defaults = array (
        'id' => false,
        'required' => false,
        'type' => 'text',
        'hide_labels' => 'false',
        'cols' => 6,
        'description' => '',                
        'class' => '',                
        'value' => '',
        'div_class' => '',
        'qty_price' => false,
    );
    
    $args = wp_parse_args( $args, $defaults );
    extract($args);
    
    if(!$id) {
        $id = $name;
    }
    
    if($description) {
        $description = '<div class="sc-field-description">'.$description.'</div>';
    }

    $class .= (!$required) ? '' : ' required';
    $class .= (!isset($_POST["sc_errors"][$name])) ? '' : ' invalid';
    $class .= ($type=='password') ? ' sc-password' : '';
    //$value = '';
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        switch($name){
            case 'first_name':
                $value = $current_user->user_firstname;
                break;
            case 'last_name':
                $value = $current_user->user_lastname;
                break;
            case 'email':
                $value = $current_user->user_email;
                break;
        }
    }
    $value = isset($_POST[$name]) ? esc_html($_POST[$name]) : $value;
    if (isset($_GET[$name])) { 
        $value = esc_html($_GET[$name]);
    } else if(strpos($name, 'sc_custom_fields') !== false) {
        $cfname = str_replace(array('sc_custom_fields[',']'), array('',''), $name);
        if(isset($_GET['custom_'.$cfname])) {
            $value = esc_html($_GET['custom_'.$cfname]);
        }   
    } ?>

    <?php if ($type != 'hidden'): ?>
    <div class="form-group col-sm-<?php echo $cols; ?> <?php echo $div_class; ?>">
        <?php if ($hide_labels != 'hide'): ?>
        <label for="<?php echo $id; ?>"><?php echo $label; ?><?php echo (!$required) ? '' : ' <span class="req">*</span>'; ?></label>
        <?php endif; ?>
    <?php endif; ?>
        <?php if ($type == 'select'): ?>
            <select id="<?php echo $name; ?>" name="<?php echo $name; ?>" class="form-control <?php echo $class; ?>">
                <?php foreach($choices as $k=>$v) {
                    $checked ='';
                    if(!empty($value) && $k==$value){
                        $checked = 'selected="selected"';
                    }
                    echo '<option value="'.$k.'" '.$checked.'>'.$v.'</option>';
                } ?>
            </select>
            <?php echo $description; ?>
        <?php elseif ($type == 'radio' || $type == 'checkbox'): ?>
            <?php if ($type=='checkbox') { $name .= '[]'; } ?>
            <br>
            <?php foreach($choices as $k=>$v) : ?>
                <?php $checked = (!empty($value) && $k==$value) ? 'checked' : ''; ?>
                <label>
                    <input type="<?php echo $type; ?>" name="<?php echo $name; ?>" <?php echo $checked; ?> class="form-control <?php echo $class; ?>" value="<?php echo $k; ?>">
                    <span class="item-name"><?php echo $v; ?></span>
                </label>
                <br>
            <?php endforeach; ?>
            <?php echo $description; ?>
        <?php elseif ($type == 'quantity'): ?>
            <input id="<?php echo $id; ?>" name="<?php echo $name; ?>" type="number" class="form-control <?php echo $class; ?>" step="1" min="1" max="" placeholder="<?php _e('Qty', 'ncs-cart'); ?>" value="<?php echo $value; ?>" aria-label="<?php echo $label; ?>" inputmode="numeric" pattern="[0-9]*" 
            <?php if ($qty_price): ?> data-scq-price="<?php echo $qty_price; ?>"<?php endif; ?>>
            <?php echo $description; ?>
            <?php if (isset($_POST["sc_errors"][$name])) : ?>
                 <div class="error"><?php echo esc_html($_POST["sc_errors"][$name]); ?></div>
            <?php endif; ?>
        <?php else: ?>
            <input id="<?php echo $id; ?>" name="<?php echo $name; ?>" type="<?php echo $type; ?>" class="form-control <?php echo $class; ?>" placeholder="<?php echo $label; ?>" value="<?php echo $value; ?>" aria-label="<?php echo $label; ?>"
            <?php if($type=='password'): ?>pattern="(?=.*\d)(?=.*[a-z]).{8,}"<?php endif; ?>
            >
            <?php if($type=='password'): ?>
                <div class="password-toggle">
                    <input type="checkbox" name="sc-show-password" id="sc-show-password" class="sc-password-toggle">
                    <label for="sc-show-password"><?php _e('Show password', 'ncs-cart'); ?></label>
                </div>
            <?php endif; ?>
            <?php echo $description; ?>
            <?php if (isset($_POST["sc_errors"][$name])) : ?>
                 <div class="error"><?php echo esc_html($_POST["sc_errors"][$name]); ?></div>
            <?php endif; ?>
        <?php endif; ?>
    <?php if ($type != 'hidden'): ?>
    </div>
    <?php endif;
}

function sc_do_checkoutform_fields($post_id, $hide_labels, $twostep = false) { 
    global $scp;
    $class = (!$twostep) ? 'sc-section card-details' : 'card-details';
    ?>
    <div class="<?php echo $class; ?>">
        <h3 class="title">
            <?php if( !isset($scp->fields_heading) || !$scp->fields_heading ): ?>
                <?php esc_html_e("Contact Info", "ncs-cart"); ?>
            <?php else: ?>
                <?php esc_html_e($scp->fields_heading, "ncs-cart"); ?>        
            <?php endif; ?>        
        </h3>
        <div class="row">
        <?php 
        
                                                            
        //$name, $label, $required=false, $type='text', $hide_labels=false, $cols=6, $class=''
        $fields = array(
            'firstname' => array('name'=>'first_name','label'=>esc_html__('First Name', 'ncs-cart'),'required'=> true, 'hide_labels'=>$hide_labels),
            'lastname' => array('name'=>'last_name','label'=>esc_html__('Last Name', 'ncs-cart'),'required'=> true, 'hide_labels'=>$hide_labels),
            
        );
        if (!isset($scp->hide_phone_field) || !$scp->hide_phone_field) {
            $fields['email'] = array('name'=>'email','label'=>esc_html__('Email', 'ncs-cart'),'type'=>'email','required'=> true, 'hide_labels'=>$hide_labels);
            $fields['phone'] = array('name'=>'phone','label'=>esc_html__('Phone Number', 'ncs-cart'),'type'=>'tel','hide_labels'=>$hide_labels);
        } else {
            $fields['email'] = array('name'=>'email','label'=>esc_html__('Email', 'ncs-cart'),'type'=>'email','required'=> true, 'hide_labels'=>$hide_labels,'cols'=>12);
        }

        $fields['company'] = array('name'=>'company','label'=>esc_html__('Company Name', 'ncs-cart'),'hide_labels'=>$hide_labels);
    
        if (!isset($scp->default_fields)) {
            foreach ($fields as $k=>$field) { // deprecated
                if(isset($scp->show_optin) || ($twostep && !isset($scp->show_address_fields)) ) {
                    $field['cols'] = 12;
                }
                if(isset($scp->hide_fields) && isset($scp->hide_fields[$k])) {
                    unset($field[$k]);
                }
            }
        }
    
        $fields = apply_filters('studiocart_order_form_fields', $fields, $scp);
        foreach($fields as $k=>$field) {
            sc_do_field($field);
        }
        ?>
        </div>
        
        <?php do_action('sc_checkout_form_fields', $post_id, $hide_labels); ?>
        
    </div>
<?php
}

function sc_do_2step_checkoutform_fields($post_id, $hide_labels) { 
    sc_do_checkoutform_fields($post_id, $hide_labels, $twostep = true);
}


add_action('sc_payment_method_fields', 'sc_do_payment_methods', 10);
function sc_do_payment_methods($post_id) {
    
    global $sc_stripe;
        
    $payment_methods = [];

    // Stripe
    if ( !get_post_meta($post_id,'_sc_disable_stripe',true) ) {
        if ( $option_val = get_option( '_sc_stripe_enable' ) == '1') { 
            if ( is_array($sc_stripe) ) {
                $payment_methods['stripe'] = array(
                    'value' => 'stripe',
                    'label' => esc_html__('Credit Card', 'ncs-cart'),
                    'single_label' => false,
                );
            }
        }
    }
    
    // COD
    if ( !get_post_meta($post_id, '_sc_disable_cod', true) ) {
        if ( $option_val = get_option( '_sc_cashondelivery_enable' ) == '1' ) { 
            $payment_methods['cashondelivery'] = array(
                'value' => 'cod',
                'label' => esc_html__('Cash on Delivery', 'ncs-cart'),
            );
        }
    }
    
    $i = 0;
    
    $payment_methods = apply_filters('sc_payment_methods', $payment_methods, $post_id );   
    
    ?>

    <h3 class="title"><?php esc_html_e("Payment Info", "ncs-cart"); ?></h3>
    <div class="pay-methods">
        <?php if(!empty($payment_methods)): ?>
            <?php foreach($payment_methods as $k=>$method) : ?>
                <label>
                <?php if (count($payment_methods) > 1) : 
                    $checked = ($i==0) ? 'checked="checked"' : '';
                    echo '<input id="method-' .$method['value'] .'" type="radio" name="pay-method" value="'. $method['value'] .'" '.$checked.'>';
                    echo '<span class="item-name">'.$method['label'].'</span>'; ?>
                <?php elseif(count($payment_methods)==1): ?>
                    <input id="method-<?php echo $method['value']; ?>" checked="checked" type="radio" name="pay-method" value="<?php echo $method['value']; ?>">
                    <?php 
                        $label = (isset($method['single_label']) && $method['single_label'] !== false) ? $method['single_label'] : $method['label'];
                        echo '<span class="item-name">'.$label.'</span>';
                    ?>
                <?php endif; $i++; ?>
                </label>
            <?php endforeach; ?>
        <?php else: ?>
            <?php esc_html_e('Sorry, it seems that there are no payment methods available. Please contact us for assistance.', 'ncs-cart'); ?>
        <?php endif; ?>
    </div>
<?php
}

function sc_do_vat_info() {?>
    <div class="vat_container" style='display:none;'>
        <div class="row">
            <div class="form-group col-sm-12">
                <input id="method-vat-number-available" type="checkbox" name="vat-number-available" value="Yes">
                <label for="method-vat-number-available"><?php esc_html_e('Enter VAT number', "ncs-cart"); ?></label>
            </div>
            <div class="form-group col-sm-12 vat_number_field" style="display:none;">
                <label for="vat_number"><?php esc_html_e('VAT Number', "ncs-cart"); ?><span class="req">*</span></label>
                <input id="vat_number" type="text" name="vat-number" placeholder="<?php esc_html_e('VAT Number', "ncs-cart"); ?>" class="form-control required" aria-label='VAT Number'>
            </div>
        </div>
    </div>
<?php
}

function sc_do_card_details_fields($post_id, $hide_labels) { 
    global $sc_stripe, $scp;
    if(!defined('DONOTCACHEPAGE')){
        define('DONOTCACHEPAGE', true);
    }
    $on_sale = sc_is_prod_on_sale();
    $name = (!$on_sale) ? 'name' : 'sale_name';
    $price = (!$on_sale) ? 'price' : 'sale_price';
    $nonce = wp_create_nonce("sc_purchase_nonce");
    ?>

    <div class="sc-section pay-info">
        
        <input type="hidden" name="<?php echo $name; ?>" id="item_name">
        <input type="hidden" name="<?php echo $price; ?>" id="item_price">
        <input type="hidden" name="sc_process_payment" value="1">
        <input type="hidden" name="sc_amount" value="">
        <input type="hidden" name="sc_product_id" value="<?php echo $post_id; ?>">
        <input type="hidden" name="sc-nonce" value="<?php echo $nonce; ?>">
        <input type="hidden" name="action" value="save_order_to_db">
        <?php echo ($on_sale) ? '<input type="hidden" name="on-sale" value="1">' : ''; ?>
        <?php // conversion tracking ?>
        <input type="hidden" name="sc_page_id" value="<?php the_ID(); ?>">
        <input type="hidden" name="sc_page_url" value="<?php echo esc_url_raw($_SERVER['REQUEST_URI']); ?>">
        
        <?php 
        if(!isset($scp->show_optin)) {
            do_action('sc_payment_method_fields', $post_id); 
            do_action('sc_before_payment_info', $post_id);
        
            if($sc_stripe): ?>
            <div class="row sc-stripe">
              <div class="form-group col-sm-12">
                <?php if ($hide_labels != 'hide'): ?>  
                <label for="card-element">
                   <?php esc_html_e("Credit or debit card", "ncs-cart"); ?> <span class="req">*</span>
                </label>
                <?php endif; ?>

                <div id="card-element" data-form-wrapper="<?php echo $scp->ID; ?>" aria-label="<?php esc_html_e("Credit or debit card", "ncs-cart"); ?>">
                  <!-- A Stripe Element will be inserted here. -->            
                </div>
                <!-- Used to display Element errors. -->
                <div id="card-errors" role="alert"></div>

              </div>
            </div>
            <?php endif; 
        } ?>
        
        <?php do_action('sc_after_payment_info', $post_id, $hide_labels); ?>
        
    </div>
    <?php
}

function sc_address_fields($post_id, $hide_labels = false) {
    global $scp;
    ?>
    <div class="address-info">
        <hr>
        <?php if (isset($scp->address_title)): ?>
        <h3 class="title"><?php echo esc_html($scp->address_title); ?></h3>
        <?php endif; ?>
        <div class="row">
            <?php 
    
            $defaults = array(
                'address_1' => '',
                'address_2' => '',
                'city'      => '',
                'state'     => '',
                'zip'       => '',
                'country'   => get_option( '_sc_country','US'),
            );
    
            $state_choices = array(''=>esc_html__('State / County', 'ncs-cart'));
    
            if(is_user_logged_in()) {
                $current_user = wp_get_current_user();
                if($address = sc_get_user_address($current_user->ID)) {
                    $defaults = $address;
                }
                if(isset($defaults['state'])) {
                    $state_choices[$defaults['state']] = $defaults['state'];
                }
            }
    
            $fields = array(
                'country' => array(
                    'name'=>'country',
                    'label'=>esc_html__('Country', 'ncs-cart'),
                    'type'=>'select',
                    'choices'=>sc_countries_list(),
                    'value'=> $defaults['country'],
                    'required'=> true,
                    'hide_labels'=>$hide_labels,
                    'cols'=>12
                ),
                'address1' => array(
                    'name'=>'address1',
                    'label'=>esc_html__('Address', 'ncs-cart'),
                    'required'=> true, 
                    'hide_labels'=>$hide_labels,
                    'value'=> $defaults['address_1'],
                    'cols'=>12,
                    'div_class'=>'sc-address-1'
                ),
                'address2' => array(
                    'name'=>'address2',
                    'label'=>esc_html__('Address Line 2', 'ncs-cart'), 
                    'hide_labels'=>true,
                    'value'=> $defaults['address_2'],
                    'cols'=>12,
                    'div_class'=>'sc-address-2'
                ),
                'city' => array(
                    'name'=>'city',
                    'label'=>esc_html__('Town / City', 'ncs-cart'),
                    'required'=> true, 
                    'value'=> $defaults['city'],
                    'hide_labels'=>$hide_labels,
                    'cols'=>12
                ),
                
                'state' => array(
                    'name'=>'state',
                    'label'=>esc_html__('State / County', 'ncs-cart'),
                    'type'=>'select',
                    'choices'=> $state_choices,
                    'value'=> $defaults['state'],
                    'required'=> true,
                    'hide_labels'=>$hide_labels
                ),
                'zip' => array(
                    'name'=>'zip',
                    'label'=>esc_html__('Postcode / Zip', 'ncs-cart'),
                    'value'=> $defaults['zip'],
                    'required'=> true, 
                    'hide_labels'=>$hide_labels
                )
            );

            if (!isset($scp->default_fields)) {
                foreach($fields as $k=>$field) { // deprecated
                    if(isset($scp->hide_fields) && isset($scp->hide_fields[$k])) {
                        unset($fields[$k]);
                    }
                }
            }

            $fields = apply_filters('studiocart_order_form_address_fields', $fields, $scp);

            foreach($fields as $k=>$field) {
                sc_do_field($field);
            }
            ?>
        </div>
    </div>
    <?php
    $vat_enable = get_option('_sc_vat_enable',false);
    if($vat_enable){
        sc_do_vat_info();
    }
}

function personalize_product_info($scp, $order_info) {

    $filter_fields = array(
        array('field'=>'us_alert', 'callback'=>'esc_html'),
        array('field'=>'us_headline', 'callback'=>'esc_html'),
        array('field'=>'us_description', 'callback'=>'esc_html'),
        array('field'=>'us_proceed', 'callback'=>'esc_html'),
        array('field'=>'us_decline', 'callback'=>'esc_html'),
        array('field'=>'thanks_url', 'callback'=>'urlencode'),
    );

    foreach($filter_fields as $filter) {
        $field=$filter['field'];
        //var_dump($scp->$field);
        $scp->$field = sc_personalize($scp->$field, $order_info, $filter['callback']); 
        //var_dump($scp->$field);
    }

    return $scp;
}

if ( sc_fs()->is__premium_only() && sc_fs()->can_use_premium_code()) {

    function sc_orderbumps($post_id) { 

        global $scp;
        
        $show_bump = isset($scp->order_bump);
        $show_bump = apply_filters( 'sc_show_orderbump', $show_bump, $post_id );

        if ($show_bump) {

            $ob_class = '';
                
            if (isset($scp->ob_type) && isset($scp->ob_plan)) {
                $bump = studiocart_plan($scp->ob_plan, $sale='', intval($scp->ob_product));
                $ob_class = ($bump->type && $bump->type == 'recurring') ? 'ob-sub' : '';
            } 
                
            if (isset($scp->ob_replace)) {
                $ob_class .= ' ob-replace'; 
            }

            $args = array(
                'key'       => 'main',
                'bump_id'   => intval($scp->ob_product),
                'cb_id'     => "sc-orderbump",
                'headline'  => $scp->ob_headline ?? '',
                'text'      => $scp->ob_description ?? '',
                'cta'       => $scp->ob_cb_label ?? '',
                'image'     => $scp->ob_image ?? '',
                'image_pos' => $scp->ob_image_pos ?? '',
                'class'     => $ob_class,
                'atts'      => ( isset($scp->ob_image) && !isset($scp->ob_image_pos) ) ? 'has-thumb" style="background-image: url('.esc_attr($scp->ob_image).')' : ''
            );

            do_action('sc_do_orderbump', apply_filters('sc_orderbump_args', $args));
        
        }

        $show_bump = isset($scp->order_bump_options);
        $show_bump = apply_filters( 'sc_show_orderbumps', $show_bump, $post_id );

        if (!$show_bump) return;

        for ($k=0;$k<count($scp->order_bump_options);$k++){
            if(isset($scp->order_bump_options[$k]['order_bump']) && !empty($scp->order_bump_options[$k]['order_bump']) &&isset($scp->order_bump_options[$k]['ob_product'])) {
                $args = array(
                    'key'       => $k,
                    'bump_id'   => intval($scp->order_bump_options[$k]['ob_product']),
                    'cb_id'     => 'sc-orderbump['. $k .']',
                    'headline'  => $scp->order_bump_options[$k]['ob_headline'] ?? '',
                    'text'      => $scp->order_bump_options[$k]['ob_description'] ?? '',
                    'cta'       => $scp->order_bump_options[$k]['ob_cb_label'] ?? '',
                    'image'     => $scp->order_bump_options[$k]['ob_image'] ?? '',
                    'image_pos' => $scp->order_bump_options[$k]['ob_image_pos'] ?? '',
                    'class'     => 'sc_orderbump',
                    'atts'      => ( isset($scp->order_bump_options[$k]['ob_image']) && !isset($scp->order_bump_options[$k]['ob_image_pos']) ) ? 'has-thumb" style="background-image: url('.esc_attr($scp->order_bump_options[$k]['ob_image']).')' : ''
                );
                do_action('sc_do_orderbump', apply_filters('sc_orderbump_args', $args));
            }
        }
    }
}

add_action('sc_do_orderbump', 'sc_do_bump_template', 10);

function sc_do_bump_template($args) {
    ncs_helper()->renderTemplate('order-form/bump', $args);
}

function sc_do_order_summary($post_id) { 

    global $scp, $customer, $intent, $scuid; ?>
    
    <div class="sc-section sc-order-summary">
        <?php if ( sc_fs()->is__premium_only() && sc_fs()->can_use_premium_code() && (isset($scp->order_bump) || isset($scp->order_bump_options) || isset($scp->custom_fields)) ) :?>
                <h3 class="title"><?php esc_html_e("Order Summary", "ncs-cart"); ?></h3>
                <div class="summary-items">
                    <div class="item">
                        <span class="sc-label"><?php echo apply_filters('sc_subtotal_label', esc_html__("Subtotal", "ncs-cart"), $scp->ID); ?>
                        </span> 
                        <span id="subtotal"></span>
                    </div>
                    
                    <?php 
                    if (isset($scp->custom_fields)) {
                        foreach($scp->custom_fields as $field) {
                            if ($field['field_type']=='quantity' && isset($field['qty_price'])) {
                                ?>
                                <div style="display: none" id="row-sc-<?php echo $field['field_id'];?>" class="item addon-item-row">
                                    <span class="sc-label"><?php echo $field['field_label'];?> 
                                        <span class="sc-quantity">x 1</span>
                                    </span>
                                    <span class="ob-price"><?php echo sc_format_price($field['qty_price']);?></span>
                                </div>

                                <?php
                            }
                        }
                    }
                    
                    if (isset($scp->order_bump)) {
                        $ob_id = isset($scp->ob_product) ? intval($scp->ob_product) : 0; 
                        if (isset($scp->ob_type)) {
                            $bump = studiocart_plan($scp->ob_plan, $sale='', $ob_id);
                            $ob_price = esc_html($bump->initial_payment);
                            if ($ob_price == 'free') { $ob_price = 0; }
                        } else {
                            $ob_price = isset($scp->ob_price) ? esc_html($scp->ob_price) : 0;
                        }

                        if($ob_id): ?>
                        <div style="display: none" id="orderbump-item-row" class="item orderbump-item-row">
                            <span class="sc-label"><?php echo get_the_title($ob_id); ?>
                            <?php 
                                if (isset($scp->ob_type) && $bump) {
                                    $bump_text = $bump->text ?? '';
                                    $bump_text = apply_filters('sc_order_summary_bump_text', $bump_text, $post_id, $ob_id);
                                    if($bump_text){
                                        echo '<small>'.wp_specialchars_decode($bump_text, 'ENT_QUOTES').'</small>'; 
                                    }
                                }  ?>
                            </span> 
                            <span class="ob-price" data-price="<?php echo $ob_price; ?>"><?php sc_formatted_price($ob_price); ?></span>
                        </div>
                        <?php endif; 
                    }
    
                    if (isset($scp->order_bump_options)){
                        for ($k=0;$k<count($scp->order_bump_options);$k++):
                            if(isset($scp->order_bump_options[$k]['ob_product'])):
                                $ob_id = intval($scp->order_bump_options[$k]['ob_product']); 
                                if (isset($scp->order_bump_options[$k]['ob_type']) && $scp->order_bump_options[$k]['ob_type'] == 'plan') {
                                    $bump = studiocart_plan($scp->order_bump_options[$k]['ob_plan'], $sale='', $ob_id);
                                    $ob_price = esc_html($bump->initial_payment);
                                } else {
                                    $ob_price = isset($scp->order_bump_options[$k]['ob_price']) ? esc_html($scp->order_bump_options[$k]['ob_price']) : 0;
                                }
                                ?>
                                <div style="display: none" class="item orderbump-item-row orderbump-item-row-<?php echo $ob_id;?>">
                                    <span class="sc-label"><?php echo get_the_title($ob_id); ?></span> 
                                    <span class="ob-price" data-price="<?php echo $ob_price; ?>"><?php sc_formatted_price($ob_price); ?></span>
                                </div>
                            <?php endif;    
                        endfor; 
                    } ?>
                    <div class="item cart-discount" style="display: none">
                        <span class="sc-label"></span>
                        <span class="price"></span>
                    </div>
                    <?php if ($scp->product_taxable) { ?>
                        <div class="item tax" style="display: none">
                            <span class="sc-label"><?php esc_html_e("Tax", "ncs-cart"); ?></span>
                            <span class="price"></span>
                        </div>
                        <div class="item redeem-tax" style="display: none">
                            <span class="sc-label"><?php esc_html_e(get_option('_sc_vat_reverse_charge',"VAT Reversal"), "ncs-cart"); ?></span>
                            <span class="price"></span>
                        </div>
                    <?php } ?>
                </div> <!-- /.summary-items -->
        <?php else: ?>
            <h3 class="title"><?php esc_html_e("Order Total", "ncs-cart"); ?></h3>
        <?php endif; ?>
        <div class="row">
          <div class="form-group col-sm-12">
             <div class="total">
                 <?php if ($scp->single_plan) {
                        esc_html_e("Amount Due", "ncs-cart");
                    } else {
                        esc_html_e("Due Today", "ncs-cart"); 
                    } ?> 
                 <span class="price"></span>
                 <small></small>
             </div>
          </div>
        </div>
        <div class="row">   
          <div class="form-group col-sm-12">
            <?php $terms = $scp->terms_url;
                $privacy = $scp->privacy_url;
            if( $terms || $privacy || ( $scp->show_optin_cb ) ): ?>
                <div id="sc-terms">
            <?php endif; ?>
                    
                <?php 
                $class = ( isset($_POST["sc_errors"]['_sc_accept_terms']) ) ? 'invalid' : '';
                // terms and conditions
                if( $terms ): ?>
                    <?php $terms_text = sprintf(esc_html__("I have read and I accept the %sterms and conditions%s", 'ncs-cart'), '<a href="'. $terms .'" target="_blank" rel="noopener noreferrer">', '</a> <span class="req">*</span>'); ?>

                    <div class="checkbox-wrap <?php echo $class; ?>">
                    <label>
                        <input type="checkbox" class="required" id="sc_accept_terms" name="sc_accept_terms" value="yes"> 
                        <span class="item-name"><?php echo apply_filters('sc_checkout_page_terms_text', $terms_text, $scp); ?></span>
                    </label>
                    </div>
                    <?php if (isset($_POST["sc_errors"]["sc_accept_terms"])) : ?>
                         <div class="error"><?php esc_html_e( $_POST["sc_errors"]["sc_accept_terms"], 'ncs-cart' ); ?></div>
                    <?php endif; ?>
                <?php endif; ?>
                    
                <?php
                // privacy policy
                if( $privacy ): ?>
                    <?php $privacy_text = sprintf(esc_html__("I have read and I accept the %sprivacy policy%s", 'ncs-cart'), '<a href="'. $privacy .'" target="_blank" rel="noopener noreferrer">', '</a> <span class="req">*</span>'); ?>
                    <div class="checkbox-wrap <?php echo $class; ?>">
                        <label>
                            <input type="checkbox" class="required" id="sc_accept_privacy" name="sc_accept_privacy" value="yes"> 
                            <span class="item-name"><?php echo apply_filters('sc_checkout_page_privacy_text', $privacy_text, $scp); ?></span>
                        </label>
                    </div>
                    <?php if (isset($_POST["sc_errors"]["sc_accept_privacy"])) : ?>
                         <div class="error"><?php esc_html_e( $_POST["sc_errors"]["sc_accept_privacy"], 'ncs-cart' ); ?></div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php 
                // consent
                if( $scp->show_optin_cb ): ?>
                    <?php  
                    $scp->optin_required = isset($scp->optin_required);
                    $required = apply_filters('sc_consent_required', $scp->optin_required, $scp); ?>
                    <div class="checkbox-wrap <?php echo $class; ?>">
                        <label>
                            <input type="checkbox" id="sc_consent" name="sc_consent" value="yes" <?php if ($required) { echo 'class="required"'; } ?> > 
                            <span class="item-name"><?php echo wp_specialchars_decode( $scp->optin_checkbox_text, 'ENT_QUOTES' ); ?>
                            <?php if ($required) { echo '<span class="req">*</span>'; } ?>
                            </span>
                        </label>
                    </div>
                <?php endif; ?>
                    
            <?php if( $terms || $privacy || ( $scp->show_optin_cb ) ): ?>
                </div>
            <?php endif; ?>

            <?php 
                do_action('sc_before_buy_button', $scp);
            ?>            
              
            <button id="sc_card_button" data-form-wrapper="sc-payment-form-<?php echo $scp->ID; ?>-<?php echo $scuid; ?>" type="button" class="btn btn-primary btn-block">
                <?php if(!isset($scp->button_subtext)): ?>
                <svg class="spinner" width="24" height="24" viewBox="0 0 24 24">
                    <g fill="none" fill-rule="nonzero">
                        <path class="ring_thumb" fill="#FCECEA" d="M17.945 3.958A9.955 9.955 0 0 0 12 2c-2.19 0-4.217.705-5.865 1.9L5.131 2.16A11.945 11.945 0 0 1 12 0c2.59 0 4.99.82 6.95 2.217l-1.005 1.741z"></path>
                        <path class="ring_track" fill="#FCECEA" d="M5.13 2.16L6.136 3.9A9.987 9.987 0 0 0 2 12c0 5.523 4.477 10 10 10s10-4.477 10-10a9.986 9.986 0 0 0-4.055-8.042l1.006-1.741A11.985 11.985 0 0 1 24 12c0 6.627-5.373 12-12 12S0 18.627 0 12c0-4.073 2.029-7.671 5.13-9.84z" style="opacity: 0.35"></path>
                    </g>
                </svg>
                <?php endif; ?>
                <span class="text">
                    <?php if($scp->button_icon && $scp->button_icon_pos != 'right'): ?>
                        <?php echo wp_specialchars_decode($scp->button_icon, 'ENT_QUOTES'); ?>
                    <?php endif; ?>
                    
                    <?php echo esc_html($scp->button_text); ?>
                    
                    <?php if($scp->button_icon && $scp->button_icon_pos == 'right'): ?>
                        <?php echo wp_specialchars_decode($scp->button_icon, 'ENT_QUOTES' ); ?>
                    <?php endif; ?>
                </span>

                <?php if(isset($scp->button_subtext)): ?>
                    <span class="sub-text"><?php echo esc_html($scp->button_subtext); ?></span>
                <?php endif; ?>
            </button>
            <?php do_action('sc_after_buy_button', $post_id); ?>              
          </div>
        </div>
    </div>
      <?php /*if(isset($_REQUEST['sc-method-change']) && $_REQUEST['sc-method-change'] !=""){ ?>
            
    <?php }*/ ?>
<?php
}

function sc_do_2step_checkout_form_open($post_id) {
    global $scp;
    global $scuid;
    
    $scuid = uniqid();

    if (!$scp || !isset($scp->ID)) {
        $scp = sc_setup_product($post_id);
    }
    $action = esc_attr($scp->form_action);
    ?>
    <div id="sc-payment-form-<?php echo $scp->ID.'-'.$scuid; ?>" class="sc-form-wrap">
        <div class="sc-embed-checkout-form-nav sc-border-none ">
            <ul class="sc-checkout-form-steps">
                <div class="steps step-one sc-current">
                    <a href="#">
                        <div class="step-number">1</div>
                        <div class="step-heading">
                            <div class="step-name"><?php echo esc_html($scp->twostep_heading_1); ?></div>
                            <div class="step-sub-name"><?php echo esc_html($scp->twostep_subhead_1); ?></div>
                        </div>
                    </a>
                </div>
                <div class="steps step-two">
                    <a href="#">
                        <div class="step-number">2</div>
                        <div class="step-heading">
                            <div class="step-name"><?php echo esc_html($scp->twostep_heading_2); ?></div>
                            <div class="step-sub-name"><?php echo esc_html($scp->twostep_subhead_2); ?></div>
                        </div>
                    </a>
                </div>
            </ul>
        </div>

        <form id="sc-payment-form" class="sc-2step-wrapper step-1" action="<?php echo $action; ?>" method="post">
        
<?php
}

function sc_step_wrappers_1(){
    echo '<div id="customer-details" class="sc-section sc-checkout-step">';
}

function sc_step_wrappers_2(){ 
    global $scp, $scuid; ?>
    <div class="row">   
      <div class="form-group col-sm-12">                          
        <button type="button" class="btn btn-primary btn-block sc-next-btn" data-form-wrapper="sc-payment-form-<?php echo $scp->ID; ?>-<?php echo $scuid; ?>">
            <span class="text">
                <?php if(isset($scp->step1_button_icon) && $scp->step1_button_icon_pos != 'right'): ?>
                    <?php echo wp_specialchars_decode($scp->step1_button_icon, 'ENT_QUOTES'); ?>
                <?php endif; ?>

                <?php echo esc_html($scp->step1_button_label); ?>

                <?php if(isset($scp->step1_button_icon) && $scp->step1_button_icon_pos == 'right'): ?>
                    <?php echo wp_specialchars_decode($scp->step1_button_icon, 'ENT_QUOTES' ); ?>
                <?php endif; ?>
            </span>
            
            <?php if(isset($scp->step1_button_subtext)): ?>
                <span class="sub-text"><?php echo esc_html($scp->step1_button_subtext); ?></span>
            <?php endif; ?>
        </button>
        <?php do_action('sc_after_step_1_button'); ?>
      </div>
    </div>
    </div><div id="billing-details" class="sc-checkout-step">
<?php
}

function sc_step_wrappers_3(){
    echo '</div>';
}

function sc_do_checkout_form_scripts($prod_id, $coupon = false) {
    global $scp;
    
    if ( sc_fs()->is__premium_only() && sc_fs()->can_use_premium_code() ) {
        if (!$coupon) {
            $coup_url = false;
            if ($coup_url_var = get_option( '_sc_coupon_url')){
                if(isset($_GET[$coup_url_var])){
                    $coup_url = sanitize_text_field($_GET[$coup_url_var]);
                }
            } else if (get_query_var('coupon')) {
                $coup_url = sanitize_text_field(get_query_var('coupon'));
            } 

            if($coup_url) {
                $coupons = $scp->coupons;

                foreach ($coupons as $c) {
                    if ( strtolower($c['url']) == strtolower($coup_url) ) {
                        $coupon = $c['code'];
                        break;
                    }
                }
            }
        }
    }
    
    ?>
    <script type="text/javascript">
        
    <?php 
    $coupon = apply_filters('sc_checkout_coupon', $coupon, $prod_id);
    if ($coupon) : ?>
        var sc_coupon = ["<?php echo sanitize_text_field($coupon); ?>"];
    <?php endif; ?>

    jQuery('document').ready(function($){
    <?php 

    // conversion tracking
    if (!current_user_can( 'manage_options' )) : ?>
        var view_data = {
            'action': 'sc_set_form_views',
            'page_id': '<?php the_ID(); ?>',
            'prod_id': '<?php echo $prod_id; ?>',
            'url': '<?php echo $_SERVER['REQUEST_URI']; ?>',
        };
        $.post(studiocart.ajax, view_data);
    <?php endif; ?>
    });    
</script>
<?php
}