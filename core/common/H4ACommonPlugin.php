<?php

namespace H4APlugin\Core\Common;


use function H4APlugin\Core\asBoolean;
use H4APlugin\Core\Config;
use function H4APlugin\Core\get_current_plugin_dir_url;
use function H4APlugin\Core\include_dir_r;
use function H4APlugin\Core\wp_debug_log;
use function H4APlugin\Core\wp_error_log;

abstract class H4ACommonPlugin {

	protected $current_plugin_dir_url;

	public function __construct(){
		wp_debug_log();
		$this->current_plugin_dir_url = get_current_plugin_dir_url();

		// Include dependencies
		$this->include_dependencies();

		// Initialize the components
		$this->init();

	}

	protected function include_dependencies(){

		$this->include_core_dependencies();

		include_dir_r( dirname( __FILE__ ) . "/../../common/classes/traits" );
		include_dir_r( dirname( __FILE__ ) . "/../../common/classes/units" );
		include_dir_r( dirname( __FILE__ ) . "/../../common/classes/widgets" );

	}

	protected function include_core_dependencies(){
		wp_debug_log();

		$h4a_config = Config::getConfig();

		include_once "H4AObjectTrait.php";
		include_once "features/item/Item_Params.php";
		include_once "features/item/DB_Item_Params.php";
		include_once "features/item/Item.php";

		if( $h4a_config['modules']['settings'] ) {
			include_once "features/settings/SettingsTrait.php";
		}

		if( $h4a_config['modules']['notices'] ){
			include_once "features/notices/CommonNotice.php";
			include_once "features/notices/Notices.php";
		}

		if( $h4a_config['modules']['form'] ){

			include_once "features/form/FormTrait.php";
			include_once "features/form/CommonForm.php";

			if( $h4a_config['modules']['editable_item'] ) {
				include_once "features/item/EditableItem.php";
			}
			if( $h4a_config['modules']['editable_form'] ) {
				include_once "features/form/EditableForm.php";
			}
		}

		if( $h4a_config['modules']['email'] ) {
			include_once "features/email/Email.php";
		}

		if( $h4a_config['modules']['paypal'] ){
			include_once "features/paypal/Paypal.php";
			include_once "features/paypal/PaypalPDT.php";
			include_once "features/paypal/PaypalIPN.php";

		}

		if( $h4a_config['modules']['countries'] ){
			include_once "features/countries/Countries.php";
		}

		if( $h4a_config['modules']['currencies'] ) {
			include_once "features/currencies/Currencies.php";
		}

		if( $h4a_config['modules']['csv'] ) {
			include_once "features/csv/CsvImporter.php";
		}
		/*if( $h4a_config['modules']['wp_editor'] ) {
			include_once "features/wp-editor/H4A_WP_Editor.php";
		}*/
	}

	protected function init(){
		wp_debug_log();
		$h4a_config = Config::getConfig();
		if( count( $h4a_config['post_types'] ) > 0 )
			add_action( "init", array( $this, "create_post_types" ), 1 );
		if(is_admin()){
			add_action( "admin_enqueue_scripts", array( $this , "set_scripts" ) );
		}else{
			add_action( "wp_enqueue_scripts", array( $this , "set_scripts" ) );
		}
		$this->common_init();

	}

	public function create_post_types() {
		wp_debug_log();
		$h4a_config = Config::getConfig();
		foreach( $h4a_config['post_types'] as $post_type ){
			$post_type_attrs = $post_type['@attributes'];
			if( !isset( $post_type_attrs['slug'] ) || !isset( $post_type_attrs['name'] ) ){
				wp_error_log( "Post Type Config imcomplete, 'name' and 'slug' attributes are mandatory", "Config" );
			}else{
				$a_post_type = array(
					'labels' => array(
						'name' => (string) $post_type_attrs['name'],
						'singular_name' => (string) $post_type_attrs['name']
					)
				);
				if( isset( $post_type_attrs['public'] ) )
					$a_post_type['public'] = asBoolean( (string) $post_type_attrs['public'] );
				if( isset( $post_type_attrs['exclude_from_search'] ) )
					$a_post_type['exclude_from_search'] = asBoolean( (string) $post_type_attrs['exclude_from_search'] );
				if( isset( $post_type_attrs['show_in_menu'] ) )
					$a_post_type['show_in_menu'] = asBoolean( (string) $post_type_attrs['show_in_menu'] );
				if( isset( $post_type_attrs['show_in_nav_menus'] ) )
					$a_post_type['show_in_nav_menus'] = asBoolean( (string) $post_type_attrs['show_in_nav_menus'] );
				if( isset( $post_type_attrs['show_in_admin_bar'] ) )
					$a_post_type['show_in_admin_bar'] = asBoolean( (string) $post_type_attrs['show_in_admin_bar'] );
				if( isset( $post_type_attrs['rewrite'] ) )
					$a_post_type['rewrite'] = asBoolean( (string) $post_type_attrs['rewrite'] );
				$result = register_post_type( (string) $post_type_attrs['slug'], $a_post_type );
				if ( is_wp_error( $result ) ) {
					wp_error_log( $result->get_error_message(), "Post type creation " . "[" . __CLASS__ . "]" );
				}
			}
		}
	}

	public function set_scripts() {
		wp_debug_log();
		$h4a_config = Config::getConfig();
		//Javascript
		wp_enqueue_script( "jquery");
		wp_enqueue_script( "h4acommonhelpers", $this->current_plugin_dir_url . "core/helpers/js/helpers.js" );
		wp_enqueue_script( "h4acommonplugin", $this->current_plugin_dir_url . "core/common/js/common-plugin.js" );
		if( $h4a_config['modules']['loader']){
			wp_enqueue_script( "h4acommonloaderplugin", $this->current_plugin_dir_url . "core/common/features/loader/js/common-loader-plugin.js" );
			wp_enqueue_style( "h4acommonloaderstyle", $this->current_plugin_dir_url . "core/common/features/loader/css/common-loader-style.css" );
		}
		if( $h4a_config['modules']['cookie'] ){
			wp_enqueue_script( "h4aexternalcookieplugin", $this->current_plugin_dir_url . "core/external/features/cookie/js/external-cookie-plugin.js");
			if( $h4a_config['modules']['timezone'] ){
				wp_enqueue_script( "h4aexternaljstzscript", $this->current_plugin_dir_url . "core/external/features/timezone/js/jstz.min.js");
				wp_enqueue_script( "h4acommoncookiescript", $this->current_plugin_dir_url . "core/common/features/timezone/js/common-timezone-script.js");
			}
		}
		//CSS
		wp_enqueue_style( "h4acommonstyle", $this->current_plugin_dir_url . "core/common/css/common.css" );
		/*if( $h4a_config['modules']['wp_editor'] ) {
			wp_enqueue_style( "h4awpeditorstyle", $this->current_plugin_dir_url . "core/common/features/wp-editor/css/h4a-wp-editor.css" );

		}*/
	}

	abstract protected function common_init();

}