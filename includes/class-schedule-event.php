<?php

//Email template
function ncs_schedule_email_temp(){ 
	ob_start();	

	$site_name = get_bloginfo('name');

	$get_schedule_data = get_option('sc_report_schedule');
	$_sc_company_logo = get_option('_sc_company_logo');
	if($get_schedule_data == 'mt_daily'){
		$date2 = date('Y-m-d');
		$date1 = date('Y-m-d', strtotime('-1 days'));
		$datenxt = date('Y-m-d', strtotime('+1 days'));
	}elseif($get_schedule_data == 'mt_weekly'){
		$date2 = date('Y-m-d');
		$date1 = date('Y-m-d', strtotime('-7 days'));
		$datenxt = date('Y-m-d', strtotime('+7 days'));
	}elseif($get_schedule_data == 'mt_semi_monthly'){
		$date2 = date('Y-m-d');
		$date1 = date('Y-m-d', strtotime('-15 days'));
		$datenxt = date('Y-m-d', strtotime('+15 days'));
	}

	$date_1 = date_format(date_create($date1),"l, M d");
	$date_2 = date_format(date_create($date2),"l, M d");

	$datenxt_1 = date_format(date_create($date2),"l, M d");
	$datenxt_2 = date_format(date_create($datenxt),"l, M d");

	$argsfunded = array(
		'post_type'  => 'sc_order',
		'post_status' => 'refunded',
		'posts_per_page' => -1,
	);

	$args = array(
		'post_type'  => 'sc_order',
		'post_status' => 'any',
		'posts_per_page' => -1,
	);
	$asc_subscriptiongs = array(
		'post_type'  => 'sc_subscription',
		'post_status' => array('All'),
		'posts_per_page' => -1,
	);

	if($date1 != '' && $date2 != '') {
		$from = new DateTime($date1);
		$to   = new DateTime($date2);
		$args['date_query'] = array(
			array(
				'after' => $from->format('Y-m-d'),
				'before' => array(
					'year'  => $to->format('Y'),
					'month' => $to->format('m'),
					'day'   => $to->format('d'),
				),
				'inclusive' => true,   
			)
		);
		$argsfunded['date_query'] = array(
			array(
				'after' => $from->format('Y-m-d'),
				'before' => array(
					'year'  => $to->format('Y'),
					'month' => $to->format('m'),
					'day'   => $to->format('d'),
				),
				'inclusive' => true,   
			)
		);
	}

	$canceled_subscription = 0;
	$Subscription_data = [];
	$trialing_data = [];

	$startDate = $date2;
	$endDate   = $datenxt;

	$startDateObj = DateTime::createFromFormat('Y-m-d', $startDate);
	$endDateObj = DateTime::createFromFormat('Y-m-d', $endDate);

	$mt_subscription = new WP_Query( $asc_subscriptiongs );
	if ( $mt_subscription->have_posts() ) {
		while( $mt_subscription->have_posts() ) {
			$mt_subscription->the_post();
			$postid = get_the_ID();

			$Subscription = new ScrtSubscription($postid);

			if(!empty($Subscription->sub_next_bill_date)){
				
				$dateToCheck = sc_maybe_format_date($Subscription->sub_next_bill_date, 'Y-m-d');
				$dateToCheckObj = DateTime::createFromFormat('Y-m-d', $dateToCheck);

				if ($dateToCheckObj >= $startDateObj && $dateToCheckObj <= $endDateObj) {
					$editlink = get_admin_url(null, "post.php?post={$Subscription->id}&action=edit");
					$arr = [ 'id' => $Subscription->id , 'customer_name' => '<a href="'.$editlink.'">'.$Subscription->customer_name.'</a>' , 'product_name' => $Subscription->product_name , 'sub_next_bill_date' => $Subscription->sub_next_bill_date , 'sub_amount' => $Subscription->sub_amount];
					if($Subscription->sub_status == 'pending-payment') {
						continue;
					} else if($Subscription->sub_status == 'trialing') {
						$trialing_data[] = $arr;
					} else {
						echo $Subscription->sub_status.'<br>';
						$Subscription_data[] = $arr;
					}
				}
			}

			if($Subscription->cancel_date){
				$dateToCheck = sc_maybe_format_date($Subscription->cancel_date,'Y-m-d');
				$dateToCheckObj = DateTime::createFromFormat('Y-m-d', $dateToCheck);
				if ($dateToCheckObj >= $startDateObj && $dateToCheckObj <= $endDateObj) {
					$canceled_subscription++;
				}
			}
		}
	}

	$carttotal = array('total'=> 0);
	$completed_orders = 0;
	$pending_payment = 0;
	$trialing_payment = 0;
	$failed_payment = 0;

	$asc_subscriptiongs = array_merge($args, $asc_subscriptiongs);
	$all_subscription = count(get_posts($asc_subscriptiongs));

	$queryrfunded = new WP_Query( $argsfunded );
	  $refunded_amount_array = array();
	  $refunds_time_array = array();
	  if ( $queryrfunded->have_posts() ) {
		while( $queryrfunded->have_posts() ) {
			$queryrfunded->the_post();
			$postid = get_the_ID();

			$refund_logs_entrie = get_post_meta( $postid, '_sc_refund_log', true);
			if(is_array($refund_logs_entrie)){
				$refund_logs_entrie_count = count(get_post_meta( $postid, '_sc_refund_log', true));
				$refund_amount = array_sum(array_column($refund_logs_entrie, 'amount'));
				$refund_amount = (!$refund_amount) ? get_post_meta( $postid , '_sc_amount', true ) : $refund_amount;
				$refunds_time_array[]= $refund_logs_entrie_count;    
				$refunded_amount_array[]= $refund_amount;  
			}
		}
	}
	$refunded_amount = array_sum($refunded_amount_array);
	$refunded_time = array_sum($refunds_time_array);
   
	$query = new WP_Query( $asc_subscriptiongs );
	if ( $query->have_posts() ) {
		while( $query->have_posts() ) {
			$query->the_post();
			$order = new ScrtSubscription(get_the_ID());

			if($order->status == 'pending-payment'){
				$all_subscription --;
			}

			if($order->status == 'trialing'){
				$trialing_payment++;
			}
		}
	}
	wp_reset_postdata(); 
	
	$query = new WP_Query( $args );
	if ( $query->have_posts() ) {
		while( $query->have_posts() ) {
			$query->the_post();
			$order = new ScrtOrder(get_the_ID());

			if($order->status == 'completed' || $order->status == 'paid'){
				$completed_orders++;
			}
			if($order->status == 'pending-payment'){
				$pending_payment++;
			}				
			if($order->status == 'failed'){
				$failed_payment++;
			}
			
			$carttotal['total'] += $order->amount;
		}
	}
	wp_reset_postdata(); 
	?>

	<!DOCTYPE html>
	<html lang="en">
		<head>
		<meta name="viewport" content="width=device-width">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="x-apple-disable-message-reformatting">
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<style type="text/css" data-premailer="ignore">
			.schedule-main.mail {padding: 15px;box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px;width: 100%;max-width: 700px;margin: auto;margin-top: 30px;}
			.summary-text li {margin-bottom: 5px;display: flex;justify-content: space-between;padding: 5px;font-size: 16px;border-bottom: 1px solid #ededed;}
			.summary {display: flex;align-items: center;padding: 20px;background-color: #f5f5f5;font-family: Arial, sans-serif;}
			.schedule-main {text-align: center;}
			.summary-text {width: 100%;}
			.summary-text p {margin-bottom: 10px;}
			.summary-text ul {list-style: none;margin: auto;padding: 0 30px;margin-top: 30px;}
			.schedule-main.mail .logo img {height: 150px;object-fit: cover;}
			.summary-text h4 {margin: 20px 0 10px 0;font-size: 18px;text-align: left;}
			.summary-text table {width: 100%;border-collapse: collapse;margin-bottom: 20px;}
			.summary-text th, .summary-text td {padding:7px 10px;text-align: left;border: 1px solid #ddd;}
			.summary-text th {font-weight: bold;background-color: #f5f5f5;}
			span.week-date, span.next-week-date {font-weight: 600;text-decoration: underline;}
		</style>
	</head>
	<body class="body" id="body" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
		<div class="schedule-main mail">
			<div class="logo">
				<?php
				if(!empty($_sc_company_logo)){ ?>
					<img src="<?php echo $_sc_company_logo ?>" alt="Logo">
				<?php }else{ ?>
					<span><?php echo $site_name ?></span>
				<?php } ?>
			</div>
			<div class="summary-text">
				<p class="header-paragraph"><?php esc_html_e('Here is your summary for', 'ncs-cart'); ?> <strong class="site-name"> <?php echo $site_name ?> </strong>
					<?php if($get_schedule_data == 'mt_weekly') : ?>
						<?php esc_html_e('for the week of', 'ncs-cart'); ?>  <span class="week-date"><?php echo $date_1 ?> - <?php echo $date_2 ?></span>.
					<?php elseif($get_schedule_data == 'mt_semi_monthly') : ?>
						<?php esc_html_e('for', 'ncs-cart'); ?>  <span class="week-date"><?php echo $date_1 ?> - <?php echo $date_2 ?></span>.
					<?php else: ?>
						<?php esc_html_e('for', 'ncs-cart'); ?>  <span class="week-date"><?php echo $date_1 ?></span>.
					<?php endif; ?>
				</p>
				<ul class="revenue-list">
					<li><span class="revenue-label"><?php esc_html_e('Gross revenue', 'ncs-cart'); ?>  :</span> <span class="revenue-amount"><?php sc_formatted_price($carttotal['total']); ?></span></li>
					<li><span class="revenue-label"><?php esc_html_e('Refunds', 'ncs-cart'); ?>  :</span> <span class="revenue-amount"><?php sc_formatted_price($refunded_amount); ?></span></li>	
					<li><span class="revenue-label"><?php esc_html_e('Net revenue', 'ncs-cart'); ?>  :</span> <span class="revenue-amount"><?php sc_formatted_price($carttotal['total'] - $refunded_amount); ?></span></li>
					<li><span class="revenue-label"><?php esc_html_e('Completed Orders', 'ncs-cart'); ?>  :</span> <span class="revenue-amount"><?php echo $completed_orders ?></span></li>
					<li><span class="revenue-label"><?php esc_html_e('Pending Orders', 'ncs-cart'); ?>  :</span> <span class="revenue-amount"><?php echo $pending_payment ?></span></li>
					<li><span class="revenue-label"><?php esc_html_e('Failed Orders', 'ncs-cart'); ?>  :</span> <span class="revenue-amount"><?php echo $failed_payment ?></span></li>
					<li><span class="revenue-label"><?php esc_html_e('Refunded Orders', 'ncs-cart'); ?>  :</span> <span class="revenue-amount"><?php echo $refunded_time ?></span></li>
					<li><span class="revenue-label"><?php esc_html_e('Subscriptions Started', 'ncs-cart'); ?>  :</span> <span class="revenue-amount"><?php echo $all_subscription ?></span></li>
					<li><span class="revenue-label"><?php esc_html_e('Trials Started', 'ncs-cart'); ?>  :</span> <span class="revenue-amount"><?php echo $trialing_payment ?></span></li>
					<li><span class="revenue-label"><?php esc_html_e('Subscriptions Canceled', 'ncs-cart'); ?>  :</span> <span class="revenue-amount"><?php echo $canceled_subscription ?></span></li>
				</ul>
				<p>
					<?php if($get_schedule_data == 'mt_weekly') : ?>
						<?php esc_html_e('Coming up for the week of', 'ncs-cart'); ?>  <span class="next-week-date"><?php echo $datenxt_1 ?>  - <?php echo $datenxt_2 ?></span>
					<?php elseif($get_schedule_data == 'mt_semi_monthly') : ?>
						<?php esc_html_e('Coming up for', 'ncs-cart'); ?>  <span class="next-week-date"><?php echo $datenxt_1 ?>  - <?php echo $datenxt_2 ?></span>
					<?php else: ?>
						<?php esc_html_e('Coming up for', 'ncs-cart'); ?>  <span class="next-week-date"><?php echo $date_2 ?></span>
					<?php endif; ?>
				</p>
				<h4 class="ending-trials-heading"><?php esc_html_e('Ending Trials', 'ncs-cart'); ?>:</h4>
				<table class="ending-trials-table">
				<thead>
					<tr>
						<th><?php esc_html_e('Customer', 'ncs-cart'); ?> </th>
						<th><?php esc_html_e('Product', 'ncs-cart'); ?> </th>
						<th><?php esc_html_e('End date', 'ncs-cart'); ?> </th>
						<th><?php esc_html_e('Recurring amount', 'ncs-cart'); ?> </th>
					</tr>
				</thead>
				<tbody>
					<?php 
					if(count($trialing_data) > 0){
						foreach ($trialing_data as $key => $value) { ?>
							<tr>
								<td><?php echo $value['customer_name'] ?></td>
								<td><?php echo $value['product_name'] ?></td>
								<td><?php echo sc_maybe_format_date($value['sub_next_bill_date']); ?></td>
								<td><?php sc_formatted_price($value['sub_amount']); ?></td>
							</tr>
						<?php } 
					} else {
						echo '<tr><td colspan="4">'.esc_html__('Nothing found for this time period', 'ncs-cart').'</td></tr>';
					} ?>
				</tbody>
				</table>
				<h4 class="subscription-renewals-heading"><?php esc_html_e('Subscription Renewals', 'ncs-cart'); ?>:</h4>
				<table class="subscription-renewals-table">
					<thead>
						<tr>
							<th><?php esc_html_e('Customer', 'ncs-cart'); ?></th>
							<th><?php esc_html_e('Product', 'ncs-cart'); ?></th>
							<th><?php esc_html_e('Next bill date', 'ncs-cart'); ?></th>
							<th><?php esc_html_e('Amount', 'ncs-cart'); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php 
					if(count($Subscription_data) > 0){
						foreach ($Subscription_data as $key => $value) { ?>
							<tr>
								<td><?php echo $value['customer_name'] ?></td>
								<td><?php echo $value['product_name'] ?></td>
								<td><?php echo sc_maybe_format_date($value['sub_next_bill_date']); ?></td>
								<td><?php sc_formatted_price($value['sub_amount']); ?></td>
							</tr>
						<?php } 
					} else {
						echo '<tr><td colspan="4">'.esc_html__('Nothing found for this time period', 'ncs-cart').'</td></tr>';
					} ?>
					</tbody>
				</table>
			</div>
		</div>
	</body>
	</html>
<?php 
	return ob_get_clean();
}
	function ncs_schedule_email_function() {	
		$to_email = get_option( 'sc_admin_email' );
		if(empty($to_email)){
			$to_email = get_option( 'admin_email' );
		}

		$from_name = get_option('_sc_email_from_name', '');
    	$from_email = get_option('_sc_email_from_email', '');
		$subject  = sprintf(esc_html__("Your %s Summary Report", 'ncs-cart'), apply_filters('studiocart_plugin_title', 'Studiocart'));
		$body 	  = ncs_schedule_email_temp();
		$headers = array(
			'Content-Type: text/html; charset=UTF-8', 
			'From: '.$from_name.' <'.$from_email.'>'
		);
		wp_mail($to_email, $subject, $body, $headers);
	}

	add_filter( 'cron_schedules', 'ncs_email_schedule_hook' );
	function ncs_email_schedule_hook( $schedules ) {
		$schedules['mt_daily'] 		  = array('interval'  => 86400, 	 'display'   => __( 'Daily', 'ncs-cart' ));
		$schedules['mt_weekly'] 	  = array('interval'  => 604800, 	 'display'   => __( 'Weekly', 'ncs-cart' ));
		$schedules['mt_semi_monthly'] = array('interval'  => 1209600,    'display'   => __( 'Semi Monthly', 'ncs-cart' ));
		return $schedules;
	}	

	$get_schedule_data = get_option('sc_report_schedule');
	if(!empty($get_schedule_data)){
		if($get_schedule_data == 'mt_none'){
			wp_clear_scheduled_hook( 'ncs_email_schedule_hook');
			update_option('current_schedule_val', '' );
		}else{
			$current_schedule = get_option('current_schedule_val');
			if($current_schedule == $get_schedule_data){
				if ( ! wp_next_scheduled( 'ncs_email_schedule_hook' ) ) {
					wp_schedule_event( time(), $get_schedule_data, 'ncs_email_schedule_hook');
					update_option('current_schedule_val', $get_schedule_data );
				}
			}else{
				wp_clear_scheduled_hook( 'ncs_email_schedule_hook');
				if ( ! wp_next_scheduled( 'ncs_email_schedule_hook' ) ) {
					wp_schedule_event( time(), $get_schedule_data, 'ncs_email_schedule_hook');
					update_option('current_schedule_val', $get_schedule_data );
				}
			}
		}
	}

	add_action( 'ncs_email_schedule_hook', 'ncs_schedule_function' );
	function ncs_schedule_function() {
		ncs_schedule_email_function();
	}


