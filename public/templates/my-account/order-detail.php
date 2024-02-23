<div class="ncs-my-account ncs-my-subscription-page">
    <?php if ($link = get_option('_sc_myaccount_page_id')): ?>
    <p style="margin: 0 0 20px;"><a href="<?php echo get_permalink($link); ?>">&larr; <?php esc_html_e('Back', 'ncs-cart'); ?></a></p>
    <?php endif; ?>
    <div class="ncs-account-subscription ">
        <?php if(isset($_REQUEST['sc-order'])){
            $post_id = intval($_REQUEST['sc-order']);
            $order = new ScrtOrder($post_id);
            $order = $order->get_data();
            ncs_template('shortcodes/receipt', '', sc_get_item_list($post_id, false));
            $address = sc_order_address($post_id);
            ?>
            
            <table class="ncs-subscription-table" border="0">
                <tr>
                    <?php if($address): ?>
                    <td>
                        <h3 class="ncs-account-title"><?php esc_html_e( 'Address', 'ncs-cart' ); ?></h3>
                        <?php echo $address; ?>
                    </td>
                    <?php endif; ?>
                    <td width="200">
                        <h3 class="ncs-account-title"><?php esc_html_e( 'Invoice', 'ncs-cart' ); ?></h3>
                        <?php echo $order['invoice_link_html']; ?>
                    </td>
                </tr>
            </table>
        <?php } else { esc_html_e( 'No orders found.', 'ncs-cart' ); } ?>
          
    </div>
</div>