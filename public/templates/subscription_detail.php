<div class="ncs-my-account ncs-my-subscription-page">
    <?php if ($link = get_option('_my_account')): ?>
    <p style="margin: 0 0 20px;"><a href="<?php echo get_permalink($link); ?>">&larr; <?php esc_html_e('Back', 'ncs-cart'); ?></a></p>
    <?php endif; ?>
    <div class="ncs-account-subscription ">
        <?php if(isset($_REQUEST['sc-plan'])){
            $post_id = intval($_REQUEST['sc-plan']);
            if (get_post_meta($post_id, '_sc_main_product_sub', true) || get_post_meta($post_id, '_sc_order_bump_subs', true)){
                $order = sc_setup_order($post_id);                
            } else { // for subs prior to v2.3 
                $order = new ScrtSubscription($post_id);
                $order = (object) $order->get_data();
            }
            if( 'sc_subscription' == get_post_type($post_id) ){
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
                    <?php
                    if(isset($order->main_product_sub) && $order->main_product_sub) :  // Deprecated
                        if($sub = $order->main_product_sub): ?>
                        <h3><?php echo get_the_title($product_id); ?></h3>  
                        <p><?php echo $sub['plan']->name; ?> - 
                            <?php echo sc_formatted_price($sub['plan']->price); ?> / 
                            <?php if($sub['plan']->frequency > 1) {
                                echo esc_html($sub['plan']->frequency . ' ' . sc_pluralize_interval($sub['plan']->interval));
                            } else {
                                echo esc_html($sub['plan']->interval);
                            } 
                            if($order->sub_installments == '-1') { 
                                if(!in_array(get_post_status($post_id),['pending','pending-payment','initiated', 'canceled']) && !$order->cancel_date) { ?> | 
                                        <a id="sc_cancel_sub" class="sc_cancel_sub" href="javascript:void(0);" data-id="<?php echo $post_id; ?>" data-item-id="<?php echo apply_filters('sc_sub_item_id',$sub['item_id'],$sub,$order); ?>"><?php esc_html_e( 'Cancel', 'ncs-cart' ); ?></a>
                                <?php }
                            } ?>
                        </p>
                        <?php endif; ?>
                    <?php elseif($order->sub_payment): ?>
                        <h3><?php echo $order->product_name; ?></h3>  
                        <p><?php echo $order->sub_item_name; ?> - 
                            <?php echo $order->sub_payment; ?>
                            <?php if($order->sub_installments == '-1') { 
                                if(!in_array(get_post_status($post_id),['pending','pending-payment','initiated', 'canceled']) && !$order->cancel_date) { ?> | 
                                        <a id="sc_cancel_sub" class="sc_cancel_sub" href="javascript:void(0);"><?php esc_html_e( 'Cancel', 'ncs-cart' ); ?></a>
                                <?php }
                            } ?>
                        </p>
                    <?php endif; ?>
                    <?php if (isset($order->order_bump_subs)): ?>
                        <?php foreach($order->order_bump_subs as $k=>$bump): ?>
                            <h3><?php echo 'Order Bump'; ?></h3>  
                            <p><?php echo $bump['name']; ?> - 
                                <?php echo sc_formatted_price($bump['plan']->price); ?> / 
                                <?php 
                                if($bump['plan']->frequency > 1) {
                                    echo esc_html($bump['plan']->frequency . ' ' . sc_pluralize_interval($bump['plan']->interval));
                                } else {
                                    echo esc_html($bump['plan']->interval);
                                } 
                                if($order->sub_installments == '-1') { 
                                   
                                    if(isset($bump['status'])){
                                        //echo $bump['status'];
                                    }
                                    if(!isset($bump['status']) && !in_array(get_post_status($post_id),['pending','pending-payment','initiated', 'canceled']) && !isset($order->cancel_request_date)) { ?> | 
                                            <a id="sc_cancel_sub" class="sc_cancel_sub" href="javascript:void(0);" data-id="<?php echo $post_id; ?>" data-item-id="<?php echo apply_filters('sc_sub_item_id',$bump['item_id'],$bump,$order); ?>"><?php esc_html_e( 'Cancel', 'ncs-cart' ); ?></a>
                                    <?php } 
                                } ?>
                            </p>
                            <table class="ncs-subscription-table" cellpadding="6">
                                <tr>
                                    <td width="200"><?php echo __('Status', 'ncs-cart'); ?></td>
                                    <td><?php 
                                    if(isset($bump['status'])){
                                        echo ucwords($bump['status']); 
                                    }else{
                                        echo ucwords($order->status); 
                                    }
                                   
                                    
                                    ?></td>
                                </tr>
                            </table>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <h3 class="ncs-account-title"><?php esc_html_e( 'Details', 'ncs-cart' ); ?></h3>
                    <table class="ncs-subscription-table" cellpadding="6">
                        
                        <tr>
                            <td width="200"><?php echo __('Start Date', 'ncs-cart'); ?></td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($order->start_date)); ?></td>
                        </tr>
                        <?php if($order->sub_end_date): ?>
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
                            if($order->status == 'canceled' || $order->cancel_date) {                                 
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
                    <br>
                    <h3 class="ncs-account-title"><?php esc_html_e( 'Invoices', 'ncs-cart' ); ?></h3>
                    <?php
                    // The Query
                    $args = array(
                        'post_type' => array( 'sc_order' ),
                        'orderby' => 'date',
                        'order'   => 'DESC',
                        'meta_query'=>array(
                            array(
                                'key' => '_sc_subscription_id',
                                'value' => $post_id,
                            ),
                        ),
                    );
                    $the_query = new WP_Query( $args );
                    if ( $the_query->have_posts() ) { ?>
                        <table class="ncs-subscription-table" border="0">
                            <?php
                            while ( $the_query->have_posts() ) {
                                $the_query->the_post(); ?>
                                <tr>
                                    <td><?php echo get_the_date('F d, Y', get_the_ID()); ?></td>
                                    <td width="200"><a href="<?php echo home_url(); ?>?sc-invoice=<?php echo get_the_ID(); ?>&dl=1"><?php esc_html_e( 'Download', 'ncs-cart' ); ?></a></td>
                                </tr>
                            <?php   } ?>
                        </table>
                    <?php } else {
                        esc_html_e( 'No orders found.', 'ncs-cart' );
                    }
                    /* Restore original Post Data */
                    wp_reset_postdata(); ?>
                </div>
            <?php } else {
                esc_html_e( 'No subscription found.', 'ncs-cart' );
            } ?>
        <?php } else {
            esc_html_e( 'No subscription found.', 'ncs-cart' );
        }?>
    </div>
</div>