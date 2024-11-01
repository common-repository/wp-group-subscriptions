<?php

namespace H4APlugin\Core\Common;


use function H4APlugin\Core\wp_debug_log;
use function H4APlugin\Core\wp_error_log;

class Email {

	public $to;
	public $subject;
	public $body;
	public $headers;
	
	public function __construct( $data ) {

		add_action( "wp_mail_failed", array( $this, "action_wp_mail_failed" ), 10, 1);

		$is_error = false;
		
		foreach ( array( "to", "subject", "body" ) as $item ){
			if( empty( $data[ $item ] ) ){
				wp_error_log( "Impossible to make the email. item '" . $item . "' is empty!" );
				$is_error = true;
			}
		}
		
		//Results
		if( $is_error ){
			return false;
		}else{
			//wp_log_error_format( "email making..." );
			$this->to = $data['to'];
			$this->subject = $data['subject'];
			$this->body = $data['body'];
			$this->headers[] = "Content-Type: text/html; charset=UTF-8";
			if( !empty( $data['from'] ) )
				$this->headers[] = "From: " . $data['from'];
		}
		return null;
	}
	
	public function send(){
		wp_debug_log();
		$resp = wp_mail( $this->to, $this->subject, $this->body, $this->headers );
		return $resp;
	}

	// define the wp_mail_failed callback
	function action_wp_mail_failed( \WP_Error $wp_error ){
		if ( !empty( $wp_error->errors ) ){
			foreach ( $wp_error->errors as $a_error ){
				foreach ( $a_error as $error ){
					wp_error_log( $error, "email error", "users" );
				}
			}
		}
	}
	
	public static function get_recipient(){
		$title_site                 = get_bloginfo( "name" );
		$admin_email                = get_bloginfo( "admin_email" );
		return sprintf( "%s <%s>", $title_site, $admin_email );
	}

}