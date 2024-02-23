<?php
$subscription = $attr;
$status = (in_array($subscription->status,['pending-payment','initiated'])) ? 'pending' : $subscription->status; 
if($status == 'completed' || $status == 'pending' || $status == 'canceled' || $status == 'paused'){ 
    $next = "--"; 
} else {
    if ($subscription->sub_next_bill_date && !is_numeric($subscription->sub_next_bill_date)) {
        $subscription->sub_next_bill_date = strtotime($subscription->sub_next_bill_date);
    }
    $next = date_i18n(get_option('date_format'), $subscription->sub_next_bill_date);
}

?>
<tr>
    <td><?php echo $subscription->product_name; ?></td>
    <td><?php echo $subscription->status_label; ?>
        <?php if ($subscription->cancel_date && $status != 'canceled' && $next != '--'): ?>
            <br><small><?php printf(esc_html__( 'Cancels %s', 'ncs-cart' ), date("m/d/y", strtotime($next))); ?></small>
        <?php endif; ?>    
    </td>
    <td><?php echo ($subscription->cancel_date) ? '--' : $next; ?></td>
    <td><?php echo $subscription->sub_payment; ?></td>

    <td>
        <?php if($subscription->status == 'incomplete' || $subscription->status == 'past_due' || $subscription->status == 'pending-payment'): ?>
            <a href="?sc-plan=<?php echo $subscription->ID; ?>&action=pay" onclick="sc_payment_pay('<?php echo $subscription->ID; ?>','subscription');" ><?php esc_html_e( 'Pay', 'ncs-cart' ); ?></a> &nbsp; 
        <?php  endif; ?>
        
        <a href="?sc-plan=<?php echo $subscription->ID; ?>"><?php esc_html_e( 'Manage', 'ncs-cart' ); ?></a>
    </td>
</tr>