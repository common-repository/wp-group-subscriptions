<?php

use function \H4APlugin\Core\wp_debug_log;
use function \H4APlugin\Core\get_current_plugin_prefix;
use H4APlugin\WPGroupSubs\Common\Subscriber;
use H4APlugin\Core\Common\Email;
use H4APlugin\WPGroupSubs\Common\Plan;
use H4APlugin\Core\Common\Currencies;

function wgs_scheduled_task_activation(){
	if ( ! wp_next_scheduled( 'wgs_scheduled_plan_expirations_checking' ) ) {
		wp_schedule_event( time(), 'daily', 'wgs_scheduled_plan_expirations_checking' );
	}

}

function wgs_plan_expirations_checking(){
	wp_debug_log();
	global $wpdb;
	$current_plugin_domain = get_current_plugin_prefix();
	$query = "SELECT * FROM {$wpdb->prefix}" . $current_plugin_domain . "subscribers WHERE status = 'active';";
	$results = $wpdb->get_results( $query, ARRAY_A );
	if( count( $results ) === 0 ){
		return false;
	}else{
        foreach ( $results as $res_subs ){
        	$current_subs = new Subscriber( $res_subs );
	        $output = $current_subs->enableSubscriber();
	        wp_debug_log( "update subs : " . $output  );
	        if( $output === 1 ){
		        $current_plan_expired = new Plan( $current_subs->plan_id, "read" );
		        $body = __( "Your plan at " . get_bloginfo( "name" ) . " was expired today." , $current_plugin_domain )."<br/><br/>";
		        if( $current_plan_expired->price === 0 ){
			        $text_explanation = __( "For more explanation, please contact us.");
		        }else{
			        $wgs_currency_options = get_option( "wgs-currency-options" );
			        $wgs_paypal_options = get_option( "wgs-paypal-options" );
			        $str_price = Currencies::format_string_price( $current_plan_expired->price, $wgs_currency_options['currency'], $wgs_currency_options['currency_position'] );
			        $text_explanation = sprintf( __( "If you want to renew your subscription for the plan '%s', please make a paypal payment of this amount %s for the following paypal account '%s'.", $current_plugin_domain ),
				        $current_plan_expired->plan_name,
				        $str_price,
				        $wgs_paypal_options['paypal_email']
			        );
		        }

		        $body .= $text_explanation . "<br/>";
		        $body .= sprintf( __( "The %s team", $current_plugin_domain ), get_bloginfo( "name" ) );
		        $data_email = array(
		        	'to' => $current_subs->email,
			        'from' => get_bloginfo( 'name' ) . " <" . get_bloginfo( 'admin_email' ) . ">",
		        	'subject' => __( "Subscriber account plan expiration", $current_plugin_domain ),
		        	'body' => $body

		        );
	        	$email = new Email( $data_email );
	        	$return_email = $email->send();
	        	wp_debug_log( ( $return_email ) ? "true" : "false" );
	        }
        }
	}
	return false;
}