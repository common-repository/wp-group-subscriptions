<?php

namespace H4APlugin\WPGroupSubs\Shortcodes;



use H4APlugin\Core\Common\CommonForm;
use H4APlugin\Core\Common\Notices;
use function H4APlugin\Core\get_current_plugin_domain;
use H4APlugin\WPGroupSubs\Common\Member;
use H4APlugin\Core\FrontEnd\FrontEndForm;
use function H4APlugin\Core\wp_build_url;
use H4APlugin\WPGroupSubs\Common\Subscriber;

class LoginShortcode extends Shortcode {

	private $current_plugin_domain;
	private $post_type;

	public function __construct( $tag ) {
		$this->current_plugin_domain = get_current_plugin_domain();
		parent::__construct( $tag );
		$this->post_type = $tag;
	}

	/**
	 * @param bool $is_first_sign_in
	 *
	 * @return FrontEndForm
	 */
	private static function getSignInForm( $is_first_sign_in ): FrontEndForm {
		$form                       = new FrontEndForm( 1, 'sign-in' );
		$form->action               = wp_build_url( "wgs-login", H4A_WGS_PLUGIN_LABEL_LOG_IN );
		$form->options['submitBox'] = array( 'button' => "Sign in" );
		if( !$is_first_sign_in )
			$form->content[0]['legend'] = __( "Connection", get_current_plugin_domain() ) ;
		return $form;
	}

	public function check_page(){
		if( get_post_type() === $this->post_type  ){
			add_filter( 'template_include', array( get_called_class(), 'page_template' ), 99 );
			add_action( "template_redirect", array( $this, "is_redirection") );
		}
		global $post;

		if( !empty( $post ) && has_shortcode( $post->post_content, $this->tag ) ){
			add_action('wp_enqueue_scripts', array( $this , 'set_styles'));
		}
	}

	public static function is_redirection() {
		if( !empty($_POST["wgs_f_email"]) && !empty($_POST["wgs_f_password"])
		    && !Member::isLoggedIn() && !Subscriber::isLoggedIn()
		){
			Subscriber::logIn( $_POST["wgs_f_email"], $_POST["wgs_f_password"] );
			if( !Member::isLoggedIn() ) //Member can already logged in by Subscriber::logIn
				Member::logIn( $_POST["wgs_f_email"], $_POST["wgs_f_password"] );
			if( Subscriber::isLoggedIn() || Member::isLoggedIn() ){
				wp_redirect( wp_build_url( MyProfileShortcode::getProfilePagePostType(), MyProfileShortcode::getProfilePageTitle() ) );
			}
		}else if( isset( $_GET["sign"] ) &&  $_GET["sign"] === "out" ){
			Member::logOut();
		}

	}

	public static function getCallBack( $attrs = null ){

		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		$current_plugin_domain = get_current_plugin_domain();

		if( Member::isLoggedIn() || Subscriber::isLoggedIn() ){
			$form = new FrontEndForm( 1, "sign-out" );
			$form->options["text_introduction"] = __( "You are logged in. You can now access all the contents.", $current_plugin_domain );
			$form->action = wp_build_url( "wgs-login", H4A_WGS_PLUGIN_LABEL_LOG_IN, array( "sign" => "out") );
			$form->options['submitBox'] = array( 'button' => "Sign out" );
		}else{
			if( isset( $_GET['registered'] ) && (boolean) $_GET['registered'] ){
				$form = self::getSignInForm(  true );
				$text_introduction = __( "Congratulations!", $current_plugin_domain );
				$text_introduction .= '<br/>'.__( "Your account has been activated.", $current_plugin_domain );
				$text_introduction .= '<br/>'.__( "You can now access all content by logging in.", $current_plugin_domain );
				$form->options["text_introduction"] = array(
					"type" => 'success',
					"text" =>  $text_introduction
				);
			}else{
				$form = self::getSignInForm(  false );
			}
		}
		if( isset( $form ) && $form instanceof CommonForm ){
			$form->options["recaptcha"]           = false;
			$form->options["has_required_fields"] = false;
		}

		$output = '';

		ob_start();

		Notices::displayAll();

		//HTML Template
		if( isset( $form ) && $form instanceof FrontEndForm )
			$form->writeForm();

		// Get the contents and clean the buffer
		$output .= ob_get_contents();
		ob_end_clean();

		return $output;

	}

	//CSS stylesheets
	public function set_styles() {
		wp_enqueue_style( "h4afrontendform", H4A_WGS_PLUGIN_DIR_URL . "core/front-end/features/form/css/front-end-form-style.css" );
		wp_enqueue_style( "wgsstyle", H4A_WGS_PLUGIN_DIR_URL . "front-end/css/wgs-front-end.css" );
	}

}