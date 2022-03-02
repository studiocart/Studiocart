<?php 
$order = (object) $order;
$post_id = $order->ID;
$total = 0;
?>     
        
<div class="u-row-container" style="padding: 0px 10px 10px;background-color: #ffffff">
  <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 600px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;">
    <div style="border-collapse: collapse;display: table;width: 100%;background-color: transparent;">
      <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px 10px 10px;background-color: #ffffff;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:600px;"><tr style="background-color: transparent;"><![endif]-->
      
<!--[if (mso)|(IE)]><td align="center" width="600" style="width: 600px;padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;" valign="top"><![endif]-->
<div class="u-col u-col-100" style="max-width: 320px;min-width: 600px;display: table-cell;vertical-align: top;">
  <div style="width: 100% !important;">
  <!--[if (!mso)&(!IE)]><!--><div style="padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;"><!--<![endif]-->
  
<table style="font-family:'Lato',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
  <tbody>
    <tr>
      <td style="overflow-wrap:break-word;word-break:break-word;padding:5px 0px 0px;font-family:'Lato',sans-serif;" align="left">
        
  <div style="color: #303030; line-height: 120%; text-align: left; word-wrap: break-word;">
    <p style="font-size: 14px; line-height: 120%;"><strong><span style="font-size: 16px; line-height: 19.2px;"><?php echo esc_attr_e('Order Details','ncs-cart'); ?></span></strong></p>
<p style="font-size: 14px; line-height: 120%;">&nbsp;</p>
<p style="font-size: 14px; line-height: 120%;"><span style="font-size: 14px; line-height: 16.8px;"><span style="line-height: 16.8px; font-size: 14px;"><?php esc_attr_e('Order Number:','ncs-cart'); ?> <?php echo $post_id; ?><br /><?php esc_attr_e('Purchase Date:','ncs-cart'); ?> <?php echo $order->date; ?></span></span></p>
  </div>

      </td>
    </tr>
  </tbody>
</table>

  <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]-->
  </div>
</div>
<!--[if (mso)|(IE)]></td><![endif]-->
      <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
    </div>
  </div>
</div>



<div class="u-row-container" style="padding: 0px 10px;background-color: rgba(255,255,255,0)">
  <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 600px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;">
    <div style="border-collapse: collapse;display: table;width: 100%;background-color: transparent;text-align: left;font-size: 14px;">
      <table style="font-family:'Lato',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
          
                <tr><td colspan="2" style="padding: 10px 0;">
        
                  <table height="0px" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;table-layout: fixed;border-spacing: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;vertical-align: top;border-top: 1px dotted #CCC;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%">
                    <tbody>
                      <tr style="vertical-align: top">
                        <td style="word-break: break-word;border-collapse: collapse !important;vertical-align: top;font-size: 0px;line-height: 0px;mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
                          <span>&nbsp;</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                    
                </td></tr>
          
                <tr>
                    <td id="main-offer-row" style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;"><?php echo $order->product_name;
                    if(isset($order->coupon) && !isset($order->discount_details)) {
                        echo ' ' . sprintf(__('(coupon %s)','ncs-cart'),$order->coupon);
                    }?>
                    </td>
                    <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;" align="right">
                        <?php echo sc_format_price($order->main_offer_amt); 
                        $total += $order->main_offer_amt; ?>
                    </td>
                </tr>
                <?php if ( isset($order->custom_prices) ) : ?> 
                    <?php foreach($order->custom_prices as $price): ?>
                      <tr>
                        <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;"><?php echo $price['label'].' x '.$price['qty']; ?></td>
                        <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;" align="right"><?php echo sc_format_price($price['price']); ?></td>
                      </tr>
                      <?php $total += $price['price']; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($order->order_bumps) && is_array($order->order_bumps)) : ?>
                   <?php foreach($order->order_bumps as $order_bump):?>
                    <tr>
                        <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;"><?php echo $order_bump['name']; ?></td>
                        <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;" align="right"><?php echo sc_format_price($order_bump['amount']); ?></td>
                    </tr>
                    <?php $total += $order_bump['amount']; ?>
                  <?php endforeach; ?>
                <?php elseif(isset($order->bump_id) && $order->bump_id): ?>
                  <tr>
                    <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;"><?php echo sc_get_public_product_name($order->bump_id); ?></td>
                    <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;" align="right"><?php echo sc_format_price($order->bump_amt); ?></td>
                  </tr>
                <?php endif; ?>
                <tr><td colspan="2" style="padding-top: 10px;">
        
                  <table height="0px" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;table-layout: fixed;border-spacing: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;vertical-align: top;border-top: 1px dotted #CCC;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%">
                    <tbody>
                      <tr style="vertical-align: top">
                        <td style="word-break: break-word;border-collapse: collapse !important;vertical-align: top;font-size: 0px;line-height: 0px;mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
                          <span>&nbsp;</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                    
                </td></tr>
          
                <?php if(isset($order->tax_amount) && $order->tax_amount>0):?>
                <tr>
                    <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;text-transform: uppercase;"><?php esc_attr_e('Subtotal','ncs-cart'); ?></td>
                    <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;" align="right"><?php echo sc_format_price($total); ?></td>
                </tr>
                <tr>
                    <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;"><?php _e($order->tax_desc.' ('.$order->tax_rate.'%)', 'ncs-cart') ?></td>
                    <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;" align="right"><?php sc_formatted_price($order->tax_amount); ?></td>
                </tr>
          
                <tr><td colspan="2">
        
                  <table height="0px" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;table-layout: fixed;border-spacing: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;vertical-align: top;border-top: 1px dotted #CCC;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%">
                    <tbody>
                      <tr style="vertical-align: top">
                        <td style="word-break: break-word;border-collapse: collapse !important;vertical-align: top;font-size: 0px;line-height: 0px;mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
                          <span>&nbsp;</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                    
                </td></tr>
                <?php endif; ?>
          
          
                <tr>
                    <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;text-transform: uppercase; font-weight: bold;"><?php esc_attr_e('Order Total','ncs-cart'); ?></td>
                    <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030; font-weight: bold;" align="right"><?php echo sc_format_price($order->amount); ?></td>
                </tr>
                <tr><td colspan="2">
        
                  <table height="0px" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;table-layout: fixed;border-spacing: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;vertical-align: top;border-top: 1px dotted #CCC;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%">
                    <tbody>
                      <tr style="vertical-align: top">
                        <td style="word-break: break-word;border-collapse: collapse !important;vertical-align: top;font-size: 0px;line-height: 0px;mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
                          <span>&nbsp;</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                    
                </td></tr>
            </table>
    </div>
  </div>
</div>


<div class="u-row-container" style="padding: 0px 10px 5px;background-color: rgba(255,255,255,0)">
  <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 600px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;">
    <div style="border-collapse: collapse;display: table;width: 100%;background-color: transparent;">
      <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px 10px 5px;background-color: rgba(255,255,255,0);" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:600px;"><tr style="background-color: transparent;"><![endif]-->
      
<!--[if (mso)|(IE)]><td align="center" width="600" style="width: 600px;padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;" valign="top"><![endif]-->
<div class="u-col u-col-100" style="max-width: 320px;min-width: 600px;display: table-cell;vertical-align: top;">
  <div style="width: 100% !important;">
  <!--[if (!mso)&(!IE)]><!--><div style="padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;"><!--<![endif]-->

<table style="font-family:'Lato',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
  <tbody>
    <tr>
      <td style="overflow-wrap:break-word;word-break:break-word;padding:25px 0px 20px;font-family:'Lato',sans-serif;" align="left">
        
  <div style="color: #303030; line-height: 120%; text-align: left; word-wrap: break-word;">
    <p style="font-size: 14px; line-height: 120%;"><span style="font-size: 14px; line-height: 16.8px;"><span style="line-height: 16.8px; font-size: 14px;"><strong><?php esc_attr_e('Customer Information','ncs-cart'); ?></strong><br />
        
        <?php echo $order->customer_name.'<br>';

        if(isset($order->email)){ echo $order->email.'<br>'; }
        if(isset($order->phone)){ echo $order->phone.'<br>'; }

        $address = sc_format_order_address($order);
        if($address) {
            echo '<br>'.$address;
        }
        ?>
        </span></span></p>
  </div>

      </td>
    </tr>
  </tbody>
</table>

  <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]-->
  </div>
</div>
<!--[if (mso)|(IE)]></td><![endif]-->
      <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
    </div>
  </div>
</div>

