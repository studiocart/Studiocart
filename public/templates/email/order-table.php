<?php 
$type = $args['type'];
$items = $args['items'];
$order = $args['order'];
$sub = $args['sub'];
?>     
  
<table style="font-family:'Lato',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
  <tbody>
    <tr>
      <td style="overflow-wrap:break-word;word-break:break-word;padding:10px 20px 30px;font-family:'Lato',sans-serif;" align="left">
        
  <div style="color: #303030; line-height: 120%; text-align: center; word-wrap: break-word;">
      
      
<div>
  <div>
    <div style="border-collapse: collapse;display: table;width: 100%;background-color: transparent;">
      <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px 10px 10px;background-color: #ffffff;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:600px;"><tr style="background-color: transparent;"><![endif]-->
      
<!--[if (mso)|(IE)]><td align="center" width="600" style="width: 600px;padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;" valign="top"><![endif]-->
<div>
  <div style="width: 100% !important;">
  <!--[if (!mso)&(!IE)]><!--><div style="padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;"><!--<![endif]-->
  
<table style="font-family:'Lato',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
  <tbody>
    <tr>
      <td style="overflow-wrap:break-word;word-break:break-word;padding:5px 0px 0px;font-family:'Lato',sans-serif;" align="left">
        
  <div style="color: #303030; line-height: 120%; text-align: left; word-wrap: break-word;">
    <p style="font-size: 14px; line-height: 120%;"><strong><span style="font-size: 16px; line-height: 19.2px;"><?php echo esc_attr_e('Order Details','ncs-cart'); ?></span></strong></p>
<p style="font-size: 14px; line-height: 120%;">&nbsp;</p>
<p style="font-size: 14px; line-height: 120%;"><span style="font-size: 14px; line-height: 16.8px;"><span style="line-height: 16.8px; font-size: 14px;"><?php esc_attr_e('Order Number:','ncs-cart'); ?> <?php echo $order->id; ?><br /><?php esc_attr_e('Purchase Date:','ncs-cart'); ?> <?php echo get_the_date('', $order->id); ?></span></span></p>
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



<div>
  <div>
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
          
                <?php
                $main_amt = $order->main_offer_amt;
                if($sub && $sub->sign_up_fee) {
                    $main_amt -= $sub->sign_up_fee;
                }
                $discount = array();
                ?>

                <?php foreach($items['items'] as $item) : ?>
                    <?php $item['item_type'] = $item['item_type'] ?? ''; ?>
                    <tr>
                      <td id="main-offer-row" style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;">
                        <strong><?php echo $item['product_name']; echo isset($item['price_name']) ? " - ".$item['price_name'] : ""; ?></strong>
                        <?php if (isset($item['purchase_note'])): ?>
                          <br><span class="sc-purchase-note"><?php echo $item['purchase_note']; ?></span>
                        <?php endif; ?>
                      </td>
                      <td style="overflow-wrap:normal;word-break:normal;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;" align="right">
                        <?php echo isset($item['subtotal']) ? sc_format_price($item['subtotal']) : ''; ?>
                      </td>
                    </tr>                        
                <?php endforeach; ?>
          
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

                  <?php // if(isset($items['discounts']) || isset($items['tax'])): ?>
                    <tr>
                        <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;text-transform: uppercase;"><?php esc_attr_e('Subtotal','ncs-cart'); ?></td>
                        <td style="overflow-wrap:normal;word-break:normal;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;" align="right"><?php echo sc_format_price($items['subtotal']['total_amount']); ?></td>
                    </tr>
                  <?php // endif; ?>

                  <?php if(isset($items['discounts'])): foreach($items['discounts'] as $item) :?>
                    <tr>
                      <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;">
                          <?php echo $item['product_name']; ?>
                      </td>
                      <td style="overflow-wrap:normal;word-break:normal;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;" align="right">
                          <?php echo '-' . sc_format_price($item['subtotal']); ?>
                      </td>
                    </tr> 
                  <?php endforeach; endif; ?>
          
                  <?php if(isset($items['shipping'])):?>   
                    <tr>
                      <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;">
                        <?php echo $items['shipping']['product_name']; ?>
                      </td>
                      <td style="overflow-wrap:normal;word-break:normal;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;" align="right">
                        <?php echo isset($items['shipping']['subtotal']) ? sc_format_price($items['shipping']['subtotal']) : ''; ?>
                      </td>
                    </tr>  
                  <?php endif; ?>

                  <?php if(isset($items['tax'])):?>   
                    <tr>
                      <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;">
                        <?php echo $items['tax']['product_name']; ?>
                      </td>
                      <td style="overflow-wrap:normal;word-break:normal;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;" align="right">
                        <?php echo isset($items['tax']['subtotal']) ? sc_format_price($items['tax']['subtotal']) : ''; ?>
                      </td>
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
                    <td style="overflow-wrap:normal;word-break:normal;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030; font-weight: bold;" align="right"><?php echo sc_format_price($order->amount); ?></td>
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


<div>
  <div>
    <div style="border-collapse: collapse;display: table;width: 100%;background-color: transparent;">
      <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px 10px 5px;background-color: rgba(255,255,255,0);" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:600px;"><tr style="background-color: transparent;"><![endif]-->
      
<!--[if (mso)|(IE)]><td align="center" width="600" style="width: 600px;padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;" valign="top"><![endif]-->
<div>
  <div style="width: 100% !important;">
  <!--[if (!mso)&(!IE)]><!--><div style="padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;"><!--<![endif]-->

<?php do_action('sc_email_after_order_table', $order); ?>

<table style="font-family:'Lato',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
  <tbody>
    <tr>
      <td style="overflow-wrap:break-word;word-break:break-word;padding:25px 0px 20px;font-family:'Lato',sans-serif;" align="left">
        
  <div style="color: #303030; line-height: 120%; text-align: left; word-wrap: break-word;">
    <p style="font-size: 14px; line-height: 120%;"><span style="font-size: 14px; line-height: 16.8px;"><span style="line-height: 16.8px; font-size: 14px;"><strong><?php esc_attr_e('Customer Information','ncs-cart'); ?></strong><br />
        
        <?php $customer_info = $order->customer_name.'<br>';

        if(isset($order->company)){ $customer_info .= $order->company.'<br>'; }
        if(isset($order->email)){ $customer_info .= $order->email.'<br>'; }
        if(isset($order->phone)){ $customer_info .= $order->phone.'<br>'; }

        $address = sc_format_order_address($order);
        if($address) {
            $customer_info .= '<br>'.$address;
        }

        if(!empty($order->vat_number)) {
          $customer_info .= esc_attr__(apply_filters("sc_vat_title","VAT Number"), 'ncs-cart').': '.esc_attr__($order->vat_number);
        }
        
        $customer_info = apply_filters('sc_email_template_customer_info', $customer_info, $type, $order->id);
        echo $customer_info;
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

      
      </div>

      </td>
    </tr>
  </tbody>
</table>
