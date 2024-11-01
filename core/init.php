<?php /** @noinspection PhpIncludeInspection */

namespace H4APlugin\Core;

define( "H4A_WGS_LICENSE_STATUS", "h4a_wgs_license_status" );

//PHP Helpers ( must be place before config )
include_once "helpers/php/php-helpers-before-config.php";

if( !defined( "H4A_ARRAY_NATIVE_MENUS_SLUGS" ) ){
	define( "H4A_ARRAY_NATIVE_MENUS_SLUGS", serialize(
		[ "index.php",
			"edit.php",
			"upload.php",
			"edit.php?post_type=page",
			"edit-comments.php",
			//"edit.php?post_type=your_post_type",
			"themes.php",
			"plugins.php",
			"users.php",
			"tools.php",
			"options-general.php",
			"settings.php’",
		]
	) );
}

if( !defined( "H4A_NOTICE_LEVELS_ALLOWED" ) ){
	define( "H4A_NOTICE_LEVELS_ALLOWED", serialize( array( "error", "warning", "info", "success" ) ) );
}

//Init Config

if( !file_exists(  dirname( __FILE__) . "/../config/config.ini" ) )
	exit('No config for the plugin !');

include_once "helpers/php/wp-helpers-before-config.php";

include_once dirname( __FILE__) . "/Config.php";
new Config(); //Config generate constants about plugin title and version

include_once "helpers/php/wp-helpers-after-config.php";

// Load constants outside the core
include_once dirname( __FILE__) . "/../config/constants/constants.php";

/*
 * Loads plugin text domain
 */
function load_text_domain(){
	wp_debug_log();
	$is_translation  = load_plugin_textdomain( get_current_plugin_domain(), false, H4A_WGS_PLUGIN_TRANSLATION_PATH );
	$lang = get_option('WPLANG');
	if( !$is_translation && !empty( $lang )  ){
		wp_warning_log( "PLUGIN_REAL_PATH : " . H4A_WGS_PLUGIN_TRANSLATION_PATH  );
		wp_warning_log( "No translation!" );
	}
}

function init_license_key(){
	wp_debug_log();
	$current_plugin_domain = get_current_plugin_domain();
	if( isset( $_GET['page'] ) && $_GET['page'] === H4A_WGS_PAGE_SETTINGS && isset( $_GET['tab'] ) && $_GET['tab'] === __( "premium", $current_plugin_domain ) ){
		if( !empty(  $_POST['wgs-premium-options']['wgs_license_key'] ) ){
			$redirect_url = wp_admin_build_url( H4A_WGS_PAGE_SETTINGS, true, array( 'tab' => "premium" ) );
			if( $_GET['action'] === "activate" ){
				activate_license( $_POST['wgs-premium-options'], $redirect_url );
			}else if(  $_GET['action'] === "deactivate" ){
				deactivate_license( $_POST['wgs-premium-options'], $redirect_url );
			}
		}
	}
}

add_action( 'plugins_loaded', "H4APlugin\Core\load_text_domain", 1 );
add_action( 'plugins_loaded', "H4APlugin\Core\init_license_key", 2 );
