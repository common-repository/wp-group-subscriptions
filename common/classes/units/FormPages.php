<?php

namespace H4APlugin\WPGroupSubs\Common;

use function H4APlugin\Core\wp_error_log;
use function H4APlugin\Core\wp_build_url;

class FormPages {

	//public function __construct() {}

	public static function getFormPages(){
		global $wpdb;
		// Start query string
		$forms_query_string       = "SELECT * FROM {$wpdb->prefix}posts WHERE post_type='wgs-form-page' AND post_status = 'publish'";
		// Return results
		$forms = $wpdb->get_results( $forms_query_string, ARRAY_A );
		$f_forms = array();
		foreach ( $forms as $key => $val ){
			$f_forms[$val['post_name']] = $val;
		}
		return $f_forms;
	}

	public static function getIdFormPageByTitle( $post_title ){
		global $wpdb;
		// Start query string
		$forms_query_string       = "SELECT ID FROM {$wpdb->prefix}posts WHERE post_type='wgs-form-page' AND post_title = '".$post_title."'";
		// Return results
		$result = $wpdb->get_results( $forms_query_string, ARRAY_A );
		if( count( $result ) === 0 ){
			wp_error_log( "WGS Form page with the post_title : " . $post_title . " does not exist!" );
			return false;
		}
		else if( count( $result ) > 1 ){
			wp_error_log( "WGS Form page with the post_title : " . $post_title . " is not unique!" );
			return false;
		}else{
			$wgs_id_form_page = $result[0]['ID'];
			return $wgs_id_form_page;
		}
	}

	public static function buildUrlAction( $page_title, $step, $args = array() ){

		$f_args = array(
			"step" => $step
		);
		if( !empty( $args ) ){
			foreach ( $args as $key => $value ){
				$f_args[$key] = $value;
			}
		}

		$href =	wp_build_url( "wgs-form-page", $page_title, $f_args );
			
		return $href;
	}
	
}