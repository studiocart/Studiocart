<div class="ncs-my-account ncs-my-subscription-page">
    <?php if ($link = get_option('_sc_myaccount_page_id')): ?>
    <p style="margin: 0 0 20px;"><a href="<?php echo get_permalink($link); ?>">&larr; <?php esc_html_e('Back', 'ncs-cart'); ?></a></p>
    <?php endif; ?>
    <div class="ncs-account-subscription ">
        <?php if(isset($_REQUEST['sc-plan'])){
            $post_id = intval($_REQUEST['sc-plan']);
            $sub = new ScrtSubscription($post_id);
            $order = (object) $sub->get_data();
          
            if( 'sc_subscription' == get_post_type($post_id) ){
                    
                $is_cancellable = apply_filters('sc_is_sub_type_valid_for_cancel', $order->sub_installments == '-1', $order);
                $cancellable_statuses = apply_filters('sc_valid_sub_statuses_for_cancel', array('trialing','active','paused'), $order);
                $update_statuses = apply_filters('sc_valid_sub_statuses_for_update', array('trialing','active','paused', 'past_due', 'unpaid', 'incomplete'), $order);
                $show_cancel = ($is_cancellable && in_array($order->status,$cancellable_statuses) && !isset($order->cancel_request_date));
                
                $is_pausable = apply_filters('sc_is_sub_type_valid_for_pause_restart', $order->sub_installments == '-1', $order);
                $pausable_statuses = apply_filters('sc_valid_sub_statuses_for_pause_restart', array('trialing','active','paused'), $order);
                $show_pause = ($is_pausable && in_array($order->status,$pausable_statuses) && !isset($order->cancel_request_date));
                
                $product_id = $order->product_id; 
                if (!is_numeric($order->sub_next_bill_date)) {
                    $order->sub_next_bill_date = strtotime($order->sub_next_bill_date);
                }
                $next = date_i18n(get_option('date_format'), $order->sub_next_bill_date);
                ?>
                <div id="subscription-all"> 
                    <input type="hidden" id="sc_nonce" value="<?php echo wp_create_nonce('sc-ajax-nonce'); ?>">
                    <input type="hidden" name="sc_payment_intent" id="sc_payment_intent" value="<?php echo $order->subscription_id; ?>">
                    <input type="hidden" id="sc_payment_method" name="sc_payment_method" value="<?php echo $order->pay_method; ?>">
                    <?php if($order->sub_payment): ?>
                        <h3><?php echo $order->product_name; ?></h3>  
                        <p><?php echo $order->sub_item_name; ?> - 
                            <?php echo $order->sub_payment; ?>
                            <?php if($show_cancel):  ?> | 
                                <a id="sc_cancel_sub" title="<?php esc_html_e( 'Cancel Subscription', 'ncs-cart' ); ?>" class="sc_cancel_sub" href="javascript:void(0);" data-id="<?php echo $post_id; ?>"><?php esc_html_e( 'Cancel', 'ncs-cart' ); ?></a>
                            <?php endif; ?>
                            <?php if($show_pause):  ?>
                                <?php if($order->status != 'paused'):?>
                                     | <a id="sc_pause_sub" title="<?php esc_html_e( 'Pause Subscription', 'ncs-cart' ); ?>" class="sc_pause_restart_sub" href="javascript:void(0);" data-action="paused" data-id="<?php echo $post_id; ?>"><?php esc_html_e( 'Pause', 'ncs-cart' ); ?></a>
                                <?php else:?>
                                     | <a id="sc_pause_sub" title="<?php esc_html_e( 'Restart Subscription', 'ncs-cart' ); ?>" class="sc_pause_restart_sub" href="javascript:void(0);" data-action="started" data-id="<?php echo $post_id; ?>"><?php esc_html_e( 'Resume', 'ncs-cart' ); ?></a>
                                <?php endif;?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <?php if(in_array($order->status,$update_statuses)):?>
                        <?php do_action('sc_show_'.$order->pay_method.'_payment_method',$order);?>
                    <?php endif;?>
                    
                    <h3 class="ncs-account-title"><?php esc_html_e( 'Details', 'ncs-cart' ); ?></h3>
                    <table class="ncs-subscription-table" cellpadding="6">
                        <?php if(isset($order->start_date) && $order->start_date): ?>
                        <tr>
                            <td width="200"><?php echo __('Start Date', 'ncs-cart'); ?></td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($order->start_date)); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if(isset($order->sub_end_date) && $order->sub_end_date): ?>
                            <tr>
                                <td><?php echo __('End Date ', 'ncs-cart'); ?></td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($order->sub_end_date)); ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td><?php echo __('Next Payment', 'ncs-cart'); ?></td>
                            <td>
                            <?php
                            if (!is_numeric($order->sub_next_bill_date)) {
                                $order->sub_next_bill_date = strtotime($order->sub_next_bill_date);
                            }
                            $next = date_i18n(get_option('date_format'), $order->sub_next_bill_date);
                            if($order->status == 'paused' || $order->status == 'canceled' || $order->cancel_date) {                                 
                                echo '--';
                            } else {
                                echo $next;
                            }
                            ?>
                            </td>
                        </tr>
                        <?php if ($order->cancel_date && $order->status != 'canceled' && $next != '--' ) : ?>
                        <tr>
                            <td><?php echo __('Cancels on', 'ncs-cart'); ?></td>
                            <td><?php echo $next; ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                    <?php if ( sc_fs()->is__premium_only() && sc_fs()->can_use_premium_code()): ?>
                    <br>
                    <h3 class="ncs-account-title"><?php esc_html_e( 'Invoices', 'ncs-cart' ); ?></h3>
                    <?php
                    // The Query
                    $orders = $sub->orders();
                    if ( $orders ) { ?>
                        <table class="ncs-subscription-table" border="0">
                            <?php
                            foreach ( $orders as $suborder ) { ?>
                                <tr>
                                    <td><?php echo get_the_date('F d, Y', $suborder->id); ?></td>
                                    <td><?php echo $suborder->invoice_link_html(); ?></td>
                                </tr>
                            <?php   } ?>
                        </table>
                    <?php } else {
                        esc_html_e( 'No orders found.', 'ncs-cart' );
                    }
                    /* Restore original Post Data */
                    wp_reset_postdata(); endif; ?>
                </div>
            <?php } else {
                esc_html_e( 'No subscription found.', 'ncs-cart' );
            } ?>
        <?php } else {
            esc_html_e( 'No subscription found.', 'ncs-cart' );
        }?>
    </div>
</div>

<!-- Update Payment Method -->
<?php 
    if($order->pay_method == 'stripe'){
        ncs_template('my-account/forms/change-card');
    }
?>
<!--/ Update Payment Method -->