<?php
/*
Plugin Name: WGS - WP Group Subscriptions
Plugin URI: https://wp-group-subscriptions.com
Description: Accepts paying group registrations. Gives access to restricted content for members or groups of members.
Version: 0.1.7
Author: Hive 4 Apps
Author URI: https://www.hive-4-apps.org/
Text Domain: wp-group-subscriptions
Domain Path: /translations
License: GPLv2 or later

== Copyright ==
Copyright 2018 Hive 4 Apps (https://wp-group-subscriptions.com)

*/

/********************************************************/
/*              PLEASE DON'T TOUCH                      */
/********************************************************/
namespace H4APlugin\WPGroupSubs;

use H4APlugin\Core\Activation;
use function H4APlugin\Core\h4a_delete_option;
use function H4APlugin\Core\h4a_delete_transient;
use H4APlugin\Core\H4APlugin;

if( !@include( ABSPATH . 'wp-includes/pluggable.php' ))
	require_once( ABSPATH . 'wp-includes/pluggable.php' );

define( "H4A_WGS_PLUGIN_FILENAME_PATH",  __FILE__ );
define( "H4A_WGS_PLUGIN_DIR_PATH", plugin_dir_path( __FILE__ ) );
define( "H4A_WGS_PLUGIN_DIR_URL", plugin_dir_url( __FILE__ )  );
define( "H4A_WGS_PLUGIN_BASENAME", plugin_basename( __FILE__ )  );
define( "H4A_WGS_PLUGIN_BASENAME_DIR", plugin_basename( dirname( __FILE__ ) )  );
define( "H4A_WGS_PLUGIN_TRANSLATION_PATH", H4A_WGS_PLUGIN_BASENAME_DIR . "/translations" );

include_once 'core/init.php';

include_once "core/iUninstall.php"; //<-- Caution : Uninstall must be include before H4APlugin
include_once "core/Uninstall.php"; //<-- Caution : Uninstall must be include before H4APlugin
include_once "core/H4APlugin.php";
include_once "core/Activation.php";
Activation::init();
/********************************************************/

class WPGroupSubs extends H4APlugin {

	public static function delete_options() {
		h4a_delete_option( "wgs-currency-options" );
		h4a_delete_option( "wgs-paypal-options" );
		h4a_delete_option( "wgs-profile-page-options" );
		h4a_delete_option( "wgs-premium-options" );
		h4a_delete_option( "wgs-license-options" );
		h4a_delete_transient( H4A_WGS_LICENSE_STATUS );
	}

}

/********************************************************/
/*              PLEASE DON'T TOUCH                      */
/********************************************************/

/*
 * Initialize the plugin
 */
new WPGroupSubs();
register_uninstall_hook( __FILE__,  array( "H4APlugin\WPGroupSubs\WPGroupSubs", "uninstall" ) );
/********************************************************/
