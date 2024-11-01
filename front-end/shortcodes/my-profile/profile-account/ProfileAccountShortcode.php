<?php

namespace H4APlugin\WPGroupSubs\Shortcodes;


use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\wp_debug_log;
use H4APlugin\WPGroupSubs\Common\Member;
use H4APlugin\WPGroupSubs\Common\Subscriber;
use H4APlugin\WPGroupSubs\Common\SubscriberEditionTrait;

class ProfileAccountShortcode extends Shortcode {

	Use SubscriberEditionTrait;

	private $current_plugin_domain;
	private $post_type;

	public function __construct( $tag ) {
		$this->current_plugin_domain = get_current_plugin_domain();
		parent::__construct( $tag );
		$this->post_type = $tag;
	}

	public function check_page() {
		wp_debug_log();
		global $post;
		if( get_post_type() === "wgs-profile" || ( !empty( $post ) && has_shortcode( $post->post_content, $this->tag ) ) ){
			add_action( "wp_enqueue_scripts", array( $this, "set_scripts" ) );
		}
	}

	public static function getCallBack( $attrs ) {

		if( Member::isLoggedIn() || Subscriber::isLoggedIn() ) {
			if( Subscriber::isLoggedIn() ){
				$subscriber_loggedIn = Subscriber::getSubscriberLoggedIn( "edit" );
				self::modifySubscriberForm( $subscriber_loggedIn );
			}
			$output = '';

			ob_start();
			//HTML Template
			include_once dirname( __FILE__ ) . '/views/view-profile-account.php';
			$output .= ob_get_contents();
			ob_end_clean();

			return $output;
		}else{
			wp_redirect( home_url() );
			exit;
		}

	}

	public function set_scripts() {
		wp_debug_log();
		//Javascripts
		wp_enqueue_script( "h4acommonformplugin", H4A_WGS_PLUGIN_DIR_URL . "core/common/features/form/js/common-form-plugin.js" );
		wp_localize_script( "h4acommonformplugin", "commonFormTranslation", array(
			'msg_must_match' => __( "It is must match with the previous input", $this->current_plugin_domain ),
		) );
		wp_enqueue_script( "wgscommonformscript", H4A_WGS_PLUGIN_DIR_URL . "common/js/wgs-common-form.js" );
		wp_enqueue_script( "wgsprofile", H4A_WGS_PLUGIN_DIR_URL . "front-end/shortcodes/my-profile/views/js/wgs-profile.js" );
		wp_localize_script( "wgsprofile", "wgsFormTranslation", array(
			'password_placeholder'   => __( "Please insert your password", $this->current_plugin_domain ),
			'password_placeholder_r' => __( "Please confirm your password", $this->current_plugin_domain ),
			'button_change_password' => __( "Change Password", $this->current_plugin_domain ),
			'button_cancel'          => __( "Cancel" )

		) );

		//CSS stylesheets
		wp_enqueue_style( "h4afrontendform", H4A_WGS_PLUGIN_DIR_URL . "core/front-end/features/form/css/front-end-form-style.css" );
		wp_enqueue_style( "wgsstyle", H4A_WGS_PLUGIN_DIR_URL . "front-end/css/wgs-front-end.css" );
	}
}