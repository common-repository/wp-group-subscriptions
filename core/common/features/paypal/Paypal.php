<?php

/**
 * Inspired of https://github.com/angelleye/paypal-php-library/
 */

namespace H4APlugin\Core\Common;


use H4APlugin\Core\Config;
use function H4APlugin\Core\error_log_array;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\wp_debug_log;
use function H4APlugin\Core\wp_error_log;
use function H4APlugin\Core\wp_get_error_back_end_system;
use H4APlugin\WPGroupSubs\Common\Payment;
use H4APlugin\WPGroupSubs\Common\Plan;
use H4APlugin\WPGroupSubs\Common\Subscriber;

class Paypal {

	private $is_sandbox = "";
	private $api_username = "";
	private $api_password = "";
	private $api_signature = "";
	//private $api_subject = "";
	private $api_version = "119.0";
	// private $api_button_source = "";
	private $APIMode = "";
	private $end_point_url = "";
	private $PathToCertKeyPEM = "";
	//private $SSL = "";
	//private $PrintHeaders = "";
	//private $LogResults = "";
	//private $LogPath = "";

	private $current_plugin_domain;

	public function __construct()
	{
		$wgs_paypal_options = get_option( "wgs-paypal-options" );
		$this->is_sandbox = ( !isset( $wgs_paypal_options['paypal_environment'] ) || $wgs_paypal_options['paypal_environment'] === "test" ) ? true : false;
		$this->api_username = ( isset( $wgs_paypal_options['paypal_api_username'] ) ) ? $wgs_paypal_options['paypal_api_username'] : "";
		$this->api_password = ( isset( $wgs_paypal_options['paypal_api_password'] ) ) ? $wgs_paypal_options['paypal_api_password'] : "";
		$this->api_signature = ( isset( $wgs_paypal_options['paypal_api_signature'] ) ) ? $wgs_paypal_options['paypal_api_signature'] : "";
		$this->end_point_url = ( $this->is_sandbox ) ? "https://api-3t.sandbox.paypal.com/nvp" : "https://api-3t.paypal.com/nvp";
	}

	/**
	 * Search PayPal transaction history for transactions that meet the specified criteria.
	 *
	 * The maximum number of transactions that can be returned from a TransactionSearch API call is 100.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray	Array structure of request data.
	 * @return	mixed[]	Returns an array structure of the PayPal HTTP response params as well as parsed errors and the raw request/response.
	 */
	private function TransactionSearch( $DataArray )
	{
		$NVPCredentials = "USER=" . $this->api_username . "&PWD=" . $this->api_password . "&VERSION=" . $this->api_version;
		$NVPCredentials .= "&SIGNATURE=" . $this->api_signature;
		$TSFieldsNVP = "&METHOD=TransactionSearch";
		$PayerNameNVP = "";

		// Transaction Search Fields
		$TSFields = isset($DataArray['TSFields']) ? $DataArray['TSFields'] : array();
		foreach($TSFields as $TSFieldsVar => $TSFieldsVal)
		{
			$TSFieldsNVP .= $TSFieldsVal != "" ? "&" . strtoupper($TSFieldsVar) . "=" . urlencode($TSFieldsVal) : "";
		}

		// Payer Name Fields
		$PayerName = isset($DataArray['PayerName']) ? $DataArray['PayerName'] : array();
		foreach($PayerName as $PayerNameVar => $PayerNameVal)
		{
			$PayerNameNVP .= $PayerNameVal != "" ? "&" . strtoupper($PayerNameVar) . "=" . urlencode($PayerNameVal) : "";
		}

		//$NVPRequest = $NVPCredentials . $TSFieldsNVP . $PayerNameNVP;
		$NVPRequest = $NVPCredentials  . $TSFieldsNVP;
		$NVPResponse = $this->CURLRequest($NVPRequest);
		$NVPRequestArray = $this->NVPToArray($NVPRequest);
		$NVPResponseArray = $this->NVPToArray($NVPResponse);

		$Errors = $this->GetErrors($NVPResponseArray);

		//TODO: Logs management
		//$this->Logger($this->LogPath, __FUNCTION__.'Request', $this->MaskAPIResult($NVPRequest));
		//$this->Logger($this->LogPath, __FUNCTION__.'Response', $NVPResponse);

		$SearchResults = array();
		$n = 0;
		while(isset($NVPResponseArray['L_TIMESTAMP' . $n . ""]))
		{
			$LTimestamp = isset($NVPResponseArray['L_TIMESTAMP' . $n . ""]) ? $NVPResponseArray['L_TIMESTAMP' . $n . ""] : "";
			$LTimeZone = isset($NVPResponseArray['L_TIMEZONE' . $n . ""]) ? $NVPResponseArray['L_TIMEZONE' . $n . ""] : "";
			$LType = isset($NVPResponseArray['L_TYPE' . $n . ""]) ? $NVPResponseArray['L_TYPE' . $n . ""] : "";
			$LEmail = isset($NVPResponseArray['L_EMAIL' . $n . ""]) ? $NVPResponseArray['L_EMAIL' . $n . ""] : "";
			$LName = isset($NVPResponseArray['L_NAME' . $n . ""]) ? $NVPResponseArray['L_NAME' . $n . ""] : "";
			$LTransID = isset($NVPResponseArray['L_TRANSACTIONID' . $n . ""]) ? $NVPResponseArray['L_TRANSACTIONID' . $n . ""] : "";
			$LStatus = isset($NVPResponseArray['L_STATUS' . $n . ""]) ? $NVPResponseArray['L_STATUS' . $n . ""] : "";
			$LAmt = isset($NVPResponseArray['L_AMT' . $n . ""]) ? $NVPResponseArray['L_AMT' . $n . ""] : "";
			$LFeeAmt = isset($NVPResponseArray['L_FEEAMT' . $n . ""]) ? $NVPResponseArray['L_FEEAMT' . $n . ""] : "";
			$LNetAmt = isset($NVPResponseArray['L_NETAMT' . $n . ""]) ? $NVPResponseArray['L_NETAMT' . $n . ""] : "";

			$CurrentItem = array(
				'L_TIMESTAMP' => $LTimestamp,
				'L_TIMEZONE' => $LTimeZone,
				'L_TYPE' => $LType,
				'L_EMAIL' => $LEmail,
				'L_NAME' => $LName,
				'L_TRANSACTIONID' => $LTransID,
				'L_STATUS' => $LStatus,
				'L_AMT' => $LAmt,
				'L_FEEAMT' => $LFeeAmt,
				'L_NETAMT' => $LNetAmt
			);

			array_push($SearchResults, $CurrentItem);
			$n++;
		}

		$NVPResponseArray['ERRORS'] = $Errors;
		$NVPResponseArray['SEARCHRESULTS'] = $SearchResults;
		$NVPResponseArray['REQUESTDATA'] = $NVPRequestArray;
		$NVPResponseArray['RAWREQUEST'] = $NVPRequest;
		$NVPResponseArray['RAWRESPONSE'] = $NVPResponse;

		return $NVPResponseArray;


	}

	/**
	 * Send the API request to PayPal using CURL.
	 *
	 * @access	public
	 * @param	string	$Request		Raw API request string.
	 * @return	string	$Response		Returns the raw HTTP response from PayPal.
	 */
	private function CURLRequest($Request = "")
	{
		set_time_limit(0);
		$curl = curl_init();
		// curl_setopt($curl, CURLOPT_HEADER,TRUE);
		curl_setopt($curl, CURLOPT_VERBOSE, $this->is_sandbox);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_TIMEOUT, 500);
		curl_setopt($curl, CURLOPT_URL, $this->end_point_url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $Request);

		if($this->APIMode == 'Certificate')
		{
			curl_setopt($curl, CURLOPT_SSLCERT, $this->PathToCertKeyPEM);
		}

		$Response = curl_exec($curl);

		/*
		 * If a cURL error occurs, output it for review.
		 */
		if($this->is_sandbox)
		{
			if(curl_error($curl))
			{
				echo curl_error($curl).'<br /><br />';
			}
		}

		curl_close($curl);
		return $Response;
	}

	/**
	 * Convert an NVP string to an array with URL decoded values.
	 *
	 * @access	public
	 * @param	string	$NVPString	Name-value-pair string that you would like to convert to an array.
	 * @return	mixed[]	Returns the NVP string as an array structure.
	 */
	private function NVPToArray($NVPString)
	{
		$proArray = array();
		while(strlen($NVPString))
		{
			// name
			$keypos= strpos($NVPString,'=');
			$keyval = substr($NVPString,0,$keypos);
			// value
			$valuepos = strpos($NVPString,'&') ? strpos($NVPString,'&'): strlen($NVPString);
			$valval = substr($NVPString,$keypos+1,$valuepos-$keypos-1);
			// decoding the respose
			$proArray[$keyval] = urldecode($valval);
			$NVPString = substr($NVPString,$valuepos+1,strlen($NVPString));
		}

		return $proArray;

	}

	/**
	 * Get all errors returned from PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray	Array structure of PayPal NVP response.
	 * @return	mixed[]	$Errors		Returns an array structure of all errors / warnings returned in a PayPal HTTP response.
	 */
	private function GetErrors($DataArray)
	{

		$Errors = array();
		$n = 0;
		while(isset($DataArray['L_ERRORCODE' . $n . '']))
		{
			$LErrorCode = isset($DataArray['L_ERRORCODE' . $n . '']) ? $DataArray['L_ERRORCODE' . $n . ''] : '';
			$LShortMessage = isset($DataArray['L_SHORTMESSAGE' . $n . '']) ? $DataArray['L_SHORTMESSAGE' . $n . ''] : '';
			$LLongMessage = isset($DataArray['L_LONGMESSAGE' . $n . '']) ? $DataArray['L_LONGMESSAGE' . $n . ''] : '';
			$LSeverityCode = isset($DataArray['L_SEVERITYCODE' . $n . '']) ? $DataArray['L_SEVERITYCODE' . $n . ''] : '';

			$CurrentItem = array(
				'L_ERRORCODE' => $LErrorCode,
				'L_SHORTMESSAGE' => $LShortMessage,
				'L_LONGMESSAGE' => $LLongMessage,
				'L_SEVERITYCODE' => $LSeverityCode
			);

			array_push($Errors, $CurrentItem);
			$n++;
		}

		return $Errors;

	}

	public static function getUrlPaypal(){
		$wgs_paypal_options = get_option( "wgs-paypal-options" );
		return ( $wgs_paypal_options['paypal_environment'] === "prod" ) ? "https://www.paypal.com/cgi-bin/webscr" : "https://www.sandbox.paypal.com/cgi-bin/webscr";
	}

	public function downloadTransactionsInPayments( $start_date ){
		$start_date = $start_date . "T00:00:00";
		wp_debug_log( $start_date );
		$default_start_date = gmdate("Y-m-d\\TH:i:sZ",strtotime('now - 7 months'));
		wp_debug_log( $default_start_date );

		$TSFields = array(
			'startdate' => $start_date, 					// Required.  The earliest transaction date you want returned.  Must be in UTC/GMT format.  2008-08-30T05:00:00.00Z
			'enddate' => '', 							// The latest transaction date you want to be included.
			'email' => '', 								// Search by the buyer's email address.
			'receiver' => '', 							// Search by the receiver's email address.
			'receiptid' => '', 							// Search by the PayPal account optional receipt ID.
			'transactionid' => '', 						// Search by the PayPal transaction ID.
			'invnum' => '', 							// Search by your custom invoice or tracking number.
			'acct' => '', 								// Search by a credit card number, as set by you in your original transaction.
			'auctionitemnumber' => '', 					// Search by auction item number.
			'transactionclass' => '', 					// Search by classification of transaction.  Possible values are: All, Sent, Received, MassPay, MoneyRequest, FundsAdded, FundsWithdrawn, Referral, Fee, Subscription, Dividend, Billpay, Refund, CurrencyConversions, BalanceTransfer, Reversal, Shipping, BalanceAffecting, ECheck
			'amt' => '', 								// Search by transaction amount.
			'currencycode' => '', 						// Search by currency code.
			'status' => '',  							// Search by transaction status.  Possible values: Pending, Processing, Success, Denied, Reversed
			'profileid' => ''							// Recurring Payments profile ID.  Currently undocumented but has tested to work.
		);

		$PayerName = array(
			'salutation' => '', 						// Search by payer's salutation.
			'firstname' => '', 							// Search by payer's first name.
			'middlename' => '', 						// Search by payer's middle name.
			'lastname' => '', 							// Search by payer's last name.
			'suffix' => ''	 							// Search by payer's suffix.
		);

		$PayPalRequest = array(
			'TSFields' => $TSFields,
			'PayerName' => $PayerName
		);

		$response = $this->TransactionSearch($PayPalRequest);
		error_log_array( $response );
		if( $response['ACK'] === "Success" ){
			$return = false;
			if( !empty( $response['SEARCHRESULTS'] ) ){
				$number_Payments_imported = 0;
				foreach ( $response['SEARCHRESULTS'] as $res_payment ){
					error_log_array( $res_payment );
					$is_payment = Payment::getPaymentByTxnId( (string) $res_payment["L_TRANSACTIONID"], "paypal" );
					if(
						!$is_payment
						&& !empty( $res_payment["L_EMAIL"] )
						&& !empty( $res_payment["L_TRANSACTIONID"] )
						&& !empty( $res_payment["L_STATUS"] )
						&& !empty( $res_payment["L_AMT"] )
						&& !empty( $res_payment["L_TIMESTAMP"] )
					){
						$data_payment = array(
							"email" => $res_payment["L_EMAIL"],
							"txn_id" => $res_payment["L_TRANSACTIONID"],
							"payment_status" => $res_payment["L_STATUS"],
							"amount" => $res_payment["L_AMT"],
							"payment_type" => "paypal",
							"payment_date" => $res_payment["L_TIMESTAMP"]
						);
						$args = Config::get_item_by_ref( "payment" );
						$payment = new Payment( $data_payment, "save", $args );
						$payment->save();
						$number_Payments_imported++;
						$return = true;
					}
				}
				Notices::setNotice( sprintf( __( "Paypal request was successful - number of payments imported '%d'.", $this->current_plugin_domain ), $number_Payments_imported ), "success", true );
				return $return;
			}else{
				Notices::setNotice( sprintf( __( "Paypal request was successful - There is no payment to import.", $this->current_plugin_domain ) ), "success", true );
				return $return;
			}
		}else{
			if( $response['ACK'] === "Failure" ){
				Notices::setNotice( sprintf( __( "Impossible to get Paypal Payments because of a Paypal error system : '%s'.", $this->current_plugin_domain ), $response['L_SHORTMESSAGE0'] ), "error", true );
			}
		}
		return false;
	}


	public static function runPayPalIPN($email, $customer_email = false, $confirmation_email = true, $log_file = true ){

		// Set this to true to use the sandbox endpoint during testing:
		$wgs_paypal_options = get_option( "wgs-paypal-options" );
		$enable_sandbox = ( $wgs_paypal_options['paypal_environment'] === "test" ) ? true : false ;

		$ipn = new PaypalIPN();
		if ($enable_sandbox) {
			$ipn->useSandbox();
		}
		try {
			$verified = $ipn->verifyIPN();
			// Check the receiver email to see if it matches your list of paypal email addresses
			$receiver_email_found = false;
			if (strtolower( $_POST["receiver_email"] ) === strtolower( $email ) ) {
				$receiver_email_found = true;
			}
			$subject = "";
			if ($_POST["test_ipn"] == 1) {
				$subject = __( "Payment confirmation");
			}
			$paypal_ipn_status = "VERIFICATION FAILED";
			if ($verified) {
				$paypal_ipn_status = "RECEIVER EMAIL MISMATCH";
				if ($receiver_email_found) {
					$paypal_ipn_status = "Completed Successfully";

					// This is an example for sending an automated email to the customer when they purchases an item for a specific amount:
					if ( $customer_email && $_POST["payment_status"] == "Completed" ) {
						self::send_customer_email_purchase_confirmation( $subject );
					}


				}
			}elseif ( $enable_sandbox ) {
				if ($_POST["test_ipn"] != 1) {
					$paypal_ipn_status = "RECEIVED FROM LIVE WHILE SANDBOXED";
				}
			}elseif ($_POST["test_ipn"] == 1) {
				$paypal_ipn_status = "RECEIVED FROM SANDBOX WHILE LIVE";
			}

			if( in_array( $paypal_ipn_status, array( "Completed Successfully", "RECEIVED FROM LIVE WHILE SANDBOXED", "RECEIVED FROM SANDBOX WHILE LIVE" ) ) ){
				// Process IPN
				// A list of variables are available here:
				// https://developer.paypal.com/webapps/developer/docs/classic/ipn/integration-guide/IPNandPDTVariables/
				$wgs_currency_options = get_option( "wgs-currency-options" );
				$amount  = Currencies::format_string_price( $_POST['mc_gross'], $wgs_currency_options['currency'], $wgs_currency_options['currency_position'] );
				$plan_id = Plan::getPlanIdByName( $_POST['item_name'] ) ;
				$subscriber_id = Subscriber::getSubscriberIdByEmail( $_POST['payer_email'] );
				$data_payment = array(
					"payment_date" => $_POST['payment_date'],
					"subscriber_id" => $subscriber_id,
					"txn_id" => $_POST['txn_id'],
					"amount" => $amount,
					"payment_status" => $_POST['payment_status'],
					"payment_type" => $_POST['payment_type'],
					"pending_reason" => ( !empty( $_POST['pending_reason'] ) )? $_POST['pending_reason'] : null,
					"plan_id" => $plan_id,
					"email" => $_POST['payer_email']
				);
				$args = Config::get_item_by_ref( "payment" );
				$payment = new Payment( $data_payment, "save", $args );
				$payment->save();
			}


			if( $confirmation_email || $log_file ){
				if( $confirmation_email ){
					self::send_owner_email_purchase_confirmation( $subject );
				}
				if( $log_file ){
					self::write_log( $subject, $paypal_ipn_status );
				}
				$data_text = "";
				foreach ($_POST as $key => $value) {
					$data_text .= $key . " = " . $value . "\r\n";
				}

			}

			// Reply with an empty 200 response to indicate to paypal the IPN was received correctly
			header("HTTP/1.1 200 OK");

			$output['ipn_status'] = $paypal_ipn_status;
			if ( isset( $payment ) && $paypal_ipn_status !== "VERIFICATION FAILED" ) {
				$output['last_subscription_date'] = $payment->payment_date;
			}else{
				$output['ipn_status'] = $paypal_ipn_status;
			}

			return $output;
		} catch ( \Exception $e ) {
			wp_error_log( $e );
			Notices::setNotice( wp_get_error_back_end_system(), "error", true );
			return false;
		}

	}

	/**
	 * @param $subject
	 */
	private static function send_customer_email_purchase_confirmation( $subject ) {
		$current_plugin_domain = get_current_plugin_domain();
		$email_to           = $_POST["first_name"] . " " . $_POST["last_name"] . " <" . $_POST["payer_email"] . ">";
		$wgs_currency_options = get_option( "wgs-currency-options" );
		$format_price       = Currencies::format_string_price( $_POST["mc_gross"], $wgs_currency_options['currency'], $wgs_currency_options['currency_position'] );
		$email_body         = sprintf( __( "The subscription payment for the plan '%s' was completed.", $current_plugin_domain ) . $_POST["item_name"] );
		$email_body         .= "\r\n";
		$email_body         .= "<table><tbody>";
		$email_body         .= "<tr><td>" . __( "Plan Name", $current_plugin_domain ) . " :</td>" . $_POST["item_name"] . "</tr>";
		$email_body         .= "<tr><td>" . __( "Price", $current_plugin_domain ) . " :</td>" . $format_price . "</tr>";
		$email_body         .= "</tbody></table>";
		$email_body         .= "\r\n\r\n";
		$email_body         .= __( "We thank you for your confidence.", $current_plugin_domain );
		$from_email_address = Email::get_recipient();

		$email_data = array(
			'to'      => $email_to,
			'subject' => $subject . sprintf( __( "Completed order - plan : %s ", $current_plugin_domain ) . $_POST["item_name"] ),
			'body'    => $email_body,
			'from'    => $from_email_address
		);
		$email = new Email( $email_data );
		$email->send();
	}

	private static function send_owner_email_purchase_confirmation( $subject ){
		$current_plugin_domain = get_current_plugin_domain();
		$owner_email    = Email::get_recipient();
		$full_name      = $_POST["first_name"] . " " . $_POST["last_name"];
		$wgs_currency_options = get_option( "wgs-currency-options" );
		$format_price   = Currencies::format_string_price( $_POST["mc_gross"], $wgs_currency_options['currency'], $wgs_currency_options['currency_position'] );
		$email_body     = sprintf( __( "New subscription payment for the plan '%s' was completed.", $current_plugin_domain ) . $_POST["item_name"] );
		$email_body    .= "\r\n";
		$email_body    .= "<table><tbody>";
		$email_body    .= "<tr><td>" . __( "Full Name", $current_plugin_domain ) . " :</td>" . $full_name . "</tr>";
		$email_body    .= "<tr><td>" . __( "Email", $current_plugin_domain ) . " :</td>" . $_POST["payer_email"] . "</tr>";
		$email_body    .= "<tr><td>" . __( "Plan Name", $current_plugin_domain ) . " :</td>" . $_POST["item_name"] . "</tr>";
		$email_body    .= "<tr><td>" . __( "Price", $current_plugin_domain ) . " :</td>" . $format_price . "</tr>";
		$email_body    .= "</tbody></table>";
		$email_data     = array(
			'to'      => $owner_email,
			'subject' => $subject . sprintf( __( "Completed order - plan : %s ", $current_plugin_domain ) . $_POST["item_name"] ),
			'body'    => $email_body,
			'from'    => $full_name . " <" . $owner_email . ">"
		);
		$email = new Email( $email_data );
		$email->send();
	}

	/**
	 * @param $subject
	 * @param $paypal_ipn_status
	 */
	private static function write_log( $subject, $paypal_ipn_status ) {
		$log_file_dir = __DIR__ . "/logs";
		list($year, $month, $day, $hour, $minute, $second, $timezone) = explode(":", date("Y:m:d:H:i:s:T"));
		$date = $year . "-" . $month . "-" . $day;
		$timestamp = $date . " " . $hour . ":" . $minute . ":" . $second . " " . $timezone;
		$dated_log_file_dir = $log_file_dir . "/" . $year . "/" . $month;

		$data_text = "";
		foreach ($_POST as $key => $value) {
			$data_text .= $key . " = " . $value . "\r\n";
		}

		// Create log file directory
		if ( ! is_dir( $dated_log_file_dir ) ) {
			if ( ! file_exists( $dated_log_file_dir ) ) {
				mkdir( $dated_log_file_dir, 0777, true );
				if ( ! is_dir( $dated_log_file_dir ) ) {
					$save_log_file = false;
				}
			} else {
				$save_log_file = false;
			}
		}
		// Restrict web access to files in the log file directory
		$htaccess_body = "RewriteEngine On" . "\r\n" . "RewriteRule .* - [L,R=404]";
		if( isset( $save_log_file ) && $save_log_file ){
			if ( ( ! is_file( $log_file_dir . "/.htaccess" ) || file_get_contents( $log_file_dir . "/.htaccess" ) !== $htaccess_body ) ) {
				if ( ! is_dir( $log_file_dir . "/.htaccess" ) ) {
					file_put_contents( $log_file_dir . "/.htaccess", $htaccess_body );
					if ( ! is_file( $log_file_dir . "/.htaccess" ) || file_get_contents( $log_file_dir . "/.htaccess" ) !== $htaccess_body ) {
						$save_log_file = false;
					}
				} else {
					$save_log_file = false;
				}
			}
			if( $save_log_file ){
				// Save data to text file
				file_put_contents( $dated_log_file_dir . "/" . $subject . "paypal_ipn_" . $date . ".txt", "paypal_ipn_status = " . $paypal_ipn_status . "\r\n" . "paypal_ipn_date = " . $timestamp . "\r\n" . $data_text . "\r\n", FILE_APPEND );
			}

		}
	}
}