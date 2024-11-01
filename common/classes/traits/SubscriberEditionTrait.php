<?php

namespace H4APlugin\WPGroupSubs\Common;


use H4APlugin\Core\Common\CommonForm;
use H4APlugin\Core\Common\Countries;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\wp_build_url;
use function H4APlugin\Core\wp_debug_log;
use H4APlugin\WPGroupSubs\Shortcodes\MyProfileShortcode;

trait SubscriberEditionTrait {

	protected static function modifySubscriberForm( Subscriber $editable_subscriber ){
		wp_debug_log();
		$plan_id = (int) $editable_subscriber->plan_id;
		$plan_selected = new Plan( $plan_id, "read" );

		//To modify fields size, it's more efficient to do in JS : see wgs-subscriber.js
		$form_type_id = Plan::getFormTypeIdByPlanId( $plan_id );
		//Fill the form with data
		self::fillFormByDBData( $editable_subscriber, $plan_selected, $form_type_id );

		//When you create an account link to single plan, you insert a password, but it´s for the member
		//So we remove passwords inputs if the subscriber is already created
		self::hidePasswordInputs( $editable_subscriber->form );
		if( !is_admin() ){
			$editable_subscriber->form->options['submitBox'] = array( 'button' => "Save Changes" );
			$editable_subscriber->form->action = wp_build_url( MyProfileShortcode::getProfilePagePostType(), MyProfileShortcode::getProfilePageTitle() );
		}
	}

	/**
	 * @param $editable_subscriber
	 * @param $plan_selected
	 * @param $form_type_id
	 */
	private static function fillFormByDBData( $editable_subscriber ,$plan_selected, $form_type_id )
	{
		wp_debug_log();
		foreach ( $editable_subscriber as $key => $value) {
			$a_exclude = array("form", "nonce", "params", "subscriber_id", "plan_id", "status", "start_date", "plan_name", "last_subscription_date", "current_plugin_domain", "current_plugin_prefix", "as_member" );
			if ($plan_selected->plan_type === "single"){
				$a_exclude[] = "group_name";
			}else{
				$a_exclude[] = "as_member";
			}
			if (!in_array($key, $a_exclude)) {
				if ($key === "options") {
					if (is_plugin_active("wgs-custom-forms-addon/WGSCustomFormsAddon.php")) {
						$editable_subscriber->form->content = apply_filters("wgs_add_optional_data_in_editable_form", $editable_subscriber->form->content, $value, $form_type_id);
					}
				} else {
					$key = ($key === "country_id") ? "country" : $key;
					$res_pos = CommonForm::getFormItemPositionByHTMLName("wgs_f_" . $key, "plan-subscription", $form_type_id);
					if ($res_pos['success']) {
						switch ($key) {
							case "country" :
								$f_value = Countries::getIsoByCountryId($value);
								$editable_subscriber->form->content[$res_pos['data']['wrapper_pos']]['rows'][$res_pos['data']['row_pos']]['columns'][$res_pos['data']['col_pos']]['items'][0]['selected'] = $f_value;
								break;
							case "email":
								$editable_subscriber->form->content[$res_pos['data']['wrapper_pos']]['rows'][$res_pos['data']['row_pos']]['columns'][$res_pos['data']['col_pos']]['items'][0]['value'] = $value;
								$res_pos = CommonForm::getFormItemPositionByHTMLName("wgs_f_" . $key . "_r", "plan-subscription", $form_type_id);
								if ($res_pos['success']) {
									$editable_subscriber->form->content[$res_pos['data']['wrapper_pos']]['rows'][$res_pos['data']['row_pos']]['columns'][$res_pos['data']['col_pos']]['items'][0]['value'] = $value;
								}
								break;
							case "phone_code" :
								$f_value = (!empty($value)) ? "+" . $value : "";
								$editable_subscriber->form->content[$res_pos['data']['wrapper_pos']]['rows'][$res_pos['data']['row_pos']]['columns'][$res_pos['data']['col_pos']]['items'][0]['value'] = $f_value;
								$res_pos = CommonForm::getFormItemPositionByHTMLName("wgs_f_" . $key . "_sel", "plan-subscription", $form_type_id);
								if ($res_pos['success']) {
									$editable_subscriber->form->content[$res_pos['data']['wrapper_pos']]['rows'][$res_pos['data']['row_pos']]['columns'][$res_pos['data']['col_pos']]['items'][0]['selected'] = $value;
								}
								break;
							default :
								$editable_subscriber->form->content[$res_pos['data']['wrapper_pos']]['rows'][$res_pos['data']['row_pos']]['columns'][$res_pos['data']['col_pos']]['items'][0]['value'] = $value;
								break;
						}
					}
				}
			}
		}
	}

	/**
	 * @param $form
	 */
	private static function hidePasswordInputs( $form )
	{
		wp_debug_log();
		$a_passwords = array("wgs_f_password", "wgs_f_password_r");
		foreach ($a_passwords as $p => $html_name) {
			$res_pos = CommonForm::getFormItemPositionByHTMLName($html_name, "plan-subscription", $form->form_type_id );
			if ($res_pos['success']) {
				unset($form->content[$res_pos['data']['wrapper_pos']]['rows'][$res_pos['data']['row_pos']]['columns'][$res_pos['data']['col_pos']]);
				if( $p === 0 ){
					$current_plugin_domain = get_current_plugin_domain();
					$link_items = array(
						'items' => array(
							0 => array(
								'type' => "label",
								'label' => __( "Password", $current_plugin_domain ),
								'id' => "wgs_f_password",
								'required' => 1
							),
							1 => array(
								'type' => "button",
								'label' => __( "Change Password", $current_plugin_domain ),
								'id' => "wgs_change-password",
								'value' => "change"
							)
						)
					);
					$form->content[$res_pos['data']['wrapper_pos']]['rows'][$res_pos['data']['row_pos']]['columns'][$res_pos['data']['col_pos']] = $link_items;
				}
				array_values($form->content[$res_pos['data']['wrapper_pos']]['rows'][$res_pos['data']['row_pos']]['columns']);
				if (empty($form->content[$res_pos['data']['wrapper_pos']]['rows'][$res_pos['data']['row_pos']]['columns'])) {
					unset($form->content[$res_pos['data']['wrapper_pos']]['rows'][$res_pos['data']['row_pos']]);
					array_values($form->content[$res_pos['data']['wrapper_pos']]['rows']);
				}
				if (empty($form->content[$res_pos['data']['wrapper_pos']]['rows'])) {
					unset($form->content[$res_pos['data']['wrapper_pos']]);
					array_values($form->content);
				}
			}
		}
	}
}