<?php 

    global $wpdb, $sc_currency_symbol, $studiocart;

    remove_filter( 'the_title', array( $studiocart, 'public_product_name' ) );

    $csv_output_report = '';
    $filename = strtolower(apply_filters('studiocart_plugin_title','Studiocart'));

    //  print_r($_REQUEST); exit();
    if($_REQUEST['sc-csv-export'] == 'contacts'){
        $csv_output_report .= esc_html__('First Name', 'ncs-cart').','.esc_html__('Last Name', 'ncs-cart').','.esc_html__('Email', 'ncs-cart').','.esc_html__('Orders', 'ncs-cart').','.esc_html__('Last Order Date', 'ncs-cart'); 
        $csv_output_report .= "\n";  
         $get_user = $wpdb->get_results("SELECT wp_posts.ID,wp_postmeta.meta_value FROM wp_posts INNER JOIN wp_postmeta ON ( wp_posts.ID = wp_postmeta.post_id ) WHERE 1=1 AND ( wp_postmeta.meta_key = '_sc_email' ) AND wp_posts.post_type = 'sc_order' AND ((wp_posts.post_status <> 'trash' AND wp_posts.post_status <> 'auto-draft')) ORDER BY `wp_posts`.`post_date` DESC");
                      
        $get_users = array_count_values(array_map('strtolower', array_column($get_user, 'meta_value'))); 
     
        foreach ($get_users as $key => $value) {
          // $ayya= array_search($key,$get_user);
           //print_r($ayya);
            $get_date = $wpdb->get_row("SELECT wp_posts.ID, wp_posts.post_date FROM wp_posts INNER JOIN wp_postmeta ON ( wp_posts.ID = wp_postmeta.post_id ) WHERE 1=1 AND ( wp_postmeta.meta_key = '_sc_email' AND wp_postmeta.meta_value = '$key' ) AND wp_posts.post_type = 'sc_order' AND ((wp_posts.post_status <> 'trash' AND wp_posts.post_status <> 'auto-draft')) ORDER BY `wp_posts`.`post_date` DESC LIMIT 1");

                $csv_output_report .= get_post_meta($get_date->ID, '_sc_firstname', true).','.get_post_meta($get_date->ID, '_sc_lastname', true).",";
                $csv_output_report .= get_post_meta($get_date->ID, '_sc_email', true).",";
                $csv_output_report .= $value.",";                      
                $csv_output_report .= get_the_time( 'M j Y', $get_date->ID ).",";                
                $csv_output_report .= "\n";
            }

    }else {
        $daterange = $_REQUEST['daterange'];
        $dates = explode(" to ",$daterange);
        $fromdate = $dates[0]." 00:00:00";
        $todate = $dates[1]." 23:59:59";
        $customer = false;
                    
        if($_REQUEST['sc-csv-export'] == 'customer'){
            $customer = $_REQUEST['emailid'];
            $filename = esc_html($customer);
        }
       
        if($_REQUEST['type'] == 'order'){
            $args = array(          
                'post_type' => 'sc_order',
                'post_status' => 'any',
                'date_query' => array(
                    array(
                        'post_date' => 'post_date',
                        'after' => $fromdate,
                    ),
                    array(
                        'post_date' => 'post_date',
                        'before'  => $todate,
                    ),
                ),
                'posts_per_page' => -1,
            );
            if($customer){
                $args['meta_query'] = array(
                    array(
                        'key' => '_sc_email',
                        'value' => $customer,
                    ),
                );
            }
            
            $results = new WP_Query($args); 
            $csv_output_report .= esc_html__('ID', 'ncs-cart').','.esc_html__('Date', 'ncs-cart').','.esc_html__('First Name', 'ncs-cart').','.esc_html__('Last Name', 'ncs-cart').','.esc_html__('Email', 'ncs-cart').','.esc_html__('Amount Paid', 'ncs-cart').','.esc_html__('Product Name', 'ncs-cart').','.esc_html__('Payment Plan', 'ncs-cart').','.esc_html__('Status', 'ncs-cart').','.esc_html__('coupons', 'ncs-cart').','.esc_html__('Purchase URL', 'ncs-cart');
            $csv_output_report .= "\n";  
            if( $results->have_posts() ){      
                while( $results->have_posts() ) {
                    $results->the_post();  global $post;
                    $status = (in_array(get_post_status( $post->ID ),['pending-payment','initiated'])) ? 'pending' : get_post_status( $post->ID );
                     if($status != 'pending'){
                        if (get_post_meta( $post->ID, '_sc_payment_status', true ) == 'refunded') {
                             $refund_logs_entrie = get_post_meta( $post->ID, '_sc_refund_log', true);
                            $total_amount = get_post_meta( $post->ID, '_sc_amount', true );
                            if(is_array($refund_logs_entrie)) {
                               $refund_amount = array_sum(array_column($refund_logs_entrie, 'amount'));
                               $total_amount = get_post_meta( $post->ID , '_sc_amount', true ) - $refund_amount;  
                               $total_amount = number_format($total_amount , 2);
                               $refundedarray[]= $refund_amount;                        
                            } 
                        } else {
                             $total_amount= number_format( (float) get_post_meta( $post->ID, '_sc_amount', true), 2);
                        }
                       // $total_amount= number_format( get_post_meta( $post->ID, '_sc_amount', true), 2);
                        $csv_output_report .= get_the_ID().",";
                        $csv_output_report .= get_the_time( 'Y-m-d', $post->ID ).",";
                        $csv_output_report .= get_post_meta( $post->ID , '_sc_firstname', true ).", ".get_post_meta( $post->ID , '_sc_lastname', true ).",";
                        $csv_output_report .= get_post_meta( $post->ID , '_sc_email', true ).",";
                        $csv_output_report .= html_entity_decode(sc_format_price($total_amount, $html=false)).",";
                         
                        $product_id = get_post_meta( $post->ID, '_sc_product_id', true);
                        $product_name_col = array();
                        $product_name_col[] = get_the_title($product_id);

                        $bumps = get_post_meta($post->ID, '_sc_order_bumps', true);
                        if (is_countable($bumps)){
                            foreach($bumps as $bump){
                                $product_name_col[] = $bump['name']." (Bump)";
                            }
                        } else if ($bump_id = get_post_meta($post->ID, '_sc_bump_id', true)){
                            $product_name_col[] = get_the_title($bump_id)." (Bump)";
                        }
                         
                        $csv_output_report .= "\"".implode("\n", $product_name_col). "\",";
                         
                        if(get_post_meta($post->ID, '_sc_us_parent')){
                             $csv_output_report .= 'Upsell'.",";
                        } elseif(get_post_meta($post->ID, '_sc_ds_parent')){
                             $csv_output_report .= '2nd upsell'.",";
                        } else {                              
                            $csv_output_report .= get_post_meta( $post->ID , '_sc_item_name', true ).",";
                        }
                         
                        $csv_output_report .= ucwords($status).","; 
                        $csv_output_report .= get_post_meta( $post->ID , '_sc_coupon', true ).",";                 
                        $csv_output_report .= get_post_meta( $post->ID , '_sc_page_url', true );
                        $csv_output_report .= "\n";
                    }
                }
            }
        }else{
            $csv_output_report .= esc_html__('Date', 'ncs-cart').','.esc_html__('Product Name', 'ncs-cart').','.esc_html__('Payment Plan', 'ncs-cart').','.esc_html__('Pay Interval', 'ncs-cart').','.esc_html__('Status', 'ncs-cart').','.esc_html__('Total Revenue', 'ncs-cart').','.esc_html__('Remaining Payments', 'ncs-cart');
            $csv_output_report .= "\n"; 
            $subscription_args = array('post_type' => 'sc_subscription',
                'post_status' => 'any',
                'date_query' => array(
                    array(
                    'post_date' => 'post_date',
                    'after' => $fromdate,
                    ),
                    array(
                    'post_date' => 'post_date',
                    'before'  => $todate,
                    ),
                ),
                'posts_per_page' => -1,
            );            
            if($customer){
                $args['meta_query'] = array(
                    array(
                        'key' => '_sc_email',
                        'value' => $customer,
                    ),
                );
            }
            
            $subscription_results = new WP_Query($subscription_args);                
            if( $subscription_results->have_posts() ){                  
                while( $subscription_results->have_posts() ) {
                    $subscription_results->the_post();  global $post;
                    $amount = get_post_meta( $post->ID , '_sc_amount', true );
                    $status = (in_array(get_post_status( $post->ID ),['pending-payment','initiated'])) ? 'pending' : get_post_status( $post->ID ); 
                    if($status != 'pending'){ 
                        $total_amount = 0; 
                        $installments = get_post_meta( $post->ID, '_sc_sub_installments', true) ?? -1; 
                        //$interval = get_post_meta( $post->ID, '_sc_sub_interval', true); 
                       // $installments--;  
                        $interval = get_post_meta( $post->ID , '_sc_sub_interval', true );
                        if ($installments > 1) {
                            $dateTime = DateTime::createFromFormat('Y-m-d', get_the_time( 'Y-m-d', $post->ID ));
                            $dateTime->add(DateInterval::createFromDateString($installments . ' ' . $interval.'s'));
                            $expiresdate = $dateTime->format('Y-m-d'); 
                        }
                        $date = date( 'Y-m-d');
                        if(get_post_meta( $post->ID, '_sc_sub_installments', true) == '-1'){
                            $expiresdate =  get_the_time( 'Y-m-d', $post->ID );
                            $diff = strtotime($expiresdate) - strtotime($date); 
                        }else{
                            $diff = strtotime($date) - strtotime($expiresdate); 
                        }      
                        $installments = get_post_meta( $post->ID, '_sc_sub_installments', true); 
                        $payments_remaining = '';

                        if($installments > 0) {

                            $payments_remaining = $installments;

                            global $post;
                            $backup = $post;

                            // The Query
                            $args = array(
                                'post_type' => array( 'sc_order' ),
                                'orderby' => 'date',
                                'order'   => 'ASC',
                                'post_status' => 'paid',
                                'meta_query'=>array(
                                    array(
                                        'key' => '_sc_subscription_id',
                                        'value' => $post->ID,
                                    ),
                                ),
                            );
                            $the_query = new WP_Query( $args );

                            // The Loop
                            if ( $the_query->have_posts() ) {

                                $num = $the_query->post_count;
                                $payments_remaining = $installments - $num;

                                while ( $the_query->have_posts() ) {
                                    $the_query->the_post(); 
                                    $total_amount += get_post_meta( get_the_ID(), '_sc_amount', true );
                                }
                            } 
                            /* Restore original Post Data */
                            wp_reset_postdata();
                            $post = $backup;
                        }
                        
                        $order = sc_setup_order($post->ID);
                        
                        $csv_output_report .= get_the_time( 'Y-m-d', $post->ID ).",";
                        $csv_output_report .= get_the_title(get_post_meta( $post->ID, '_sc_product_id', true)).",";
                        $csv_output_report .= $order->sub_item_name.",";                      
                        $csv_output_report .= $order->sub_payment_terms_plain.",";
                        $csv_output_report .= ucwords($status).",";
                        $csv_output_report .= sc_format_price($total_amount, $html=false).",";
                        if($status == 'canceled') {
                            $csv_output_report .= esc_html__('Canceled','ncs-cart').",";
                        }else if(get_post_meta( $post->ID, '_sc_sub_installments', true) == '-1'){
                            $csv_output_report .= esc_html__('Never expires','ncs-cart').",";
                        }else{
                            $csv_output_report .= $payments_remaining.",";
                        }
                        $csv_output_report .= "\n";
                    }
                }
            }
        }
    }

    header("Pragma: public");
    header("Expires: 0");
    header('Content-Encoding: UTF-8');        
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
   // header("Cache-Control: private", false );
    header("Content-Type: text/csv; charset=utf-8");
    //header("Content-type: text/csv");
    //header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"".$filename."-".esc_html($_REQUEST['type'])."-export.csv\";" );
    header("Content-Transfer-Encoding: binary");
    echo html_entity_decode($csv_output_report);
    exit();