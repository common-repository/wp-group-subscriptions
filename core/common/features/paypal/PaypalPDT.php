<?php

namespace H4APlugin\Core\Common;

use function H4APlugin\Core\wp_get_error_system;
use function H4APlugin\Core\is_https;
use function H4APlugin\Core\wp_error_log;

class PaypalPDT {
   public $tx;
   public $response;
   public $error;
   public $response_status;
   public $status;
   
   public function __construct( $tx ) {
   	    $this->tx              = $tx;
   	    $this->response        = null;
   	    $this->response_status = null;
   	    $this->status          = null;
   	    $this->error           = null;
   }
   
   public function back2Paypal(){
	   // Init cURL
	   $request = curl_init();

	   $wgs_paypal_options = get_option( "wgs-paypal-options" );

	   $curlopt_url = Paypal::getUrlPaypal();
	   $curlopt_ssl_verifyer = is_https();
	   // Set request options
		curl_setopt_array($request, array
		(
			CURLOPT_URL => $curlopt_url,
			CURLOPT_POST => TRUE,
			CURLOPT_POSTFIELDS => http_build_query(array
			(
				'cmd' => "_notify-synch",
				'tx' => $this->tx,
				'at' => $wgs_paypal_options['paypal_pdt_token'],
			)),
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_HEADER => FALSE,
			CURLOPT_SSL_VERIFYPEER => $curlopt_ssl_verifyer,
			// CURLOPT_CAINFO => "cacert.pem",
		));
		
		// Execute request and get response and status code
		$response = curl_exec($request);
		if( preg_match( '/^(FAIL)/', $response ) ){
			$this->response_status = "FAILED";
			wp_error_log( "Response Paypal (PDT) failed" );
			$this->error = wp_get_error_system();
		}else if(preg_match( '/^(SUCCESS)/', $response ) ){
			$this->response_status = "SUCCESS";
			$this->response        = $response;
			$this->format_response();
		}else{
			wp_error_log( "Error Paypal request (PDT)" );
			$this->error = wp_get_error_system();
		}
		$this->status  = curl_getinfo($request, CURLINFO_HTTP_CODE);

	   if ($this->response === false || $this->status == "0") {
		   $errno = curl_errno($request);
		   $errstr = curl_error($request);
		   $error_message = "cURL error: [$errno] $errstr";
		   wp_error_log( $error_message );
	   }
		
		// Close connection
		curl_close($request);
	   
   }
   
   private function format_response(){
	   // Remove SUCCESS part (7 characters long)
	   $this->response = substr($this->response, 7);

		// URL decode
	   $this->response = urldecode($this->response);

		// Turn into associative array
	   preg_match_all('/^([^=\s]++)=(.*+)/m', $this->response, $m, PREG_PATTERN_ORDER);
	   $this->response = array_combine($m[1], $m[2]);

		// Fix character encoding if different from UTF-8 (in my case)
	   if(isset( $this->response['charset'] ) AND strtoupper( $this->response['charset'] ) !== "UTF-8" )
	   {
		   foreach( $this->response as $key => &$value )
		   {
			    if( $key === "payment_date"){
				    $payment_time = strtotime( $value );
				    $value = date("Y-m-d H:i:s", $payment_time );
			    }
		   	    $value = mb_convert_encoding($value, "UTF-8", $this->response['charset']);
		   }
		   $this->response['charset_original'] = $this->response['charset'];
		   $this->response['charset'] = "UTF-8";
	   }

		// Sort on keys for readability (handy when debugging)
	    ksort($this->response);
   }
   
}