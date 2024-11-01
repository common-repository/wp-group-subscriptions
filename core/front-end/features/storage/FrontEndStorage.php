<?php

namespace H4APlugin\Core\FrontEnd;

use function H4APlugin\Core\get_current_plugin_prefix;

class FrontEndStorage {

	public static function set_user_data( $user_data, $member_key = null, $expiration = 0 ){
		$transient_name = self::gen_transient_name( $member_key );
		delete_transient( $transient_name );
		set_transient( $transient_name, $user_data, $expiration );
	}

	public static function get_user_data( $member_key = null ){
		$transient_name = self::gen_transient_name( $member_key );
		return get_transient( $transient_name );
	}

	public static function delete_user_data( $member_key = null ){
		$transient_name = self::gen_transient_name( $member_key );
		delete_transient( $transient_name );
	}

	public static function gen_transient_name( $member_key = null ) {
		$key = get_current_plugin_prefix() . "data" . "_";
		if( isset( $member_key ) ){
			return $key . $member_key;
		}else if( isset( $_COOKIE["h4a_key" ] ) ){
			return $key . $_COOKIE["h4a_key" ];
		}else{
			return false;
		}
	}

}