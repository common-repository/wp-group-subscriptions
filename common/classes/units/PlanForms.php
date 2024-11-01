<?php

namespace H4APlugin\WPGroupSubs\Common;


use H4APlugin\Core\Common\CommonForm;
use H4APlugin\Core\Common\Countries;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\get_current_plugin_prefix;
use function H4APlugin\Core\wp_debug_log;

class PlanForms {

	public static function getAllPlanFormsAsOptions( $plan_type = 'single'){

		global $wpdb;

		$current_plugin_domain = get_current_plugin_domain();

		// Start query string
		$forms_query_string  =  "SELECT form_type_id, name FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "forms WHERE form_type = 'plan-subscription' AND plan_type = '" . $plan_type . "' ";

		// Return results
		$a_forms = $wpdb->get_results( $forms_query_string, ARRAY_A );
		$f_forms = array();
		$f_forms[null] =  __( 'Please select your form', $current_plugin_domain );
		foreach ($a_forms as $form){
			$f_forms[$form['form_type_id']] = __( $form['name'], $current_plugin_domain );
		}
		return $f_forms;

	}

	public static function getAllPlanFormsAsOptionsByAjax(){
		wp_debug_log();
		global $wpdb;

		$current_plugin_domain = get_current_plugin_domain();

		// Start query string
		$forms_query_string  =  "SELECT form_type_id, name FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "forms WHERE office = 'both' AND plan_type = '" . $_POST['plan_type'] . "' ";

		// Return results
		$a_forms = $wpdb->get_results( $forms_query_string, ARRAY_A );
		$f_forms = array();
		$f_forms[null] =  __( 'Please select your form', $current_plugin_domain );
		foreach ($a_forms as $form){
			$f_forms[$form['form_type_id']] = __( $form['name'], $current_plugin_domain );
		}
		$json = json_encode( $f_forms );
		echo $json;
		wp_die();
	}

	/**
	 *  Function that returns
	 *  if data are correct : an array with data prepared
	 *  if data are wrong : an array of errors
	 *
	 * @param array $data
	 * @param string $plan_type
	 * @param bool $isUpdate
	 *
	 * @return array
	 */
	public static function checkFormData( $data = array(), $plan_type = 'single', $isUpdate = false ){
		wp_debug_log();
		$current_plugin_domain = get_current_plugin_domain();

		$check_data = array(
			0 => array(
				'function' => "checkEmail",
				'data' => array(
					$data['wgs_f_email'],
					$data['wgs_f_email_r'],
					$isUpdate
				)
			),
			1 => array(
				'function' => "checkName",
				'data' => array(
					$data['wgs_f_first_name'],
					__( 'first name', $current_plugin_domain )
				)
			),
			2 => array(
				'function' => "checkName",
				'data' => array(
					$data['wgs_f_last_name'],
					__( 'last name', $current_plugin_domain )
				)
			),
			3 => array(
				'function' => "checkAddress",
				'data' => array(
					$data['wgs_f_street_name'],
					$data['wgs_f_zip_code'],
					$data['wgs_f_city'],
					$data['wgs_f_country'],
					( !empty( $data['wgs_f_street_number'] ) ) ? $data['wgs_f_street_number'] : null
				)
			)
		);

		if( !empty( $data['wgs_f_phone_code'] ) && !empty( $data['wgs_f_phone_number'] ) ){
			$check_data[] = array(
				'function' => "checkPhone",
				'data' => array(
					$data['wgs_f_phone_code'],
					$data['wgs_f_phone_number']
				)
			);
		}

		if( isset( $data['wgs_f_password'] ) ) {
			$check_data[] = array(
				'function' => "checkPassword",
				'data'     => array(
					$data['wgs_f_password'],
					$data['wgs_f_password_r']
				)
			);
		}
		if ( $plan_type === 'multiple'  ){
			$check_data[] = array(
				'function' => "checkGroupName",
				'data' => array(
					$data['wgs_f_group_name']
				)
			);
		}
		if( !is_admin() && ! function_exists( 'is_plugin_active' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php');
		if ( is_plugin_active( 'wgs-custom-forms-addon/WGSCustomFormsAddon.php' ) ) {
			$check_data = apply_filters('wgs_check_form_data', $check_data, $data, $plan_type );
		}
		//Results
		$results = CommonForm::get_form_results( $check_data );

		if( $results['success'] ){
			$f_data = array(
				'email' => htmlspecialchars( $data['wgs_f_email'] ),
				'first_name' => htmlspecialchars( $data['wgs_f_first_name'] ),
				'last_name' => htmlspecialchars( $data['wgs_f_last_name'] ),
				'phone_code' => ( !empty( $data['wgs_f_phone_code'] ) ) ? htmlspecialchars( substr( $data['wgs_f_phone_code'], 1 ) ) : null,
				'phone_number' => ( !empty( $data['wgs_f_phone_number'] ) ) ? htmlspecialchars( $data['wgs_f_phone_number'] ) : null,
				'street_number' => htmlspecialchars( $data['wgs_f_street_number'] ),
				'street_name' => htmlspecialchars( $data['wgs_f_street_name'] ),
				'zip_code' => htmlspecialchars( $data['wgs_f_zip_code'] ),
				'city' => htmlspecialchars( $data['wgs_f_city'] ),
				'country_id' => Countries::getCountryIdByIso( $data['wgs_f_country'] )
			);
			if( $plan_type === 'multiple' )
				$f_data['group_name'] = htmlspecialchars( $data['wgs_f_group_name'] );
			if( isset( $data['wgs_f_password'] ) )
				$f_data['password'] = htmlspecialchars( $data['wgs_f_password'] );
			if ( is_plugin_active( 'wgs-custom-forms-addon/WGSCustomFormsAddon.php' ) ) {
				$f_data = apply_filters('wgs_format_form_data', $f_data, $data, $plan_type );
			}
			$results['data'] = $f_data;
		}
		return $results;

	}

}