<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once plugin_dir_path( __FILE__ ) . 'template-functions.php';

global $scp;

$prod_id		= $scp->ID;
if(get_post_type($prod_id)!='sc_product') {
    return;
}
$cart_closed 	= sc_is_cart_closed();
$show_confirm 	= ( isset($_GET['sc-order']) && $_GET['sc-order'] > 0 ) ? true : false;
$orderID 		= ($show_confirm) ? $_GET['sc-order'] : false;

$method_change  = ( isset($_GET['sc-method-change']) && $_GET['sc-method-change'] > 0 ) ? true : false;
$orderID        = ($method_change) ? $_GET['sc-method-change'] : false;
$hide_labels    = isset($scp->hide_labels);
    
if (isset($scp->show_optin)) {
    unset($scp->upsell,$scp->downsell,$scp->order_bump,$scp->order_bump_options,$scp->show_coupon_field);
}

add_action('sc_payment_method_change', 'sc_do_payment_method_change', 10);

add_action('sc_payment_confirmation', 'sc_do_payment_confirmation', 10);
add_action('sc_checkout_page_heading', 'sc_do_error_messages', 15);
add_action('sc_checkout_form_scripts', 'sc_do_checkout_form_scripts', 10, 2);

add_action('sc_checkout_form', 'sc_do_checkout_form', 10, 2);
add_action('sc_card_details_fields', 'sc_do_card_details_fields', 10, 2); 
add_action('sc_before_payment_info', 'sc_do_test_mode_message', 10);
add_action('sc_order_summary', 'sc_do_order_summary', 10);

if (!isset($scp->show_2_step)) {
    add_action('sc_checkout_form_open', 'sc_do_checkout_form_open', 10);
    add_action('sc_card_details_fields', 'sc_payment_plan_options', 1); 
    add_action('sc_card_details_fields', 'sc_do_checkoutform_fields', 5, 2); 
    add_action('sc_checkout_form_close', 'sc_do_checkout_form_close', 10);

    if (isset($scp->show_address_fields)) {
        add_action('sc_checkout_form_fields', 'sc_address_fields', 10, 2);
    }

} else {
    add_action('sc_checkout_form_open', 'sc_do_2step_checkout_form_open', 10);
    add_action('sc_card_details_fields', 'sc_step_wrappers_1', 1); 
    add_action('sc_card_details_fields', 'sc_do_2step_checkoutform_fields', 1, 2); 

    if (isset($scp->show_address_fields)) {
        add_action('sc_card_details_fields', 'sc_address_fields', 1, 2);
    }

    add_action('sc_card_details_fields', 'sc_step_wrappers_2', 1); 
    add_action('sc_card_details_fields', 'sc_payment_plan_options', 5); 
    add_action('sc_checkout_form_close', 'sc_step_wrappers_3', 5); 
    add_action('sc_checkout_form_close', 'sc_do_checkout_form_close', 10);
}
?>

<!DOCTYPE html>

<html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <?php if ( ! current_theme_supports( 'title-tag' ) ) : ?>
            <title><?php echo wp_get_document_title(); ?></title>
        <?php endif; ?>
        <?php wp_head(); ?>
        <style type="text/css">
            .studiocart .btn-block,
            .studiocart input[type="button"],
            body.single-sc_product .container .sc-embed-checkout-form-nav .sc-checkout-form-steps .steps.sc-current a .step-number {
                background-color: <?php echo $scp->button_color; ?>
            }
            
            <?php
            $show_bump = isset($scp->order_bump_options);
            $show_bump = apply_filters( 'sc_show_orderbump', $show_bump, $post_id );
            if ($show_bump) {
                for ($k=0;$k<count($scp->order_bump_options);$k++){
                    if($scp->order_bump_options[$k]['bump_bg_color']) { ?>
                        .studiocart-page .container #sc-payment-form #sc-orderbump-<?php echo $k; ?>.sc-section.orderbump {
                            background-color: <?php echo $scp->order_bump_options[$k]['bump_bg_color']; ?>
                        }
                        <?php
                    }
                }
            }
            ?>

            .studiocart .sc-checkout-form-steps .steps.sc-current a .step-heading .step-name {
                color: <?php echo $scp->button_color; ?>
            }

            <?php if ($scp->header_color): ?>
            .sc-hero-banner {
                background-color: <?php echo $scp->header_color; ?>
            }
            <?php endif; ?>
        </style>
    </head>

    <body <?php body_class('sc-checkout-1'); ?>>

        <?php while ( have_posts() ) : the_post(); ?>

            <div class="sc-hero-banner" <?php if (isset($scp->header_image)): ?>style="background-image: url('<?php echo esc_html($scp->header_image); ?>');"<?php endif; ?>>
                <div class="container">
                    <?php if (!isset($scp->hide_title)): ?>
                    <h2><?php echo sc_get_public_product_name(); ?></h2>
                    <?php endif; ?>
                </div>
            </div>

            <main class="studiocart-page payment-page <?php echo ( $show_confirm || $cart_closed ) ? 'page-closed' : '' ?>">
                <div class="container">
                <?php if ( $show_confirm || $cart_closed ) : ?>
                    <?php do_action('sc_payment_confirmation', $prod_id); ?>
                 <?php elseif ( $method_change || $cart_closed ) : ?>
                       <div class="main-content">
                            <?php the_content(); ?>   
                        </div>
                       <section id="sc-form-container" class="studiocart">
                        <?php
                            do_action('sc_checkout_page_heading', $prod_id); 
                            do_action('sc_checkout_form_open', $prod_id);
                            do_action('sc_checkout_form', $prod_id, $hide_labels);
                            do_action('sc_checkout_form_close');
                            do_action('sc_payment_method_change', $prod_id);
                        ?> 
                       
                    </section>
                <?php else: ?>

                    <div class="main-content">
                        <?php the_content(); ?>   
                    </div>

                    <section id="sc-form-container" class="studiocart">
                        <?php
                        do_action('sc_checkout_page_heading', $prod_id); 
                        do_action('sc_checkout_form_open', $prod_id);
                        do_action('sc_checkout_form', $prod_id, $hide_labels);
                        do_action('sc_checkout_form_close');
                        ?>	
                    </section>
                <?php endif; ?>

                </div>
            </main>

        <?php endwhile; ?>

        <?php if ( !$show_confirm && !$cart_closed) {
            do_action('sc_checkout_form_scripts', $prod_id);
    
            if (isset($scp->show_2_step)): ?>
            <script type="text/javascript">
                jQuery(document).ready(function($){
                    $('#sc-payment-form').on("studiocart/orderform/lead_captured", function() { 
                        <?php echo wp_specialchars_decode($scp->tracking_lead, 'ENT_QUOTES'); ?>
                    }); 
                });
            </script>
            <?php endif;
        }
        
        wp_footer(); 
        ?>

    </body>
</html>