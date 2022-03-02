<div class="ncs-my-account">
    <?php 
    $subscriptions = sc_get_user_subscriptions(get_current_user_id(), $status='any');
    $subscription_trialing = sc_get_user_subscriptions(get_current_user_id(), $status='trialing');
    $subscription_active = sc_get_user_subscriptions(get_current_user_id(), $status='active');
    //$subscription_pending = sc_get_user_subscriptions(get_current_user_id(), $status='pending');
    $subscription_pastdue = sc_get_user_subscriptions(get_current_user_id(), $status='past_due');
    $subscription_canceled = sc_get_user_subscriptions(get_current_user_id(), $status='canceled'); 

    $sub_tables = array(); 

    if ($subscriptions): ?>
    <div class="ncs-account-list ncs-account-subscription">
        <h3 class="ncs-account-title">Subscriptions</h3>
        <div class="ncs-account-tabs">            
            <ul class="ncs-nav-tabs">
                <?php            
                if($subscriptions) {
                    echo '<li><a href="#subscription-all" class="active">All</a></li>';
                    $sub_tables['all'] = $subscriptions;
                }
                if($subscription_active) {
                    echo '<li><a href="#subscription-active">Active</a></li>';
                    $sub_tables['active'] = $subscription_active;
                }
                if($subscription_trialing) {
                    echo '<li><a href="#subscription-trialing">Trialing</a></li>';
                    $sub_tables['trialing'] = $subscription_trialing;
                }
                /*if($subscription_pending) {
                    echo '<li><a href="#subscription-pending">Pending</a></li>';
                    $sub_tables['pending'] = $subscription_pending;
                }*/
                if($subscription_pastdue) {
                    echo '<li><a href="#subscription-past_due">Past Due</a></li>';
                    $sub_tables['past_due'] = $subscription_pastdue;
                }
                if($subscription_canceled) {
                    echo '<li><a href="#subscription-canceled">Canceled</a></li>';
                    $sub_tables['canceled'] = $subscription_canceled;
                }
                ?>
            </ul>
            
            <?php foreach($sub_tables as $type=>$subscriptions) : ?>    
                <div id="subscription-<?php echo $type; ?>" class="ncs-account-tab-pane">               
                    <table class="ncs-account-table" cellpadding="0" cellspacing="0">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('ID', 'ncs-cart'); ?></th>
                                <th><?php esc_html_e('Product', 'ncs-cart'); ?></th>
                                <th><?php esc_html_e('Status', 'ncs-cart'); ?></th>
                                <th><?php esc_html_e('Next Payment', 'ncs-cart'); ?></th>
                                <th><?php esc_html_e('Price', 'ncs-cart'); ?></th>
                                <th></th>
                            </tr>       
                        </thead>
                        <tbody>                  
                            <?php if($subscriptions){
                                    foreach($subscriptions as $subscription) {
                                        sc_do_subscription_row($subscription);
                                    } 
                                } else { ?>
                                    <tr><td colspan="6"><?php esc_html_e('No records found', 'ncs-cart'); ?></td></tr>
                              <?php } ?>                         
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php 
    $subscriptions = sc_get_user_subscriptions(get_current_user_id(), $status='any',$type='installment');
    //$subscription_pending = sc_get_user_subscriptions(get_current_user_id(), $status='pending',0);
    $subscription_active = sc_get_user_subscriptions(get_current_user_id(), $status='active',$type='installment');
    $subscription_canceled = sc_get_user_subscriptions(get_current_user_id(), $status='completed',$type='installment'); 
    $subscription_pastdue = sc_get_user_subscriptions(get_current_user_id(), $status='past_due',$type='installment');

    $sub_tables = array(); 
    
    if ($subscriptions): ?>
    <div class="ncs-account-list ncs-account-payment-plans">
        <h3 class="ncs-account-title"><?php esc_html_e('Payment Plans', 'ncs-cart'); ?></h3>
        <div class="ncs-account-tabs">            
            <ul class="ncs-nav-tabs">
                <?php            
                if($subscriptions) {
                    echo '<li><a href="#payment-plans-all" class="active">'.esc_html('All', 'ncs-cart').'</a></li>';
                    $sub_tables['all'] = $subscriptions;
                }
                if($subscription_active) {
                    echo '<li><a href="#payment-plans-active">'.esc_html('Active', 'ncs-cart').'</a></li>';
                    $sub_tables['active'] = $subscription_active;
                }
                /*if($subscription_pending) {
                    echo '<li><a href="#payment-plans-pending">Pending</a></li>';
                    $sub_tables['pending'] = $subscription_pending;
                }*/
                if($subscription_pastdue) {
                    echo '<li><a href="#payment-plans-past_due">'.esc_html('Past Due', 'ncs-cart').'</a></li>';
                    $sub_tables['past_due'] = $subscription_pastdue;
                }
                if($subscription_canceled) {
                    echo '<li><a href="#payment-plans-canceled">'.esc_html('Canceled', 'ncs-cart').'</a></li>';
                    $sub_tables['canceled'] = $subscription_canceled;
                }
                ?>
            </ul>
            
            <?php foreach($sub_tables as $type=>$subscriptions) : ?>    
                <div id="payment-plans-<?php echo $type; ?>" class="ncs-account-tab-pane">               
                    <table class="ncs-account-table" cellpadding="0" cellspacing="0">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('ID', 'ncs-cart'); ?></th>
                                <th><?php esc_html_e('Product', 'ncs-cart'); ?></th>
                                <th><?php esc_html_e('Status', 'ncs-cart'); ?></th>
                                <th><?php esc_html_e('Next Payment', 'ncs-cart'); ?></th>
                                <th><?php esc_html_e('Price', 'ncs-cart'); ?></th>
                                <th></th>
                            </tr>       
                        </thead>
                        <tbody>                  
                            <?php if($subscriptions){
                                    foreach($subscriptions as $subscription) {
                                        sc_do_subscription_row($subscription);
                                    } 
                                } else { ?>
                                    <tr><td colspan="6"><?php esc_html_e('No records found', 'ncs-cart'); ?></td></tr>
                              <?php } ?>                         
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="ncs-account-list ncs-account-history">
         <h3 class="ncs-account-title"><?php esc_html_e('Order History', 'ncs-cart'); ?></h3>
        <div class="ncs-account-tabs">           
            <div class="ncs-account-tab-pane-order">
                <table class="ncs-account-table" cellpadding="0" cellspacing="0">
                    <thead> 
                        <th><?php esc_html_e('ID', 'ncs-cart'); ?></th>
                        <th><?php esc_html_e('Product', 'ncs-cart'); ?></th>
                        <th><?php esc_html_e('Order Date', 'ncs-cart'); ?></th>
                        <th><?php esc_html_e('Status', 'ncs-cart'); ?></th>
                        <th><?php esc_html_e('Total', 'ncs-cart'); ?></th>
                        <th></th>
                    </thead>
                    <tbody>
                    <?php  $orders = sc_get_user_orders(get_current_user_id(), $status='any');
                        if($orders){
                            foreach($orders as $order) {
                                $status = (in_array(get_post_status( $order['ID'] ),['pending','pending-payment','initiated'])) ? 'pending' : get_post_status( $order['ID'] ); ?>
                                <tr>
                                    <td><?php echo $order['ID']; ?></td>
                                    <td>
                                        <?php echo get_the_title($order['product_id']);
                                        if ( isset($order['custom_prices']) ): foreach($order['custom_prices'] as $price): ?>
                                        <?php echo '<br>' . $price['qty'] . ' '. $price['label']; ?>
                                        <?php endforeach; endif; ?>
                                        
                                        <?php if (!empty($order['order_bumps']) && is_array($order['order_bumps'])) : ?>
                                           <?php foreach($order['order_bumps'] as $order_bump):?>
                                            <?php echo '<br>' . $order_bump['name']; ?>
                                          <?php endforeach; ?>
                                        <?php elseif ( isset($order['bump_id']) ): ?>
                                            <?php echo'<br>' . get_the_title( $order['bump_id']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $order['date']; ?></td>
                                    <td><?php echo ucwords($status); ?></td>
                                    <td><?php echo sc_format_price($order['amount']); ?></td>
                                    <td>                                        
                                    <?php if($status == 'pending') { ?>
                                        
                                    <?php } else { 
                                        $invoice_id = $order['ID'];
                                        if (isset($order->ob_parent) || isset($order->us_parent) || isset($order->ds_parent)) {
                                            if (isset($order->ob_parent)) {
                                                $invoice_id = $order->ob_parent;
                                            } else if (isset($order->ds_parent)) {
                                                $invoice_id = $order->ds_parent;
                                            } else {
                                                $invoice_id = $order->us_parent;
                                            }
                                        }
                                        ?>
                                        <a href="<?php echo home_url(); ?>?sc-invoice=<?php echo $invoice_id; ?>&dl=1" target="_blank"><?php esc_html_e('Download Invoice', 'ncs-cart'); ?></a>
                                    <?php } ?></td>
                                </tr>
                            <?php }
                        }else{ ?>
                            <tr><td colspan="5"><?php esc_html_e('No orders found', 'ncs-cart'); ?></td></tr>   
                       <?php } ?>
                        
                    </tbody>
                </table>
            </div>           
        </div>
    </div>
</div>
<script>
    jQuery(function(){
        jQuery('.ncs-account-tab-pane').hide();
        jQuery('.ncs-nav-tabs + .ncs-account-tab-pane').show();
        jQuery('.ncs-nav-tabs li a').click(function(event){
           event.preventDefault();
           var tabID = jQuery(this).attr('href');
           var parents = jQuery(this).parents('.ncs-account-list');
           parents.find('.ncs-nav-tabs li a').removeClass('active');
           jQuery(this).addClass('active');
           parents.find('.ncs-account-tab-pane').hide();
           jQuery(tabID).show();
           return false;
        }); 
    });
</script>
          
           