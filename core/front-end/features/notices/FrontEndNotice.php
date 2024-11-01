<?php

namespace H4APlugin\Core\FrontEnd;

use H4APlugin\Core\Common\CommonNotice;
use function H4APlugin\Core\get_current_plugin_prefix;

class FrontEndNotice extends CommonNotice {

	public function __construct( $message, $level_notice = "info" ) {
		parent::__construct( $message, $level_notice );
		$this->updateTransient();
	}

	private function updateTransient() {
		$transient = get_transient( $this->transient_name );
		delete_transient( $this->transient_name );
		if( !empty( $transient ) ){
			$notices = $transient;
		}else{
			$notices = array(
				'front-end' => array()
			);
		}
		$notices['front-end'][] = [
			'message' => esc_html( $this->message ),
			'level_notice' => $this->level_notice
		];
		set_transient( $this->transient_name, $notices, MINUTE_IN_SECONDS );
	}

	public static function buildHTML( $message, $level_notice ){
		$p = sprintf( '<p class="h4a-alert h4a-alert-%s" role="alert">%s</p>',
			$level_notice,
			$message
		);
		return $p;
	}

	public static function gen_transient_name() {
		if( isset( $_COOKIE["h4a_key"] ) ){
			$name = get_current_plugin_prefix() . "notices" . "_" . $_COOKIE["h4a_key" ];
			return $name;
		}else{
			return false;
		}
	}
}