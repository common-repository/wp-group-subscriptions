<?php

namespace H4APlugin\Core\Common;

use function H4APlugin\Core\wp_error_log;


abstract class CommonNotice {

	public $transient_name;
	public $message;
	public $level_notice; //error, warning, info, success

	public function __construct( $message, $level_notice = "info" ) {
		$this->transient_name = static::gen_transient_name();
		$this->message = $message;
		if( !in_array( $level_notice, unserialize( H4A_NOTICE_LEVELS_ALLOWED ) ) ){
			$error_message = sprintf( "This level notice '%s' is not supported. It can be 'error', 'warning', 'info', 'success'", $level_notice );
			wp_error_log( $error_message );
			exit;
		}else{
			$this->level_notice = $level_notice;
		}
	}

	abstract public static function gen_transient_name();
}