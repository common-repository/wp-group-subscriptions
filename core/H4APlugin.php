<?php

namespace H4APlugin\Core;

use H4APlugin\WPGroupSubs\Common\Common;
use H4APlugin\WPGroupSubs\Admin\Admin;
use H4APlugin\WPGroupSubs\FrontEnd\FrontEnd;

abstract class H4APlugin implements iUninstall {

	use Uninstall;

	private static $initiated = false;
	/*
	 * Initialize the plugin
     */

	public function __construct() {
		wp_debug_log();

		if ( ! self::$initiated ) {

			$h4a_config = Config::getConfig();

			$this->include_dependencies();

			//Common instantiation
			if( ! class_exists( "H4APlugin\\WPGroupSubs\\Common\\Common" ) ) {
				wp_error_log("Common was not included");
			}else{
				new Common();
			}
			if( is_admin() && $h4a_config['offices']['admin'] ){
				if( empty( $h4a_config['rights']['admin_user'] ) || current_user_can( $h4a_config['rights']['admin_user']  ) ){
					//Admin instantiation
					if( ! class_exists( "H4APlugin\\WPGroupSubs\\Admin\\Admin" ) ) {
						wp_error_log("Admin was not included");
					}else{
						new Admin();
					}
				}
			}else if( $h4a_config['offices']['front_end'] ){
				if( ! class_exists( "H4APlugin\\WPGroupSubs\\FrontEnd\\FrontEnd" ) ) {
					wp_error_log("FrontEnd was not included");
				}else{
					new FrontEnd();
				}
			}
			self::$initiated = true;
		}
	}

	/*
	 * Function to include the files needed
	 */
	protected function include_dependencies(){
		wp_debug_log();

		include_once dirname( __FILE__) . "/common/H4ACommonPlugin.php";

		
		if( file_exists( get_current_plugin_dir_path() . "common/Common.php" ) ) {
			include_once dirname( __FILE__) . "/../common/Common.php";
		}

		$h4a_config = Config::getConfig();
		if( is_admin() && $h4a_config['offices']['admin'] ){
			if( empty( $h4a_config['rights']['admin_user'] ) || current_user_can( $h4a_config['rights']['admin_user'] ) ){
				
				include_once dirname( __FILE__) . "/admin/H4AAdminPlugin.php";

				if( file_exists( get_current_plugin_dir_path() . "admin/Admin.php" ) ) {
					include_once dirname( __FILE__) . "/../admin/Admin.php";
				}
			}
		}else if( $h4a_config['offices']['front_end'] ){

			include_once dirname( __FILE__) . "/front-end/H4AFrontEndPlugin.php";

			
			if( file_exists( get_current_plugin_dir_path() . "front-end/FrontEnd.php" ) ) {
				include_once dirname( __FILE__) . "/../front-end/FrontEnd.php";
			}
		}
	}

	//Can be overwritten
	public static function scheduled_tasks_activation(){}
}
