<?php

namespace H4APlugin\WPGroupSubs\Shortcodes;

use H4APlugin\Core\Common\Notices;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\wp_build_url;
use function H4APlugin\Core\wp_debug_log;
use H4APlugin\WPGroupSubs\Common\Member;

class PlansListShortcode extends Shortcode {

	public function check_page(){
		$pattern = "/(\[".$this->tag."\])/";
		global $post;
		$sidebars_widgets = wp_get_sidebars_widgets();
		foreach ( $sidebars_widgets as $sidebar => $widgets ) {
			if ( 'wp_inactive_widgets' === $sidebar || 'orphaned_widgets' === substr( $sidebar, 0, 16 ) ) {
				continue;
			}

			if ( is_array($widgets) ) {
				foreach ( $widgets as $widget ) {
					if( preg_match(  "/text-/", $widget ) ){
						global $wp_widget_factory;
						//$content = apply_filters( 'widget_text', $widget );
						$widget_obj = $wp_widget_factory->widgets[ "WP_Widget_Text" ];
						if( $widget_obj instanceof \WP_Widget ){
							$all_instances = $widget_obj->get_settings();
							foreach ( $all_instances as $instance ){
								if( !empty( $instance ) && !empty( $instance['text'] ) ){
									if( has_shortcode( $instance['text'], $this->tag ) ){
										add_action( "wp_enqueue_scripts", array( $this , "set_styles" ) );
										break;
									}
								}
							}
						}
					}
				}
			}
		}
		if( !empty( $post->post_content ) && preg_match(  $pattern, $post->post_content ) ){
			add_action( "wp_enqueue_scripts", array( $this , "set_styles" ) );
		}
	}

	public static function getCallBack( $attrs = null ){
		wp_debug_log();

		$output = "";

		//var_dump( $plans );
		// Start catching the contents of the subscriptions form
		ob_start();

		self::getNotices();

		//HTML Template
		include_once dirname( __FILE__ ) . "/views/view-plans-list.php";

		// Get the contents and clean the buffer
		$output .= ob_get_contents();
		ob_end_clean();

		return $output;
	}

	protected static function getNotices(){
		wp_debug_log();
		$wgs_paypal_options = get_option( "wgs-paypal-options" );
		$wgs_currency_options = get_option( "wgs-currency-options" );
		if( empty( $wgs_paypal_options ) || empty( $wgs_currency_options ) || empty( $wgs_paypal_options['paypal_email']) || empty( $wgs_currency_options['currency'] ) ){
			$message = __( "Settings missing error - if you see this message, please contact the administrator" , get_current_plugin_domain() );
			Notices::setNotice( $message, "error" );
		}else if( Member::isLoggedIn() ){
			$current_plugin_domain = get_current_plugin_domain();
			$message = __( "You are logged in." , $current_plugin_domain );
			$message .= "<br/>";
			$message .= __( "To see all plans, please log out." , $current_plugin_domain );
			$message .= sprintf( "<br/><a href=%s>%s</a>",
				wp_build_url( "wgs-login", H4A_WGS_PLUGIN_LABEL_LOG_IN, [] ),
				__( "Go to sign out page", $current_plugin_domain )
			);
			Notices::setNotice( $message, "error" );
		}
	}

	//CSS stylesheets
	public function set_styles() {
		wp_enqueue_style( "wgsstyle", H4A_WGS_PLUGIN_DIR_URL . "front-end/css/wgs-front-end.css" );
		wp_enqueue_style( "wgscardsstyle", H4A_WGS_PLUGIN_DIR_URL . "front-end/css/wgs-cards.css" );
		wp_enqueue_style( "wgsplancardsstyle", H4A_WGS_PLUGIN_DIR_URL . "front-end/shortcodes/plans-list/views/css/wgs-plan-card.css" );
	}

}