<?php

namespace H4APlugin\WPGroupSubs\Shortcodes;

use function H4APlugin\Core\get_current_plugin_domain;
use H4APlugin\WPGroupSubs\Common\Subscriber;

class ProfileSubscriptionShortcode extends Shortcode {

	private $current_plugin_domain;
	private $post_type;

	public function __construct( $tag ) {
		$this->current_plugin_domain = get_current_plugin_domain();
		parent::__construct( $tag );
		$this->post_type = $tag;
	}

	public function check_page() {

		global $post;

		if( !empty( $post ) && has_shortcode( $post->post_content, $this->tag ) ){
			add_action('wp_enqueue_scripts', array( $this , 'set_scripts'));
		}
	}

	public static function getCallBack( $attrs ) {
		$output = '';

		ob_start();

		if( Subscriber::isLoggedIn() ) {
			//HTML Template
			include_once dirname( __FILE__ ) . '/views/view-profile-subscription.php';
		}
		$output .= ob_get_contents();
		ob_end_clean();

		return $output;
	}

	public function set_scripts() {

	}
}