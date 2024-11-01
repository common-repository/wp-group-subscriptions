<?php

namespace H4APlugin\WPGroupSubs\Common;


use H4APlugin\Core\FrontEnd\FrontEndStorage;
use function H4APlugin\Core\wp_debug_log;

trait UserTrait {

	public $password;

	/**
	 * Additional CRUD functions
	 */

	private function encodePassword(){
		$password_decode = htmlspecialchars_decode( $this->password );
		$hash = password_hash( $password_decode, PASSWORD_DEFAULT );

		return $hash;

	}

	public static function logOut(){
		wp_debug_log( "logout", "users", "users" );
		FrontEndStorage::delete_user_data();
		wp_redirect( home_url() );
		exit;
	}
}