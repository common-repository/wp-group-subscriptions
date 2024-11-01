<?php

namespace H4APlugin\WPGroupSubs\Admin\Accounting;

use function H4APlugin\Core\addHTMLinDOMDocument;
use H4APlugin\Core\Admin\AdminForm;
use H4APlugin\Core\Admin\EditItemTemplate;
use H4APlugin\Core\Common\Currencies;
use H4APlugin\Core\Common\Notices;
use H4APlugin\Core\Config;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\is_number;
use function H4APlugin\Core\return_datetime;
use function H4APlugin\Core\wp_admin_build_url;
use function H4APlugin\Core\wp_get_error_back_end_system;
use function H4APlugin\Core\wp_error_log;
use function H4APlugin\Core\wp_debug_log;
use H4APlugin\WPGroupSubs\Common\Payment;
use H4APlugin\WPGroupSubs\Common\Plan;
use H4APlugin\WPGroupSubs\Common\PlanForms;
use H4APlugin\WPGroupSubs\Common\Subscriber;
use H4APlugin\WPGroupSubs\Common\SubscriberEditionTrait;

class Edit_Subscriber extends EditItemTemplate {

	Use SubscriberEditionTrait;

	private $page_step;

	public function __construct( $data ) {
		wp_debug_log();
		if(
			( isset( $_POST ) ) && ( array_key_exists("save", $_POST ) ) || array_key_exists("publish", $_POST )
		){
			//necessary to get the nonce
			$data['editable_item'] = new Subscriber(
				0,
				"edit",
				$this->get_mandatory_args()
			);
		}
		parent::__construct( $data );
	}

	/**
	 * @param $subscriber_id
	 * @param $subscriber_status
	 * @param $plan_selected
	 * @param $last_payment
	 *
	 * @return array
	 */
	public static function build_misc_publishing_action_status( $subscriber_id, $subscriber_status, $plan_selected, $last_payment )
	{
		wp_debug_log();
		$current_plugin_domain = get_current_plugin_domain();
		if ( !isset( $subscriber_id )
		     || (!$last_payment && $plan_selected->price > 0 )
		) {
			$f_status = "disabled";
		}else{
			$f_status = $subscriber_status;
		}
		$status_postbox_misc_actions = array(
			0 => array(
				'keys' => array( "subscriber", "status"),
				'label' => "Status",
				'value' => _x( ucfirst( $f_status ), "edition", $current_plugin_domain )
			)
		);
		/* add status modify if payment or free plan
		 * See Manual - Subscriber activation, user cases 4 and 5.
		 */
		$status_postbox_misc_actions[0]['modify'] = array(
			'id' => "wgs_f_status",
			'name' => "wgs_f_status",
			'selected' => $subscriber_status,
			'options' => array(
				array('label' => _x("Disabled", "edition", $current_plugin_domain ), 'value' => "disabled"),
				array('label' => _x("Active", "edition", $current_plugin_domain ), 'value' => "active")
			)
		);

		return $status_postbox_misc_actions;
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
				wp_redirect( wp_admin_build_url( "edit-subscriber", false, $args_action ) );
				exit;
			}else if( $_GET['action'] === "trash" ){
				$res_subscriber = $this->trashItem();
				if( !$res_subscriber['success'] ){
					$args_action = array(
						'action' => "edit",
						'subs' => $_GET['subs'],
						'pl' => $_GET['pl']
					);
					wp_redirect( wp_admin_build_url( "edit-subscriber", false, $args_action ) );
				}else{
					wp_redirect( wp_admin_build_url( "subscribers" ) );
					exit();
				}
			}
		}else{
			if( isset( $_GET['action'] ) && $_GET['action'] === "edit" && !empty( $_GET['subs'] ) ){  // Show subscriber
				$this->setEditableItem( "edit" );
				$this->page_step = 2;
				if( $this->editable_item->status === "trash" ) { // Lock if the subscriber is trashed
					$message_info = $message_success = sprintf( __( "%s '%s' has been trashed.", $this->current_plugin_domain ),
						_x( "The " . $this->editable_item->params->name, "message_item_name", $this->current_plugin_domain ),
						$this->editable_item->first_name . " " . $this->editable_item->last_name
					);
					wp_die( $message_info );
					exit;
				}
			}else if( !empty( $_GET['pl'] ) ){ // Show blank item
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
			include_once "views/view-subscriber-step-1.php";

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

	protected function set_url_args( $args_action, $res = array() ){
		wp_debug_log();
		if( $res['success'] ){
			$args_action['subs'] = ( is_array(  $res['data'] ) ) ? $res['data']['subscriber_id'] : $res['data'] ;
		}else if( !empty( $_GET[ $this->editable_item->params->slug ] ) ){ //Case : updating failed
			$args_action['subs'] = $_GET['subs'];
		}
		$args_action[ 'pl' ] = $_POST['wgs_f_plan_id'];
		return $args_action;
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
			if( !is_number( $_POST['wgs_f_plan_id'] ) ){
				$error_message = sprintf("The plan id is not a number : '%s'.", $_POST['wgs_f_plan_id'] );
				wp_error_log( $error_message );
				$message_error = wp_get_error_back_end_system();
				Notices::setNotice( $message_error, "error", true );
			}else{
				$plan = new Plan( (int) $_POST['wgs_f_plan_id'], "read" );
				$res_check = PlanForms::checkFormData( $_POST, $plan->plan_type, $isUpdate );
				if( !$res_check['success'] ){
					wp_error_log("Data Form checking show errors!" );
					Notices::setNotices( $res_check['errors'], "error", true );
				}else{
					$res_check['data']['plan_id'] = (int) $_POST['wgs_f_plan_id'];
					$res_check['data']['status']  = $_POST['wgs_f_status'];
					if( $plan->plan_type === "multiple"  ){
						$res_check['data']['as_member'] = ( isset( $_POST['wgs_f_as_member'] ) && $_POST['wgs_f_as_member'] === "on" ) ? true : false;
					}
					if( $init_format === "update" ){
						$res_check['data']['subscriber_id'] = (int) $_GET['subs'];
					}else{
						$res_check['data']['subscriber_id'] = null;
					}
					if ( is_plugin_active( 'wgs-custom-forms-addon/WGSCustomFormsAddon.php' ) ) {
						$res_options = apply_filters( 'wgs_get_only_subs_options', $res_check['data']  );
						if( $res_options['success'] && !empty( $res_options['data'] ) ){
							$res_check['data']['options'] = $res_options['data'];
						}
					}
					$args = $this->get_mandatory_args();
					$args['plan_id'] = (int) $_POST['wgs_f_plan_id'];
					$this->editable_item = new Subscriber( $res_check['data'], "edit", $args );
					$isSaveMember = ( $plan->plan_type === "single" || $res_check['data']['as_member'] ) ? true : false ;
					$password = ( isset( $res_check['data']['password'] ) ) ? $res_check['data']['password'] : null ;
					$res_subscriber = call_user_func_array( array( $this->editable_item, $init_format ), array( $isSaveMember, $password ) );
					if ( !$res_subscriber['success'] ) {
						$message_error = ( !empty( $_GET['subs'] ) ) ? __( "Updating failed!", $this->current_plugin_domain ) : __( "Saving failed!", $this->current_plugin_domain );
						wp_error_log( $message_error );
						Notices::setNotice( $message_error, "error", true );
					}else{
						$output['success'] = true;
						$output['data']    = $res_subscriber['data'] ; // subscriber_id  or array( subscriber_id, member_id )
						if( $init_format === "save" ){
							$success_message = __( "Successfully saved!", $this->current_plugin_domain );
							Notices::setNotice( $success_message, "success", true );
						}else{
							$success_message = __( "Successfully updated!", $this->current_plugin_domain );
							Notices::setNotice( $success_message, "success", true );
						}
						if( !empty( $res_subscriber['data']['member_id'] ) ){
							if( $init_format === "save" ){
								$success_message = __( "A member with the same name was automatically saved!", $this->current_plugin_domain );
								Notices::setNotice( $success_message, "success", true );
							}else if( $init_format === "update" ){
								$success_message = __( "The member was updated as well!", $this->current_plugin_domain );
								Notices::setNotice( $success_message, "success", true );
							}

						}
					}
				}

			}
		}
		return $output ;
	}

	protected function trashItem(){
		$output = array(
			'success' => false
		);
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], "trash-subscriber_".$_GET['subs'] ) ) {
			wp_error_log( "_wpnonce did not verify!" );
			wp_error_log( "POST['_wpnonce'] : " . $_POST['_wpnonce'] );
			wp_error_log( "this->nonce : " . $this->editable_item->nonce );
			$message_error = __( "Sorry, your nonce did not verify.", $this->current_plugin_domain );
			Notices::setNotice( $message_error, "error", true );
		} else {
			$args = $this->get_mandatory_args();
			$subscriber = new Subscriber( (int) $_GET['subs'], "edit", $args );
			$res_subscriber = $subscriber->trash();
			if( !$res_subscriber['success'] ){
				Notices::setNotices( $res_subscriber['errors'], "error", true );
			}else{
				$output['success'] = true;
				$output['data'] = $res_subscriber['data'] ; // subscriber_id
				$full_name = $subscriber->first_name . " " . $subscriber->last_name;
				$message_success = $message_success = sprintf( __( "%s '%s' has been trashed.", $this->current_plugin_domain ),
					_x( "The " . $this->editable_item->params->name, "message_item_name", $this->current_plugin_domain ),
					$full_name
				);
				Notices::setNotice( $message_success, "success", true );
			}
		}
		return $output ;
	}

	protected function setBlankEditableItem() {
		wp_debug_log();
		$args = $this->get_mandatory_args();
		$this->editable_item = new Subscriber( 0, "edit", $args );
	}

	protected function setEditableItem( $format ) {
		wp_debug_log();
		$args = $this->get_mandatory_args();
		$this->editable_item = new Subscriber( (int) $_GET['subs'], $format, $args );
	}

	protected function initEditableForm(){
		wp_debug_log();
		$plan_id = (int) $this->editable_item->plan_id;
		$plan_selected = new Plan( $plan_id, "read" );
		$this->get_form_options( $plan_selected );
		$this->editable_item->form->action = $this->get_form_action( $plan_id );
		if( isset( $_GET['subs'] ) ){
			self::modifySubscriberForm( $this->editable_item );
		}
	}

	private function get_status_postbox_major_actions(){
		if( isset( $_GET['subs'] ) && isset( $this->editable_item->plan_id ) ){
			$args_delete = array(
				'action' => "trash",
				'subs' => $_GET['subs'],
				'pl' => $this->editable_item->plan_id
			);
			$delete_link = wp_admin_build_url( "edit-subscriber", false, $args_delete );
			return array(
				'delete' => array(
					'label' => __( "Move to Trash" ),
					'href' => wp_nonce_url( $delete_link, "trash-subscriber_" . $_GET['subs'])
				),
				'save' => array(
					'value' => sprintf( __( "Update %s", $this->current_plugin_domain ), _x( "subscriber", "save-subscriber-edition", $this->current_plugin_domain ) ),
					'name' => "save"
				)
			);
		}else{
			return array(
				'save' => array(
					'value' => sprintf( __( "Save %s", $this->current_plugin_domain ), _x( "subscriber", "save-subscriber-edition", $this->current_plugin_domain ) ),
					'name' => "publish"
				)
			);
		}
	}

	private function get_status_postbox_misc_actions( $subscriber_status, $plan_selected, $last_payment ){
		$subscriber_id = ( isset( $this->editable_item->subscriber_id ) ) ? $this->editable_item->subscriber_id : null;
		$status_postbox_misc_actions = self::build_misc_publishing_action_status( $subscriber_id, $subscriber_status, $plan_selected , $last_payment );

		if( isset( $_GET['subs'] ) ){
			$status_postbox_misc_actions[1] = array(
				'keys'   => array( "last", "subscription", "date" ),
				'label'  => _x( "Last Subscription", "misc_postbox_label", $this->current_plugin_domain ),
				'value'  => ( !empty( $this->editable_item->last_subscription_date ) ) ? return_datetime( $this->editable_item->last_subscription_date, "d/m/Y H:i" ) : __( "Never activated", $this->current_plugin_domain )
			);
			$status_postbox_misc_actions[2] = array(
				'keys'   => array( "creation", "date" ),
				'label'  => _x( "Creation date", "misc_postbox_label", $this->current_plugin_domain ),
				'value'  => return_datetime( $this->editable_item->start_date, "d/m/Y H:i" )
			);
		}

		return $status_postbox_misc_actions;
	}

	private function get_payments_postbox_misc_actions( $last_payment ){
		$payments_postbox_misc_actions = array();
		if( isset( $_GET['subs'] ) ){
			$last_payment_date = ( !$last_payment ) ? "None" : return_datetime( $last_payment->payment_date, "d/m/Y H:i" ) ;
			$payments_postbox_misc_actions[0] = array(
				'keys' => array( "last", "payment" ),
				'label' => __( "Last payment", $this->current_plugin_domain ),
				'value' => $last_payment_date
			);
			if( $last_payment_date !== "None" )
				$payments_postbox_misc_actions[0]['data-id'] = $last_payment->payment_id;
		}else{
			$payments_postbox_misc_actions[0]['html'] = "<p>" . __( "Please, save the subscriber before to assign payments", $this->current_plugin_domain ) . "</p>";
		}
		return $payments_postbox_misc_actions;
	}

	private function get_payments_postbox_major_actions(){
		$payments_postbox_major_actions = null;
		if( isset( $_GET['subs'] ) ){
			$payments_postbox_major_actions = array(
				'button' => array(
					'value' => __( "Assign / Unassign", $this->current_plugin_domain ),
					'id' => "open_payments_binding"
				)
			);
		}
		return $payments_postbox_major_actions;
	}

	protected function set_additional_scripts(){
		//Javascript
		wp_enqueue_script( "wgscommonformscript", H4A_WGS_PLUGIN_DIR_URL . "common/js/wgs-common-form.js" );
		wp_enqueue_script( "wgssubscriber",
			H4A_WGS_PLUGIN_DIR_URL . "admin/headings/accounting/views/js/wgs-subscriber.js",
			array( "jquery" ), false, true );
		wp_localize_script( "wgssubscriber", "wgsFormTranslation", array(
			'plan_type_single'     => __( "Single", $this->current_plugin_domain ),
			'plan_type_multiple'   => __( "Multiple", $this->current_plugin_domain ),
			'payments_modal_title' => __( "Payments assignment", $this->current_plugin_domain ),
			'payments_modal_source_title'      => __( "Unassigned", $this->current_plugin_domain ),
			'payments_modal_destination_title' => __( "Assigned to this subscriber", $this->current_plugin_domain ),
			'save_payments'          => __( "Save Changes"),
			'password_placeholder'   => __( "Please insert your password", $this->current_plugin_domain ),
			'password_placeholder_r' => __( "Please confirm your password", $this->current_plugin_domain ),
			'button_change_password' => __( "Change Password", $this->current_plugin_domain ),
			'button_cancel'          => __( "Cancel" ),
			'include_as_member'      => __( "Include as member", $this->current_plugin_domain )

		) );
		$this->set_modal_scripts();
		$this->set_dual_list_scripts();
		//CSS
		wp_enqueue_style( 'wgssubscriberstyle', H4A_WGS_PLUGIN_DIR_URL . "admin/headings/accounting/views/css/wgs-subscriber.css" );
	}

	public static function getPlanTypeByAjax(){
		$plan_type = ucfirst( Plan::getPlanTypeById( (int) $_POST['plan_id'] ) );
		_e( $plan_type, get_current_plugin_domain() );
		wp_die();
	}

	public static function getPlanPriceByAjax(){
		$plan = new Plan( (int) $_POST['plan_id'], "read" );
		echo $plan->price;
		wp_die();
	}

	public static function getSubscriberFormContentByAjax(){
		wp_debug_log();
		$plan_selected = new Plan( (int) $_POST['plan_id'], "read" );
		$form = new AdminForm(  $plan_selected->form_type_id, "plan-subscription", true );
		self::hidePasswordInputs( $form );
		$htmlContent = $form->writeFormWrappers();
		$data_form = [
			'html_id' => $form->html_id,
			'content' => $htmlContent
		];
		$json = json_encode( $data_form );
		echo $json;
		wp_die();

	}

	public static function assignPaymentsByAjax(){
		wp_debug_log();
		$output = array(
			'success' => false
		);
		//wp_debug_log( str_replace( "&", "&amp;", serialize( $_POST['data'] )  ) );
		parse_str( $_POST['data'], $post );
		//wp_debug_log( serialize( $post ) );
		Subscriber::unassignPayments( (int) $post['subscriber_id'] );
		$payment_ids = null;
		if( !empty( $post['wgs-payments-assigned-select'] ) ){
			$payment_ids = ( is_array( $post['wgs-payments-assigned-select'] ) ) ? $post['wgs-payments-assigned-select'] : array( $post['wgs-payments-assigned-select'] );
		}
		$current_plugin_domain = get_current_plugin_domain();
		if( !empty( $payment_ids ) ){
			$res_assignment = Subscriber::assignPayments( (int) $post['subscriber_id'], $payment_ids);
			if( !$res_assignment ){
				$output['errors'] = array(
					0 => __( "Error during the payments assignment", $current_plugin_domain )
				);
			}else{
				$output['success'] = true;
				$last_payment = Payment::getLastPaymentBySubscriberId( $post['subscriber_id'], true );
				$payment_date =  return_datetime( $last_payment->payment_date, "d/m/Y H:i" );
				$output['last_payment_date'] = $payment_date;
				$output['last_payment_id'] = $last_payment->payment_id;
				$output['message'] = __( "Payment(s) (un)assigned succesfully!", $current_plugin_domain );
			}
		}else{
			$current_subscriber = new Subscriber( (int) $post['subscriber_id'], "edit" );
			$plan = new Plan( $current_subscriber->plan_id, "read" );

			$output['last_payment_date'] = __( "None", $current_plugin_domain );
			$output['last_payment_id'] = null;
			if( $plan->price !== 0.00 ) {
				$error_message = wp_get_error_back_end_system();
				$res_update_status = $current_subscriber->updateStatus( "disabled", $error_message );
				if( $res_update_status['success'] ){
					$output['success'] = true;
					$output['status'] = "disabled";
					$output['message'] = __( "The subscriber has beed disabled because there is no payment assigned.", $current_plugin_domain );
				}else{
					$output['success'] = false;
					$output['errors'] = $error_message;
				}
			}else{
				$output['success'] = true;
				$output['message'] = __( "Payment(s) unassigned succesfully!", $current_plugin_domain );
			}

		}
		$json = json_encode( $output );
		echo $json;
		wp_die();
	}

	public static function getSubscriberStatusContentByAjax( ){
		wp_debug_log();
		$plan_selected = new Plan( (int)$_POST['plan_id'], "read" );
		$status_postbox_misc_actions = self::build_misc_publishing_action_status( (int)$_POST['subscriber_id'], $_POST['status'], $plan_selected, $_POST['last_payment'] );
		$html = AdminForm::write_misc_publishing_action( $status_postbox_misc_actions[0] );
		if( !empty( $status_postbox_misc_actions[1] ) )
			$html .= AdminForm::write_misc_publishing_action( $status_postbox_misc_actions[1] );
		echo $html;
		wp_die();
	}

	public static function getPaymentsToAssignByAjax( ){
		wp_debug_log();
		$assigned = Payment::getAllPaymentsBySubscriberId( $_POST['subscriber_id'], true );
		$unassigned = Payment::getAllUnassignedPayments( true );
		$f_unassigned = [];
		$f_assigned = [];
		$current_plugin_domain = get_current_plugin_domain();
		if( $unassigned !== false ){
			foreach ( $unassigned as $payment ){
				$payment_date = return_datetime( $payment['payment_date'], "d/m/Y H:i" );
				$wgs_currency_options = get_option( "wgs-currency-options" );
				$price = Currencies::format_string_price( $payment['amount'], $wgs_currency_options['currency'], $wgs_currency_options['currency_position'] );
				$f_unassigned[] = [
					'value' => $payment['payment_id'],
					'text' => __( "nbr", $current_plugin_domain ) . " " . $payment['payment_id'] . " - " . $payment_date . " - " . $payment['email'] . " - " . $price
				];
			}
		}
		if( $assigned !== false ){
			foreach ( $assigned as $payment ){
				$payment_date = return_datetime( $payment['payment_date'], "d/m/Y H:i" );
				$wgs_currency_options = get_option( "wgs-currency-options" );
				$price = Currencies::format_string_price( $payment['amount'], $wgs_currency_options['currency'], $wgs_currency_options['currency_position'] );
				$f_assigned[] = [
					'value' => $payment['payment_id'],
					'text' => __( "nbr", $current_plugin_domain ) . " " . $payment['payment_id'] . " - " . $payment_date . " - " . $payment['email'] . " - " . $price
				];
			}
		}
		$data_payments = [
			'unassigned' => $f_unassigned,
			'assigned' => $f_assigned
		];
		$json = json_encode( $data_payments );
		echo $json;
		wp_die();
	}

	protected function get_mandatory_args() {
		$args = Config::get_item_by_ref( "subscriber" );
		if( isset( $this->editable_item->plan_id ) ){
			$args['plan_id'] = $this->editable_item->plan_id;
		}
		else if( isset( $_GET['pl'] ) ){
			$args['plan_id'] = (int) $_GET['pl'];
		}
		return $args;
	}

	/**
	 * @param $subscriber_status
	 * @param $plan_selected
	 * @param $last_payment
	 * @param $display_plan_name
	 *
	 * @return array
	 */
	private function get_postboxes($subscriber_status, Plan $plan_selected, $last_payment, $display_plan_name )
	{
		wp_debug_log();
		$status_postbox_major_actions = $this->get_status_postbox_major_actions();
		$status_postbox_misc_actions = $this->get_status_postbox_misc_actions($subscriber_status, $plan_selected, $last_payment);
		$payments_postbox_misc_actions = $this->get_payments_postbox_misc_actions($last_payment);
		$payments_postbox_major_actions = $this->get_payments_postbox_major_actions();

		$postboxes = [
			0 => array(
				'key' => "submit",
				'title' => "Status",
				'content' => array(
					'minor-actions' => null,
					'misc-actions' => $status_postbox_misc_actions,
					'major-actions' => $status_postbox_major_actions
				)
			),
			1 => array(
				'key' => "plan",
				'title' => "Plan",
				'content' => array(
					'minor-actions' => null,
					'misc-actions' => array(
						0 => array(
							'keys' => array( "plan", "selected"),
							'label' => __( "Plan", $this->current_plugin_domain),
							'value' => $display_plan_name,
							'modify' => array(
								'id' => "wgs_f_plan_id",
								'name' => "wgs_f_plan_id",
								'selected' => $plan_selected->plan_id,
								'options' => Plan::getPlans("options")
							)
						),
						1 => array(
							'keys' => array( "plan", "type"),
							'label' => __( "Type", $this->current_plugin_domain),
							'value' => __( $plan_selected->plan_type, $this->current_plugin_domain )
						)
					)
				)
			),
			2 => array(
				'key' => "payments",
				'title' => "Payments",
				'content' => array(
					'minor-actions' => null,
					'misc-actions' => $payments_postbox_misc_actions,
					'major-actions' => $payments_postbox_major_actions
				)
			)
		];
		if( $plan_selected->plan_type === "multiple" ){
			$html_input_group = sprintf(
				'<label for="wgs_f_as_member">%s</label> : <input type="checkbox" id="wgs_f_as_member" name="wgs_f_as_member" %s autocomplete="off"/>',
				__( "Include as member", $this->current_plugin_domain ),
				( !empty( $this->editable_item->as_member ) && $this->editable_item->as_member ) ? 'checked="checked"' : null
			);
			$html = sprintf( '<div class="misc-pub-section misc-pub-as-member" >%s</div>',
					$html_input_group
				);
			$postboxes[1]['content']['misc-actions'][2] = array(
				'html' => $html,
			);
		}
		return $postboxes;
	}

	private function get_form_options( $plan_selected ){
		//item_type
		$this->editable_item->form->options['item_type'] = "subscriber";
		//Postboxes
		$subscriber_status = $this->editable_item->status;
		$display_plan_name = $plan_selected->plan_name;
		if( !empty( $this->editable_item->subscriber_id ) ){
			$last_payment = Payment::getLastPaymentBySubscriberId( $this->editable_item->subscriber_id, true );
		}else{
			$last_payment = false;
		}
		$this->editable_item->form->options['postboxes'] = $this->get_postboxes($subscriber_status, $plan_selected, $last_payment, $display_plan_name );
		//CRUD
		if( isset( $_GET['subs'] ) ){
			$options['crud'] = array(
				'c' => false,
				'r' => true,
				'u' => $this->editable_item->status,
				'd' => true
			);
		}else {
			$this->editable_item->status = "disabled";
			$options['crud'] = array(
				'c' => true,
				'r' => true,
				'u' => $this->editable_item->status,
				'd' => false
			);
		}
		$this->editable_item->form->options['crud'] = $options['crud'];
		//Title display
		$this->editable_item->form->options['title_display'] = null; //To hide it
	}

	private function get_form_action( $plan_id )
	{
		if( isset( $_GET['subs'] ) ){
			$args_actions = array(
				'action' => "edit",
				'subs' => $_GET['subs'],
				'pl' => $plan_id
			);
		}else {
			$args_actions = array(
				'action' => "edit",
				'pl' => $plan_id
			);
		}
		return wp_admin_build_url( "edit-subscriber", false, $args_actions );
	}
}