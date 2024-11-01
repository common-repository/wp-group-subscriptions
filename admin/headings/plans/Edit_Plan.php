<?php

namespace H4APlugin\WPGroupSubs\Admin\Plans;

use H4APlugin\Core\Common\Notices;
use H4APlugin\Core\Admin\EditItemTemplate;
use function H4APlugin\Core\wp_debug_log;
use H4APlugin\WPGroupSubs\Common\Plan;
use function H4APlugin\Core\wp_get_error_back_end_system;
use function H4APlugin\Core\wp_error_log;

class Edit_Plan extends EditItemTemplate {

	public function __construct( $data ) {
		wp_debug_log();
		parent::__construct( $data );
	}

	protected function set_url_args( $arg_actions, $res = array() ){
		$plan_id = ( !empty( $res['data']['plan_id'] ) ) ? $res['data']['plan_id'] : (int) $_GET[ $this->editable_item->params->slug ];
		$arg_actions[ $this->editable_item->params->slug ] = $plan_id;
		return $arg_actions;
	}

	protected function saveOrUpdateItem(){
		wp_debug_log();
		$output = array(
			'success' => false
		);
		if ( !isset( $_POST['_wpnonce'] ) || !wp_verify_nonce( $_POST['_wpnonce'], $this->editable_item->nonce ) ) {
			wp_error_log( "_wpnonce did not verify!" );
			Notices::setNotice(  __( "Sorry, your nonce did not verify.", $this->current_plugin_domain ), "error", true );
		}else if( !isset( $_POST['publish'] ) && !isset( $_POST['save'] ) ){
			wp_error_log( "The key 'publish' or 'save' is not in the global POST!" );
			Notices::setNotice( wp_get_error_back_end_system(), "error", true );
		}else{
			if( !empty( $_GET[ $this->editable_item->params->slug ] ) ){
				$init_format = "update";
				$_POST['plan_id'] = $_GET[ $this->editable_item->params->slug ];
			}else{
				$init_format = "save";
				$plan_id = Plan::getPlanIdByName( $_POST['plan_title'] );
				if( $plan_id ){
					$message_error = sprintf( __( "A plan with this name '%s' already exits!", $this->current_plugin_domain ), $_POST['plan_title'] );
					Notices::setNotice( $message_error, "error", true );
				}
			}
			if( $init_format === "update" || ( isset( $plan_id ) && !$plan_id ) ){
				$args = $this->get_mandatory_args();
				$plan = new Plan( $_POST, "edit", $args );
				$res_plan = call_user_func( array( $plan ,$init_format ) );
				if ( $res_plan['success'] ) {
					$output['success'] = true;
					$output['data']    = $res_plan['data']; //plan_id and post_id
					if( $init_format === "update" ){
						Notices::setNotice( __( "Successfully updated!", $this->current_plugin_domain ), "success", true );
					}else{
						Notices::setNotice( __( "Successfully saved!", $this->current_plugin_domain ), "success", true);
					}
				}
			}
		}
		return $output;
	}

	protected function trashItem(){
		wp_debug_log();
		$output = array(
			'success' => false
		);
		if ( !isset( $_GET['_wpnonce'] ) || !wp_verify_nonce( $_GET['_wpnonce'] , $this->editable_item->nonce ) ) {
			wp_error_log( "_wpnonce did not verify!" );
			Notices::setNotice( __( "Sorry, your nonce did not verify.", $this->current_plugin_domain ), "error", true );
		}else {
			if( $this->editable_item instanceof Plan ){
				$res_plan = $this->editable_item->trash();
				if( !$res_plan['success'] ){
					$message_error = __( "Trash failed!", $this->current_plugin_domain );
					wp_error_log( $message_error );
					Notices::setNotices( $res_plan['errors'], "error", true );
				}else{
					$output['success'] = true;
					$output['data']    = $res_plan['data']; //plan_id and post_id
					$message_success = $message_success = sprintf( __( "%s '%s' has been trashed.", $this->current_plugin_domain ),
						_x( "The " . $this->editable_item->params->name, "message_item_name", $this->current_plugin_domain ),
						$this->editable_item->plan_name
					);
					Notices::setNotice( $message_success, "success", true );
				}
			}
		}
		return $output ;
	}

	protected function initEditableForm(){
		wp_debug_log();
		if( isset( $_GET['pl'] ) ){
			$subscribers = Plan::getSubscribersByPlanId( (int) $_GET['pl'] );
			if( is_array( $subscribers ) && count( $subscribers ) > 0 ){
				$this->editable_item->form->content[1]['rows'][2]['columns'][1]['items'][0]['disabled'] = true;
				$this->editable_item->form->content[1]['rows'][2]['columns'][2]['items'][0]['disabled'] = true;
				$this->editable_item->form->content[1]['rows'][3]['columns'][1]['items'][0]['disabled'] = true;

			}
		}
	}

	protected function set_additional_scripts(){
		wp_debug_log();
		//Javascript
		wp_enqueue_script( "wgsplan", H4A_WGS_PLUGIN_DIR_URL . "admin/headings/plans/views/js/wgs-plan.js");
		wp_localize_script( "wgsplan", "wgsPlanTranslation", array(
			'msg_must_greater' => __( "This value must be greater than ", $this->current_plugin_domain )
		) );
		//CSS
		wp_enqueue_style( 'wgsplanstyle', H4A_WGS_PLUGIN_DIR_URL . "admin/headings/plans/views/css/wgs-plan.css" );
	}

}