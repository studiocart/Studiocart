<div class="tab-container">
    <div id="order-history" class="tab-content">
        
            <table class="ncs-account-table" cellpadding="0" cellspacing="0">
                <thead>
                    <th><?php 
esc_html_e( 'Product', 'ncs-cart' );
?></th>
                    <th><?php 
esc_html_e( 'Date', 'ncs-cart' );
?></th>
                    <th><?php 
esc_html_e( 'Status', 'ncs-cart' );
?></th>
                    <th><?php 
esc_html_e( 'Total', 'ncs-cart' );
?></th>
                    <?php 
?>
                </thead>
                <tbody>
                    <?php 
$hide_free = (bool) get_option( '_sc_myaccount_hide_free' );
$orders = sc_get_user_orders(
    get_current_user_id(),
    $status = array( 'paid', 'completed', 'refunded' ),
    $order_id = 0,
    $renewals = false,
    $hide_free
);

if ( $orders ) {
    foreach ( $orders as $order ) {
        $status = ( in_array( $order['status'], [ 'pending', 'pending-payment', 'initiated' ] ) ? 'pending' : $order['status'] );
        ?>
                    <tr>
                        <td><a href="?sc-order=<?php 
        echo  $order['ID'] ;
        ?>"><?php 
        echo  $order['product_name'] ;
        ?></a></td>
                        <td><?php 
        echo  $order['date'] ;
        ?></td>
                        <td><?php 
        echo  $order['status_label'] ;
        ?></td>
                        <td><?php 
        echo  sc_format_price( $order['amount'] ) ;
        ?></td>

                        <?php 
        ?>
                    </tr>
                    <?php 
    }
} else {
    ?>
                    <tr>
                        <td colspan="5"><?php 
    esc_html_e( 'No orders found', 'ncs-cart' );
    ?></td>
                    </tr>
                    <?php 
}

?>

                </tbody>
            </table>
        
    </div>

</div><!-- container -->