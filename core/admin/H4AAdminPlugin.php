<?php

namespace H4APlugin\Core\Admin;

use H4APlugin\Core\Config;
use function H4APlugin\Core\get_current_plugin_dir_path;
use function H4APlugin\Core\get_current_plugin_dir_url;
use function H4APlugin\Core\include_dir_r;
use function H4APlugin\Core\is_license_activated;
use function H4APlugin\Core\is_plugin_active_before_admin_init;
use function H4APlugin\Core\wp_debug_log;
use function H4APlugin\Core\wp_error_log;
use H4APlugin\WPGroupSubs\Admin\Settings\Options;

abstract class H4AAdminPlugin {

	private $headings_path;

	protected $current_plugin_dir_url;

	public function __construct(){
		wp_debug_log();

		$this->current_plugin_dir_url = get_current_plugin_dir_url();

		$this->headings_path = dirname( __FILE__ ) . "/../../admin/headings";
		// Include dependencies
		$this->include_dependencies();

		// Initialize the components
		$this->init();

	}

	private function include_dependencies(){
		wp_debug_log();
		$this->include_core_dependencies();

		include_dir_r( $this->headings_path, "/^(?!view-).*$/" );

	}

	private function include_core_dependencies(){
		wp_debug_log();
		$h4a_config = Config::getConfig();

		if( $h4a_config['modules']['admin_page'] ){

			include_once "features/menu/Menu.php";
			include_once "features/menu/SubMenu.php";
			include_once "features/page/Screen.php";
			include_once "features/page/Page.php";
			include_once "features/page/SubPage.php";
			include_once "features/page/template/Template_Base.php";
			include_once "features/page/template/Template.php";
			include_once "features/page/template/MenuTemplate.php";

			if( $h4a_config['modules']['admin_notices'] ){
				include_once "features/notices/AdminNotice.php";
			}

			if( $h4a_config['modules']['settings'] ){
				include_once "features/page/settings/SettingsTemplate.php";
				add_filter( "plugin_action_links_" . H4A_WGS_PLUGIN_BASENAME, array( $this, "plugin_add_settings_link" ) );
			}

			if( $h4a_config['modules']['list_table'] ){

				include_once "features/page/template/list-table/H4A_List_Table_Base.php";
				include_once "features/page/template/list-table/H4A_List_Table.php";
				include_once "features/page/template/ListTableFromDBTemplate.php";

				if( $h4a_config['modules']['csv_list_table'] ){

					include_once "features/csv/list-table/CSV_Item_Params.php";
					include_once "features/csv/list-table/H4A_CSV_List_Table.php";
					include_once "features/csv/list-table/ListTableFromCSVTemplate.php";

					if( $h4a_config['modules']['log_reports'] ){
						include_once "features/log_reports/LogTemplate.php";
					}

				}

				if( $h4a_config['modules']['editable_list_table'] ){

					include_once "features/page/template/list-table/editable/H4A_Editable_List_Table.php";
					include_once "features/page/template/EditableListTableFromDBTemplate.php";

				}

			}

			if( $h4a_config['modules']['form'] && $h4a_config['modules']['editable_item'] ){
				include_once "features/form/AdminForm.php";
			}

			include_once "features/page/template/EditItemTemplate.php";

			if( $h4a_config['modules']['settings'] ){

				include_once "features/settings/Settings.php";

				if( file_exists( get_current_plugin_dir_path() . "admin/options/Options.php" ) ) {
					include_once dirname( __FILE__) . "/../../admin/options/Options.php";
				}
				//If one of addon is active
				$is_license_activated = is_license_activated();
				foreach ( $h4a_config['addons'] as $c_addon ){
					$attr_addon = $c_addon['@attributes'];
					if( $is_license_activated || $attr_addon['main'] === "WGSLicenseKeyAddon.php" ){
						require_once(  H4A_WGS_PLUGIN_DIR_PATH . "core/admin/features/update/plugin-update-checker.php" );
						if( !isset( $attr_addon['dir'] ) || !isset( $attr_addon['main'] ) ){
							$error_message = sprintf("'dir' and 'main' attributes are mandatory for 'addon' tag  in addons.xml" );
							wp_error_log( $error_message, "Config" );
							break;
						}else{
							$addon_dir = $attr_addon['dir'];
							$is_plugin_active = is_plugin_active_before_admin_init( $addon_dir . "/". Config::f_str( $attr_addon['main'] ) );
							if( $is_plugin_active ){
								$addon_options_class = ABSPATH . "wp-content/plugins/" . $addon_dir . "/admin/options/Options.php";
								if( file_exists( $addon_options_class ) ) {
									include_once $addon_options_class;
								}
							}
						}
					}
				}
			}
		}
	}

	public static function plugin_add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=settings-wp-group-subscriptions">' . __( "Settings" ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}

	protected function init(){
		wp_debug_log();
		$h4a_config = Config::getConfig();

		//1.
		add_action( "wp_loaded", array( $this, "add_wp_ajax_functions" ) );
		//2.
		add_action( "admin_menu", array( $this, "init_menus" ) );
		//3.
		if( $h4a_config['modules']['settings'] ){
			new Options();
			$is_license_activated = is_license_activated();

			foreach ( $h4a_config['addons'] as $c_addon ){
				$attr_addon = $c_addon['@attributes'];
				if( $is_license_activated || $attr_addon['main'] === "WGSLicenseKeyAddon.php" ){
					if( !isset( $attr_addon['dir'] ) || !isset( $attr_addon['main'] ) || !isset( $attr_addon['namespace'] ) ){
						$error_message = sprintf("'dir', 'main' and 'namespace' attributes are mandatory for 'addon' tag  in addons.xml" );
						wp_error_log( $error_message, "Config" );
						break;
					}else{
						$addon_dir = Config::f_str( $attr_addon['dir'] );
						$addon_main_class = Config::f_str( $attr_addon['main'] );
						$addon_namespace = Config::f_str( $attr_addon['namespace'] );
						$is_plugin_active = is_plugin_active_before_admin_init( $addon_dir . "/". $addon_main_class);
						$options_class_name = "H4APlugin\\" . $addon_namespace  . "\Admin\Settings\Options";
						if( $is_plugin_active && class_exists( $options_class_name ) ){
							new $options_class_name();
						}
					}
				}
			}
		}
		//4.
		add_action( "admin_enqueue_scripts", array( $this, "set_scripts" ) );

		$this->admin_init();
	}

	/**
	 *
	 */
	public function add_wp_ajax_functions(){
		wp_debug_log();
		$h4a_config = Config::getConfig();
		$c_pages = Config::getChildrenItem( $h4a_config['pages'], "page" );
		foreach ( $c_pages as $c_page ){
			$attr_page = $c_page['@attributes'];
			$c_templates =  Config::getChildrenItem( $c_page, "template" );
			foreach ( $c_templates as $c_template ){
				$c_wp_ajax = Config::getChildItem( $c_template, "wp_ajax" );
				if( !empty( $c_wp_ajax ) ){
					$c_functions = Config::getChildrenItem( $c_wp_ajax, "function" );
					if( empty( $c_functions ) ){
						$error_message = sprintf("the tag function is mandatory inside wp_ajax tag for the page with slug '%s'", $attr_page['slug'] );
						wp_error_log( $error_message, "Config" );
						break;
					}else{
						foreach ( $c_functions as $c_function ){
							$attr_function = $c_function['@attributes'];
							if( empty( $attr_function['class'] ) || empty( $attr_function['name'] ) || empty( $attr_function['file'] ) ){
								$error_message = sprintf("'file', 'class' and 'name' attributes are mandatory for 'function' tag - page with slug '%s'", $attr_page['slug'] );
								wp_error_log( $error_message, "Config" );
								break;
							}else{
								$function_file = (string) $attr_function['file'];
								$function_class = (string) $attr_function['class'];
								$function_name  = (string) $attr_function['name'];
								$filename = WP_PLUGIN_DIR . "/" . $function_file;
								if( file_exists( $filename )
								    && !class_exists( $function_class ) ) {
									include_once $filename;
								}
								add_action("wp_ajax_" . $function_name, array( $function_class, $function_name ) );
							}
						}
					}
				}
			}
		}
	}

	public function init_menus(){
		wp_debug_log();

		// ref : https://developer.wordpress.org/reference/functions/add_submenu_page/

		$h4a_config = Config::getConfig();
		if( !empty( $h4a_config['menus'] ) ){
			$attr_menus = $h4a_config['menus']['@attributes'];
			$position = (int) $attr_menus['position'];
			foreach ( $h4a_config['menus']['children'] as $child ){
				if( $child['type'] === "separator" ){
					Menu::add_admin_menu_separator( $position );
				}else if( $child['type'] === "menu" ){

					$attr_menu      = $child['@attributes'];
					$menu_slug      = $attr_menu['slug'];
					if( !in_array( $menu_slug, unserialize( H4A_ARRAY_NATIVE_MENUS_SLUGS ) ) ){
						$menu_title = Config::get_menu_title( $child );
						$c_page = Config::get_page_data_by_slug( $menu_slug );
						if( $c_page === false){
							$error_message  = sprintf( "No page with this slug '%s'", $menu_slug);
							wp_error_log( $error_message, "Config" );
						}
						$page_title     = ( $c_page !== false ) ? Config::get_page_title($c_page) : null ;
						$menu_icon      = Config::get_menu_icon( $child );
						$capability     = ( !empty( $attr_menu['capability'] ) ) ? (string) $attr_menu['capability'] : "manage_options" ;
						$text_domain    = ( !empty( $attr_menu['text_domain'] ) ) ? (string) $attr_menu['text_domain'] : null;
						if( empty( $menu_slug ) || empty( $menu_title) || empty( $page_title )
						    || empty( $menu_icon) || empty( $capability ) ){
							$error_message = sprintf( "Impossible to make the menu %s", $position );
							wp_error_log( $error_message, "Config" ) ;
						}else{
							new Menu(
								$menu_slug,
								$menu_title,
								$page_title,
								$capability,
								$menu_icon,
								$position,
								( !empty( $text_domain ) ) ? $text_domain : null
							);
						}
					}
					$c_submenus = Config::getChildrenItem( $child, "submenu" );
					if( !empty( $c_submenus ) ){
						$submenu_position = 1;
						foreach ( $c_submenus as $c_submenu ) {
							$submenu_title = Config::get_submenu_title( $c_submenu, $menu_slug );
							$submenu_capability = config::get_submenu_capability( $c_submenu );
							$attr_submenu = $c_submenu['@attributes'];
							$submenu_slug = Config::f_str( $attr_submenu['slug'] );
							$text_domain    = ( !empty( $attr_menu['text_domain'] ) ) ? (string) $attr_menu['text_domain'] : null;
							if( isset( $c_page ) && $menu_slug === $submenu_slug ){
								$c_subpage = $c_page;
							}else{
								$c_subpage = Config::get_page_data_by_slug( $submenu_slug );
							}
							if( !$c_subpage ) {
								$error_message = sprintf( "No (sub)page with this slug '%s'", $menu_slug );
								wp_error_log( $error_message, "Config" );
								break;
							}else{
								$subpage_title = Config::get_subpage_title( $c_subpage );
								if ( empty( $submenu_slug ) || empty( $subpage_title ) || empty( $submenu_title )
								     || empty( $submenu_capability ) ) {
									$error_message = sprintf( "Impossible to make the submenu %s", $submenu_position );
									wp_error_log( $error_message, "Config" );
									wp_error_log( "sub menu slug : " . $submenu_slug, "Config");
									wp_error_log( "sub page title : " . $subpage_title, "Config");
									wp_error_log( "sub menu title : " . $submenu_title, "Config");
									wp_error_log( "sub menu capability : " . $submenu_capability, "Config");
									break;
								} else {
									// $menu_slug can be null if you want to make page without menu access
									new SubMenu(
										$menu_slug,
										$subpage_title,
										$submenu_title,
										$submenu_capability,
										$submenu_slug,
										( !empty( $text_domain ) ) ? $text_domain : null
									);
								}
							}

							$submenu_position++;
						}
					}

					$position++;
				}
			}
		}
	}

	public function set_scripts() {
		//Javascript
		$h4a_config = Config::getConfig();
		wp_enqueue_script( "h4aadminplugin", $this->current_plugin_dir_url . "core/admin/js/admin-plugin.js" );
		if( $h4a_config['modules']['admin_notices'] ){
			wp_enqueue_script( "h4aadminnoticesplugin", $this->current_plugin_dir_url . "core/admin/features/notices/js/admin-notices-plugin.js" );
		}
		if( $h4a_config['modules']['admin_modals'] ){
			wp_enqueue_script( "h4aadminmodalplugin", $this->current_plugin_dir_url . "core/admin/features/modal/js/admin-modal-plugin.js" );
			wp_enqueue_style( "h4aadminmodalstyle", $this->current_plugin_dir_url . "core/admin/features/modal/css/admin-modal-style.css" );
		}
		//CSS
		wp_enqueue_style( "h4aadminstyle", $this->current_plugin_dir_url . "core/admin/css/admin.css" );
	}

	abstract protected function admin_init();

}