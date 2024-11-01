<?php

namespace H4APlugin\WPGroupSubs\Shortcodes;


use H4APlugin\Core\Common\Notices;
use H4APlugin\Core\FrontEnd\FrontEndNotice;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\wp_build_url;
use function H4APlugin\Core\wp_debug_log;
use function H4APlugin\Core\wp_error_log;
use function H4APlugin\Core\wp_get_error_system;
use function H4APlugin\Core\wp_redirect_404;
use H4APlugin\WPGroupSubs\Common\Member;
use H4APlugin\WPGroupSubs\Common\Plan;
use H4APlugin\WPGroupSubs\Common\PlanForms;
use H4APlugin\WPGroupSubs\Common\Subscriber;

class MyProfileShortcode extends Shortcode{

	private $current_plugin_domain;
	private $post_type;

	public function __construct( $tag ) {
		$this->current_plugin_domain = get_current_plugin_domain();
		parent::__construct( $tag );
		$this->post_type = $tag;
	}

	public function check_page() {
		if( get_post_type() === $this->post_type  ){
			wp_debug_log();
			add_filter( 'template_include', array( get_called_class(), 'page_template' ), 99 );
			add_action( "template_redirect", array( $this, "is_redirection") );
		}
	}

	public static function is_redirection() {
		wp_debug_log();
		if ( !empty( $_POST ) ){
			$res_update = self::updateSubscriberOrMember();
			if( $res_update['success'] ){
				$message_success = __( "Your account information was updated successfully !", get_current_plugin_domain() );
				Notices::setNotice( $message_success, "success" );
			}else{
				$message_error = wp_get_error_system();
				Notices::setNotice( $message_error, "error" );
			}
			wp_redirect( wp_build_url( self::getProfilePagePostType(), self::getProfilePageTitle() ) );
			exit;
		}else if( !Member::isLoggedIn() && !Subscriber::isLoggedIn() ){
			wp_redirect_404();
			exit;
		}else {
			$wgs_profile_page_options = get_option( "wgs-profile-page-options" );

			if( !empty( $wgs_profile_page_options )
			    && !empty( $wgs_profile_page_options["profile_page"] ) ){
				$page_id = (int) $wgs_profile_page_options["profile_page"];
				if( $page_id > 0 ){
					$profile_page = get_post( $page_id );
				}
			}

			if( !empty( $profile_page ) ){
				wp_redirect( wp_build_url( "page", $profile_page->post_title ) );
				exit;
			}
		}
	}

	protected static function updateSubscriberOrMember(){
		wp_debug_log();
		$output = array(
			'success' => false
		);
		$is_subs_loggedIn = Subscriber::isLoggedIn();
		$is_mbr_loggedIn  = Member::isLoggedIn();
		if( $is_subs_loggedIn ){
			$subscriber_loggedIn = Subscriber::getSubscriberLoggedIn( "read" );
			$plan_type = Plan::getPlanTypeById( $subscriber_loggedIn->plan_id );
		}else{
			$member_loggedIn = Member::getMemberLoggedIn( "read" );
			$subscriber = new Subscriber( $member_loggedIn->subscriber_id, "read" );
			$plan_type = Plan::getPlanTypeById( $subscriber->plan_id );
		}
		$res_check = PlanForms::checkFormData( $_POST, $plan_type, true );
		if( !$res_check['success'] ){
			wp_error_log("Data Form checking show errors!" );
			Notices::setNotices( $res_check['errors'], "error", true );
		}else{
			if( $is_subs_loggedIn && isset( $subscriber_loggedIn ) ){
				$data_subs = array(
					'subscriber_id' => $subscriber_loggedIn->subscriber_id,
					'first_name'    => $res_check['data']['first_name'],
					'last_name'     => $res_check['data']['last_name'],
					'email'         => $subscriber_loggedIn->email,
					'street_name'   => $res_check['data']['street_name'],
					'zip_code'      => $res_check['data']['zip_code'],
					'city'          => $res_check['data']['city'],
					'country_id'    => $res_check['data']['country_id'],
					'plan_id'       => $subscriber_loggedIn->plan_id,
					'status'        => $subscriber_loggedIn->status,
				);
				if( !empty( $res_check['data']['password'] ) )
					$data_subs['password'] = $res_check['data']['password'];
				if( !empty( $res_check['data']['phone_code'] ) )
					$data_subs['phone_code'] =  $res_check['data']['phone_code'];
				if( !empty( $res_check['data']['phone_number'] ) )
					$data_subs['phone_number'] =  $res_check['data']['phone_number'];
				if( !empty( $res_check['data']['street_number'] ) )
					$data_subs['street_number'] =  $res_check['data']['street_number'];
				$subscriber_to_update = new Subscriber( $data_subs, "edit" );
				$addSingleMember = ( $is_mbr_loggedIn ) ? true : false ;
				$res_update = $subscriber_to_update->update( $addSingleMember );
			}else if( isset( $member_loggedIn ) ){
				$data_mbr = array(
					'first_name'    => $res_check['data']['first_name'],
					'last_name'     => $res_check['data']['last_name'],
					'email'         => $member_loggedIn->email,
				);
				if( !empty( $res_check['data']['password'] ) )
					$data_subs['password'] = $res_check['data']['password'];
				$member_to_update = new Member( $data_mbr, "edit" );
				$res_update = $member_to_update->update();
			}
			if( isset( $res_update ) ){
				if ( !$res_update['success'] ) {
					$message_error = ( !empty( $_GET['subs'] ) ) ? __( "Updating failed!", get_current_plugin_domain() ) : __( "Saving failed!", get_current_plugin_domain() );
					wp_error_log( $message_error );
					Notices::setNotice( $message_error, "error", true );
				}else{
					$output['success'] = true;
					$output['data']    = $res_update['data'];
				}
			}
		}
		return $output ;
	}

	public static function getCallBack( $attrs ) {

		$output = '';

		ob_start();

		$transient_name = FrontEndNotice::gen_transient_name();
		$transient = get_transient( $transient_name );
		if( !empty( $transient ) && !empty( $transient[ 'front-end' ] ) ){
			Notices::displayAll();
		}

		//HTML Template
		include_once dirname( __FILE__ ) . '/views/view-my-profile.php';

		// Get the contents and clean the buffer
		$output .= ob_get_contents();
		ob_end_clean();

		return $output;
	}

	public static function getProfilePageTitle(){
		$page_title = null;
		$wgs_profile_page_options = get_option( "wgs-profile-page-options" );
		if( !empty( $wgs_profile_page_options )
		    && !empty( $wgs_profile_page_options["profile_page"] ) ){
			$page_id = (int) $wgs_profile_page_options["profile_page"];
			if( $page_id > 0 ){
				$profile_page = get_post( $page_id );
				if( !empty( $profile_page ) )
					$page_title = $profile_page->post_title;
			}
		}
		if( empty( $profile_page ) ){
			$page_title = __( "My profile", get_current_plugin_domain() );
		}
		return $page_title;
	}

	public static function getProfilePagePostType(){
		$wgs_profile_page_options = get_option( "wgs-profile-page-options" );
		if( !empty( $wgs_profile_page_options )
		    && !empty( $wgs_profile_page_options["profile_page"] ) ){
			$page_id = (int) $wgs_profile_page_options["profile_page"];
			if( $page_id > 0 ){
				$profile_page = get_post( $page_id );
				if( !empty( $profile_page ) )
					return $profile_page->post_type;
			}
		}
		if( empty( $profile_page ) ){
			return "wgs-profile";
		}
		return null;
	}
}