<?php

namespace H4APlugin\WPGroupSubs\Common;

use H4APlugin\Core\Admin\AdminForm;
use H4APlugin\Core\Common\EditableItem;
use H4APlugin\Core\Common\Email;
use H4APlugin\Core\Common\Notices;
use H4APlugin\Core\Common\Paypal;
use H4APlugin\Core\FrontEnd\FrontEndForm;
use H4APlugin\Core\FrontEnd\FrontEndStorage;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\get_current_plugin_prefix;
use function H4APlugin\Core\is_float_as_string;
use function H4APlugin\Core\is_https;

use function H4APlugin\Core\is_number;
use function H4APlugin\Core\wp_debug_log;
use H4APlugin\WPGroupSubs\Shortcodes\ActivationShortcode;
use function H4APlugin\Core\format_str_capitalize_first_letter;
use function H4APlugin\Core\getToken;
use function H4APlugin\Core\wp_get_error_system;
use function H4APlugin\Core\get_today_as_datetime;
use function H4APlugin\Core\wp_error_log;

class Member extends EditableItem {
	use UserTrait;

	public $member_id;
	public $first_name;
	public $last_name;
	public $email;
	public $group_name;
	public $last_connection;
	public $last_activation;
	public $start_date;
	public $status;
	public $subscriber_id;

	public function __construct( $email_or_data, $format = "list-table", $args = array() ){

		if( filter_var( $email_or_data, FILTER_VALIDATE_EMAIL  ) ){
			$this->get_item( $email_or_data );
		}else{
			parent::__construct( $email_or_data, $format, $args );
		}
		return false;
	}

	/**
	 * @param $res_plan_expired
	 * @param Member $member_found
	 */
	public static function resultAfterExpirationPlanChecking( $res_plan_expired, Member $member_found )
	{
		if ( !$res_plan_expired['success'] ) {
			wp_debug_log( $res_plan_expired['errors'] );
		} else {
			$current_plugin_domain = get_current_plugin_domain();
			if ( $res_plan_expired['data'] ) {
				$message_error = __( "Your subscription has expired!", $current_plugin_domain );
				Notices::setNotice( $message_error, "error" );
			} else if( empty( $_COOKIE["h4a_key" ] ) ){
				$message_error = __( "Error, please refresh this page.", $current_plugin_domain );
				Notices::setNotice( $message_error, "error" );
			}else{
				$user_data = array(
					$current_plugin_domain => array(
						'member_id' =>  $member_found->member_id,
						'status'    => "loggedIn"
					)
				);
				if( Subscriber::isLoggedIn() ){
					$user_data[ $current_plugin_domain ]['subscriber_id'] =	$member_found->subscriber_id;
				}
				FrontEndStorage::set_user_data( $user_data, null, 12 * HOUR_IN_SECONDS );
				$member_found->set_last_connection();
			}
		}
	}

	/*
	 * Initializers
	 */

	public function initForm(){

	}

	/**
	 * Getters
	 */

	/**
	 * @param array $args
	 */
	protected function get_blank( $args = array() ){
		$this->member_id       = null;
		$this->first_name      = "";
		$this->last_name       = "";
		$this->email           = "";
		$this->password        = "";
		$this->start_date      = null;
		$this->last_connection = null;
		$this->last_activation = null;
		$this->status          = null;
		$this->subscriber_id   = null;
	}

	public function get_item_to_edit( $data ){
		$this->member_id       = ( !empty($data['member_id']) )       ? $data['member_id']       : null;
		$this->email           = sanitize_email( $data['email'] );
		$this->first_name      = ( !empty($data['first_name']) )      ? sanitize_text_field( $data['first_name'] ) : null;
		$this->last_name       = ( !empty($data['last_name']) )       ? sanitize_text_field( $data['last_name']  ) : null;
		$this->password        = ( !empty($data['password']) )        ? $data['password']        : null;
		$this->start_date      = ( !empty($data['start_date']) )      ? $data['start_date']      : null;
		$this->last_connection = ( !empty($data['last_connection']) ) ? $data['last_connection'] : null;
		$this->last_activation = ( !empty($data['last_activation']) ) ? $data['last_activation'] : null;
		$this->status          = sanitize_text_field( $data['status'] );
		$this->subscriber_id   = $data['subscriber_id'];
	}

	protected function get_item_to_list( $data ){
		$this->member_id       = $data['member_id'];
		$this->first_name      = $data['first_name'];
		$this->last_name       = $data['last_name'];
		$this->email           = $data['email'];
		if( isset( $data['group_name'] ) )
			$this->group_name  = $data['group_name'];
		$this->last_connection = $data['last_connection'];
		$this->last_activation = $data['last_activation'];
		$this->start_date      = $data['start_date'];
		$this->status          = $data['status'];
		$this->subscriber_id   = $data['subscriber_id'];
	}

	public function get_item( $id_or_email ){

		global $wpdb;

		$query = "";

		if( is_number( $id_or_email ) ){
			$query = "SELECT * FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "members WHERE member_id = " . (int) $id_or_email . ";";
		}else if( !filter_var( $id_or_email, FILTER_VALIDATE_EMAIL  ) ){
			wp_error_log( sprintf( "Invalid argument : '%s' ! To get a member, you need a member_id or an email.", $id_or_email ) );
		}else{
			$query = "SELECT * FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "members WHERE email = '" . $id_or_email . "';";
		}
		$results = $wpdb->get_results( $query, ARRAY_A );

		if(count($results) === 0){
			wp_error_log( "Member not found!");
		}else{
			foreach ( $results[0] as $column_name => $value ){
				if( is_float_as_string( $value ) ){
					$value = (float) $value;
				}else if( is_number( $value ) ){
					$value = (int) $value;
				}
				$this->$column_name = $value;
			}
		}
	}

	protected function get_editable_item_form() {
		wp_debug_log( "subscriber_id : " . $this->subscriber_id );
		if( is_admin() ){
			$this->form = new AdminForm( 1, "member-activation-account", true );
		}else{
			$current_plugin_domain = get_current_plugin_domain();
			$this->form = new FrontEndForm( 1, "member-activation-account", true );
			if ( ! $this->form ) {
				wp_error_log( "The right form does not exist!" );
				$errors[] = wp_get_error_system();
			} else {
				//$text_introduction = sprintf( __( "Welcome to %s!", $current_plugin_domain ), get_bloginfo( "name" ) );
				//$text_introduction .= "<br/>".__( "To access all documents, please activate your member account by filling in the following information.", $current_plugin_domain );

				//$form->action                                   = self::makeActivationUrl( $_GET['e'], $_GET['t'] );
				//$form->options['text_introduction']             = $text_introduction;
				$this->form->options['submitBox']               = array( 'button' => "Save Changes" );
				$this->form->options['has_required_fields']     = true;
				$this->form->content[0]['rows'][0]['columns'][0]['items'][0]['value'] = $this->email;
				unset( $this->form->content[0]['rows'][1]); //remove confirmation email
				$this->form->content[0]['rows'][0]['columns'][0]['items'][0]['readonly'] = true;
				$this->form->content[0]['rows'][2]['columns'][0]['items'][0]['value'] = $this->last_name;
				$this->form->content[0]['rows'][2]['columns'][1]['items'][0]['value'] = $this->first_name;
				$this->form->content[0]['rows'][3] = array(
					0 => array(
						'type' => "label",
						'label' => __( "Password", $current_plugin_domain ),
						'id' => "wgs_f_password",
						'col_size' => 2
					),
					1 => array(
						'type' => "button",
						'label' => __( "Change Password", $current_plugin_domain ),
						'id' => "wgs_change-password",
						'value' => "change",
						'col_size' => 10
					)
				);
			}
			$subscriber = new Subscriber( $this->subscriber_id, "read"  );
			if( $subscriber->email !== $this->email ){
				$this->form->content[0]['rows'][4] = array(
					0 => array(
						'type' => "text",
						'label' => __( "Group name", $current_plugin_domain ),
						'id' => "wgs_f_group_name",
						'name' => "wgs_f_group_name",
						'value' => $subscriber->group_name,
						'col_size' => 10,
						'readonly' => 1,
						'disabled' => 1,
					)
				);
			}
		}
		$this->form->options['action_wpnonce'] = $this->nonce;
	}

	/**
	 * CRUD functions
	 */

	/**
	 * @param bool|null $gen_rand_password
	 * @param bool|null $encode_password
	 *
	 * @return array
	 */
	public function save( bool $gen_rand_password = null, bool $encode_password = null ){
		wp_debug_log();
		$output = array(
			'success' => false
		);

		global $wpdb;
		if( !empty( $this->member_id ) ){
			wp_error_log( sprintf( "This %s has got an id ! Please update it instead of save it.", $this->params->name ) );
			$message_error  = sprintf( _x( "%s '%s' already exists in members!", "editable_item", $this->current_plugin_domain ), __( "The " . $this->params->name, "editable_item", $this->current_plugin_domain ), $this->params->name, $this->email );
			Notices::setNotice( $message_error, "error", true );
		}else if( self::isMemberWithEmail( $this->email ) ){
			$message_error = sprintf( __( "The email '%s' already exists!" ), $this->email );
			Notices::setNotice( $message_error, "error", true );
		}else{
			$password = null;
			if( $gen_rand_password ){
				$password = $this->generateRandomPassword();
			}else if( $encode_password ){
				$password = $this->encodePassword();
			}else{
				$password = $this->password;
			}
			$data = array(
				'first_name'    => ( !empty( $this->first_name ) ) ? $this->first_name : null,
				'last_name'     => ( !empty( $this->last_name ) ) ? $this->last_name : null,
				'password'      => $password,
				'email'         => $this->email,
				'subscriber_id' => $this->subscriber_id,
				'start_date'    => get_today_as_datetime(),
				'status'        => "published"
			);
			$res_ins = $wpdb->insert( $wpdb->prefix . get_current_plugin_prefix() . "members", $data );
			if( !$res_ins ){
				$message_error = sprintf( _x( "%s '%s' could not be saved!", "editable_item", $this->current_plugin_domain ), _x( "The " . $this->params->name, "editable_item", $this->current_plugin_domain ), $this->email );
				wp_error_log( $message_error );
				Notices::setNotice( $message_error, "error", true );
			}else{
				$member_id = $wpdb->insert_id;
				$output['success'] = true;
				$output['data'] = $member_id; // member_id
			}
		}
		return $output;

	}

	public function update( $encode_password = true ){
		wp_debug_log();
		$output = array(
			'success' => false
		);

		if( empty( $this->member_id ) ){
			wp_error_log( sprintf( "This %s has not got an id ! Please save it instead of update it.", $this->params->name ) );
			$message_error  = sprintf( __( "%s ( id : '%s' ) does not already exist to update it!", "editable_item", $this->current_plugin_domain ), __( "The " . $this->params->name, "editable_item", $this->current_plugin_domain ), $this->member_id );
			Notices::setNotice( $message_error, "error", true );
		}else{
			$data = array(
				'email'         => $this->email,
				'first_name'    => $this->first_name,
				'last_name'     => $this->last_name,
				'subscriber_id' => $this->subscriber_id // To modify of subscription group.
			);
			if( !empty( $this->password ) ){
				if( $encode_password ){
					$data['password'] = $this->encodePassword();
				}else{
					$data['password'] = $this->password;
				}
			}
			$where_others = array (
				'member_id' => $this->member_id
			);
			$res_update_member = $this->update_item( "members", $data, $this->email, $where_others );
			if( !$res_update_member['success'] ){
				Notices::setNotices( $res_update_member['errors'], "error", true );
			}else{
				$output['success'] = true;
				$output['data'] = $res_update_member['data']; //member_id
			}
		}
		return $output;
	}

	public function trash(){
		$full_name = $this->first_name . " " . $this->last_name;
		$error_message = sprintf( __( "Impossible to move the %s '%s' to trash!", $this->current_plugin_domain ), $this->params->name, $full_name );
		$res_update_trash = $this->updateStatus( "trash", $error_message );
		return $res_update_trash;
	}

	public function untrash(){
		wp_debug_log();
		$full_name = $this->first_name . " " . $this->last_name;
		$error_message = sprintf( __( "Impossible to restore the %s '%s' from the trash!", $this->current_plugin_domain ), $this->params->name, $full_name );
		$res_update_untrash = $this->updateStatus( "published", $error_message );
		return $res_update_untrash;
	}

	public function delete(){
		$output = array(
			'success' => false
		);

		global $wpdb;

		$res_del_query = $wpdb->delete( $wpdb->prefix. get_current_plugin_prefix() ."members" ,array( 'member_id' => $this->member_id ) );
		if( $res_del_query === 0 || $res_del_query === false ){
			wp_error_log( sprintf( "For some reason the member ( id : '%s') could not be deleted!", $this->member_id ) );
			$message_error = sprintf( __( "Impossible to delete the member %s %s linked to this account!" , $this->current_plugin_domain ), $this->first_name, $this->last_name );
			Notices::setNotice( $message_error, "error", true );
		}else{
			if( !empty( $this->first_name ) && !empty( $this->last_name ) ){
				$full_name = $this->first_name . " " . $this->last_name;
			}else{
				$full_name = $this->email;
			}
			$message_success = sprintf( __( "%s '%s' has been deleted.", $this->current_plugin_domain ), _x( "The member", "message_item_name", $this->current_plugin_domain ), $full_name );
			Notices::setNotice( $message_success, "success", true );
			$output['success'] = true;
			$output['data'] = $res_del_query;
		}
		return $output;
	}

	/**
	 * Additional Getters
	 */

	/**
	 * @param $address_email
	 * @param $subscriber_id
	 *
	 * @return bool|string
	 */
	public static function getTokenMemberByEmailAndSubsId( $address_email, $subscriber_id ){
		global $wpdb;

		$query = "SELECT password, start_date FROM {$wpdb->prefix}" . get_current_plugin_prefix() ."members 
				  WHERE email = '" . $address_email . "'
				  AND subscriber_id = ". $subscriber_id .";";

		$results = $wpdb->get_results( $query, ARRAY_A );
		if( count($results) === 1 ){
			$password = $results[0]['password'].strtotime( $results[0]['start_date'] );
			$token = password_hash( $password, PASSWORD_DEFAULT );
			return $token;
		}else{
			return false;
		}
	}

	public static function getMemberByTokenAndEmail( $address_email, $token ){

		if( self::isMemberByTokenAndEmail( $address_email, $token ) ){

			global $wpdb;

			$query = "SELECT * FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "members 
				  WHERE email = '" . $address_email . "';";

			$results = $wpdb->get_results( $query, ARRAY_A );
			if( count($results) === 1 ){
				$member_data_init = array(
					'member_id' => $results[0]['member_id'],
					'email' => $address_email,
					'subscriber_id' => $results[0]['subscriber_id'],
					'start_date' => $results[0]['start_date']
				);
				$member = new Member( $member_data_init );
				return $member;
			}else{
				return false;
			}
		}else{
			return false;
		}

	}

	public static function getMemberByPasswordAndEmail( $address_email, $password ){
		wp_debug_log();
		global $wpdb;
		$plugin_domain = get_current_plugin_prefix();
		$query = "SELECT * FROM {$wpdb->prefix}{$plugin_domain}members WHERE email = '{$address_email}';";

		$results = $wpdb->get_results( $query, ARRAY_A );
		if( count( $results ) === 1 ){
			if( password_verify( $password, $results[0]['password'] ) ){
				$member = new Member( $results[0] );
				return $member;
			}else{
				wp_error_log( sprintf( "Invalid password : '%s'", $password ) );
				return false;
			}

		}else{
			wp_error_log( sprintf( "No member with this email : '%s'", $address_email ) );
			return false;
		}
	}

	/**
	 * Additional CRUD functions
	 */

	private function generateRandomPassword(){
		try {
			$t = getToken( 45 );
		} catch ( \Exception $e ) {
			$t = $e;
		}
		return $t;
	}

	/**
	 * @param $email
	 * @param $password
	 *
	 * @return array
	 */
	public static function logIn( $email, $password ){

		$output = array(
			'success' => false
		);

		$current_plugin_domain = get_current_plugin_domain();

		$is_email =  self::isMemberWithEmail( $email );

		$mgs_invalid_email_or_password = __("Invalid Email or Password!", $current_plugin_domain );
		if( !$is_email ){
			wp_error_log( "Invalid Email", "Log In" );
			if( !Notices::containsNotice( $mgs_invalid_email_or_password ) )
				Notices::setNotice( $mgs_invalid_email_or_password, "error" );
		}else{
			$member_found = new Member( $email );
			if( $member_found->status === "trash" ){
				$error_message = sprintf("Member ( %s ) status : 'trash'", $member_found->email );
				wp_error_log( $error_message, "Log In" );
				if( !Notices::containsNotice( $mgs_invalid_email_or_password ) )
					Notices::setNotice( $mgs_invalid_email_or_password, "error" );
			}else{
				if( !password_verify ( $password , $member_found->password ) || $member_found->last_activation === null ){
					$message_error = sprintf( "Invalid Password" );
					wp_error_log( $message_error, "Log In" );
					if( !Notices::containsNotice( $mgs_invalid_email_or_password ) )
						Notices::setNotice( $mgs_invalid_email_or_password, "error" );
				}
				else{

					$subscriber = new Subscriber( $member_found->subscriber_id, "read" );

					if( !$subscriber ){
						$error_message = sprintf( "The subscriber ( id : %s ) does not exist in the database!", $member_found->subscriber_id );
						wp_error_log( $error_message, "Log In" );
						Notices::setNotice( wp_get_error_system(), "error" );
					}else if( !$subscriber->is_active() ){
						if( !Notices::containsNotice( $mgs_invalid_email_or_password ) )
							Notices::setNotice( $mgs_invalid_email_or_password, "error" );
					}else{

						$plan = new Plan( $subscriber->plan_id, "read" ) ;

						if( !$plan || $plan->status === "trash" ){
							$message_error = sprintf("The plan ( id : %s ) does not exist anymore!", $subscriber->plan_id );
							wp_error_log( $message_error );
							$message_error = __( "The plan does not exist anymore!", $current_plugin_domain );
							Notices::setNotice( $message_error, "error" );
						}else {
							if ( !$plan->is_active() ) {
								$message_error     = __( "Member access for this plan is temporarily closed.", $current_plugin_domain );
								wp_error_log( $message_error, "Log In" );
								Notices::setNotice( $message_error, "error" );
							} else {
								if( $plan->price > 0 ){
									$last_payment = Payment::getLastPaymentBySubscriberId( $subscriber->subscriber_id, true );
									if ( !$last_payment ) {
										//Case : No Payment saved
										if( !is_https() ){
											$error_message     = __( "Impossible to use PaypalIPN to check the payment. You should add https on your site.", $current_plugin_domain );
											wp_error_log( $error_message, "Log In" );
											if( !Notices::containsNotice( $mgs_invalid_email_or_password ) )
												Notices::setNotice( $mgs_invalid_email_or_password, "error" );
										}else{
											$res_paypal_ipn = Paypal::runPayPalIPN( $subscriber->email );
											if( !$res_paypal_ipn ){
												if ( $plan->plan_type === "single" ) {
													$error_message = sprintf( __( "The account %s is not active yet. No payment found!", $current_plugin_domain ), $member_found->email );
													wp_error_log( $error_message, "Log In" );
													if( !Notices::containsNotice( $mgs_invalid_email_or_password ) )
														Notices::setNotice( $mgs_invalid_email_or_password, "error" );
													//TODO : Must think if there a risk another guy pay for an account which is not the owner
													// Best way : send an email with token
													/*$subscriber_data = array(
														'first_name'    => $subscriber->first_name,
														'last_name'     => $subscriber->last_name,
														'street_number' => $subscriber->street_number,
														'street_name'   => $subscriber->street_name,
														'zip_code'      => $subscriber->zip_code,
														'city'          => $subscriber->city,
														'country'       => Countries::getIsoByCountryId( $subscriber->country_id ),
													);

													$wp_session                           = \WP_Session::get_instance();
													$wp_session['wgs_current_subscriber'] = PlanFormsShortcode::getSubsObjectForPayment( $subscriber_data );
													//redirection to step 2 : proceed to payment
													$args     = array(
														'step'  => 2,
														'email' => $subscriber->email
													);
													$href     = wp_build_url( "wgs-form-page", $plan->plan_name, $args );
													$errors[] = sprintf( __( "Your account is not active yet. Please complete your subscription by clicking <a href=\"%s\">here</a>", $this->current_plugin_domain ), $href );*/
												} else {
													$error_message = sprintf( __( "The account %s is not active yet. No payment found!", $current_plugin_domain ), $member_found->email );
													wp_error_log( $error_message, "Log In" );
													$message_error = __( "Your account is not active yet.", $current_plugin_domain ) . __( "Please contact the subscriber to proceed to payment.", $current_plugin_domain );
													Notices::setNotice( $message_error, "error" );

												}
											}else{
												//Case : Payment just saved thanks Paypal IPN
												$res_plan_expired = $plan->checkExpirationPlan( $res_paypal_ipn['last_subscription_date'] );
												self::resultAfterExpirationPlanChecking($res_plan_expired, $member_found);
											}
										}
									}else{
										//Case : there is one last payment
										$res_plan_expired = $plan->checkExpirationPlan( $subscriber->last_subscription_date );
										self::resultAfterExpirationPlanChecking($res_plan_expired, $member_found);

									}
								}else{
									//Case : free plan
									$res_plan_expired = $plan->checkExpirationPlan( $subscriber->last_subscription_date );
									self::resultAfterExpirationPlanChecking($res_plan_expired, $member_found);
								}

							}
						}
					}
				}
			}
		}

		if( Notices::isNoErrors() && isset( $member_found ) ){
			$output['success'] = true;
			$output['data'] = $member_found->member_id; //member id
		}

		return $output;
	}

	public function activate(){
		wp_debug_log( (string) $this->member_id );
		global $wpdb;

		$data_update = array ( 'last_activation' => get_today_as_datetime() );
		$where = array ( 'member_id' => $this->member_id );

		$results = $wpdb->update( $wpdb->prefix . get_current_plugin_prefix() . "members", $data_update, $where );
		return $results;
	}

	private function set_last_connection(){
		global $wpdb;

		$data_update = array ( 'last_connection' => get_today_as_datetime() );
		$where = array ( 'member_id' => $this->member_id );

		$results = $wpdb->update( $wpdb->prefix . get_current_plugin_prefix() . "members", $data_update, $where );

		return $results;

	}

	public static function makeEmailActivation( $address_email, $subscriber ){

		$token = self::getTokenMemberByEmailAndSubsId( $address_email, $subscriber->subscriber_id );

		$url = ActivationShortcode::makeActivationUrl( $address_email, $token );

		//TODO make beautiful email with table html
		$current_plugin_domain = get_current_plugin_domain();
		$body = __( "Welcome!", $current_plugin_domain )."<br/><br/>";
		$body .= format_str_capitalize_first_letter( $subscriber->first_name ). " ";
		$body .= format_str_capitalize_first_letter( $subscriber->last_name ). " ";
		$body .= sprintf( __( " signed up for %s", $current_plugin_domain ), get_bloginfo( "name" ) ).".<br/><br/>";
		$body .= sprintf( __( "You are invited to the group : '%s', and a member account at this email address has assigned to you", $current_plugin_domain ), $subscriber->group_name ).".<br/>";
		$body .= __( "To activate your member account, please fill in the latest information of your profile", $current_plugin_domain ).".<br/><br/>";
		$body .= "<div>";
		$body .= "<!--[if mso]><v:roundrect xmlns:v=\"urn:schemas-microsoft-com:vml\" xmlns:w=\"urn:schemas-microsoft-com:office:word\"";
		$body .= sprintf("href=\"%s\"", $url );
		$body .= "style=\"height:40px;v - text - anchor:middle;width:200px;\" arcsize=\"10 % \" strokecolor=\"#1e3650\" fill=\"t\">";
		$body .= "<v:fill type=\"tile\" src=\"https://i.imgur.com/0xPEf.gif\" color=\"#556270\" />";
		$body .= "<w:anchorlock/><div style=\"text-align:center;color:#ffffff;font-family:sans-serif;font-size:13px;font-weight:bold;\">";
		$body .= __( "Activate my member account!", $current_plugin_domain );
		$body .= "</div></v:roundrect>";
		$body .= sprintf("<![endif]--><a href=\"%s\"", $url );
		$body .= "style=\"background-color:#556270;background-image:url(https://i.imgur.com/0xPEf.gif);border:1px solid #1e3650;border-radius:4px;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:13px;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:200px;-webkit-text-size-adjust:none;mso-hide:all;\">";
		$body .= __( "Activate my member account!", $current_plugin_domain );
		$body .= "</a>";
		$body .= "<br/><br/>";
		$body .= __( "Have a great day,", $current_plugin_domain );
		$body .= "<br/>";
		$body .= sprintf(__( "Team %s", $current_plugin_domain ) , get_bloginfo( "name" ) );

		return array(
			'from'    => Email::get_recipient(),
			'to'      => $address_email,
			'subject' => __( "Member account activation", $current_plugin_domain ),
			'body'    => $body
		);
	}

	/**
	 * @param int $subscriber_id
	 * @param string $email
	 * @param array $data_member
	 *
	 * @return array
	 */
	public static function updateSingleMemberBySubsId( int $subscriber_id, string $email, array $data_member ){
		wp_debug_log();
		$output = array(
			'success' => false
		);

		$errors = array();

		$current_plugin_domain = get_current_plugin_domain();
		$current_plugin_prefix = get_current_plugin_prefix();

		global $wpdb;

		$where_member = array(
			'subscriber_id' => $subscriber_id,
			'email'         => $email
		);

		$res_update = $wpdb->update( $wpdb->prefix . $current_plugin_prefix . "members", $data_member, $where_member );
		if( $res_update === false ){ //Notice : cannot be !$res_update because if can return 0 if update modify anything
			$message_error = sprintf( __( "Impossible to update the member linked to this subscriber ( id : '%s' )!", $current_plugin_domain ), $subscriber_id );
			wp_error_log( $message_error );
			$errors[] = $message_error;
		}else{
			$query     = "SELECT member_id FROM {$wpdb->prefix}" . $current_plugin_prefix . "members WHERE subscriber_id = '" . $subscriber_id . "';";
			$res_query = $wpdb->get_results( $query, ARRAY_A );
			if ( empty( $res_query ) ) {
				$message_error = sprintf( __( "Impossible to get the member linked to this subscriber ( id : '%s' )!", $current_plugin_domain ), $subscriber_id );
				wp_error_log( $message_error );
				$errors[] = $message_error;
			}else{
				$res_member_id = $res_query[0]['member_id'];
			}
		}
		if( empty( $errors ) && isset( $res_member_id ) ){
			$output['success'] = true;
			$output['data'] = $res_member_id;
		}else{
			$output['errors'] = $errors;
		}
		return $output;
	}

	/**
	 * Additional checking functions
	 */

	//TODO : check if we can switch in private
	/**
	 * @param $email
	 *
	 * @return bool
	 */
	public static function isMemberWithEmail( $email ){

		global $wpdb;

		$query = "SELECT email FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "members WHERE email = '" . $email . "';";

		$results = $wpdb->get_results( $query, ARRAY_A );

		if( count($results) === 1 ){
			return true;
		}else{
			return false;
		}

	}

	public static function isMemberByTokenAndEmail( $address_email, $token ){

		global $wpdb;

		$query = "SELECT password, start_date FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "members 
				  WHERE email = '" . $address_email . "';";

		$results = $wpdb->get_results( $query, ARRAY_A );
		if( count($results) === 1 ){
			$password = $results[0]['password'] . strtotime( $results[0]['start_date'] );
			return password_verify( $password, $token );
		}else{
			return false;
		}

	}

	public static function isAccountAlreadyActive( $address_email ){

		global $wpdb;
		$query = "SELECT last_activation FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "members 
				  WHERE email = '" . $address_email . "';";

		$results = $wpdb->get_results( $query, ARRAY_A );

		if( isset( $results[0] ) && !empty( $results[0]['last_activation'] ) ){
			return true;
		}

		return false;
	}

	public static function isLoggedIn(){
		if( empty( $_COOKIE["h4a_key" ] ) ){
			return false;
		}else{
			$current_user_data = FrontEndStorage::get_user_data();
			if( isset( $current_user_data[ get_current_plugin_domain() ] )
			    && $current_user_data[ get_current_plugin_domain() ]['status'] === "loggedIn"
			    && !empty( $current_user_data[ get_current_plugin_domain() ]['member_id'] )
			){
				return true;
			}else{
				return false;
			}
		}
	}

	public static function getMemberLoggedIn( $format = "read" ){
		wp_debug_log();
		$current_user_data = FrontEndStorage::get_user_data();
		if( isset( $current_user_data[ get_current_plugin_domain() ] )
		    && $current_user_data[ get_current_plugin_domain() ]['status'] === "loggedIn"
		    && !empty( $current_user_data[ get_current_plugin_domain() ][ 'member_id' ] )
		){
			return new Member( $current_user_data[ get_current_plugin_domain() ][ 'member_id' ], $format );
		}else{
			return false;
		}
	}

}
