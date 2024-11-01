<?php
namespace H4APlugin\WPGroupSubs\Common;

use H4APlugin\Core\Common\H4AObjectTrait;
use H4APlugin\Core\Common\Item;
use H4APlugin\Core\Config;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\get_current_plugin_prefix;
use function H4APlugin\Core\is_float_as_string;
use function H4APlugin\Core\is_number;
use function H4APlugin\Core\wp_debug_log;
use function H4APlugin\Core\wp_error_log;
use function H4APlugin\Core\wp_info_log;

class Payment extends Item {

	use H4AObjectTrait;

	public $payment_id;
	public $payment_date;
	public $subscriber_id;
	public $first_name;
	public $last_name;
	public $group_name;
	public $txn_id;
	public $amount;
	public $plan_id;
	public $plan_name;
	public $payment_status;
	public $payment_type;
	public $pending_reason;
	public $email;

	public function get_item_to_save( $data ){
		$mandatory_params = array( "payment_date", "amount", "payment_status", "payment_type", "email" );
		$f_data = array(
			'payment_date'   => $data['payment_date'],
			'amount'         => sanitize_text_field( $data['amount'] ),
			'payment_status' => sanitize_text_field( $data['payment_status'] ),
			'payment_type'   => sanitize_text_field( $data['payment_type'] ),
			'email'          => sanitize_email( $data['email'] ),
		);
		$this->setObject( $mandatory_params, $f_data );

		$this->payment_id     = null;
		if( isset( $data['subscriber_id'] ) )
			$this->subscriber_id  = $data['subscriber_id'];
		if( isset( $data['txn_id'] ) )
			$this->txn_id         = $data['txn_id'];
		if( isset( $data['plan_id'] ) )
			$this->plan_id        = $data['plan_id'];
		if( isset( $data['pending_reason'] ) )
			$this->pending_reason = sanitize_text_field( $data['pending_reason'] );
	}

	protected function get_item_to_read( $data ){
		$this->payment_id     = $data['payment_id'];
		$this->payment_date   = $data['payment_date'];
		$this->email          = $data['email'];
		$this->payment_status = $data['payment_status'];
		$this->payment_type   = $data['payment_type'];
		$this->amount         = $data['amount'];
		if( !empty( $data['subscriber_id'] ) )
			$this->subscriber_id = $data['subscriber_id'];
		if( !empty( $data['first_name'] ) )
			$this->first_name = $data['first_name'];
		if( !empty( $data['last_name'] ) )
			$this->last_name      = $data['last_name'];
		if( !empty( $data['group_name'] ) )
			$this->group_name = $data['group_name'];
		if( !empty( $data['txn_id'] ) )
			$this->txn_id     = $data['txn_id'];
		if( !empty( $data['plan_id'] ) )
			$this->plan_id  = (int) $data['plan_id'];
		if( !empty( $data['plan_name'] ) )
			$this->plan_name  = $data['plan_name'];
	}

	protected function get_item_to_list( $data ){
		$this->payment_id     = $data['payment_id'];
		$this->payment_date   = $data['payment_date'];
		$this->email          = $data['email'];
		$this->payment_status = $data['payment_status'];
		$this->payment_type   = $data['payment_type'];
		$this->amount         = $data['amount'];
		if( !empty( $data['subscriber_id'] ) )
			$this->subscriber_id = $data['subscriber_id'];
		if( !empty( $data['first_name'] ) )
			$this->first_name = $data['first_name'];
		if( !empty( $data['last_name'] ) )
			$this->last_name      = $data['last_name'];
		if( !empty( $data['group_name'] ) )
			$this->group_name = $data['group_name'];
		if( !empty( $data['txn_id'] ) )
			$this->txn_id     = $data['txn_id'];
		if( !empty( $data['plan_name'] ) )
			$this->plan_name  = $data['plan_name'];

	}


	public function get_item( $id ){

		global $wpdb;

		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "payments WHERE payment_id = '" . $id . "';", ARRAY_A );

		if( count($results) === 0 ){
			wp_error_log( "Payment not found!");
		}else{
			foreach ( $results[0] as $column_name => $value ){
				if( is_float_as_string( $value ) ){
					$value = (float) $value;
				}else if( is_number( $value ) ){
					$value = (int) $value;
				}
				$this->$column_name = $value;
			}
		}

	}


	public function save() {
		wp_debug_log();
		global $wpdb;

		$data = array(
			'payment_date'   => $this->payment_date,
			'amount'         => $this->amount,
			'payment_status' => $this->payment_status,
			'payment_type'   => $this->payment_type,
			'email'          => $this->email
		);
		if( isset( $this->plan_id ) )
			$data['plan_id' ]	 = $this->plan_id;
		if( isset( $this->subscriber_id ) )
			$data['subscriber_id' ]	 = $this->subscriber_id;
		if( isset( $this->txn_id ) )
			$data['txn_id' ]	 = $this->txn_id;
		if( isset( $this->pending_reason ) )
			$data['pending_reason' ]	 = $this->pending_reason;

		$current_plugin_prefix = get_current_plugin_prefix();
		$res_ins = $wpdb->insert( "{$wpdb->prefix}{$current_plugin_prefix}payments", $data );
		if ( !$res_ins ) {
			var_dump( $data );
		}else {
			$payment_id = $wpdb->insert_id;
			wp_info_log( "New payment saved" );
			$current_payment = new Payment( $payment_id, "read" );
			if ( ! empty( $this->subscriber_id )
			     && ! empty( $this->plan_id )
			     && $current_payment->payment_status === "Completed"
			) {
				$args            = Config::get_item_by_ref( "subscriber" );
				$args['plan_id'] = $this->plan_id;
				$subscriber      = new Subscriber( $this->subscriber_id, "read", $args );
				//See Manual - Subscriber activation, user cases 2 and 3.
				$subscriber->enableSubscriber();
				$message_info = sprintf( "The subscriber '%s' was enabled after a payment", $subscriber->email );
				wp_info_log( $message_info );
				$plan_type = Plan::getPlanTypeById( $this->plan_id );
				$members   = Subscriber::getAllMembersById( $subscriber->subscriber_id );
				if ( $plan_type === "single" && count( $members ) === 1 ) {
					$first_member = $members[0];
					if ( $first_member instanceof Member ) {
						$first_member->activate();
						$message_info = sprintf( "The member '%s' was activated after a payment", $first_member->email );
						wp_info_log( $message_info );
					}
				} else {
					foreach ( $members as $member ) {
						if ( $member instanceof Member ) {
							if ( $member->last_activation !== null ) { // First activation is when the member fill it all information.
								$member->activate();
								$message_info = sprintf( "The member '%s' was activated after a payment", $member->email );
								wp_info_log( $message_info );
							}
						}
					}
				}
			}
		}
	}

	public static function getPaymentByTxnId( $txn_id, $payment_type ) {

		global $wpdb;

		$query = "SELECT * FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "payments WHERE txn_id = '" . $txn_id . "' AND payment_type = '" . $payment_type . "';";
		$results = $wpdb->get_results( $query, ARRAY_A );

		if ( count( $results ) === 1 ) {
			$args = Config::get_item_by_ref( "payment" );
			return new Payment( $results[0], "read", $args );
		}else {
			if( count( $results ) > 1 ){
				wp_error_log( "Error system : the request found " . count( $results ) . " payments with the same txn_id!" );
			}
			return false;
		}

	}

	public static function getReturnPageUrl() {
		
		/*$current_plugin_domain = get_current_plugin_domain();

		$page_title = __( H4A_WGS_PLUGIN_LABEL_RETURN, $current_plugin_domain );*/

		global $wpdb;

		// Return results
		$result = $wpdb->get_results( "SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'wgs-return' ;", ARRAY_A );

		if ( isset( $result[0] ) ) {
			$url = get_site_url() . "/?post_type=wgs-return&p=" . $result[0]['ID'];
		}

		return ( ! empty( $url ) ) ? $url : false;
	}

	public static function getErrorPaymentStatus( $payment_status ) {
		$current_plugin_domain = get_current_plugin_domain();

		$error = null;

		switch ( $payment_status ) {

			case "Denied":
				$error = __( "Your account is not active.", $current_plugin_domain ) . '<br/>' . __( "Your payment was denied.", $current_plugin_domain );
				break;

			case "Expired":
				$error = __( "Your account is not active.", $current_plugin_domain ) . '<br/>' . __( "Your authorization payment has expired and cannot be captured.", $current_plugin_domain );
				break;

			case "Failed":
				$error = __( "Your account is not active.", $current_plugin_domain ) . '<br/>' . __( "The payment has failed. This happens only if the payment was made from your bank account.", $current_plugin_domain );
				break;

			case "Pending":
				$error = __( "The payment is pending.", $current_plugin_domain ) . '<br/>' . __( "Your account will be active as soon as the payment is completed.", $current_plugin_domain );
				break;

			case "Refunded":
				$error = __( "Your account is not active.", $current_plugin_domain ) . '<br/>' . __( "The payment has been refunded.", $current_plugin_domain );
				break;

			case "Reversed":
				$error = __( "Your account is not active.", $current_plugin_domain ) . '<br/>' . __( "The payment has been refunded.", $current_plugin_domain );
				break;

			case "Processed":
				$error = __( "Your account is not active.", $current_plugin_domain ) . '<br/>' . __( "The payment is processing.", $current_plugin_domain );
				break;

			case "Voided":
				$error = __( "Your account is not active.", $current_plugin_domain ) . '<br/>' . __( "The payment was canceled.", $current_plugin_domain );
				break;

			case "Created":
			case "Canceled_Reversal":
				$error = __( "Your account is not active.", $current_plugin_domain ) . '<br/>' . __( "Payment error.", $current_plugin_domain );
				break;
		}

		return $error;
	}

	public static function getLastPaymentBySubscriberId( $subs_id, $is_completed ){

		global $wpdb;

		$query = "SELECT * FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "payments";
		$query .= " WHERE subscriber_id = " . $subs_id;
		if( $is_completed )
			$query .= " AND payment_status = 'Completed'";
		$query .= " ORDER BY payment_date DESC LIMIT 1;";
		$results = $wpdb->get_results( $query, ARRAY_A );
		if( count( $results ) === 1){
			return new Payment( $results[0] );
		}else{
			return false;
		}

	}

	public static function getAllPaymentsBySubscriberId( $subs_id, $is_completed ){

		global $wpdb;

		$query = "SELECT payment_id, payment_date, email, amount FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "payments";
		$query .= " WHERE subscriber_id = " . $subs_id;
		if( $is_completed )
			$query .= " AND payment_status = 'Completed'";
		$query .= " ORDER BY payment_date DESC;";
		$results = $wpdb->get_results( $query, ARRAY_A );

		if( count( $results ) >= 1){
			return $results;
		}else{
			return false;
		}

	}

	public static function getAllUnassignedPayments( $is_completed ){

		global $wpdb;

		$query = "SELECT payment_id, payment_date, email, amount FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "payments";
		$query .= " WHERE subscriber_id IS NULL ";
		if( $is_completed )
			$query .= " AND payment_status = 'Completed'";
		$query .= " ORDER BY payment_date DESC;";
		$results = $wpdb->get_results( $query, ARRAY_A );

		if( count( $results ) >= 1){
			return $results;
		}else{
			return false;
		}

	}
}