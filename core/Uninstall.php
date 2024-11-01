<?php
namespace H4APlugin\Core;

use H4APlugin\Core\Admin\AdminNotice;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

trait Uninstall{

	public static function uninstall(){
		wp_debug_log();

		//deactivate license
		$wgs_premium_options = get_option( "wgs-premium-options" );
		if ( ! empty( $wgs_premium_options ) ) {
			deactivate_license( $wgs_premium_options );
		}

		$str_addons = file_get_contents( dirname( __FILE__) . "/../config/addons.xml" );
		$c_addons = xmlAsStringToArray( $str_addons );
		if( !empty( $c_addons['addon']['@attributes'] ) ){ //Case : only one addon
			$attr_addon = $c_addons['addon']['@attributes'];
			$addon_path = $attr_addon['dir'] . "/" . $attr_addon['main'];
			self::uninstall_break_by_addon($addon_path);
		}else{
			foreach ( $c_addons['addon'] as $addon ){
				$attr_addon = $addon['@attributes'];
				$addon_path = $attr_addon['dir'] . "/" . $attr_addon['main'];
				self::uninstall_break_by_addon($addon_path);
			}
		}
		$str_install = file_get_contents( dirname( __FILE__) . "/../config/install.xml" );
		$c_install = xmlAsStringToArray( $str_install );
		if( self::is_plugin_table( $c_install ) )
			self::delete_tables();
		$plugin_post_types = self::get_plugin_post_types();
		/* unregister_post_types is unuseful for the moment */
		/*if( count( $plugin_post_types ) > 0 )
			self::unregister_post_types( $plugin_post_types );*/
		if( self::is_plugin_post( $c_install ) )
			self::delete_posts( $plugin_post_types );

		// delete settings options
		static::delete_options();

		//delete transient/cookie
		$transient_name = AdminNotice::gen_transient_name();
		delete_transient( $transient_name );
		if( isset( $_COOKIE[ "h4a_key" ] ) ){
			$transient_name = "wgs_notices_" . $_COOKIE[ "h4a_key" ];
			delete_transient( $transient_name );
			$transient_name = "wgs_data_" . $_COOKIE[ "h4a_key" ];
			delete_transient( $transient_name );
			unset( $_COOKIE[ "h4a_key" ] );
			setcookie("h4a_key", null, -1, '/');
		}
	}

	private static function uninstall_break_by_addon( $addon_path ){
		$is_plugin_active = is_plugin_active_before_admin_init( $addon_path );
		if( $is_plugin_active ){
			$error = new \WP_Error( 'uninstall_error', sprintf( __( "Before uninstall the plugin '%s'. You should uninstall the addons before.", get_current_plugin_domain() ), get_current_plugin_title(), $addon_path ) );
			if( is_wp_error( $error ) ) {
				echo $error->get_error_message();
				exit;
			}
		}
	}

	private static function is_plugin_table( $c_install ){
		return ( !empty( $c_install['database']['tables']['table'] ) ) ? true : false;
	}

	private static function is_plugin_post( $c_install ){
		return ( !empty( $c_install['database']['posts']['post'] ) ) ? true : false;
	}



	/***************/
	/* POST TYPES  */
	/***************/

	private static function get_plugin_post_types(){
		$str_post_types = file_get_contents(dirname( __FILE__) . "/../config/post-types.xml" );
		$c_post_types = xmlAsStringToArray( $str_post_types )['post_type'];
		if( count( $c_post_types ) > 0 ){
			$post_types = array();
			foreach ( $c_post_types as $c_post_type ){
				$post_type_attrs = $c_post_type['@attributes'];
				if( !isset( $post_type_attrs['slug'] ) ){
					wp_error_log( "Post type Config imcomplete, 'slug' attribute is mandatory", "Config" );
				}else{
					$post_types[] = (string) $post_type_attrs['slug'];
				}
			}
			return $post_types;
		}else{
			return false;
		}
	}

	/*private static function unregister_post_types( $plugin_post_types ){
		wp_debug_log();
		if( is_array( $plugin_post_types ) && count( $plugin_post_types ) > 0 ){
			foreach ( $plugin_post_types as $post_type ){
				if( post_type_exists( $post_type ) ){
					$res_unregister = unregister_post_type( $post_type );
					if( is_wp_error( $res_unregister ) ) {
						wp_log_error_format( $res_unregister->get_error_message(), "Unregister Post Type " .  "[" . __CLASS__ . "]" );
					}
				}
			}
		}
	}*/

	/**********/
	/* POSTS  */
	/**********/

	/*
	 * Removes all the custom posts we create
	 *
	 */
	private static function delete_posts( $plugin_post_types ){
		wp_debug_log();
		if( is_array( $plugin_post_types ) && count( $plugin_post_types ) > 0 ){
			global $wpdb;
			foreach ( $plugin_post_types as $post_type ){
				//wp_debug_log( "delete post with type : " . $post_type );
				$wpdb->query( "DELETE FROM {$wpdb->prefix}posts WHERE post_type = '" . $post_type . "'" );
			}
		}
	}

	/***************/
	/*   DATABASE  */
	/***************/

	/*
	 * Removes all the custom tables we create
	 */
	private static function delete_tables(){
		wp_debug_log();
		global $wpdb;

		$results = $wpdb->get_results("
			SELECT CONCAT( 'DROP TABLE ', GROUP_CONCAT(table_name) , ';' ) 
   			 AS statement FROM information_schema.tables 
   			 WHERE table_name LIKE '{$wpdb->prefix}" . get_current_plugin_prefix() . "%'"
		);

		if( !empty($results[0]->statement) )
			$wpdb-> query( $results[0]->statement );

	}


}




