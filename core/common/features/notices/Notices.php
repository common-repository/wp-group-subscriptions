<?php

namespace H4APlugin\Core\Common;


use H4APlugin\Core\Admin\AdminNotice;
use H4APlugin\Core\FrontEnd\FrontEndNotice;

class Notices {

	public static function displayAll( $clean = true ){
		$office = ( is_admin() ) ? "admin" : "front-end" ;
		$transient_name = ( $office === "admin" ) ? AdminNotice::gen_transient_name() : FrontEndNotice::gen_transient_name();
		$transient = get_transient( $transient_name );
		if( !empty( $transient ) && !empty( $transient[ $office ] ) ){
			foreach ( $transient[ $office ] as $notice ){
				$f_message = html_entity_decode( $notice['message'] );
				if( is_admin() ){
					echo AdminNotice::buildHTML( $f_message, $notice['level_notice'], $notice['is_dimissible'] );
				}else{
					echo FrontEndNotice::buildHTML( $f_message, $notice['level_notice'] );
				}
			}
			if( $clean )
				delete_transient( $transient_name );
		}
	}

	public static function setNotice( string $message, $level_notice, $is_dimissible = false ){
		if( is_admin() ){
			new AdminNotice( $message, $level_notice, $is_dimissible );
		}else{
			new FrontEndNotice( $message, $level_notice );
		}
	}

	public static function setNotices( array $messages = array(), string $level_notice = "error", bool $is_dimissible = false ){
		if( empty( $messages ) )
			return false;
		else{
			foreach ( $messages as $message ){
				self::setNotice( $message, $level_notice, $is_dimissible );
			}
			return true;
		}
	}

	public static function getErrors(  $clean = "true" ){
		$office = ( is_admin() ) ? "admin" : "front-end" ;
		$transient_name = ( $office === "admin" ) ? AdminNotice::gen_transient_name() : FrontEndNotice::gen_transient_name();
		$transient = get_transient( $transient_name );
		$errors = array();
		if( !empty( $transient ) || !empty( $transient[$office] ) ){
			foreach ( $transient[$office] as $key => $notice ){
				if( $notice['level_notice'] === "error" ){
					$errors[] = $notice;
					if( $clean )
						unset( $transient[$office][$key] );

				}
			}
			if( $clean )
				set_transient( $transient_name, $transient, MINUTE_IN_SECONDS );
		}
		return $errors;
	}

	public static function containsNotice( string $message ){
		$office = ( is_admin() ) ? "admin" : "front-end" ;
		$transient_name = ( $office === "admin" ) ? AdminNotice::gen_transient_name() : FrontEndNotice::gen_transient_name();
		$transient = get_transient( $transient_name );
		if( empty( $transient ) || empty( $transient[$office] ) ){
			return false;
		}else{
			foreach ( $transient[$office] as $notice ){
				if( esc_html( $notice['message'] ) === esc_html( $message ) )
					return true;
			}
		}
		return false;
	}

	public static function removeNotice( string $message ){
		$office = ( is_admin() ) ? "admin" : "front-end" ;
		$transient_name = ( $office === "admin" ) ? AdminNotice::gen_transient_name() : FrontEndNotice::gen_transient_name();
		$transient = get_transient( $transient_name );
		if( empty( $transient ) || empty( $transient[$office] ) ){
			return false;
		}else{
			foreach ( $transient[$office] as $n => $notice ){
				if( esc_html( $notice['message'] ) === esc_html( $message ) ){
					unset( $transient[$office][$n] );
					return true;
				}
			}
		}
		return false;
	}

	public static function isNoErrors(){
		$office = ( is_admin() ) ? "admin" : "front-end" ;
		$transient_name = ( $office === "admin" ) ? AdminNotice::gen_transient_name() : FrontEndNotice::gen_transient_name();
		$transient = get_transient( $transient_name );
		if( empty( $transient ) || empty( $transient[$office] ) ){
			return true;
		}else{
			foreach ( $transient[$office] as $notice ){
				if( $notice['level_notice'] === "error" )
					return false;
			}
		}
		return true;
	}
}