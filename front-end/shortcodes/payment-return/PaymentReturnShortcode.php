<?php

namespace H4APlugin\WPGroupSubs\Shortcodes;


use H4APlugin\Core\Common\Currencies;
use H4APlugin\Core\Common\Notices;
use H4APlugin\Core\Common\PaypalPDT;
use H4APlugin\Core\Config;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\wp_build_url;
use function H4APlugin\Core\wp_debug_log;
use function H4APlugin\Core\wp_error_log;

use function H4APlugin\Core\wp_get_error_system;
use H4APlugin\WPGroupSubs\Common\Member;
use H4APlugin\WPGroupSubs\Common\Payment;
use H4APlugin\WPGroupSubs\Common\Plan;
use function H4APlugin\Core\wp_redirect_404;
use H4APlugin\WPGroupSubs\Common\Subscriber;

class PaymentReturnShortcode extends Shortcode {
	public function check_page(){
		global $post;
		$pattern = '/(\['.$this->tag.'\])/';
		if( !empty( $post->post_content ) && preg_match(  $pattern, $post->post_content) ){
			if( Member::isLoggedIn() || Subscriber::isLoggedIn() ){
				wp_redirect( wp_build_url( "wgs-profile", H4A_WGS_PLUGIN_LABEL_MY_PROFILE ) );
			}else {
				add_action('wp_enqueue_scripts', array( $this , 'set_styles'));
				add_filter( 'template_include', array( $this, 'page_template' ), 99 );
			}
		}
	}

	public static function getCallBack( $atts = null ){
		if(!isset($_GET['tx'])) {
			wp_redirect_404();
			exit;
		}else{

			/*$atts = shortcode_atts(
				array(
					'response' => 'no response'
				), $atts, 'wgs-return' );*/

			self::save_and_get_notices();

			$output = '';

			ob_start();

			//HTML Template
			include_once dirname( __FILE__ ) . '/views/view-payment-return.php';

			// Get the contents and clean the buffer
			$output .= ob_get_contents();
			ob_end_clean();

			return $output;

		}
	}

	private static function save_and_get_notices() {
		wp_debug_log();
		$current_plugin_domain = get_current_plugin_domain();

		$tx = $_GET['tx'];

		$payment = Payment::getPaymentByTxnId( $_GET['tx'], "paypal" );
		if ( !$payment ) {
			$pdt = new PaypalPDT( $tx );
			try {
				$pdt->back2Paypal();
			} catch ( \Exception $e ) {
				wp_error_log( $e );
			}

			if ( empty( $pdt->error ) ) {
				$subscriber_id = Subscriber::getSubscriberIdByEmail( $pdt->response["payer_email"] );
				$plan_id       = Plan::getPlanIdByName( $pdt->response["item_name"] );
				if ( ! $plan_id ) {
					$message_error = __( "Payment checking error!", $current_plugin_domain ) . '<br/>' . wp_get_error_system();
					Notices::setNotice( $message_error, "error" );
				} else {
					$wgs_currency_options = get_option( "wgs-currency-options" );
					$data_payment         = array(
						"payment_date"   => $pdt->response['payment_date'],
						"subscriber_id"  => (int) $subscriber_id,
						"txn_id"         => $pdt->response['txn_id'],
						"amount"         => Currencies::format_string_price( $pdt->response['mc_gross'], $wgs_currency_options['currency'], $wgs_currency_options['currency_position'] ),
						"payment_status" => $pdt->response['payment_status'],
						"payment_type"   => "paypal",
						"pending_reason" => ( ! empty( $pdt->response['pending_reason'] ) ) ? $pdt->response['pending_reason'] : null,
						"plan_id"        => (int) $plan_id,
						"email"          => $pdt->response['payer_email']
					);
					$args                 = Config::get_item_by_ref( "payment" );
					$payment              = new Payment( $data_payment, "save", $args );
					$payment->save();
					if( !empty( $payment->payment_status ) ){
						switch( $payment->payment_status ){
							case 'Completed' :
								Notices::setNotice( __( "The payment has been done.", $current_plugin_domain ), "success" );
								break;
							default:
								Notices::setNotice( Payment::getErrorPaymentStatus( $payment->payment_status ), "warning" );
								break;
						}
					}
					else{
						$message_error = __( "Payment checking error!", $current_plugin_domain ) . '<br/>' . wp_get_error_system();
						Notices::setNotice( $message_error, "error" );
					}
				}
			} else {
				$message_error = __( "Payment checking error!", $current_plugin_domain ) . '<br/>' . $pdt->error;
				Notices::setNotice( $message_error, "error" );
			}

		}

	}


	//CSS stylesheets
	public function set_styles() {
		wp_enqueue_style( 'wgsstyle', H4A_WGS_PLUGIN_DIR_URL . 'front-end/css/wgs-front-end.css' );

		if( !empty( $_GET['item_name'] ) ){
			$plan_type = Plan::getPlanTypeByName( $_GET['item_name'] );
			if( $plan_type === 'single' ){
				wp_enqueue_style( "h4afrontendform", H4A_WGS_PLUGIN_DIR_URL . "core/front-end/features/form/css/front-end-form-style.css" );
			}
		}
	}

	public static function getReturnPageID(){
		global $wpdb;
		// Start query string
		$forms_query_string       = "SELECT ID FROM {$wpdb->prefix}posts WHERE post_type='wgs-return'";
		// Return results
		$result = $wpdb->get_results( $forms_query_string, ARRAY_A );
		if( count( $result ) === 0 ){
			wp_error_log( "Error system : WGS Return does not exist!" );
			return false;
		}
		else if( count( $result ) > 1 ){
			wp_error_log( "Error system : WGS Return is not unique!" );
			return false;
		}else{
			return $result[0]['ID'];
		}
	}
}