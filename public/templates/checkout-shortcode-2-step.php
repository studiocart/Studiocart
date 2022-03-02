<?php

require_once plugin_dir_path( __FILE__ ) . 'template-functions.php';

global $sc_currency_symbol;

if(isset($product_id)){
    $scp = sc_setup_product( $product_id );
	$cart_closed = sc_is_cart_closed();
} 

add_action('sc_closed_message', 'sc_do_cart_closed_message');
add_action('sc_checkout_form_scripts', 'sc_do_checkout_form_scripts',10,2);
add_action('sc_checkout_page_heading', 'sc_do_error_messages', 15);
add_action('sc_checkout_form_open', 'sc_do_2step_checkout_form_open', 10);
add_action('sc_checkout_form', 'sc_do_checkout_form', 10, 2);
add_action('sc_card_details_fields', 'sc_step_wrappers_1', 1); 
add_action('sc_card_details_fields', 'sc_do_2step_checkoutform_fields', 1, 2); 

if (isset($scp->show_address_fields)) {
add_action('sc_card_details_fields', 'sc_address_fields', 1, 2);
}

add_action('sc_card_details_fields', 'sc_step_wrappers_2', 1); 
add_action('sc_card_details_fields', 'sc_payment_plan_options', 5); 
add_action('sc_card_details_fields', 'sc_do_card_details_fields', 10, 2);
add_action('sc_before_payment_info', 'sc_do_test_mode_message', 10);
add_action('sc_order_summary', 'sc_do_order_summary', 10);
add_action('sc_checkout_form_close', 'sc_step_wrappers_3', 5); 
add_action('sc_checkout_form_close', 'sc_do_checkout_form_close', 10);

if (!$builder) : ?>

<style type="text/css">
    .studiocart button,
    .studiocart .btn-block {
        background-color: <?php echo $scp->button_color; ?>
    }
    
    .studiocart .sc-checkout-form-steps .steps.sc-current {
        border-color: <?php echo $scp->button_color; ?>;
    }
    .studiocart .sc-checkout-form-steps .steps a {
        color: <?php echo $scp->button_color; ?>
    }
    
    <?php
    $show_bump = isset($scp->order_bump_options);
    $show_bump = apply_filters( 'sc_show_orderbump', $show_bump, $post_id );
    if ($show_bump) {
        for ($k=0;$k<count($scp->order_bump_options);$k++){
            if($scp->order_bump_options[$k]['bump_bg_color']) { ?>
                .studiocart #sc-payment-form #sc-orderbump-<?php echo $k; ?>.sc-section.orderbump {
                    background-color: <?php echo $scp->order_bump_options[$k]['bump_bg_color']; ?>
                }
                .studiocart #sc-payment-form #sc-orderbump-<?php echo $k; ?>.orderbump .title {
                    background: transparent;        
                }
                .studiocart #sc-payment-form #sc-orderbump-<?php echo $k; ?>.orderbump {
                    border: none;
                }
                <?php
            }
        }
    }
    ?>
    
</style>
<?php endif; ?>
<section id="sc-form-container" class="studiocart scshortcode">
  <div class="container"> 
	
	<?php 
	if ($cart_closed) {
		if ( $scp->cart_close_action == 'redirect' ) {
			$redirect = $scp->cart_redirect;
			echo '<script type="text/javascript">window.location.replace("'.$redirect.'");</script>';
		} else {
			do_action('sc_closed_message', $product_id);
		}
		
	} else {
        do_action('sc_checkout_page_heading', $product_id);
		do_action('sc_checkout_form_open', $product_id);
        do_action('sc_checkout_form', $product_id, $hide_labels);
        do_action('sc_checkout_form_close');	
	} ?>
	  
  </div>
</section>

<?php do_action('sc_checkout_form_scripts', $product_id, $coupon); ?>
<?php if(isset($scp->tracking_lead)): ?>
<script type="text/javascript">
    jQuery(document).ready(function($){
        $('#sc-payment-form').on("studiocart/orderform/lead_captured", function() { 
            <?php echo wp_specialchars_decode($scp->tracking_lead, 'ENT_QUOTES'); ?>
        }); 
    });
</script>
<?php endif; ?>