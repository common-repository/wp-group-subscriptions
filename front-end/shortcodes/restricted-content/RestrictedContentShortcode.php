<?php

namespace H4APlugin\WPGroupSubs\Shortcodes;

use function H4APlugin\Core\asBoolean;
use H4APlugin\WPGroupSubs\Common\Member;

class RestrictedContentShortcode extends Shortcode {

	public function check_page(){
		add_filter( "template_include", array( get_called_class(), "page_template" ), 99 );
		add_action("wp_enqueue_scripts", array( $this , "set_styles" ) );
	}

	public static function getCallBack( $atts = null, $content = null ){
		if( Member::isLoggedIn() ){
			return do_shortcode( $content );
		}else{
			if( ( !isset( $atts['message'] ) || asBoolean( $atts['message'] ) )
			){
				return sprintf( '<p class="h4a-alert h4a-alert-warning"><span class="dashicons dashicons-lock"></span>%s</p>', __("Content only for subscribed members."));
			}else{
				return null;
			}
		}
	}

	//CSS stylesheets
	public function set_styles() {
		wp_enqueue_style( "wgsstyle", H4A_WGS_PLUGIN_DIR_URL . "front-end/css/wgs-front-end.css" );
	}

}