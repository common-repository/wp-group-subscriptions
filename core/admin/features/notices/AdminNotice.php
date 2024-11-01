<?php

namespace H4APlugin\Core\Admin;


use H4APlugin\Core\Common\CommonNotice;
use function H4APlugin\Core\get_current_plugin_prefix;

class AdminNotice extends CommonNotice {

	public $is_dimissible;

	public function __construct( $message, $level_notice = "info", $is_dimissible = false ) {
		parent::__construct( $message, $level_notice );
		$this->is_dimissible = $is_dimissible;
		$this->updateTransient();
	}

	private function updateTransient() {

		$transient = get_transient( $this->transient_name );
		delete_transient( $this->transient_name );
		if( !empty( $transient ) ){
			$notices = $transient;
		}else{
			$notices = array(
				'admin' => array()
			);
		}
		$notices['admin'][] = [
			'message' => esc_html( $this->message ),
			'level_notice' => $this->level_notice,
			'is_dimissible' => $this->is_dimissible
		];
		set_transient( $this->transient_name, $notices, MINUTE_IN_SECONDS );
	}

	public static function buildHTML( $message, $level_notice, $is_dimissible ){
		$p = sprintf( "<p>%s</p>", $message );
		$html = sprintf( '<div class="notice notice-%s %s">%s</div>',
			$level_notice,
			( $is_dimissible ) ? "is-dismissible" : "" ,
			$p
		);
		return $html;
	}

	public static function gen_transient_name() {
			return get_current_plugin_prefix() . "notices" . "_" . get_current_user_id();
	}
}