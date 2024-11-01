<?php
namespace H4APlugin\Core\FrontEnd;


use H4APlugin\Core\Config;
use function H4APlugin\Core\get_current_plugin_dir_url;
use function H4APlugin\Core\include_dir_r;
use function H4APlugin\Core\is_plugin_active_before_admin_init;
use function H4APlugin\Core\wp_debug_log;
use function H4APlugin\Core\wp_error_log;

abstract class H4AFrontEndPlugin {

	private $shortcodes;

	protected $current_plugin_dir_url;

	public function __construct(){
		wp_debug_log();
		$this->current_plugin_dir_url = get_current_plugin_dir_url();
		$this->shortcodes = dirname( __FILE__ ) . "/../../front-end/shortcodes";
		// Include dependencies
		$this->include_dependencies();

		// Initialize the components
		$this->init();

	}

	private function include_dependencies(){
		wp_debug_log();

		$this->include_core_dependencies();

		include_dir_r( $this->shortcodes, "/^(?!view-).*$/" );

		$h4a_config = Config::getConfig();

		if( !empty( $h4a_config['addons'] ) ){
			foreach ( $h4a_config['addons']	as $addon ){
				$attr_addon = $addon['@attributes'];
				$is_plugin_active = is_plugin_active_before_admin_init( $attr_addon['dir'] . "/" . $attr_addon['main'] );
				if( $is_plugin_active ){
					$addon_shortcodes_dir_path = ABSPATH . "wp-content/plugins/" . $attr_addon['dir'] . "/front-end/shortcodes";
					if( is_dir( $addon_shortcodes_dir_path ) )
						include_dir_r( $addon_shortcodes_dir_path, "/^(?!view-).*$/" );

				}
			}
		}

	}

	private function include_core_dependencies(){
		wp_debug_log();

		$h4a_config = Config::getConfig();
		include_once "features/shortcode/iShortcode.php";
		include_once "features/shortcode/Shortcode.php";
		if( $h4a_config['modules']['front_end_notices'] ){
			include_once "features/notices/FrontEndNotice.php";
			if( $h4a_config['modules']['form'] && $h4a_config['modules']['front_end_form'] ){
				include_once "features/form/FrontEndForm.php";
			}
		}
		if( $h4a_config['modules']['front_end_storage'] ){
			include_once "features/storage/FrontEndStorage.php";
		}
	}

	private function init(){
		wp_debug_log();

		$h4a_config = Config::getConfig();
		//1.
		if( !empty( $h4a_config['shortcodes'] ) ){
			foreach ( $h4a_config['shortcodes']['children'] as $shortcode ){
				if( empty( $shortcode['@attributes'] ) || empty( $shortcode['@attributes']['tag'] ) || empty( $shortcode['@attributes']['class'] ) ){
					$error_message  = sprintf( "The 'tag' and 'class' attributes are mandatories for the 'shortcode' item" );
					wp_error_log( $error_message, "Config" );
					exit;
				}else{
					$attr_shortcode  = $shortcode['@attributes'];
					$tag_shortcode   = $attr_shortcode['tag'];
					$class_shortcode = $attr_shortcode['class'];
					if( ! class_exists( $class_shortcode ) ) {
						wp_error_log("{$class_shortcode} was not included" );
					}else{
						//echo $class_shortcode . " - ". $tag_shortcode;
						new $class_shortcode( $tag_shortcode );
					}
				}
			}
		}

		//2.
		add_action( "wp_enqueue_scripts", array( $this, "set_scripts" ) );

		$this->front_end_init();
	}

	public function set_scripts() {
		$h4a_config = Config::getConfig();
		//CSS
		if( $h4a_config['modules']['cookie'] && $h4a_config['modules']['front_end_cookie'] ){
			wp_enqueue_script( "h4afrontendcookiescript", $this->current_plugin_dir_url . "core/front-end/features/cookie/js/front-end-cookie-script.js");
			if( $h4a_config['modules']['front_end_notices'] ){
				wp_enqueue_style( "h4afrontendstyle", $this->current_plugin_dir_url . "core/front-end/features/notices/css/front-end-notices-style.css" );
			}
		}
	}

	abstract protected function front_end_init();

}