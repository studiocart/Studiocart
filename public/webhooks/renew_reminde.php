<?php
global $wpdb; global $sc_currency_symbol;
 //$next_bill_date = strtotime(  date( "Y-m-d" ) . "+1 day" );
$next_date = date('Y-m-d', strtotime( date( "Y-m-d" ) . " +1 days"));
//echo "<br />"; echo "<br />";
if(get_option('_sc_reminder_notification')){
    $subscription_args = array('post_type' => 'sc_subscription',
        'post_status' => 'active',                
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_sc_sub_next_bill_date',
                //'value' => $next_bill_date,
            ),
        )
    );
    $subscription_results = new WP_Query($subscription_args); 
    if( $subscription_results->have_posts() ){                  
        while( $subscription_results->have_posts() ) {
            $subscription_results->the_post();
            global $post;       

            $nextdate = intval(get_post_meta( $post->ID , '_sc_sub_next_bill_date', true ));
            $next_bill_date = get_date_from_gmt(date( 'Y-m-d H:i:s', $nextdate ), 'Y-m-d');
            if($next_date == $next_bill_date){
                echo "<br />";        
                echo $post->ID; 
                echo "<br />";        
                echo  trim(get_post_meta( $post->ID, '_sc_email', true )); 
                echo "<br />";
                echo get_date_from_gmt(date( 'Y-m-d H:i:s', $nextdate ), 'Y-m-d').'======='.$next_bill_date;
                echo "<br />";
                studiocart_notification_send('reminder', $post->ID);
            }
            //
        }
    }
}else{
    echo "no notification";
}
exit();