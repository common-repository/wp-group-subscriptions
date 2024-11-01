<?php

namespace H4APlugin\WPGroupSubs\Common;


use H4APlugin\Core\Admin\AdminForm;
use H4APlugin\Core\Common\EditableItem;
use H4APlugin\Core\Common\Notices;
use H4APlugin\Core\Config;

use H4APlugin\Core\FrontEnd\FrontEndForm;
use H4APlugin\Core\FrontEnd\FrontEndStorage;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\get_current_plugin_prefix;
use function H4APlugin\Core\get_today_as_datetime;
use function H4APlugin\Core\is_float_as_string;
use function H4APlugin\Core\is_number;
use function H4APlugin\Core\wp_debug_log;
use function H4APlugin\Core\wp_get_error_back_end_system;
use function H4APlugin\Core\wp_error_log;
use function H4APlugin\Core\wp_get_error_system;
use function H4APlugin\Core\wp_warning_log;

class Subscriber extends EditableItem {

	use UserTrait;

	public $subscriber_id;
	public $first_name;
	public $last_name;
	public $email;
	public $phone_code;
	public $phone_number;
	public $group_name;
	public $street_name;
	public $street_number;
	public $zip_code;
	public $city;
	public $country_id;
	public $plan_id;
	public $status;
	public $plan_name;
	public $start_date;
	public $last_subscription_date;
	public $as_member;
	public $options = array();

	public function __construct( $email_or_data, $format = "list-table", $args = array() ){

		if( filter_var( $email_or_data, FILTER_VALIDATE_EMAIL  ) ){
			$this->current_plugin_domain = get_current_plugin_domain();
			$this->current_plugin_prefix = get_current_plugin_prefix();
			$this->get_item( $email_or_data );
		}else{
			parent::__construct( $email_or_data, $format, $args );
		}
		return false;
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
		wp_debug_log();
		if( isset( $args['plan_id'] ) ) // to avoid error for the initalization in Config.php
			$plan_type = Plan::getPlanTypeById( (int) $args['plan_id'] );
		$this->subscriber_id = null;
		$this->first_name    = "";
		$this->last_name     = "";
		$this->email         = "";
		$this->password      = "";
		$this->phone_code    = null;
		$this->phone_number  = "";
		if( isset( $args['plan_id'] ) ) // to avoid error for the initalization in Config.php
			$this->group_name    = ( isset( $plan_type ) && $plan_type === "multiple" ) ? "" : null;
		$this->street_name   = "";
		$this->street_number = null;
		$this->zip_code      = "";
		$this->city          = "";
		$this->country_id    = null;
		if( isset( $args['plan_id'] ) ) // to avoid error for the initalization in Config.php
			$this->plan_id       = (int) $args['plan_id'];
		$this->status        = "disabled";
		$this->start_date    = null;
		if( isset( $plan_type ) && $plan_type === "multiple" )
			$this->as_member = false;
		$this->options       = null;
	}

	protected function get_item_to_edit( $data ){
		wp_debug_log();
		if( !empty($data['subscriber_id']) && !is_number($data['subscriber_id']) ){
			$message_error = sprintf( "The subscriber id : '%s' is not a number!", $data['subscriber_id'] );
			wp_error_log( $message_error );
			exit;
		}else{
			if( !empty($data['subscriber_id']) )
				$this->subscriber_id = (int) $data['subscriber_id'];
			$this->first_name    = sanitize_text_field( $data['first_name'] );
			$this->last_name     = sanitize_text_field( $data['last_name'] );
			$this->email         = sanitize_email( $data['email'] );
			$this->password      = ( !empty( $data['password'] ) )     ? $data['password']  : null;
			$this->phone_code    = ( !empty( $data['phone_code'] ) )   ? sanitize_text_field( $data['phone_code'] )    : null;
			$this->phone_number  = ( !empty( $data['phone_number'] ) ) ? sanitize_text_field( $data['phone_number'] )  : null;
			$this->group_name    = ( isset( $data['group_name'] ) )    ? sanitize_text_field( $data['group_name'] )    : null;
			$this->street_name   = sanitize_text_field( $data['street_name'] );
			$this->street_number = ( isset( $data['street_number'] ) ) ? sanitize_text_field( $data['street_number'] ) : null;
			$this->zip_code      = sanitize_text_field( $data['zip_code'] );
			$this->city          = sanitize_text_field( $data['city'] );
			$this->country_id    = $data['country_id'];
			$this->plan_id       = $data['plan_id'];
			$this->status        = sanitize_text_field( $data['status'] );
			$this->start_date    = ( isset($data['start_date']) )     ? $data['start_date'] : null;
			$this->as_member     = ( !empty( $data['as_member'] ) )   ? $data['as_member']  : null;
			if ( is_plugin_active( 'wgs-custom-forms-addon/WGSCustomFormsAddon.php' ) ) {
				$this->options = $data['options'];
			}
		}
	}

	protected function get_item_to_list( $data ){
		$this->subscriber_id          = $data['subscriber_id'];
		$this->email                  = $data['email'];
		$this->group_name             = $data['group_name'];
		$this->first_name             = $data['first_name'];
		$this->last_name              = $data['last_name'];
		$this->plan_id                = $data['plan_id'];
		$this->plan_name              = $data['plan_name'];
		$this->start_date             = $data['start_date'];
		$this->last_subscription_date = $data['last_subscription_date'];
		$this->status                 = $data['status'];
	}

	protected function get_item( $id_or_email ){
		wp_debug_log();
		global $wpdb;
		$query_string = "";
		if( is_number( $id_or_email ) ){
			$query_string = "SELECT * FROM {$wpdb->prefix}{$this->current_plugin_prefix}subscribers WHERE subscriber_id = " . (int) $id_or_email ;
		}else if( !filter_var( $id_or_email, FILTER_VALIDATE_EMAIL  ) ){
			wp_error_log( sprintf( "Invalid argument : '%s' ! To get a member, you need a member_id or an email.", $id_or_email ) );
		}else{
			$query_string = "SELECT * FROM {$wpdb->prefix}{$this->current_plugin_prefix}subscribers WHERE email = '{$id_or_email}'";
		}
		// Return results
		$results = $wpdb->get_results( $query_string, ARRAY_A );

		if(count($results) === 0){
			wp_error_log( "Subscriber not found!");
		}else{
			foreach ( $results[0] as $column_name => $value ){
				if( is_float_as_string( $value ) ){
					$value = (float) $value;
				}else if( is_number( $value ) ){
					$value = (int) $value;
				}
				$this->$column_name = $value;
			}
			if( !empty( $this->plan_id ) ){
				$plan_type = Plan::getPlanTypeById(  $this->plan_id );
				if( $plan_type === "multiple" ){
					$this->as_member = Member::isMemberWithEmail( $this->email );
				}
			}

			/*if ( is_plugin_active( 'wgs-custom-forms-addon/WGSCustomFormsAddon.php' ) ) {
				$data_options = apply_filters( "wgs_get_only_options_not_blank", $results[0] );
				$this->options =  ( !empty( $data_options ) ) ? $data_options : null;
			}*/
		}
	}

	/**
	 *
	 */
	protected function get_editable_item_form() {
		wp_debug_log( "plan_id : " . $this->plan_id );
		if( !empty( $this->plan_id ) && $this->plan_id !== 0 ){
			$form_type_id = Plan::getFormTypeIdByPlanId( $this->plan_id );
			wp_debug_log( "form_type_id : " . $form_type_id );
			if( is_admin() ){
				$this->form = new AdminForm( $form_type_id, "plan-subscription", true );
			}else{
				$this->form = new FrontEndForm( $form_type_id, "plan-subscription", true );
			}
			$this->form->options['action_wpnonce'] = $this->nonce;
			wp_debug_log( $this->form->office );
		}
	}

	/**
	 * CRUD functions
	 */

	/**
	 * @param bool $addSingleMember
	 * @param null $password
	 *
	 * @return array
	 */
	public function save( $addSingleMember = false, $password = null ){
		wp_debug_log();
		$output = array(
			'success' => false
		);

		global $wpdb;

		if( empty( $this->email ) ){
			wp_error_log( "Unfound email to save the subscriber!" );
			Notices::setNotice( wp_get_error_back_end_system(), "error" );
		}
		else if( !empty( $this->subscriber_id ) ){
			wp_error_log( "This subscriber has got an id ! Please update it instead of save it." );
			$message_error = sprintf( __( "The email '%s' already exists in subscribers!" ), $this->email );
			Notices::setNotice( $message_error, "error" );
		}else if( self::getSubscriberIdByEmail( $this->email ) !== false || Member::isMemberWithEmail( $this->email ) ){
			if( self::getSubscriberIdByEmail( $this->email ) !== false ){
				$message_error  = sprintf( _x( "%s '%s' already exists!", "editable_item", $this->current_plugin_domain ), __( "The " . $this->params->ref, "editable_item", $this->current_plugin_domain ), $this->email );
				Notices::setNotice( $message_error, "error" );
			}
			if( Member::isMemberWithEmail( $this->email ) ){
				$message_error = sprintf( __( "A member with this email '%s' already exists!" ), $this->email );
				Notices::setNotice( $message_error, "error" );
			}
		}else{

			$encoded_password = $this->encodePassword();

			$data = array(
				'first_name' => $this->first_name,
				'last_name' => $this->last_name,
				'email' => $this->email,
				'password' => $encoded_password,
				'phone_code' => $this->phone_code,
				'phone_number' => $this->phone_number,
				'group_name' => $this->group_name,
				'street_name' => $this->street_name,
				'street_number' => $this->street_number,
				'zip_code' => $this->zip_code,
				'city' => $this->city,
				'country_id' => $this->country_id,
				'plan_id' => $this->plan_id,
				'status' => $this->status,
				'start_date' => get_today_as_datetime()
			);
			if ( !empty( $this->options ) && is_plugin_active( 'wgs-custom-forms-addon/WGSCustomFormsAddon.php' ) ) {
				$data = apply_filters( "wgs_format_more_save_data", $data, $this->options );
			}
			$res_ins = $wpdb->insert( $wpdb->prefix . $this->current_plugin_prefix . "subscribers", $data );
			if( !$res_ins ){
				wp_error_log( sprintf( "Impossible to save the subscriber with this email '%s'.", $this->email ) );
				Notices::setNotice( wp_get_error_back_end_system(), "error" );
			}else{
				$output['success'] = true;
				$subscriber_id =  $wpdb->insert_id;
				$output['data'] = array( 'subscriber_id' => $subscriber_id );
				if( $addSingleMember && is_int( $subscriber_id ) && !empty( $password ) ){
					$member_save_res = $this->addSingleMember( $encoded_password, $subscriber_id );
					if ( Notices::isNoErrors() ){
						$member_id = $member_save_res['data'];
						$output['data']['member_id'] = $member_id;
					}
				}
			}
		}
		return $output;

	}

	public function update( $addSingleMember = false, $password = null ){

		wp_debug_log();
		$output = array(
			'success' => false
		);

		if( empty( $this->subscriber_id ) ){
			wp_error_log( sprintf( "This %s has not got an id ! Please save it instead of update it.", $this->params->name ) );
			$message_error  = sprintf( __( "%s ( id : '%s' ) does not already exist to update it!", "editable_item", $this->current_plugin_domain ), __( "The " . $this->params->name, "editable_item", $this->current_plugin_domain ), $this->subscriber_id );
			Notices::setNotice( $message_error, "error" );
		}else{
			global $wpdb;

			$query_first = "SELECT plan_id, status, email FROM {$wpdb->prefix}". $this->current_plugin_prefix ."subscribers WHERE subscriber_id = " .$this->subscriber_id. ";";

			$res_query_first = $wpdb->get_results( $query_first, ARRAY_A );

			if( count( $res_query_first ) !== 1 ){
				wp_error_log( sprintf( "Impossible to get the plan_id for the subscriber with this id '%s' ! ", $this->subscriber_id ) );
				Notices::setNotice( wp_get_error_back_end_system(), "error" );
			}else{
				$old_plan_id = (int) $res_query_first[0]['plan_id'];
				$old_plan_type = Plan::getPlanTypeById( $old_plan_id );
				$new_plan_type = Plan::getPlanTypeById( $this->plan_id );
				$old_status = $res_query_first[0]['status'];
				$old_email = $res_query_first[0]['email'];
				//New email checking
				if ( $old_email !== $this->email ) {
					$res_check_email = $this->checkEmailAlreadyExists( $this->email );
					if( !$res_check_email['success'] ){
						Notices::setNotices( $res_check_email['errors'], "error", true );
					}
				}
				//Password
				$encoded_password = ( !empty( $this->password ) ) ? $this->encodePassword() : $this->getEncodedPassword();
				if( Notices::isNoErrors() ){
					if( $old_plan_id === $this->plan_id ){ //Case : the plan did not change
						//wp_debug_log( 'plan not changed' );
						if( $old_plan_type === "single" ){
							//Update member
							/*$res_update_member = */$this->updateSingleMember( $old_email, $encoded_password );
						}else{ //Multiple plan
							$is_member = Member::isMemberWithEmail( $old_email );
							if( $addSingleMember ){
								if( $is_member ){
									$member_res = $this->updateSingleMember( $old_email, $encoded_password );
								}else{
									$member_res = $this->addSingleMember( $encoded_password, $this->subscriber_id );
								}
							}else{
								if( $is_member )
									$this->deleteSingleMember();
							}
							$this->activateMultiplePlanMembers();
						}
						if( Notices::isNoErrors() ){
							$res_update_subs = $this->updateSubscriber( false, $old_status, $encoded_password );
							if( $res_update_subs['success'] ){
								$output['success'] = true;
								$output['data']['subscriber_id'] = $res_update_subs['data']; //subscriber_id
								if( !empty( $member_res ) ){
									$member_id = $member_res['data'];
									$output['data']['member_id'] = $member_id;
								}
							}
						}
					}else{ //Case : the plan changed
						wp_debug_log( 'plan changed' );
						if( $old_plan_type === "single" ){
							if( $new_plan_type === "single" ){ //Case 1 : single plan to single plan
								//Update member
								$res_update_member = $this->updateSingleMember( $old_email, $encoded_password );
								if( $res_update_member['success'] ){
									/*$res_member_id = */$res_update_member['data'];
								}
							}else{ //Case 2 : single plan to multiple plan
								//Delete member
								$this->deleteAllMembersBySubsId( $new_plan_type );
								if( $addSingleMember ){
									$member_res = $this->addSingleMember( $encoded_password, $this->subscriber_id );
								}
							}

						}else{
							if( $new_plan_type === "multiple" ){ //Case 3 : multiple plan to multiple
								if( $addSingleMember )
									$member_res = $this->addSingleMember( $encoded_password, $this->subscriber_id );
								$this->activateMultiplePlanMembers();
							}else{ //Case 4 : multiple plan to single plan
								//Delete all members
								$this->deleteAllMembersBySubsId( "multiple" );
								$member_res = $this->addSingleMember( $encoded_password, $this->subscriber_id );
								if( Notices::isNoErrors() ){
									if( !$addSingleMember || empty( $encoded_password ) ){
										$message_error = 'Normally for a single plan, a single member with a password must be created !';
										wp_error_log( $message_error );
										Notices::setNotice(  wp_get_error_back_end_system(), "error", true );
									}
								}
							}
						}
						//Update subscriber
						$res_update_subs = $this->updateSubscriber( true, $old_status, $encoded_password );
						if( Notices::isNoErrors() ){
							$output['success'] = true;
							$res_subscriber_id = $res_update_subs['data'];
							if( isset( $member_res ) ){
								$res_member_id = $member_res['data'];
								$data =  array( 'subscriber_id' => $res_subscriber_id, 'member_id' => $res_member_id );
							}else{
								$data = $res_subscriber_id;
							}
							$output['data'] = $data;
						}
					}
				}
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
		$full_name = $this->first_name . " " . $this->last_name;
		$error_message = sprintf( __( "Impossible to restore the %s '%s' from the trash!", $this->current_plugin_domain ), $this->params->name, $full_name );
		$res_update_untrash = $this->updateStatus( "disabled", $error_message );
		return $res_update_untrash;
	}

	public function delete(){
		$output = array(
			'success' => false
		);

		if( empty( $this->subscriber_id ) ){
			$message_error  = sprintf( __( "The subscriber with the id '%s' does not exist!" ), $this->subscriber_id );
			wp_error_log( $message_error );
			Notices::setNotice( $message_error, "error", true );
		}else{
			global $wpdb;

			$plan_type = Plan::getPlanTypeById( $this->plan_id );
			$res_del_members = $this->deleteAllMembersBySubsId( $plan_type );
			if( !$res_del_members['success'] ){
				Notices::setNotices( $res_del_members['errors'], "error", true );
			}else{
				$res_query = $wpdb->delete( $wpdb->prefix. $this->current_plugin_prefix ."subscribers" ,array( 'subscriber_id' => $this->subscriber_id ) );
				if( $res_query === false || $res_query === 0 ){
					$message_error  = sprintf( __( "Impossible to delete the subscriber ( id : '%s' )!" ), $this->subscriber_id );
					wp_error_log( $message_error );
					Notices::setNotice( $message_error, "error", true );
				}else{
					self::unassignPayments( $this->subscriber_id );
					$full_name = $this->first_name . " " . $this->last_name;
					$message_success = sprintf( __( "%s '%s' has been deleted.", $this->current_plugin_domain ), _x( "The subscriber", "message_item_name", $this->current_plugin_domain ), $full_name );
					Notices::setNotice( $message_success, "success", true );
					$output['success'] = true;
					$output['data'] = $this->subscriber_id;
				}
			}
		}
		return $output;
	}

	/**
	 * Additional Getters
	 */

	/**
	 * @param $email
	 *
	 * @return bool
	 */
	public static function getSubscriberIdByEmail( $email ){

		global $wpdb;

		$query = "SELECT subscriber_id FROM {$wpdb->prefix}". get_current_plugin_prefix() ."subscribers WHERE email = '".$email."';";

		$results = $wpdb->get_results( $query, ARRAY_A );

		if( count($results) === 1 ){
			return $results[0]['subscriber_id'];
		}else{
			return false;
		}

	}

	private function getEncodedPassword() {
		global $wpdb;

		$query = "SELECT password FROM {$wpdb->prefix}{$this->current_plugin_prefix}subscribers WHERE subscriber_id={$this->subscriber_id};";

		$results = $wpdb->get_results( $query, ARRAY_A );

		if( count($results) === 1 ){
			return $results[0]['password'];
		}else{
			return null;
		}
	}

	public static function getAllMembersById( $subs_id ){

		global $wpdb;

		$query = "SELECT * FROM {$wpdb->prefix}" . get_current_plugin_prefix() ."members WHERE subscriber_id = ".$subs_id.";";

		$res_query = $wpdb->get_results( $query, ARRAY_A );

		$a_members = array();
		if( count( $res_query ) !== 0 ){
			foreach ( $res_query as $result ){
				$a_members[] = new Member( $result );
			}
		}
		return $a_members;

	}

	public static function getSubscriberByTxnId( $txn_id ){

		global $wpdb;
		$current_plugin_prefix = get_current_plugin_prefix();
		$query = "SELECT *
				  FROM {$wpdb->prefix}{$current_plugin_prefix}subscribers as s1,
				  {$wpdb->prefix}{$current_plugin_prefix}payments as p2
				  WHERE s1.subscriber_id = p2.subscriber_id
				  AND s1.status = 'active'
				  AND p2.txn_id = '".$txn_id."';";
		$result = $wpdb->get_results( $query, ARRAY_A );
		if( count( $result ) === 1  ){
			return new Subscriber( $result[0] );
		}else if( count( $result ) === 0 ){
			return false;
		}else{
			wp_error_log( "Error System : Several Subscribers for a same transaction ID");
			return null;
		}
	}

	public static function getAllGroup(){
		global $wpdb;

		$query = "SELECT s.subscriber_id, s.group_name, p.members_max 
				FROM {$wpdb->prefix}" .  get_current_plugin_prefix() . "subscribers as s,
				{$wpdb->prefix}" .  get_current_plugin_prefix() . "plans as p
				WHERE s.status IN ( 'active', 'disabled' )
				AND s.group_name IS NOT NULL
				AND p.plan_id = s.plan_id";
		$result = $wpdb->get_results( $query, ARRAY_A );

		if( count( $result ) === 0 ){
			return false;
		}else{
			return $result;
		}
	}

	/**
	 * @param int $subscriber_id
	 *
	 * @return bool|Plan
	 */
	public static function getPlanBySubscriberId( int $subscriber_id ){

		global $wpdb;

		$query = "SELECT p.plan_id, 
						p.user_id, 
						p.plan_name, 
						p.post_id, 
						p.plan_type, 
						p.members_max, 
						p.members_min,
						p.start_date,
						p.duration_type,
						p.expiration_date,
						p.status,
						p.price,
						p.publish_date";
		$query .= " FROM {$wpdb->prefix}" .  get_current_plugin_prefix() . "plans as p,";
		$query .= "	{$wpdb->prefix}" .  get_current_plugin_prefix() . "subscribers as s";
		$query .= " WHERE s.subscriber_id = " . $subscriber_id;
		$query .= " AND s.plan_id = p.plan_id ";
		$result = $wpdb->get_results( $query, ARRAY_A );
		if( count( $result ) === 0 ){
			return false;
		}else{
			return new Plan( $result[0], "read" );
		}
	}


	/**
	 * Additional CRUD functions
	 */

	/**
	 * @param $where_email
	 * @param null $encoded_password
	 *
	 * @return array
	 */
	private function updateSingleMember( $where_email, $encoded_password = null ) {
		wp_debug_log();
		$output = array(
			'success' => false
		);

		$data_member = array(
			'email'      => $this->email,
			'first_name' => $this->first_name,
			'last_name'  => $this->last_name
		);

		if( !empty( $encoded_password ) )
			$data_member['password'] = $encoded_password;

		if( $this->status === "active" ){
			$data_member['last_activation'] = get_today_as_datetime();
		}

		$res_update_member = Member::updateSingleMemberBySubsId( $this->subscriber_id, $where_email, $data_member );
		if ( !$res_update_member['success'] ) {
			Notices::setNotices( $res_update_member['errors'], "error", true );
		}else {
			$res_member_id = $res_update_member['data'];
			$output['success'] = true;
			$output['data'] = array( 'member_id' => $res_member_id );
		}
		return $output;
	}

	private function updateSubscriber( $plan_changed, $old_status, $encoded_password = null ){
		wp_debug_log();
		$output = array(
			'success' => false
		);

		//Update subscriber

		if ( is_plugin_active( 'wgs-custom-forms-addon/WGSCustomFormsAddon.php' ) ) {
			//Reset options
			$res_reset_options = apply_filters( "wgs_reset_subscriber_options", $this->subscriber_id );
			if ( ! $res_reset_options['success'] ) {
				wp_error_log( "The function 'wgs_reset_subscriber_options' failed!" );
				Notices::setNotices( $res_reset_options['errors'], "error", true );
			}
		}
		if( Notices::isNoErrors() ){
			$data = array(
				'first_name'    => $this->first_name,
				'last_name'     => $this->last_name,
				'email'         => $this->email,
				'phone_code'    => $this->phone_code,
				'phone_number'  => $this->phone_number,
				'group_name'    => $this->group_name,
				'street_name'   => $this->street_name,
				'street_number' => $this->street_number,
				'zip_code'      => $this->zip_code,
				'city'          => $this->city,
				'country_id'    => $this->country_id,
				'plan_id'       => $this->plan_id
			);
			if( !empty( $encoded_password ) )
				$data['password'] = $encoded_password;

			if( $this->status === "active" ){
				$this->enableSubscriber();
			}else if( $old_status !== $this->status && !$plan_changed ){ //and new status = "disabled"
				$data['status'] = $this->status; // MUST BE "disabled"
			}
			if ( !empty( $this->options ) && is_plugin_active( 'wgs-custom-forms-addon/WGSCustomFormsAddon.php' ) ) {
				$data = apply_filters( "wgs_format_more_save_data", $data, $this->options );
			}
			$res_update_subscriber = $this->update_item( "subscribers", $data, $this->subscriber_id );
			if( !$res_update_subscriber['success'] ){
				Notices::setNotices( $res_update_subscriber['errors'], "error", true );
			}else{
				$subscriber_id = $res_update_subscriber['data'];
				$output['success'] = true;
				$output['data'] = $subscriber_id;
			}

		}
		return $output;
	}

	public function enableSubscriber(){
		wp_debug_log();
		global $wpdb;
		$args = Config::get_item_by_ref( "plan" );
		$plan = new Plan( $this->plan_id, "read", $args );
		$data_update = null;
		if( $plan->price === 0.00 ){
			$res_plan = $plan->checkExpirationPlan( $this->last_subscription_date );
			if( !$res_plan['success'] ){
				Notices::setNotices( $res_plan['errors'], "error", true );
			}else{
				if( $res_plan['data'] ){
					$data_update = array (
						'status' => "disabled"
					);
					$warning_message     = __( "Impossible to enable the subscriber because the last subscription date is too old - plan has expired.", $this->current_plugin_domain );
					wp_warning_log( $warning_message );
					Notices::setNotice( $warning_message, "warning" );
				}else{
					$data_update = array (
						'status' => "active",
						'last_subscription_date' => get_today_as_datetime()
					);
				}
			}
		}else{
			$last_payment = Payment::getLastPaymentBySubscriberId( $this->subscriber_id, true );
			if( empty( $last_payment->payment_date ) ){
				$error_message     = __( "You cannot enable a Subscriber if there is no completed payment for a paid plan.", $this->current_plugin_domain );
				Notices::setNotice( $error_message, "error", true );
				return $output['success'] = false;
			}else{
				$res_plan = $plan->checkExpirationPlan( $last_payment->payment_date );
				if( !$res_plan['success'] ){
					Notices::setNotices( $res_plan['errors'], "error", true );
				}else{
					if( $res_plan['data'] ){
						$data_update = array (
							'status' => "disabled"
						);
						$warning_message     = __( "Impossible to enable the subscriber because the last payment is too old - plan has expired.", $this->current_plugin_domain );
						wp_warning_log( $warning_message );
						Notices::setNotice( $warning_message, "warning", true );
					}else{
						$data_update = array (
							'status' => "active",
							'last_subscription_date' => $last_payment->payment_date
						);
					}
				}
			}
		}

		$where = array ( 'subscriber_id' => $this->subscriber_id );

		$output = $wpdb->update( $wpdb->prefix. $this->current_plugin_prefix ."subscribers", $data_update, $where );

		return $output;
	}

	public function addSingleMember( $encoded_password, $subscriber_id ){
		wp_debug_log();
		if( empty( $this->plan_id ) ){
			$message_error = __( "Impossible to add a single member without plan id.", $this->current_plugin_domain );
			wp_error_log( $message_error );
			Notices::setNotice( wp_get_error_system(), "error" );
			return false;
		}else{
			$plan_type = Plan::getPlanTypeById( $this->plan_id );
			if( $plan_type === "multiple" ){
				$plan    = new Plan( $this->plan_id, "read" );
				$members = Subscriber::getAllMembersById( $subscriber_id );
				if( $plan->members_max > count( $members )  ){
					$can_save = true;
				}else{
					$can_save = false;
				}
			}else{
				$can_save = true;
			}
			if( $can_save ){
				$data_member = array(
					'first_name'    => $this->first_name,
					'last_name'     => $this->last_name,
					'email'         => $this->email,
					'password'      => $encoded_password,
					'subscriber_id' => $subscriber_id,
					'status'        => "published"
				);
				$member_args = Config::get_item_by_ref( "member" );
				$member          = new Member( $data_member, "edit", $member_args );
				$member_save_res = $member->save( false, false );
				if ( $member_save_res['success'] ) {
					$saved_member = new Member( $member_save_res['data'] );
					$saved_member->activate(); //activate means with all information, form completed
				}
				return $member_save_res;
			}
			return false;
		}
	}

	private function activateMultiplePlanMembers() {
		wp_debug_log();
		if ( $this->status === "active" ) {
			$members = self::getAllMembersById( $this->subscriber_id );
			if ( count( $members ) > 0 ) {
				foreach ( $members as $member ) {
					if ( ! empty( $member->last_name ) && ! empty( $member->first_name ) ) {
						$member->activate();
					}
				}
			}
		}
	}

	private function deleteSingleMember(){
		$output = array(
			'success' => false
		);
		$members = self::getAllMembersById( $this->subscriber_id );
		if ( count( $members ) > 0 ) {
			foreach ( $members as $member ) {
				if ( ! empty( $member->last_name ) && ! empty( $member->first_name ) ) {
					if( $member->email === $this->email ){
						$res_delete_member = $member->delete();
						if ( Notices::isNoErrors() ){
							$res_member_id = $res_delete_member['data'];
							$output['success'] = true;
							$output['data'] = $res_member_id;
						}
					}
				}
			}
		}
		return $output;
	}

	private function deleteAllMembersBySubsId( $plan_type ){
		wp_debug_log();
		$output = array(
			'success' => false
		);

		$members = self::getAllMembersById( $this->subscriber_id );
		if( $plan_type === "single" ){
			if( count( $members ) > 1 ){
				$error_message = __( 'The members array must contain only one member for a single plan', $this->current_plugin_domain );
				wp_error_log( $error_message );
				Notices::setNotice( $error_message, "error" );
			}else if( count( $members ) === 0  ){
				$error_massage = __( "The single member not found!", $this->current_plugin_domain );
				wp_error_log( $error_massage );
				Notices::setNotice( wp_get_error_back_end_system(), "error" );
			}
		}
		if ( count( $members ) > 0 ) {
			foreach ( $members as $member ) {
				$res_del_member = $member->delete();
				if ( ! $res_del_member['success'] ) {
					Notices::setNotices( $res_del_member['errors'], "error", true );
				}
			}
		}
		if( Notices::isNoErrors() ){
			$output['success'] = true;
			$output['data'] = $this->subscriber_id;
		}
		return $output;
	}

	/**
	 * Additional checking functions
	 */

	/**
	 * @param $txn_id
	 *
	 * @return bool|null
	 */
	public static function isActiveSubscriberByTxnId( $txn_id ){

		global $wpdb;
		$current_plugin_prefix = get_current_plugin_prefix();
		$query = "SELECT COUNT(*) 
				  FROM {$wpdb->prefix}" . $current_plugin_prefix ."subscribers as s1,
				  {$wpdb->prefix}" . $current_plugin_prefix ."payments as p2
				  WHERE s1.subscriber_id = p2.subscriber_id
				  AND s1.status = 'active'
				  AND p2.txn_id = '".$txn_id."';";
		$result = $wpdb->get_results( $query );
		if( (int) $result === 1 ){
			return true;
		}else if( (int) $result === 0){
			return false;
		}else{
			wp_error_log( "Error System : Several Subrecibers for a same transaction ID");
		}
		return null;
	}

	private static function checkEmailAlreadyExists( $email ){
		$output = array(
			'success' => false
		);

		$errors = array();
		global $wpdb;

		//Check if a susbcriber got this new email
		$current_plugin_prefix = get_current_plugin_prefix();
		$query_first     = "SELECT email FROM {$wpdb->prefix}" . $current_plugin_prefix . "subscribers WHERE email = '" . $email . "';";
		$res_query_first = $wpdb->get_results( $query_first, ARRAY_A );
		if ( ! empty( $res_query_first ) ) {
			$message_error = sprintf( __( "Another subscriber got this email '%s'!" ), $email );
			wp_error_log( $message_error );
			$errors[] = $message_error;
		}
		//Check if a member got this new email
		$query_second     = "SELECT email FROM {$wpdb->prefix}" . $current_plugin_prefix . "members WHERE email = '" . $email . "';";
		$res_query_second = $wpdb->get_results( $query_second, ARRAY_A );
		if ( ! empty( $res_query_second ) ) {
			$message_error = sprintf( __( "Another member got this email '%s'!" ), $email );
			wp_error_log( $message_error );
			$errors[] = $message_error;
		}

		if( empty( $errors ) ){
			$output['success'] = true;
			$output['data'] = $email;
		}else{
			$output['errors'] = $errors;
		}

		return $output;
	}

	private static function isSubscriberWithEmail( $email ){
		global $wpdb;

		//Check if a susbcriber got this new email
		$current_plugin_prefix = get_current_plugin_prefix();
		$query_first = "SELECT email FROM {$wpdb->prefix}{$current_plugin_prefix}subscribers WHERE email = '{$email}';";
		$res_email = $wpdb->get_results( $query_first, ARRAY_A );
		if( empty( $res_email ) || !$res_email ){
			return false;
		}else{
			return true;
		}
	}

	public function is_active(){
		return $this->status === "active";
	}

	public static function isLoggedIn(){
		if( empty( $_COOKIE["h4a_key" ] ) ){
			return false;
		}else{
			$current_user_data = FrontEndStorage::get_user_data();
			if( isset( $current_user_data[ get_current_plugin_domain() ] )
			    && $current_user_data[ get_current_plugin_domain() ]['status'] === "loggedIn"
			    && !empty( $current_user_data[ get_current_plugin_domain() ]['subscriber_id'] )
			){
				return true;
			}else{
				return false;
			}
		}
	}

	public static function logIn( $email, $password ){
		wp_debug_log();
		$output = array(
			'success' => false
		);

		$current_plugin_domain = get_current_plugin_domain();

		$is_email =  self::isSubscriberWithEmail( $email );

		$mgs_invalid_email_or_password = __("Invalid Email or Password!", $current_plugin_domain );
		if( !$is_email ){
			if( !Member::isLoggedIn() )
				Member::logIn( $email, $password );
		}else{
			$subscriber_found = new Subscriber( $email );
			if( !in_array( $subscriber_found->status, array( "active", "disabled" ) ) ){
				$error_message = sprintf("Subscriber ( %s ) status : 'trash'", $subscriber_found->email );
				wp_error_log( $error_message, "Log In" );
				if( !Notices::containsNotice( $mgs_invalid_email_or_password ) )
					Notices::setNotice( $mgs_invalid_email_or_password, "error" );
			}else{
				if( !password_verify ( $password , $subscriber_found->password ) ){
					$message_error = sprintf( "Invalid Password" );
					wp_error_log( $message_error, "Log In" );
					if( !Notices::containsNotice( $mgs_invalid_email_or_password ) )
						Notices::setNotice( $mgs_invalid_email_or_password, "error" );
				}else{
					$user_data = array(
						$current_plugin_domain => array(
							'subscriber_id' =>  $subscriber_found->subscriber_id,
							'status'    => "loggedIn"
						)
					);
					if(  Member::isLoggedIn() ){
						$member_linked = Member::getMemberLoggedIn();
						$user_data[ $current_plugin_domain ]['member_id'] =	$member_linked->member_id;
					}
					FrontEndStorage::set_user_data( $user_data, null, 12 * HOUR_IN_SECONDS );
					$output['success'] = true;
					$output['data'] = $subscriber_found->subscriber_id; //subscriber id
				}
			}
		}
		return $output;
	}

	public static function getSubscriberLoggedIn( $format = "read" ){
		wp_debug_log();
		$current_user_data = FrontEndStorage::get_user_data();
		if( isset( $current_user_data[ get_current_plugin_domain() ] )
		    && $current_user_data[ get_current_plugin_domain() ]['status'] === "loggedIn"
		    && $current_user_data[ get_current_plugin_domain() ][ 'subscriber_id' ]
		){
			$subscriber = new Subscriber( $current_user_data[ get_current_plugin_domain() ][ 'subscriber_id' ], $format );
			if( !isset( $subscriber->subscriber_id ) ){
				return false;
			}else{
				return $subscriber;
			}
		}else{
			return false;
		}
	}

	public static function unassignPayments( int $subscriber_id ){
		global $wpdb;
		$data_update = array (
			'subscriber_id' => null
		);
		$where = array ( 'subscriber_id' => $subscriber_id );
		$output = $wpdb->update( $wpdb->prefix . get_current_plugin_prefix() ."payments", $data_update, $where );
		return $output;
	}

	public static function assignPayments( $subscriber_id, $payment_ids = array() ){

		if( empty( $payment_ids ) )
			return false;

		global $wpdb;
		$data_update = array (
			'subscriber_id' => $subscriber_id
		);
		foreach( $payment_ids as $payment_id ){
			$where = array (
				'payment_id' => (int) $payment_id
			);
			$output = $wpdb->update( $wpdb->prefix . get_current_plugin_prefix() ."payments", $data_update, $where );
			if( !$output ){
				return false;
			}
		}
		return true;
	}
}