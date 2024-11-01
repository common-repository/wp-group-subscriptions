<?php

namespace H4APlugin\WPGroupSubs\Admin\Members;

use function H4APlugin\Core\addHTMLinDOMDocument;
use H4APlugin\Core\Admin\EditItemTemplate;
use H4APlugin\Core\Common\CommonForm;
use H4APlugin\Core\Common\Notices;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\return_datetime;
use function H4APlugin\Core\wp_admin_build_url;
use function H4APlugin\Core\wp_debug_log;
use function H4APlugin\Core\wp_error_log;
use function H4APlugin\Core\wp_get_error_back_end_system;
use H4APlugin\WPGroupSubs\Common\Member;
use H4APlugin\WPGroupSubs\Common\Plan;
use H4APlugin\WPGroupSubs\Common\Subscriber;

class Edit_Member extends EditItemTemplate {

	private $page_step;

	public function __construct( $data ) {
		$this->current_plugin_domain = get_current_plugin_domain();
		parent::__construct( $data );
	}

	public function init_template_content() {
		wp_debug_log();
		if( isset( $_GET['action'] ) && $_GET['action'] === "trash"
		    || ( isset( $_POST ) && ( array_key_exists("save", $_POST ) || array_key_exists("publish", $_POST ) ) ) ){
			if ( isset( $_POST ) && ( array_key_exists("save", $_POST ) || array_key_exists("publish", $_POST ) ) ){
				wp_debug_log( "Save or Update" );
				$res_item = $this->saveOrUpdateItem();
				$args_action = array(
					'action' => "edit"
				);
				$args_action = $this->set_url_args( $args_action, $res_item );
				wp_redirect( wp_admin_build_url( "edit-member", false, $args_action ) );
				exit;
			}else if( $_GET['action'] === "trash" ){
				$res_member = $this->trashItem();
				if( !$res_member['success'] ){
					$args_action = array(
						'action' => "edit",
						'subs' => $_GET['subs'],
						'mbr' => $_GET['mbr']
					);
					wp_redirect( wp_admin_build_url( "edit-member", false, $args_action ) );
				}else{
					wp_redirect( wp_admin_build_url( "members" ) );
					exit();
				}
			}
		}else{
			if( isset( $_GET['action'] ) && $_GET['action'] === "edit" && !empty( $_GET['mbr'] ) ){  // Show member
				$this->setEditableItem( "edit" );
				$this->page_step = 2;
				if( $this->editable_item->status === "trash" ) { // Lock if the member is trashed
					$message_info = $message_success = sprintf( __( "%s '%s' has been trashed.", $this->current_plugin_domain ),
						_x( "The " . $this->editable_item->params->name, "message_item_name", $this->current_plugin_domain ),
						$this->editable_item->first_name . " " . $this->editable_item->last_name
					);
					wp_die( $message_info );
					exit;
				}
			}else if( !empty( $_GET['subs'] ) ){ // Show blank item
				wp_debug_log("step 2");
				$this->setBlankEditableItem();
				$this->page_step = 2;
			}else{
				wp_debug_log("step 1");
				$this->page_step = 1;
			}
		}
	}

	/*
	 * Override to switch between step 1 and edit form view
	 *
	 */
	public function write( &$htmlTmpl ){
		$html = "";
		if( $this->page_step === 1 ){
			wp_debug_log( "step 1" );

			ob_start();

			//HTML Template
			include_once "views/view-member-step-1.php";

			// Get the contents and clean the buffer
			$html .= ob_get_contents();
			ob_end_clean();
		}else{
			wp_debug_log( "step 2" );
			$this->initEditableForm();

			//HTML Template
			$html .= $this->editable_item->form->writeForm( null, false );
		}
		addHTMLinDOMDocument($htmlTmpl, $html, "form" );
	}

	protected function set_url_args( $arg_actions, $res = array() ){
		if( $res['success'] ){
			$arg_actions['mbr'] = $res['data'] ;
		}else if( !empty( $_GET[ $this->editable_item->params->slug ] ) ){ //Case : updating failed
			$arg_actions['mbr'] = (int) $_GET['mbr'];
		}
		$arg_actions[ 'subs' ] = (int) $_GET['subs'];
		return $arg_actions;
	}

	protected function saveOrUpdateItem(){
		wp_debug_log();
		$output = array(
			'success' => false
		);
		$data = null;
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $this->editable_item->nonce ) ) {
			wp_error_log( "_wpnonce did not verify!" );
			wp_error_log( "POST['_wpnonce'] : " . $_POST['_wpnonce'] );
			wp_error_log( "this->nonce : " . $this->editable_item->nonce );
			$message_error = __( "Sorry, your nonce did not verify.", $this->current_plugin_domain );
			Notices::setNotice( $message_error, "error", true );
		}else if( !isset( $_POST['publish'] ) && !isset( $_POST['save'] ) ){
			wp_error_log( "The key 'publish' or 'save' is not in the global POST!" );
			$message_error = wp_get_error_back_end_system();
			Notices::setNotice( $message_error, "error", true );
		}else{
			if( !empty( $_GET[ $this->editable_item->params->slug ] ) ){
				$init_format = "update";
				$isUpdate = true;
			}else{
				$init_format = "save";
				$isUpdate = false;
			}
			$plan = Subscriber::getPlanBySubscriberId( (int) $_GET['subs'] );
			$res_check = $this->checkMemberFormData( $_POST, $plan->plan_type, $isUpdate );
			if( !$res_check['success'] ){
				wp_error_log("Data Form checking show errors!" );
				Notices::setNotices( $res_check['errors'], "error", true );
			}else{
				if( $isUpdate )
					$res_check['data']['member_id'] = (int) $_GET[ $this->editable_item->params->slug ];
				$args = $this->get_mandatory_args();
				$this->editable_item = new Member( $res_check['data'], "edit", $args );
				$res_member = call_user_func_array( array( $this->editable_item, $init_format ), array( false ) );
				if ( !$res_member['success'] ) {
					$message_error = ( $isUpdate ) ? __( "Updating failed!", $this->current_plugin_domain ) : __( "Saving failed!", $this->current_plugin_domain );
					wp_error_log( $message_error );
					Notices::setNotice( $message_error, "error", true );
				}else{
					$output['success'] = true;
					$output['data']    = $res_member['data'] ; // member_id  or array( member_id, subscriber_id )
					if( $init_format === "save" ){
						$this->editable_item = new Member( $output['data'], "edit", $args );
						$this->editable_item->activate();
						$success_message = __( "Successfully saved!", $this->current_plugin_domain );
						Notices::setNotice( $success_message, "success", true );
					}else{
						$success_message = __( "Successfully updated!", $this->current_plugin_domain );
						Notices::setNotice( $success_message, "success", true );
					}
				}

			}

		}
		return $output ;
	}

	/**
	 * @param array $data
	 * @param string $plan_type
	 * @param bool $isUpdate
	 *
	 * @return mixed
	 */
	private function checkMemberFormData( array $data, string $plan_type, $isUpdate ) {
		$current_plugin_domain = get_current_plugin_domain();

		$check_data = array(
			0 => array(
				'function' => "checkName",
				'data'     => array(
					$data['wgs_f_first_name'],
					__( 'first name', $current_plugin_domain )
				)
			),
			1 => array(
				'function' => "checkName",
				'data'     => array(
					$data['wgs_f_last_name'],
					__( 'last name', $current_plugin_domain )
				)
			)
		);
		if( $plan_type === "multiple" ){
			if( $isUpdate ){
				$isEmailChanged = $this->checkIfEmailChanged( $data['wgs_f_email'] );
				$checkUniqueEmail = $isEmailChanged;
			}else{
				$checkUniqueEmail = false;
			}
			$check_data [] = array(
				'function' => "checkEmail",
				'data'     => array(
					$data['wgs_f_email'],
					$data['wgs_f_email_r'],
					$checkUniqueEmail
				)
			);
		}
		if ( isset( $data['wgs_f_password'] ) ) {
			$check_data [] = array(
				'function' => "checkPassword",
				'data'     => array(
					$data['wgs_f_password'],
					null
				)
			);
		}
		if( Notices::isNoErrors() ){
			//Results
			$results = CommonForm::get_form_results( $check_data );

			if( $results['success'] ){
				$f_data = array(
					'email' => htmlspecialchars( $data['wgs_f_email'] ),
					'first_name' => htmlspecialchars( $data['wgs_f_first_name'] ),
					'last_name' => htmlspecialchars( $data['wgs_f_last_name'] ),
					'subscriber_id' => (int) $_GET['subs'],
					'status' => "published"
				);
				if( isset( $data['wgs_f_password'] ) )
					$f_data['password'] = htmlspecialchars( $data['wgs_f_password'] );
				$results['data'] = $f_data;
			}
		}else{
			$results['success'] = false;
			$results['errors'] = array();
		}
		return $results;
	}

	protected function trashItem(){
		$output = array(
			'success' => false
		);
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], "trash-member_".$_GET['subs'] ) ) {
			wp_error_log( "_wpnonce did not verify!" );
			wp_error_log( "POST['_wpnonce'] : " . $_POST['_wpnonce'] );
			wp_error_log( "this->nonce : " . $this->editable_item->nonce );
			$message_error = __( "Sorry, your nonce did not verify.", $this->current_plugin_domain );
			Notices::setNotice( $message_error, "error", true );
		} else {
			$member = new Member( (int) $_GET['mbr'] );
			$res_member = $member->trash();
			if( !$res_member['success'] ){
				Notices::setNotices( $res_member['errors'], "error", true );
			}else{
				$output['success'] = true;
				$output['data'] = $res_member['data'] ; // member_id
				if( !empty( $member->first_name ) && !empty( $member->last_name ) ){
					$full_name = $member->first_name . " " . $member->last_name;
				}else{
					$full_name = $member->email;
				}
				$message_success = $message_success = sprintf( __( "%s '%s' has been trashed.", $this->current_plugin_domain ),
					_x( "The " . $this->editable_item->params->name, "message_item_name", $this->current_plugin_domain ),
					$full_name
				);
				Notices::setNotice( $message_success, "success", true );
			}
		}
		return $output ;
	}

	protected function initEditableForm(){
		wp_debug_log();
		$this->editable_item->form->options['postboxes'] = $this->get_postboxes();
		$this->editable_item->form->action = $this->get_form_action();
		//Email
		$this->editable_item->form->content[0]['rows'][0]['columns'][0]['items'][0]['value'] = $this->editable_item->email;
		$subscriber_id = ( is_int( $this->editable_item->subscriber_id ) ) ? $this->editable_item->subscriber_id : (int) $_GET['subs'] ;
		$plan = Subscriber::getPlanBySubscriberId( $subscriber_id );
		if( $plan->plan_type === "single" )
			$this->editable_item->form->content[0]['rows'][0]['columns'][0]['items'][0]['readonly'] = true;
		//Confirmation email
		if( $plan->plan_type === "single" ){
			unset( $this->editable_item->form->content[0]['rows'][1] );
		}else{
			$this->editable_item->form->content[0]['rows'][1]['columns'][0]['items'][0]['value'] = $this->editable_item->email;
		}
		//Last name
		$this->editable_item->form->content[0]['rows'][2]['columns'][0]['items'][0]['value'] = $this->editable_item->last_name;
		if( $plan->plan_type === "single" )
			$this->editable_item->form->content[0]['rows'][2]['columns'][0]['items'][0]['readonly'] = true;
		//First name
		$this->editable_item->form->content[0]['rows'][2]['columns'][1]['items'][0]['value'] = $this->editable_item->first_name;
		if( $plan->plan_type === "single" )
			$this->editable_item->form->content[0]['rows'][2]['columns'][1]['items'][0]['readonly'] = true;
		//Password
		$this->editable_item->form->content[0]['rows'][3]['columns'][0]['items'][0] = array(
			'label' =>  __( "New Password", $this->current_plugin_domain ),
			'id' => "pass1-text",
			'required' => true,
			'type' => "label",
			'col_size' => 4
		);
		$this->editable_item->form->content[0]['rows'][3]['columns'][] = array(
			'items' => array(
				0 => array(
					'type' => "hidden",
					'value' => " "
				),
				1 => array(
					'label' => __( "Generate Password", $this->current_plugin_domain ),
					'id'    => "btn_gen_password",
					'type'  => "button",
					'class' => "button wp-generate-pw hide-if-no-js",
				)
			)
		);

		//Confirmation password
		unset( $this->editable_item->form->content[0]['rows'][3]['columns'][1] );
	}

	protected function set_additional_scripts() {
		wp_enqueue_script( "wgscommonformscript", H4A_WGS_PLUGIN_DIR_URL . "common/js/wgs-common-form.js" );
		wp_enqueue_script( "wp-util", ABSPATH . "/wp-includes/js/wp-util.js" );
		wp_enqueue_script( "zxcvbn-async", ABSPATH . "/wp-includes/js/zxcvbn-async.js", array(), '1.0' );
		wp_localize_script( "zxcvbn-async", "_zxcvbnSettings", array(
			'src' => wp_guess_url() . '/wp-includes/js/zxcvbn.min.js'
		) );
		wp_enqueue_script( "password-strength-meter", ABSPATH . "/wp-admin/js/password-strength-meter.js", array( 'jquery', 'zxcvbn-async' ), false, 1 );
		wp_localize_script( "password-strength-meter", "pwsL10n", array(
			'unknown'  => _x( 'Password strength unknown', 'password strength' ),
			'short'    => _x( 'Very weak', 'password strength' ),
			'bad'      => _x( 'Weak', 'password strength' ),
			'good'     => _x( 'Medium', 'password strength' ),
			'strong'   => _x( 'Strong', 'password strength' ),
			'mismatch' => _x( 'Mismatch', 'password mismatch' )
		) );
		wp_enqueue_script( "wgsmember",
			H4A_WGS_PLUGIN_DIR_URL . "admin/headings/members/views/js/wgs-member.js",
			array( "jquery" ), false, true );
		wp_localize_script( "wgsmember", "wgsFormTranslation", array(
			'button_hide_label' => esc_attr__( 'Hide password' ),
			'button_hide_text' => __( 'Hide' ),
			'button_cancel_label' => esc_attr__( 'Cancel password change' ),
			'button_cancel_text' => __( 'Cancel' ),
			'warn'     => __( 'Your new password has not been saved.' ),
			'warnWeak' => __( 'Confirm use of weak password' ),
			'show'     => __( 'Show' ),
			'hide'     => __( 'Hide' ),
			'cancel'   => __( 'Cancel' ),
			'ariaShow' => esc_attr__( 'Show password' ),
			'ariaHide' => esc_attr__( 'Hide password' ),
		) );
		//CSS
		wp_enqueue_style( 'wgsmemberstyle', H4A_WGS_PLUGIN_DIR_URL . "admin/headings/members/views/css/wgs-member.css" );
	}

	private function get_postboxes(){
		$subscriber = new Subscriber( (int) $_GET['subs'] );
		$plan = Subscriber::getPlanBySubscriberId( (int) $_GET['subs'] );
		$status_postbox_major_actions = $this->get_status_postbox_major_actions( $plan );
		$postboxes = [
			0 => array(
				'key' => "submit",
				'title' => "Saving",
				'content' => array(
					'minor-actions' => null,
					'misc-actions' => null,
					'major-actions' => $status_postbox_major_actions
				)
			),
			1 => array(
				'key' => "subscription",
				'title' => "Subscription",
				'content' => array(
					'minor-actions' => null,
					'misc-actions' => [
						0 => array(
							'keys'   => array( "current", "plan" ),
							'label'  => _x( "Plan", "misc_postbox_label", $this->current_plugin_domain ),
							'value'  => $plan->plan_name
						),
						1 => array(
							'keys'   => array( "subscription", "status" ),
							'label'  => _x( "Status", "misc_postbox_label", $this->current_plugin_domain ),
							'value'  => $subscriber->status
						),
						2 => array(
							'keys'   => array( "last", "subscription" ),
							'label'  => _x( "Last Subscription", "misc_postbox_label", $this->current_plugin_domain ),
							'value'  => ( !empty( $subscriber->last_subscription_date ) ) ? return_datetime( $subscriber->last_subscription_date, "d/m/Y H:i" ) : __( "None", $this->current_plugin_domain )
						)
					],
					'major-actions' => null
				)
			)
		];
		if( isset( $_GET['mbr'] ) ){
			$postboxes[0]['content']['misc-actions'] = [
				0 => array(
					'keys'   => array( "last", "connection" ),
					'label'  => _x( "Last connection", "misc_postbox_label", $this->current_plugin_domain ),
					'value'  => ( !empty( $this->editable_item->last_connection ) ) ? return_datetime( $this->editable_item->last_connection, "d/m/Y H:i" ) : __( "Never connected", $this->current_plugin_domain )
				),
				1 => array(
					'keys'   => array( "last", "activation" ),
					'label'  => _x( "Last activation", "misc_postbox_label", $this->current_plugin_domain ),
					'value'  => ( !empty( $this->editable_item->last_activation ) ) ? return_datetime( $this->editable_item->last_activation, "d/m/Y H:i" ) : __( "Never activated", $this->current_plugin_domain )
				),
				2 => array(
					'keys'   => array( "creation", "date" ),
					'label'  => _x( "Creation date", "misc_postbox_label", $this->current_plugin_domain ),
					'value'  => return_datetime( $this->editable_item->start_date, "d/m/Y H:i" )
				),
			];
		}
		if( $plan->plan_type === "multiple" ){
			$postboxes[1]['content']['misc-actions'][] = array(
				'keys'   => array( "group", "name" ),
				'label'  => _x( "Group name", "misc_postbox_label", $this->current_plugin_domain ),
				'value'  => $subscriber->group_name
			);
		}
		return $postboxes;
	}

	private function get_status_postbox_major_actions( Plan $plan = null ){

		if( isset( $_GET['mbr'] ) && isset( $_GET['subs'] ) ){
			$actions = array();
			if( empty( $plan )){
				$message_error = "No plan found but it´s mandatory to show the status postbox !";
				wp_error_log( $message_error );
				Notices::setNotice( wp_get_error_back_end_system(), "error" );
			}else{
				if( $plan->plan_type === "multiple" ){
					$args_delete = array(
						'action' => "trash",
						'subs' => $_GET['subs'],
						'mbr' => $_GET['mbr']
					);
					$delete_link = wp_admin_build_url( "edit-member", false, $args_delete );
					$actions['delete'] = array(
						'label' => __( "Move to Trash" ),
						'href' => wp_nonce_url( $delete_link, "trash-member_" . $_GET['subs'])
					);
				}
			}
			$actions['save'] = array(
				'value' => sprintf( __( "Update %s", $this->current_plugin_domain ), _x( "member", "save-member-edition", $this->current_plugin_domain ) ),
				'name' => "save"
			);
			return $actions;
		}else{
			return array(
				'save' => array(
					'value' => sprintf( __( "Save %s", $this->current_plugin_domain ), _x( "member", "save-member-edition", $this->current_plugin_domain ) ),
					'name' => "publish"
				)
			);
		}
	}

	private function get_form_action() {
		if( isset( $_GET['mbr'] ) ){
			$args_actions = array(
				'action' => "edit",
				'subs' => $_GET['subs'],
				'mbr' => $_GET['mbr']
			);
		}else {
			$args_actions = array(
				'action' => "edit",
				'subs' => $_GET['subs']
			);
		}
		return wp_admin_build_url( "edit-member", false, $args_actions );
	}

	public static function getNewPasswordByAjax(){
		echo esc_attr( wp_generate_password( 24 ) );
		wp_die();
	}

	private function checkIfEmailChanged( $new_email ) {
		global $wpdb;
		$query = "SELECT email FROM {$wpdb->prefix}" . $this->current_plugin_prefix . "members WHERE member_id = " . (int) $_GET['mbr'] . ";";
		$result = $wpdb->get_results( $query, ARRAY_A );
		if( count( $result ) === 0 ){
			wp_error_log( "Email not found when we try to know if it is changed" );
			Notices::setNotice( wp_get_error_back_end_system(), "error" );
			return null;
		}else{
			$old_email = $result[0];
			return $old_email !== $new_email;
		}

	}
}


