<?php

namespace H4APlugin\WPGroupSubs\Shortcodes;


use H4APlugin\Core\Common\Notices;
use H4APlugin\Core\FrontEnd\FrontEndNotice;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\wp_debug_log;
use H4APlugin\WPGroupSubs\Common\Member;
use \H4APlugin\Core\FrontEnd\FrontEndForm;
use function H4APlugin\Core\wp_redirect_404;
use function H4APlugin\Core\wp_get_error_system;
use function H4APlugin\Core\wp_error_log;
use function H4APlugin\Core\wp_build_url;

class ActivationShortcode extends Shortcode {

	private $current_plugin_domain;
	private $post_type;

	public function __construct( $tag ) {
		$this->current_plugin_domain = get_current_plugin_domain();
		parent::__construct( $tag );
		$this->post_type = $tag;
	}

	public function check_page(){
		if( get_post_type() === $this->post_type  ){
			add_filter( "template_include", array( get_called_class(), "page_template" ), 99 );
			add_action( "template_redirect", array( $this, "is_redirection") );
			add_action( "wp_enqueue_scripts", array( $this, "set_scripts" ) );
		}
	}

	public static function is_redirection() {
		wp_debug_log();
		if( Member::isAccountAlreadyActive( $_GET['e'] ) ) {
			$args = array(
				'registered' => "true"
			);
			$login_page_url = wp_build_url( "wgs-login", H4A_WGS_PLUGIN_LABEL_LOG_IN, $args );
			wp_redirect( $login_page_url );
		}else{
			if ( ! self::checkToDisplayPageContent() ) {
				wp_redirect_404();
			}else if ( !empty( $_POST ) ) {
				include_once( ABSPATH . "wp-admin/includes/plugin.php" );
				$result = self::checkDataToSubmit();
				if ( $result['success'] ) {
					$currentMember = Member::getMemberByTokenAndEmail( $_GET['e'], $_GET['t'] );
					$data_member = array(
						'member_id' => $currentMember->member_id,
						'first_name' => $result['data']['first_name'],
						'last_name' => $result['data']['last_name'],
						'email' => $_GET['e'],
						'password' => $result['data']['password'],
						'start_date' => $currentMember->start_date,
						'subscriber_id' => $currentMember->subscriber_id
					);
					$currentMember = new Member( $data_member );
					$is_updated = $currentMember->update();
					if( !$is_updated ){
						wp_error_log( "Member could not be updated!" );
						Notices::setNotice( wp_get_error_system(), "error" );
					}else{
						$currentMember->activate();
						$args = array(
							'registered' => "true"
						);
						$login_page_url = wp_build_url( "wgs-login", H4A_WGS_PLUGIN_LABEL_LOG_IN, $args );
						wp_redirect( $login_page_url );
					}
				}

			}
		}
	}

	public static function getCallBack( $atts = null ){

		include_once( ABSPATH . "wp-admin/includes/plugin.php" );

		$form = new FrontEndForm( 1, "member-activation-account", true );
		if ( ! $form ) {
			wp_error_log( "The right form does not exist!" );
			$errors[] = wp_get_error_system();
		} else {
			$current_plugin_domain = get_current_plugin_domain();
			$text_introduction = sprintf( __( "Welcome to %s!", $current_plugin_domain ), get_bloginfo( "name" ) );
			$text_introduction .= "<br/>".__( "To access all documents, please activate your member account by filling in the following information.", $current_plugin_domain );

			$form->action                                   = self::makeActivationUrl( $_GET['e'], $_GET['t'] );
			$form->options['text_introduction']             = $text_introduction;
			$form->options['submitBox']                     = array( 'button' => "Activate my account" );
			$form->options['recaptcha']                     = true;
			$form->options['has_required_fields']           = true;
			$form->content[0]['rows'][0]['columns'][0]['items'][0]['value']   = $_GET['e'];
			unset( $form->content[0]['rows']['1']); //remove confirmation email
			$form->content[0]['rows'][0]['columns'][0]['items'][0]['readonly'] = true;
		}

		$output = "";

		ob_start();

		$transient_name = FrontEndNotice::gen_transient_name();
		$transient = get_transient( $transient_name );
		if( !empty( $transient ) && !empty( $transient[ 'front-end' ] ) ){
			Notices::displayAll();
		}

		//HTML Template
		$form->writeForm();

		// Get the contents and clean the buffer
		$output .= ob_get_contents();
		ob_end_clean();

		return $output;
	}

	public static function checkToDisplayPageContent(  ) {
		if( !isset($_GET['e'])
		    || !isset($_GET['t'])
		    || !Member::isMemberByTokenAndEmail( $_GET['e'], $_GET['t'] )
		) {
			return false;
		}else{
			return true;
		}
	}

	public static function checkDataToSubmit(){
		wp_debug_log();
		$server_check = array (
			'success' => false
		);
		if ( is_plugin_active( "wgs-recaptcha-addon/WGSRecaptchaAddon.php" ) ) {
			if( !empty( $_POST['g-recaptcha-response'] ) ){
				$server_check = json_decode( apply_filters_ref_array( "recaptcha_server_check", array( $_POST['g-recaptcha-response'] ) ), true );
			}else{
				$current_plugin_domain = get_current_plugin_domain();
				$message_error = sprintf( __( "Please check the box: \"%s\"", $current_plugin_domain ), __( "I am not a robot", $current_plugin_domain ) );
				Notices::setNotice( $message_error, "error" );
			}
		}else{
			$server_check['success'] = true;
		}

		if ( $server_check['success'] === true && Notices::isNoErrors() ) {
			return FrontEndForm::checkDataMemberActivation( $_POST );
		}else{
			$results = array();
			$results['success'] = false;
			return $results;
		}
	}

	public function set_scripts() {
		//Javascripts
		wp_enqueue_script( "h4acommonformplugin", H4A_WGS_PLUGIN_DIR_URL . "core/common/features/form/js/common-form-plugin.js" );
		wp_localize_script( "h4acommonformplugin", "commonFormTranslation", array(
			'msg_must_match' => __( "It is must match with the previous input", $this->current_plugin_domain ),
		) );
		wp_enqueue_script( "wgscommonformscript", H4A_WGS_PLUGIN_DIR_URL . "common/js/wgs-common-form.js" );

		//CSS stylesheets
		wp_enqueue_style( "h4afrontendform", H4A_WGS_PLUGIN_DIR_URL . "core/front-end/features/form/css/front-end-form-style.css" );
		wp_enqueue_style( "wgsstyle", H4A_WGS_PLUGIN_DIR_URL . "front-end/css/wgs-front-end.css" );
	}

	public static function makeActivationUrl( $address_email, $token){
		$activation_page = self::getActivationPageNumber();
		return sprintf( "%s?post_type=wgs-activation&p=%s&e=%s&t=%s", get_site_url(), $activation_page, $address_email, $token );
	}

	public static function getActivationPageNumber(){

		global $wpdb;

		// Start query string
		$query_string       = "SELECT ID FROM {$wpdb->prefix}posts WHERE post_type='wgs-activation'";

		// Return results
		$result = $wpdb->get_results( $query_string, ARRAY_A );

		if( count($result) === 1 ){
			return $result[0]['ID'];
		}else{
			wp_error_log( "WGS Activation Page does not exist!" );
			return false;
		}

	}
}