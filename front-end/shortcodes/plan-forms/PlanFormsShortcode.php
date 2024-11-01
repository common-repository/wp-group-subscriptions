<?php

namespace H4APlugin\WPGroupSubs\Shortcodes;

use H4APlugin\Core\Common\Countries;
use H4APlugin\Core\Common\Email;
use H4APlugin\Core\Common\Notices;
use H4APlugin\Core\Common\Paypal;
use H4APlugin\Core\Config;
use H4APlugin\Core\FrontEnd\FrontEndStorage;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\is_number;
use function H4APlugin\Core\wp_debug_log;

use H4APlugin\WPGroupSubs\Common\Plan;
use H4APlugin\WPGroupSubs\Common\PlanForms;
use H4APlugin\WPGroupSubs\Common\Subscriber;
use H4APlugin\WPGroupSubs\Common\Payment;
use H4APlugin\WPGroupSubs\Common\Member;
use H4APlugin\WPGroupSubs\Common\FormPages;
use function H4APlugin\Core\format_str_to_display;
use H4APlugin\Core\FrontEnd\FrontEndForm;
use function H4APlugin\Core\wp_redirect_404;
use function H4APlugin\Core\wp_get_error_system;
use function H4APlugin\Core\wp_error_log;
use function H4APlugin\Core\wp_build_url;

class PlanFormsShortcode extends Shortcode {
	
	public $current_subscriber;
	public $current_plugin_domain;
	private $post_type;

	public function __construct( $tag ) {
		$this->current_plugin_domain = get_current_plugin_domain();
		parent::__construct( $tag );
		$this->post_type = "wgs-form-page";
		if( !empty( $_GET['tx'] ) && isset( $_GET['step'] ) && (int) $_GET['step'] === 3 ){
			$subscriber = Subscriber::getSubscriberByTxnId( $_GET['tx'] );
			if( !isset( $subscriber ) || !$subscriber ){
				wp_redirect_404();
			}
		}
	}

	public function check_page() {
		if ( get_post_type() === $this->post_type ) {
			add_action( "template_redirect", array($this, "is_redirection" ) );
			add_action( "wp_enqueue_scripts", array( $this, "set_styles" ) );
			add_action( "wp_enqueue_scripts", array( $this, "set_scripts" ) );
			add_filter( "template_include", array( get_called_class(), "page_template" ), 99 );
		}
	}

	public static function is_redirection() {
		wp_debug_log();
		if ( ! self::checkToDisplayPageContent() ) {
			wp_redirect_404();
		}else{
			if ( !empty( $_POST ) ) {
				include_once( ABSPATH . "wp-admin/includes/plugin.php" );
				$post_title = self::getPlanName();
				$plan   = new Plan( self::getPlanId(), "read" );
				if ( (int) $_GET['step'] === 1 ) {
					$result = self::checkDataToSubmit();
					if ( $result['success'] ) {
						$isAddSingleMember = ( $plan->plan_type === "single"  ) ? true : false ;
						$password = ( isset( $result['data']['password'] ) ) ? $result['data']['password'] : null ;
						$res_subscriber = self::submitSubscriber( $result['data'], $isAddSingleMember, $password );
						if ( !$res_subscriber['success'] ) {
							$error_message = "Subscriber cannot be saved!";
							wp_error_log( $error_message );
							Notices::setNotice( $error_message, "error" );
							Notices::setNotice( wp_get_error_system(), "error" );
						}else if ( $isAddSingleMember  ) {
							//Case : single plan
							if( $plan->price > 0){
								//Case : paid plan
								self::redirectToStep2( $result['data'], $post_title );
							}else{ 
								//Case : free plan
								$subscriber_id = ( is_number( $res_subscriber['data']['subscriber_id'] ) ) ? (int) $res_subscriber['data']['subscriber_id'] : false;
								if( ! is_int( $subscriber_id ) ){
									$error_message = "Subscriber id is not an integer!";
									wp_error_log( $error_message );
									Notices::setNotice( wp_get_error_system(), "error" );
								}else{
									$args = Config::get_item_by_ref( "subscriber" );
									$args['plan_id'] = $plan->plan_id;
									$current_subscriber = new Subscriber( $subscriber_id, "read", $args );
									$current_member = Member::getMemberByPasswordAndEmail( $result['data']['email'], $password );
									if( ! $current_subscriber ){
										Notices::setNotice( wp_get_error_system(), "error" );
									}else{
										//See Manual - Subscriber activation, user case 1.
										$is_sub_active = $current_subscriber->enableSubscriber();
										if( ! $current_member ){
											Notices::setNotice( wp_get_error_system(), "error" );
										}else{
											if( !$is_sub_active ){
												wp_error_log( sprintf( "Error during the activation of this subscriber : '%s'", $current_subscriber->email ) );
												Notices::setNotice( wp_get_error_system(), "error" );
											}else{
												$args = array(
													'registered' => "true"
												);
												$login_page_url = wp_build_url( "wgs-login", H4A_WGS_PLUGIN_LABEL_LOG_IN, $args );
												wp_redirect( $login_page_url );
												exit;
											}
										}
									}
								}
							}
						}else {
							//Case : multiple plan
							if( $plan->price > 0){
								//Case : paid plan
								self::redirectToStep2( $result['data'], $post_title );
							}else{ 
								//Case : free plan
								$current_subscriber = new Subscriber( $res_subscriber['data']['subscriber_id'] );
								$current_user_storage = array(
									'wgs_current_subscriber' => array(
										'subscriber_id' => $current_subscriber->subscriber_id,
										'email' => $current_subscriber->email
									)
								);
								FrontEndStorage::set_user_data( $current_user_storage, null,0 );
								self::redirectToStep3( $post_title );
							}
						}
					} else {
						$_POST['wgs-errors'] = $result['errors'];
					}
				} else if ( (int) $_GET['step'] === 3 ) {

					if( $plan->price > 0 && isset( $_GET['tx'] ) ) {
						$current_subscriber = Subscriber::getSubscriberByTxnId( $_GET['tx'] );
						$resultEmails       = self::checkDataToSubmit();
					}else if( $plan->price === 0.00 ){
						$current_user_storage = FrontEndStorage::get_user_data();
						$current_subscriber = new Subscriber( (int) $current_user_storage['wgs_current_subscriber']['subscriber_id'] );
						$resultEmails       = self::checkDataToSubmit();
					}else{
						wp_redirect_404();
					}
					if ( isset( $current_subscriber ) && isset( $resultEmails ) && $resultEmails['success'] ) {
						foreach ( $resultEmails['data'] as $address_email ) {
							if( $address_email === $current_subscriber->email ){
								$current_subscriber->addSingleMember( $current_subscriber->password, $current_subscriber->subscriber_id );
							}else{
								$data_member = array( 'email' => $address_email, 'subscriber_id' => $current_subscriber->subscriber_id );
								$current_member   = new Member( $data_member );
								$member_save_res  = $current_member->save( true );
								if ( $member_save_res['success'] ) {
									$member_id = $member_save_res['data'];
									if ( ! empty( $member_id ) && $member_id !== false ) {
										$data_email = Member::makeEmailActivation( $address_email, $current_subscriber );
										$email      = new Email( $data_email );
										if ( ! $email ) {
											wp_error_log( "Email cannot be sent!" );
											Notices::setNotice( wp_get_error_system(), "error" );
										} else {
											$resp = $email->send();
											if ( !$resp ) {
												$message_error = sprintf( "email not sent to '%s'!", $address_email );
												wp_error_log( $message_error);
												Notices::setNotice( $message_error, "error" );
											}
										}
									}
								}
							}

						}
						if( Notices::isNoErrors() ){
							if( $plan->price > 0 ) {
								self::redirectToStep4( $post_title,  array( 'tx' => $_GET['tx'] ) );
							}else{
								//Free plan
                                //See Manual - Subscriber activation, user case 1.
								$is_sub_active = $current_subscriber->enableSubscriber();
								if( !$is_sub_active ){
									wp_error_log( sprintf( "Error during the activation of this subscriber : '%s'", $current_subscriber->email ) );
									Notices::setNotice( wp_get_error_system(), "error" );
								}else{
									self::redirectToStep4( $post_title );
								}
							}
						}
					}
				}
			} else if(
				$_GET['step'] === 2
				&& !empty( $_GET['email'] )
			){
				wp_debug_log( "step 2" );
				$subscriber_id = Subscriber::getSubscriberIdByEmail( $_GET['email'] );
				$current_subscriber = new Subscriber( $subscriber_id );
				if( !$current_subscriber || $current_subscriber->status === "active"  ){
					wp_redirect_404();
				}
			}
		}
	
	}

	public static function getCallBack( $atts ) {
		wp_debug_log( "getCallBack" );

		include_once( ABSPATH . "wp-admin/includes/plugin.php" );
		$wgs_paypal_options = get_option( "wgs-paypal-options" );
		$wgs_currency_options = get_option( "wgs-currency-options" );
		$post = get_post();
		$post_title = $post->post_title;
		$plan_id = Plan::getPlanIdByName( $post_title );
		$plan = new Plan( $plan_id, "read" );
		if ( (int) $_GET['step'] === 1 ) {
			$form_type_id = Plan::getFormTypeIdByPlanId( $plan_id );
			$form = new FrontEndForm( $form_type_id, "plan-subscription", true );
			if ( ! $form ) {
				wp_error_log( "The right form does not exist!" );
				Notices::setNotice( wp_get_error_system(), "error" );
			} else {
				$form->action                         = FormPages::buildUrlAction( $post_title, 1 );
				$form->options['step']                = $_GET['step'];
				$form->options['submitBox']           = array( 'button' => "Subscription" );
				$form->options['recaptcha']           = true;
				$form->options['has_required_fields'] = true;
			}
		}
		else if( (int) $_GET['step'] === 2 ){
			$form = new FrontEndForm( 1, "activation-account" );
			if( !$form ){
				wp_error_log( "The right form does not exist!" );
				Notices::setNotice( wp_get_error_system(), "error" );
			}else{
				$current_plugin_domain = get_current_plugin_domain();
				$text_introduction =  __( "Thanks for your subscription!", $current_plugin_domain );
				$single_comment = __( "To activate your account, please proceed to payment.", $current_plugin_domain );
				$multiple_comment = __( "To create all user accounts, please proceed to payment.", $current_plugin_domain );
				$comment = ( $plan->plan_type === "multiple" ) ? $multiple_comment : $single_comment ;
				$text_introduction .= "<br/>".$comment;
				
				$form->options['text_introduction'] = $text_introduction;
				$form->options['step'] = $_GET['step'];
				$form->action = Paypal::getUrlPaypal();
				$form->options['target'] = "_top";
				$form->options['submitBox'] = array( 'button' => "Step 2 : Proceed to payment" );
				$form->options['has_required_fields'] = false;

				$return_url = Payment::getReturnPageUrl();
				$current_user_storage = FrontEndStorage::get_user_data();

				$form->content[0]['items']['business']['value'] = ( !empty( $wgs_paypal_options['paypal_email'] ) ) ? $wgs_paypal_options['paypal_email'] : "";
				$form->content[0]['items']['item_name']['value'] = $plan->plan_name;
				$form->content[0]['items']['amount']['value'] = $plan->price;
				$form->content[0]['items']['currency_code']['value'] = ( !empty( $wgs_currency_options['currency'] ) ) ? $wgs_currency_options['currency'] : "";
				$form->content[0]['items']['first_name']['value'] = $current_user_storage['wgs_current_subscriber']['first_name'];
				$form->content[0]['items']['last_name']['value'] = $current_user_storage['wgs_current_subscriber']['last_name'];
				$form->content[0]['items']['address1']['value'] = $current_user_storage['wgs_current_subscriber']['address1'];
				$form->content[0]['items']['city']['value'] = $current_user_storage['wgs_current_subscriber']['city'];
				$form->content[0]['items']['zip']['value'] = $current_user_storage['wgs_current_subscriber']['zip'];
				$form->content[0]['items']['country']['value'] = $current_user_storage['wgs_current_subscriber']['country'];
				$form->content[0]['items']['return']['value'] = $return_url;
				$form->content[0]['items']['cancel']['value'] = $return_url;
				wp_debug_log( "Check if reset wp_session work here!");
			}
		}
		else if( (int) $_GET['step'] === 3 ){
			$form = new FrontEndForm( 1, "members-account-creation" );
			if ( ! $form ) {
				wp_error_log( "The right form does not exist!" );
				$errors[] = wp_get_error_system();
			} else {
				$action = FormPages::buildUrlAction( $post_title, 3 );
				if( $plan->price > 0 ){
					$action .= "&tx=".$_GET['tx'];
				}
				$form->action                         = $action;
				$form->options['step']                = $_GET['step'];
				$form->options['submitBox']           = array( 'button' => "Save member accounts" );
				$form->options['recaptcha']           = true;
				$form->options['has_required_fields'] = true;
			}
			
			if( !empty( $plan->members_max ) ){
				$a_number_user_accounts = array(
					0 => array(
						'type' => "label",
						'label' => "Number of member accounts :",
						'id' => "wgs_num_user_accounts" // for attribute
					),
					1 => array(
						'col_size' => 1,
						'type' => "select",
						'id' => "wgs_num_user_accounts",
						'function_options' => "H4APlugin\WPGroupSubs\Common\Plan::getInterval#".$plan_id
					)
				);
			}else{
				$a_number_user_accounts = array(
					0 => array(
						'col_size' => 6,
						'type' => "button",
						'class' => "h4a-button",
						'label' => "Add a member account",
						'id' => "wgs_add_user_account"
					),
					1 => array(
						'col_size' => 6,
						'type' => "button",
						'class' => "h4a-button",
						'label' => "Remove a member account",
						'id' => "wgs_remove_user_account",
						'disabled' => true
					)
				);
			}

			$a_include_as_member = array(
				0 => array(
					'col_size' => 6,
					'type'  => "checkbox",
					'id'    => "wgs_f_as_member",
					'name'  => "wgs_f_as_member",
					'label' => __( "Include your account as member", get_current_plugin_domain() ),
					'value' => "on"
				)
			);

			$form->content[0]['rows'][0] = $a_number_user_accounts;
			$form->content[0]['rows'][1] = $a_include_as_member;
			for ( $f = 0; $f < $plan->members_min; $f++){
				$current_already_member = null;
				$n_email = $f + 1 ;
				$inp_email = array(
					'col_size' => 6,
					'type' => "email",
					'label' => array( "Member email %d", $n_email ),
					'id' => "wgs_f_email".$n_email,
					'name' => "wgs_f_email".$n_email,
					'class' => "wgs-email",
					'placeholder' => "Please insert the email",
					'required' => "1",
					'autocomplete' => "off"
				);
				$inp_emailRepeat = array(
					'col_size' => 6,
					'type' => "email",
					'id' => "wgs_f_email_r".$n_email,
					'name' => "wgs_f_email_r".$n_email,
					'class' => "wgs-email-repeat",
					'placeholder' => "Please confirm the email",
					'required' => "1",
					'autocomplete' => "off"
				);
				$form->content[1]['rows'][$f] = array();
				$form->content[1]['rows'][$f][] = $inp_email ;
				$form->content[1]['rows'][$f][] = $inp_emailRepeat ;
			}
		}else if( (int) $_GET['step'] === 4 ){
			$current_plugin_domain  = get_current_plugin_domain();
			$message = __( "Thank you!", $current_plugin_domain ) . "<br/>" . __( "Emails for members of your group have been sent. Each member can now activate his account by clicking on the link in this email.", $current_plugin_domain );
			Notices::setNotice( $message, "success" );
			FrontEndStorage::delete_user_data();
		}

		$output = "";

		ob_start();

		self::getNotices();

		//Template
		include_once dirname( __FILE__ ) . "/views/view-plan-forms.php";

		// Get the contents and clean the buffer
		$output .= ob_get_contents();
		ob_end_clean();

		return $output;
	}

	private static function getNotices(){
		wp_debug_log();
		$wgs_paypal_options = get_option( "wgs-paypal-options" );
		$wgs_currency_options = get_option( "wgs-currency-options" );
		if( empty( $wgs_paypal_options ) || empty( $wgs_currency_options ) || empty( $wgs_paypal_options['paypal_email']) || empty( $wgs_currency_options['currency'] ) ){
			$message = __( "Settings missing error - if you see this message, please contact the administrator" , get_current_plugin_domain() );
			Notices::setNotice( $message, "error" );
		}
	}

	public static function checkToDisplayPageContent(  ) {
		wp_debug_log();
		if( isset( $_GET['step'] ) ){
			//Check step
			if( ! in_array( (int) $_GET['step'], array( 1, 2, 3, 4 ) ) )
				return false;
			//Check is valid plan_id
			$plan_id = self::getPlanId();
			if( !$plan_id )
				return false;

			//Checking for step 2
			if( (int) $_GET['step'] === 2 ){
				$current_user_storage = FrontEndStorage::get_user_data();
				if( empty( $current_user_storage['wgs_current_subscriber']['first_name'] )
				    || empty( $current_user_storage['wgs_current_subscriber']['last_name'] )
				    || empty( $current_user_storage['wgs_current_subscriber']['address1'] )
				    || empty( $current_user_storage['wgs_current_subscriber']['city'] )
				    || empty( $current_user_storage['wgs_current_subscriber']['zip'] )
				    || empty( $current_user_storage['wgs_current_subscriber']['country'] )
				)
					return false;
			}

			//Checking for step 3 and 4
			if( (int) $_GET['step'] === 3 || (int) $_GET['step'] === 4 ){
				if( isset( $_GET['tx'] ) ){
					$current_subscriber = Subscriber::getSubscriberByTxnId( $_GET['tx'] );
					$payment = Payment::getPaymentByTxnId( $_GET['tx'], "paypal" );
					if( !$current_subscriber
					    || $current_subscriber->status !== "active"
					    || !$payment
					    || $payment->payment_status !== "Completed"
					    || $payment->plan_id !== $plan_id
					){
						return false;
					}

				}else{
					$plan = new Plan( $plan_id, "read" );
					if( $plan->price > 0 )
						return false;
					if( ( (int) $_GET['step'] === 3 ) && ( $plan->plan_type === "single" ) )
						return false;
					$current_user_storage = FrontEndStorage::get_user_data();
					if( empty( $current_user_storage['wgs_current_subscriber']['subscriber_id'] )
					    || empty( $current_user_storage['wgs_current_subscriber']['email'] )
					)
						return false;
				}
			}
			return true;
		}else{
			return false;
		}
		
	}
	
	public static function checkDataToSubmit(){
		$server_check = array (
			'success' => false
		);
		if ( is_plugin_active( "wgs-recaptcha-addon/WGSRecaptchaAddon.php" ) ) {
			if( !empty( $_POST['g-recaptcha-response'] ) ){
				$server_check = json_decode( apply_filters_ref_array( "recaptcha_server_check", array( $_POST['g-recaptcha-response'] ) ), true );
			}else{
				$current_plugin_domain = get_current_plugin_domain();
				$error_message = sprintf( __( "Please check the box: \"%s\"", $current_plugin_domain ), __( "I am not a robot", $current_plugin_domain ) );
				Notices::setNotice( $error_message, "error" );
			}
		}else{
			$server_check['success'] = true;
		}
		
		if ( $server_check['success'] ) {
			if( (int) $_GET['step'] === 1 ){
				$plan_type = Plan::getPlanTypeById( self::getPlanId() );
				$res_check_form_data = PlanForms::checkFormData( $_POST, $plan_type );
				if( !$res_check_form_data['success'] )
					Notices::setNotices( $res_check_form_data['errors'], "error" );
				return $res_check_form_data;
			}else if( (int) $_GET['step'] === 3 ){
				$res_check_form_email = FrontEndForm::checkDataEmails( $_POST, false, true, false );
				if( !$res_check_form_email['success'] )
					Notices::setNotices( $res_check_form_email['errors'], "error" );
				return $res_check_form_email;
			}				
		}else{
			return $server_check;
		}
		return $server_check;
	}
	
	public static function submitSubscriber( $data, $isSaveMember = false, $password = null ){
		wp_debug_log();
		$data_subs = array(
			'first_name' => $data['first_name'],
			'last_name' => $data['last_name'],
			'email' => $data['email'],
			'phone_code' => ( !empty ( $data['phone_code'] ) ) ? $data['phone_code'] : null,
			'phone_number' => ( !empty ( $data['phone_number'] ) ) ? $data['phone_number'] : null,
			'group_name' => ( !empty ( $data['group_name'] ) ) ? $data['group_name'] : null,
			'street_number' => $data['street_number'],
			'street_name' => $data['street_name'],
			'zip_code' => $data['zip_code'],
			'city' => $data['city'],
			'country_id' => $data['country_id'],
			'plan_id' => self::getPlanId(),
			'status' => "disabled"
		);

		if( !empty( $password ) )
			$data_subs['password'] = $password;
		
		if ( is_plugin_active( "wgs-custom-forms-addon/WGSCustomFormsAddon.php" ) ) {
			$data_subs = apply_filters( "wgs_set_data_as_options", $data_subs, $data );
		}
		$subscriber = new Subscriber( $data_subs, "edit" );
		$subscriber_id = $subscriber->save( $isSaveMember, $password );
		return $subscriber_id;
	}
	
	public static function getSubsObjectForPayment( $data ){
		$address1 = null;
		if( !empty( $data['street_number'] ) ){
			$address1 =  format_str_to_display( $data['street_number'] . " " . $data['street_name'] );
		}else{
			$address1 =  format_str_to_display( $data['street_name'] );
		}
		return array(
			'email'      => format_str_to_display( $data['email'] ),
			'first_name' => format_str_to_display( $data['first_name'] ),
			'last_name'  => format_str_to_display( $data['last_name'] ),
			'address1'   => $address1,
			'city'       => format_str_to_display( $data['city'] ),
			'zip'        => format_str_to_display( $data['zip_code'] ),
			'country'    => Countries::getIsoByCountryId( $data['country_id'] )
		);
	}
	
	public static function redirectToStep2( $data, $plan_title ){
		$wgs_current_subscriber =  self::getSubsObjectForPayment( $data );
		$current_user_storage = array(
			'wgs_current_subscriber' => $wgs_current_subscriber
		);
		FrontEndStorage::set_user_data(  $current_user_storage, null,0 );
		wp_redirect( FormPages::buildUrlAction( $plan_title, 2 ) );
		exit;
	}

	public static function redirectToStep3( $post_title, $args = null ){
		wp_redirect( FormPages::buildUrlAction( $post_title, 3, $args ) );
		exit;
	}
	
	public static function redirectToStep4( $post_title, $args = null ){
		wp_redirect( FormPages::buildUrlAction( $post_title, 4, $args ) );
		exit;
	}

	public static function getPlanName(){
		$post = get_post();
		$post_title = $post->post_title;
		return $post_title;
	}

	public static function getPlanId(){
		$post_title = self::getPlanName();
		$plan_id = Plan::getPlanIdByName( $post_title );
		if( is_int( $plan_id ) ){
			return $plan_id;
		}else{
			$message_error = sprintf( "plan_id : '%s' is not an integer.", $plan_id );
			wp_error_log( $message_error );
			return false;
		}
	}
	
	//CSS stylesheets
	public function set_styles() {
		wp_enqueue_style( "h4afrontendform", H4A_WGS_PLUGIN_DIR_URL . "core/front-end/features/form/css/front-end-form-style.css" );
		wp_enqueue_style( "wgsstyle", H4A_WGS_PLUGIN_DIR_URL . "front-end/css/wgs-front-end.css" );
	}

	//Javascripts
	public function set_scripts() {
		if ( ! empty( $_GET['step'] ) && in_array( (int) $_GET['step'], array( 1, 3 ) ) ) {
			wp_enqueue_script( "h4acommonformplugin", H4A_WGS_PLUGIN_DIR_URL . "core/common/features/form/js/common-form-plugin.js" );
			wp_localize_script( "h4acommonformplugin", "commonFormTranslation", array(
				'msg_must_match' => __( "It is must match with the previous input", $this->current_plugin_domain ),
			) );
			wp_enqueue_script( "wgscommonformscript", H4A_WGS_PLUGIN_DIR_URL . "common/js/wgs-common-form.js" );
		}if ( ! empty( $_GET['step'] ) && (int) $_GET['step'] === 3 ){
			wp_enqueue_script( "wgsfrontendemailsplugin", H4A_WGS_PLUGIN_DIR_URL . "front-end/shortcodes/plan-forms/views/js/wgs-emails-plugin.js" );
			wp_enqueue_script( "wgsfrontendemailsscript", H4A_WGS_PLUGIN_DIR_URL . "front-end/shortcodes/plan-forms/views/js/wgs-emails.js" );
			wp_localize_script( 'wgsfrontendemailsscript', 'wgs_ajax_object',
				array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
			wp_localize_script( "wgsfrontendemailsscript", "wgsEmailTranslation", array(
				'msg_must_email_unique'        => __( "You have already entered this email", $this->current_plugin_domain ),
				'msg_email'                    => __( "Member email", $this->current_plugin_domain ),
				'msg_email_placeholder'        => __( "Please insert the email", $this->current_plugin_domain ),
				'msg_repeat_email_placeholder' => __( "Please confirm the email", $this->current_plugin_domain ),
			) );
		}
	}

	public static function page_template( $template = "" ) {
		if( empty( $template ) ){
			$template = locate_template( array( 'single.php' ) );
		}
		return $template;
	}
}