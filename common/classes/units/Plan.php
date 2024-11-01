<?php

namespace H4APlugin\WPGroupSubs\Common;

use H4APlugin\Core\Common\Notices;
use H4APlugin\Core\Common\EditableItem;
use function H4APlugin\Core\format_str_to_kebabcase;
use function H4APlugin\Core\get_current_plugin_prefix;
use function H4APlugin\Core\get_today_as_datetime;
use function H4APlugin\Core\is_date_expired_by_date;
use function H4APlugin\Core\is_date_expired_by_duration;

use function H4APlugin\Core\is_float_as_string;
use function H4APlugin\Core\is_number;
use function H4APlugin\Core\wp_admin_build_url;
use function H4APlugin\Core\wp_get_error_back_end_system;
use function H4APlugin\Core\wp_get_error_system;
use function H4APlugin\Core\wp_error_log;
use function H4APlugin\Core\wp_debug_log;
use function H4APlugin\Core\wp_get_symbol_currency;

class Plan extends EditableItem {

	public $plan_id;
	public $user_id;
	public $plan_name;
	public $post_id;
	public $plan_type;
	public $form_type_id;
	public $members_min;
	public $members_max;
	public $start_date;
	public $duration_type;
	public $expiration_date;
	public $duration_value;
	public $status;
	public $price;
	public $publish_date;
	public $user_nicename;
	public $options = array();

	/*
	 * Initializers
	 */

	public function initForm(){
		wp_debug_log();

		$this->form->options['action_wpnonce'] = $this->get_nonce( "edit" );
		$this->form->options['item_type']      = $this->params->ref;
		$this->form->options['item_name']      = $this->params->name;

		$args_action = array();
		if( isset( $_GET[ $this->params->slug ] ) ){
			$this->form->options['title_display']  = $this->plan_name;

			$args_action = array(
				'pl' => $_GET[ $this->params->slug ],
				'action' => "edit"
			);
			$args_delete = array(
				'pl' => $_GET[ $this->params->slug ],
				'action' => "trash",
			);
			$delete_link = wp_admin_build_url( "edit-plan", false, $args_delete );
			$this->form->options['delete_href'] = wp_nonce_url( $delete_link, $this->get_nonce( "trash" ));
			$this->form->options['submitBox'] = array(
				'title' => "Status",
				'button' => "Save Changes"
			);
			$this->form->options['crud'] = array(
				'c' => false,
				'r' => true,
				'u' => $this->status,
				'd' => true
			);
		}else{
			$this->form->options['title_display']  = "";
			$this->form->options['submitBox'] = array(
				'title' => "Status",
				'button' => sprintf(__( "Save %s", $this->current_plugin_domain ), _x( "Plan", "save-plan-edition", $this->current_plugin_domain ) )
			);
			$this->form->options['draft'] = true;
			$this->form->options['crud'] = array(
				'c' => true,
				'r' => true,
				'u' => $this->status,
				'd' => false
			);
		}
		$this->form->action = wp_admin_build_url( "edit-plan", false, $args_action );
		$wgs_surrency_options = get_option( "wgs-currency-options" );
		/* post_id hidden */
		$this->form->content[0]['items']['wgs_f_post_id']['value'] = ( isset ( $this->post_id ) ) ? $this->post_id  : "";

		/* currency as symbol */
		$this->form->content[1]['rows'][0]['columns'][1]['items'][0]['label'] = wp_get_symbol_currency( $wgs_surrency_options['currency'] );
		if( $wgs_surrency_options['currency_position'] === "after"){
			$i_pos_price = 0;
			$currency_symbol = $this->form->content[1]['rows'][0]['columns'][1]['items'][0];
			$price_input = $this->form->content[1]['rows'][0]['columns'][1]['items'][1];
			$this->form->content[1]['rows'][0]['columns'][1]['items'][0] = $price_input;
			$this->form->content[1]['rows'][0]['columns'][1]['items'][1] = $currency_symbol;
		}else{
			$i_pos_price = 1;
		}
		/* input price */
		$this->form->content[1]['rows'][0]['columns'][1]['items'][$i_pos_price]['disabled'] = ( $this->price > 0 ) ? null :  "disabled";
		$this->form->content[1]['rows'][0]['columns'][1]['items'][$i_pos_price]['placeholder'] = "00,00";
		$this->form->content[1]['rows'][0]['columns'][1]['items'][$i_pos_price]['step'] = "0.01";
		$this->form->content[1]['rows'][0]['columns'][1]['items'][$i_pos_price]['value'] = number_format( $this->price, 2, '.', '');
		/* link change currency */
		global $wp_settings_sections;
		if( count( $wp_settings_sections[H4A_WGS_PAGE_SETTINGS] ) > 1 ){
			$currency_args['tab'] = "currency";
		}
		$currency_args['inp'] = "currency";
		$this->form->content[1]['rows'][0]['columns'][1]['items'][2]['href'] = wp_admin_build_url( H4A_WGS_PAGE_SETTINGS, true, $currency_args );
		/* free checkbox */
		$this->form->content[1]['rows'][0]['columns'][2]['items'][0]['checked'] = ( $this->price > 0 ) ? false :  true;
		/* value plan duration radio button */
		$this->form->content[1]['rows'][1]['columns'][1]['items'][0]['checked'] = ( !empty( $this->duration_value ) ) ? "checked" :  false;
		/* input number plan duration */
		$this->form->content[1]['rows'][1]['columns'][1]['items'][1]['min'] = 1;
		$this->form->content[1]['rows'][1]['columns'][1]['items'][1]['step'] = 1;
		$this->form->content[1]['rows'][1]['columns'][1]['items'][1]['value'] = ( !empty( $this->duration_value ) ) ? $this->duration_value :  null;
		$this->form->content[1]['rows'][1]['columns'][1]['items'][1]['disabled'] = ( empty( $this->duration_value ) ) ? "disabled" : false;
		/* select y/m/d */
		$this->form->content[1]['rows'][1]['columns'][1]['items'][2]['selected'] = ( !empty( $this->duration_type ) ) ? $this->duration_type :  "year";
		$this->form->content[1]['rows'][1]['columns'][1]['items'][2]['disabled'] = ( empty( $this->duration_value ) ) ? "disabled" : false;
		/* valid until radio button */
		$this->form->content[1]['rows'][1]['columns'][2]['items'][0]['checked'] = ( !empty( $this->expiration_date ) ) ? "checked" :  false;
		/* input date */
		$this->form->content[1]['rows'][1]['columns'][2]['items'][1]['value'] = ( !empty( $this->expiration_date ) ) ? $this->expiration_date  :  "0000-00-00";
		$this->form->content[1]['rows'][1]['columns'][2]['items'][1]['disabled'] = ( empty( $this->expiration_date ) ) ? "disabled" : false;
		/* unlimited radio button */
		$this->form->content[1]['rows'][1]['columns'][3]['items'][0]['checked'] = ( !empty( $this->duration_type ) && $this->duration_type === "unlimited" ) ? "checked" :  false;
		/* single radio button */
		$this->form->content[1]['rows'][2]['columns'][1]['items'][0]['checked'] = ( $this->plan_type === "single" ) ? "checked" :  false;
		/* multiple radio button */
		$this->form->content[1]['rows'][2]['columns'][2]['items'][0]['checked'] = ( $this->plan_type === "multiple" ) ? "checked" :  false;
		/* number min members input */
		$this->form->content[1]['rows'][2]['columns'][3]['items'][0]['selected'] = $this->members_min;
		$this->form->content[1]['rows'][2]['columns'][3]['items'][0]['value'] = $this->members_min;
		$this->form->content[1]['rows'][2]['columns'][3]['items'][0]['function_options'] = "H4APlugin\WPGroupSubs\Common\Plan::getIntervalByPlanType#".$this->plan_type;
		$this->form->content[1]['rows'][2]['columns'][3]['items'][0]['disabled'] = ( $this->plan_type === "single" ) ? "disabled" : false ;
		/* number max members input */
		$this->form->content[1]['rows'][2]['columns'][3]['items'][1]['selected'] = $this->members_max;
		$this->form->content[1]['rows'][2]['columns'][3]['items'][1]['value'] = $this->members_max;
		$this->form->content[1]['rows'][2]['columns'][3]['items'][1]['function_options'] = "H4APlugin\WPGroupSubs\Common\Plan::getIntervalByPlanType#".$this->plan_type."#".true;
		$this->form->content[1]['rows'][2]['columns'][3]['items'][1]['disabled'] = ( $this->plan_type === "single" ) ? "disabled" : false ;
		/* link change ceiling */
		$ceiling_args = array(
			'tab' => "plans",
			'inp' => "ceiling_number_members"
		);
		$this->form->content[1]['rows'][2]['columns'][3]['items'][2]['href'] = wp_admin_build_url( H4A_WGS_PAGE_SETTINGS, true, $ceiling_args );
		/* select creation account form */
		$this->form->content[1]['rows'][3]['columns'][1]['items'][0]['required']         = "required";
		$this->form->content[1]['rows'][3]['columns'][1]['items'][0]['selected']         = ( !empty( $this->form_type_id ) ) ? $this->form_type_id :  "";
		$this->form->content[1]['rows'][3]['columns'][1]['items'][0]['function_options'] = "H4APlugin\WPGroupSubs\Common\PlanForms::getAllPlanFormsAsOptions#".$this->plan_type;
		if ( is_plugin_active( 'wgs-plan-edition-plus-addon/WGSPlanEditionPlusAddon.php' ) ) {
			$edit_plan_form_link = wp_admin_build_url( "edit-plan-form", false );
			$this->form->content = apply_filters("wgs_add_optional_plan_data_in_editable_form", $this->form->content, $this->options );
		}else{
			$form_plan_args = array(
				'tab' => "plan-forms"
			);
			$edit_plan_form_link = wp_admin_build_url( H4A_WGS_PAGE_SETTINGS, true, $form_plan_args );
		}
		$this->form->content[1]['rows'][3]['columns'][1]['items'][1]['href']             = $edit_plan_form_link;

	}

	/**
	 * Getters
	 */

	/**
	 * @param array $args
	 */
	protected function get_blank( $args = array() ){
		$current_user          = wp_get_current_user();
		$this->plan_id         = null;
		$this->plan_name       = "";
		$this->plan_type       = "single";
		$this->members_min     = 1;
		$this->members_max     = null;
		$this->start_date      = null;
		$this->duration_type   = null;
		$this->expiration_date = null;
		$this->duration_value  = null;
		$this->duration_type   = "unlimited";
		$this->status          = "draft";
		$this->price           = 00.00;
		$this->publish_date    = null;
		$this->user_nicename   = $current_user->user_nicename;
		$this->options         = null;
	}

	protected function get_item_to_edit( $data ){
		wp_debug_log();
		if( !empty($data['plan_id'] ) )
			$this->plan_id = $data['plan_id'];
		$this->user_id   = ( !empty($data['plan_id'] ) )      ? null : get_current_user_id();
		$this->plan_name = sanitize_text_field( $data['plan_title'] );
		$this->post_id   = ( !empty($data['plan_id'] ) )      ? $data['wgs_f_post_id'] : null;;
		$this->price     = ( !empty( $data['wgs_f_price'] ) ) ? $data['wgs_f_price'] : "0.00" ;
		$duration_type   = null;
		$duration_value  = null;
		$expiration_date = null;
		if( $data['wgs_f_plan_duration'] === "unlimited" ){
			$duration_type   = $data['wgs_f_plan_duration'];
		}else if( $data['wgs_f_plan_duration'] === "delay" ){
			$duration_type   = $data['wgs_f_plan_duration_time_type'];
			$duration_value  = $data['wgs_f_plan_duration_number'];
		}else if( $data['wgs_f_plan_duration'] === "date" ){
			$duration_type   = $data['wgs_f_plan_duration'];
			$expiration_date = date("Y-m-d", strtotime( $data['wgs_f_plan_duration_date'] ) );
		}
		$this->duration_type   = sanitize_text_field( $duration_type );
		$this->duration_value  = $duration_value;
		$this->expiration_date = $expiration_date;
		$this->plan_type       = sanitize_text_field( $data['wgs_f_plan_type'] );
		$this->members_min     = $data['wgs_f_number_min_member_accounts'];
		$this->members_max     = ( $this->plan_type === "multiple" ) ? $data['wgs_f_number_max_member_accounts'] : null;
		$this->form_type_id    = $data['wgs_f_plan_form'];
		if ( is_plugin_active( 'wgs-plan-edition-plus-addon/WGSPlanEditionPlusAddon.php' ) ) {
			$res_options = apply_filters( 'wgs_get_only_plan_options', $data );
			if( !$res_options['success'] ){
				wp_error_log( "The function 'wgs_get_only_options' failed!" );
			}else{
				$this->options = $res_options['data'];
			}
		}
		$status = null;
		if( isset( $data['publish'] ) ){
			$status = "published";
		}else if ( isset( $data['save'] ) ){
			if( isset( $data['plan_status'] ) ){
				$status = $data['plan_status'];
			}else{
				$status = "draft";
			}
		}
		$this->status = $status;
	}

	protected function get_item_to_list( $data ){
		$this->plan_id         = $data['plan_id'];
		$this->plan_name       = $data['plan_name'];
		$this->plan_type       = $data['plan_type'];
		$this->start_date      = $data['start_date'];
		$this->duration_type   = ( isset( $data['duration_type'] ) )   ? $data['duration_type'] : null;
		$this->expiration_date = ( isset( $data['expiration_date'] ) ) ? $data['expiration_date'] : null;
		$this->duration_value  = ( isset( $data['duration_value'] ) )  ? $data['duration_value'] : null;
		$this->status          = $data['status'];
		$this->price           = $data['price'];
		$this->publish_date    = $data['publish_date'];
		$this->user_nicename   = $data['user_nicename'];
	}

	protected function get_item( $id ){
		wp_debug_log();
		global $wpdb;

		// Start query string
		$query_string       = "SELECT * FROM {$wpdb->prefix}" . $this->current_plugin_prefix . "plans WHERE plan_id = " . $id ;

		// Return results
		$results = $wpdb->get_results( $query_string, ARRAY_A );

		if(count($results) === 0){
			wp_error_log( "Plan not found!");
		}else{
			foreach ( $results[0] as $column_name => $value ){
				if( is_float_as_string( $value ) ){
					$value = (float) $value;
				}else if( is_number( $value ) ){
					$value = (int) $value;
				}
				if(  property_exists( __CLASS__ , $column_name ) )
					$this->$column_name = $value;
			}
			if( !is_admin() && ! function_exists( 'is_plugin_active' ) )
				require_once( ABSPATH . 'wp-admin/includes/plugin.php');
			if( is_plugin_active( 'wgs-plan-edition-plus-addon/WGSPlanEditionPlusAddon.php' ) ){
				if( has_filter( "wgs_get_only_plan_options_not_blank" ) ){
					$data_options = apply_filters( "wgs_get_only_plan_options_not_blank", $results[0] );
					$this->options =  ( !empty( $data_options ) ) ? $data_options : null;
				}else if( !isset( $_GET['page'] ) || $_GET['page'] !== "logs-wp-group-subscriptions" ){
					$error_message = "the function/filter 'wgs_get_only_plan_options_not_blank' does not exist";
					wp_error_log( $error_message );
					/*wp_die( $error_message );
					exit;*/
				}
			}
		}
	}

	protected function get_item_to_read( $data ){
		wp_debug_log();

		foreach ( $data as $attr => $value ){
			if( is_float_as_string( $value ) ){
				//wp_info_log( "float : " . $value );
				$value = (float) $value;
			}else if( is_number( $value ) ){
				//wp_info_log( "number : " . $value );
				$value = (int) $value;
			}/*else if( is_string( $value ) ){
			    //wp_info_log( "string : " . $value );
		    }*/
			if(  property_exists( __CLASS__ , $attr ) )
				$this->$attr = $value;
		}
		if( !is_admin() && ! function_exists( 'is_plugin_active' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php');
		if( is_plugin_active( 'wgs-plan-edition-plus-addon/WGSPlanEditionPlusAddon.php' ) ){
			$data_options = apply_filters( "wgs_get_only_plan_options_not_blank", $data );
			$this->options =  ( !empty( $data_options ) ) ? $data_options : null;
		}
	}

	/**
	 * CRUD functions
	 */

	public function save(){
		wp_debug_log();
		$output = array(
			'success' => false
		);

		global $wpdb;

		if( !empty( $this->plan_id ) ){
			wp_error_log( sprintf( "This %s has got an id ! Please update it instead of save it.", $this->params->name ) );
			$message_error  = sprintf( __( "%s '%s' already exists!", "editable_item", $this->current_plugin_domain ), __( "The " . $this->params->name, "editable_item", $this->current_plugin_domain ), $this->plan_name );
			Notices::setNotice( $message_error, "error", true );
		}else if( $this->getPlanByName() !== false ){
			$message_error = sprintf( __( "The plan '%s' already exists!" ), $this->plan_name );
			Notices::setNotice( $message_error, "error", true );
		}else{
			$a_post = array(
				'post_type'      => "wgs-form-page",
				'post_status'    => ( $this->status === "published" ) ? "publish" : "draft",
				'post_title'     => $this->plan_name,
				'post_content'   => sprintf("[wgs-form][/wgs-form]" ),
				'comment_status' => "closed",
				'ping_status'    => "closed",
				'post_author'    => 1,
				'guid'           => null
			);
			$post_id = wp_insert_post( $a_post, true);
			if( is_wp_error( $post_id ) ) {
				wp_error_log( $post_id->get_error_message());
				Notices::setNotice( wp_get_error_system(), "error", true );
			}else{
				$data = array(
					'plan_name'       => $this->plan_name,
					'post_id'         => $post_id,
					'user_id'         => $this->user_id,
					'plan_type'       => $this->plan_type,
					'form_type_id'    => $this->form_type_id,
					'members_max'     => $this->members_max,
					'members_min'     => $this->members_min,
					'start_date'      => get_today_as_datetime(),
					'duration_type'   => $this->duration_type,
					'expiration_date' => $this->expiration_date,
					'duration_value'  => $this->duration_value,
					'status'          => $this->status,
					'price'           => $this->price,
					'publish_date'    => ( $this->status === "published" ) ? get_today_as_datetime() : null
				);
				if ( !empty( $this->options ) && is_plugin_active( 'wgs-plan-edition-plus-addon/WGSPlanEditionPlusAddon.php' ) ) {
					$data = apply_filters( "wgs_format_more_save_plan_data", $data, $this->options );
				}
				$res_ins = $wpdb->insert( $wpdb->prefix . $this->current_plugin_prefix . "plans", $data );
				if( !$res_ins ){
					$message_error = sprintf( _x( "%s '%s' could not be saved!", "editable_item", $this->current_plugin_domain ), _x( "The " . $this->params->name, "editable_item", $this->current_plugin_domain ), $this->plan_name );
					wp_error_log( $message_error );
					Notices::setNotice( $message_error, "error", true );
				}else{
					$plan_id = $wpdb->insert_id;
					$output['success'] = true;
					$output['data'] = array( 'plan_id' => $plan_id, 'post_id' => $post_id ); // plan_id and post_id
				}
			}
		}
		return $output;
	}

	public function update(){

		$output = array(
			'success' => false
		);

		$errors = array();

		if( empty( $this->plan_id ) ){
			wp_error_log( sprintf( "This %s has not got an id ! Please save it instead of update it.", $this->params->name ) );
			$message_error  = sprintf( __( " %s ( id : '%s' ) does not already exist to update it!", "editable_item", $this->current_plugin_domain ), __( "The " . $this->params->name, "editable_item", $this->current_plugin_domain ), $this->plan_id );
			$errors[] = $message_error;
		}else{
			$res_update_post = $this->update_post();
			if( !$res_update_post['success'] ){
				Notices::setNotices( $res_update_post['errors'], "error", true );
			}else{

				$data = array(
					'plan_name'       => $this->plan_name,
					'duration_type'   => $this->duration_type,
					'expiration_date' => $this->expiration_date,
					'duration_value'  => $this->duration_value,
					'status'          => $this->status,
					'price'           => $this->price
				);
				if ( !empty( $this->options ) && is_plugin_active( 'wgs-plan-edition-plus-addon/WGSPlanEditionPlusAddon.php' ) ) {
					$data = apply_filters( "wgs_format_more_save_plan_data", $data, $this->options );
				}
				$subscribers = self::getSubscribersByPlanId( (int) $_GET['pl'] );
				if( !$subscribers ){
					$data['plan_type'] = $this->plan_type;
					$data['form_type_id'] = $this->form_type_id;
					$data['members_max'] = $this->members_max;
					$data['members_min'] = $this->members_min;
				}else{
					$message_error = "Impossible to update the plan_type, form_type_id, members_max, members_min if there are subscribers linked to this plan.";
					wp_error_log( $message_error );
				}
				$res_update_plan = $this->update_item( "plans", $data, $this->plan_id );
				if( !$res_update_plan['success'] ){
					Notices::setNotices( $res_update_plan['errors'], "error", true );
				}else{
					$output['success'] = true;
					$plan_id = $res_update_plan['data'];
					$output['data'] = $plan_id; //plan_id
				}
			}
		}

		return $output;
	}

	public function trash(){
		wp_debug_log();
		$error_message = sprintf( __( "Impossible to trash '%s'!", $this->current_plugin_domain ), $this->plan_name );
		$res_update_trash = $this->updateStatus( "trash", $error_message );
		return $res_update_trash;
	}

	public function untrash(){
		wp_debug_log();
		$error_message = sprintf( __( "Impossible to restore '%s' from the trash!", $this->current_plugin_domain ), $this->plan_name );
		$res_update_untrash = $this->updateStatus( "draft", $error_message );
		return $res_update_untrash;
	}

	public function delete(){
		$output = array(
			'success' => false
		);

		global $wpdb;

		$res_del = $wpdb->delete( $wpdb->prefix.$this->current_plugin_prefix . "plans" ,array( 'plan_id' => $this->plan_id ) );
		if( $res_del === false || $res_del === 0 ){
			$message_error =  sprintf( __( "Impossible to delete %s '%s'!", $this->current_plugin_domain ), _x( "the plan", "message_item_name", $this->current_plugin_domain), $this->plan_name );
			wp_error_log( $message_error );
			Notices::setNotice( $message_error, "error", true );
		}else{
			$post = get_page_by_title( $this->plan_name, OBJECT, "wgs-form-page" );
			$delete_post_id = wp_delete_post( $post->ID  );
			if( $delete_post_id === false ){
				$message_error =  sprintf( __( "Impossible to delete the wgs-form-page post ( id : '%s' )!", $this->current_plugin_domain ), $post->ID );
				wp_error_log( $message_error );
				Notices::setNotice( $message_error, "error", true );
			}
		}
		if( Notices::isNoErrors() ){

			$message_success = sprintf( __( "%s '%s' has been deleted.", $this->current_plugin_domain ), _x( "The plan", "message_item_name", $this->current_plugin_domain ), $this->plan_name );
			Notices::setNotice( $message_success, "success", true );

			$output['success'] = true;
			$output['data'] = $this->plan_id;
		}
		return $output;

	}

	/**
	 * Additional CRUD functions
	 */

	private function update_post(){

		$output = array(
			'success' => false
		);

		$errors = array();

		$post = get_post( $this->post_id );

		if( empty( $post ) ){
			wp_error_log( sprintf( "Post '%s' not found!", $this->post_id ) );
			$errors[] = wp_get_error_back_end_system();
		}else{
			$post_status = ( $this->status === "published" ) ? "publish" : "draft" ;
			$query = array(
				'ID' => $post->ID,
				'post_status' => $post_status
			);

			if( $this->plan_name !== $post->post_title ){
				$query['post_title']     = $this->plan_name;
				$post_name = format_str_to_kebabcase( $this->plan_name, true );
				$query['post_name']      = $post_name;
				$query['guid']           = get_site_url() . "/wgs-form-page/" . $post_name ;
			}

			$res_update = wp_update_post( $query, true );
			if( $res_update === 0){
				wp_error_log( sprintf( "Post '%s' could not be updated!", $this->post_id ));
				$errors[] = wp_get_error_back_end_system();
			}else{
				$output['success'] = true;
				$output['data'] = $res_update; //post id
			}

		}

		if( !empty( $errors ) ){
			$output['errors'] = $errors;
		}

		return $output;
	}

	/**
	 * Additional Getters
	 */

	/**
	 * @param string $format
	 *
	 * @return bool
	 */
	private function getPlanByName( $format = "array" ){
		wp_debug_log();
		global $wpdb;

		$query = "SELECT * FROM {$wpdb->prefix}" . $this->current_plugin_prefix . "plans WHERE plan_name = '" . $this->plan_name . "';";

		$results = $wpdb->get_results( $query, ARRAY_A );

		if( count($results) === 1 ){
			if( $format === "array"){
				return $results[0];
			}else if( $format === "object" ){
				new Plan( $results[0] );
				return true;
			}else{
				wp_error_log( "Invalid format : " . $format  );
				return false;
			}
		}else{
			return false;
		}

	}

	public static function getPlans( $format = "array", $status = "published" ){
		global $wpdb;

		// Start query string
		$plans_query_string       = "SELECT * FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "plans WHERE status = '" . $status . "' ";

		$results = $wpdb->get_results( $plans_query_string, ARRAY_A );

		if( !empty( $results ) ){
			if( $format === "array"){
				return $results;
			}else if( $format === "object" ){
				$a_plans = array();
				foreach ( $results as $result ){
					$plan = new Plan( $result, "read" );
					$a_plans[] = $plan;
				}
				return $a_plans;
			}else if( $format === "options" ){
				$a_plans = array();
				foreach ( $results as $result ){
					$a_plans[] = array( 'label' => $result['plan_name'], 'value' => $result['plan_id']);
				}
				return $a_plans;
			}else{
				wp_error_log( "Invalid format : " . $format  );
				return false;
			}
		}else{
			return false;
		}
	}

	/**
	 * @param $plan_name
	 *
	 * @return bool|int
	 */
	public static function getPlanIdByName ( $plan_name ){

		global $wpdb;

		$query = "SELECT plan_id FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "plans WHERE plan_name = '" . $plan_name . "';";

		$results = $wpdb->get_results( $query, ARRAY_A );

		if( count($results) === 1 ){
			return (int) $results[0]['plan_id'];
		}else{
			return false;
		}

	}

	public static function getPlanTypeById ( $plan_id ){

		global $wpdb;

		$query = "SELECT plan_type FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "plans WHERE plan_id = '" . $plan_id . "';";

		$results = $wpdb->get_results( $query, ARRAY_A );

		if( count($results) === 1 ){
			return $results[0]['plan_type'];
		}else{
			return false;
		}

	}

	public static function getPlanTypeByName ( $plan_name ){

		global $wpdb;

		$query = "SELECT plan_type FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "plans WHERE plan_name = '" . $plan_name . "';";

		$results = $wpdb->get_results( $query, ARRAY_A );

		if( count($results) === 1 ){
			return $results[0]['plan_type'];
		}else{
			return false;
		}

	}

	/**
	 * @param int $plan_id
	 *
	 * @return array|bool
	 */
	public static function getSubscribersByPlanId( int $plan_id ) {

		global $wpdb;

		$query = "SELECT * FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "subscribers WHERE plan_id = " . $plan_id . ";";

		$results = $wpdb->get_results( $query, ARRAY_A );

		if( count($results) > 0 ){
			$output = array();
			foreach ( $results as $result ){
				$output[] = new Subscriber( $result, "read" );
			}
			return $output;
		}else{
			return false;
		}

	}

	public static function getInterval( $plan_id ) {
		$plan = new Plan( (int) $plan_id, "read" );
		$f_interval = array();
		for ( $e = $plan->members_min; $e <= $plan->members_max; $e++){
			$f_interval[$e] = $e;
		}
		return $f_interval;
	}

	public static function getIntervalByPlanType( $plan_type, $opt_unlimited = false ) {
		wp_debug_log();
		$f_interval = array();
		if( $plan_type === "single" ){
			$f_interval[1] = 1;
		}else if( $plan_type === "multiple"){
			$ceiling_number_members = 3;
			if ( is_plugin_active( "wgs-plan-edition-plus-addon/WGSPlanEditionPlusAddon.php" ) ) {
				if( has_filter( "wgs_set_members_interval" ) ){
					$f_interval = apply_filters( "wgs_set_members_interval", $f_interval, $opt_unlimited  );
				}else{
					$error_message = "the function/filter 'wgs_set_members_interval' does not exist";
					wp_error_log( $error_message );
					wp_die( $error_message );
					exit;
				}
				$ceiling_number_members = apply_filters( "wgs_get_ceiling_number_members" );
			}

			for ( $e = 2; $e <= $ceiling_number_members ; $e++){
				$f_interval[$e] = $e;
			}
		}
		return $f_interval;
	}

	public static function getIntervalByPlanTypeByAjax() {
		wp_debug_log();
		$opt_unlimited = ( isset( $_POST['opt_unlimited'] ) && (bool) $_POST['opt_unlimited'] ) ?  $_POST['opt_unlimited'] : false ;
		$f_interval = self::getIntervalByPlanType( $_POST['plan_type'], $opt_unlimited );
		$json = json_encode( $f_interval );
		echo $json;
		wp_die();
	}

	public static function getFormTypeIdByPlanId( $plan_id ){
		wp_debug_log();
		global $wpdb;

		$query = "SELECT form_type_id FROM {$wpdb->prefix}". get_current_plugin_prefix() ."plans WHERE plan_id = '".$plan_id."';";

		$results = $wpdb->get_results( $query, ARRAY_A );
		if( count($results) === 1 ){

			return (int) $results[0]['form_type_id'];
		}else{
			return false;
		}
	}

	/**
	 * Additional checking functions
	 */

	public function is_active(){
		return ( in_array( $this->status, array( "published", "hidden" ) ) );
	}

	/**
	 * @param $last_activation
	 *
	 * @return bool|array true or false if plan expired or not or array of errors
	 */
	public function checkExpirationPlan( $last_activation ){
		wp_debug_log();
		$output = array(
			'success' => false
		);

		$errors = array();

		$plan_expiration_date = null;

		$plan_expired = true;

		switch ( $this->duration_type ){

			case "day" :
			case "month" :
			case "year" :
				$is_date_expired = is_date_expired_by_duration( $last_activation, $this->duration_value, $this->duration_type );
				if(is_bool( $is_date_expired )){
					$plan_expired = $is_date_expired;
				}else{
					wp_error_log( $is_date_expired );
					$errors[] = wp_get_error_system();
				}
				break;
			case "date" :
				$is_date_expired = is_date_expired_by_date( $this->expiration_date );
				if( is_bool( $is_date_expired )){
					$plan_expired = $is_date_expired;
				}else{
					wp_error_log( $is_date_expired );
					$errors[] = wp_get_error_system();
				}
				break;

			case "unlimited" :
				$plan_expired = false;
				break;
		}

		if( empty( $errors ) ){
			$output['success'] = true;
			$output['data'] = $plan_expired; //true or false
		}else{
			$output['errors'] = $errors;
		}

		return $output;
	}

}
