<div class="tab-container">

    <div id="subscriptions" class="tab-content">
        
        <?php
        $subscriptions = sc_get_user_subscriptions(get_current_user_id(), $status=array('active', 'trialing'));
        $subscription_paused = sc_get_user_subscriptions(get_current_user_id(), $status='paused');
        $subscription_pastdue = sc_get_user_subscriptions(get_current_user_id(), $status='past_due');
        $subscription_canceled = sc_get_user_subscriptions(get_current_user_id(), $status='canceled'); 

        $sub_tables = array(); 
        $sub_labels = array(); 
        if($subscriptions) {
            $sub_tables['all'] = $subscriptions;
            $sub_labels['all'] = __('Active Subscriptions','ncs-cart');
        }
        if($subscription_paused) {
            $sub_tables['paused'] = $subscription_paused;
            $sub_labels['paused'] = __('Paused Subscriptions','ncs-cart');
        }
        if($subscription_pastdue) {
            $sub_tables['past_due'] = $subscription_pastdue;
            $sub_labels['past_due'] = __('Past Due Subscriptions','ncs-cart');
        }
        if($subscription_canceled) {
            $sub_tables['canceled'] = $subscription_canceled;
            $sub_labels['canceled'] = __('Canceled Subscriptions','ncs-cart');
        }

        foreach($sub_tables as $type=>$subscriptions) : ?>
        <div id="subscription-<?php echo $type; ?>" class="ncs-account-tab-pane">
            <h4><?php echo $sub_labels[$type]; ?></h4>
            <table class="ncs-account-table" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
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
                            ncs_template('my-account/subscription-row','', $subscription);
                        } 
                    } else { ?>
                    <tr>
                        <td colspan="6"><?php esc_html_e('No records found', 'ncs-cart'); ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>
        
    </div>

</div><!-- container -->
