<?php

/********************************************************/
/*              PLEASE DON'T MOVE THIS FILE             */
/********************************************************/

namespace H4APlugin\WPGroupSubs\Admin\Settings;


use H4APlugin\Core\Admin\Settings;
use H4APlugin\Core\Common\Currencies;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\is_license_activated;
use function H4APlugin\Core\wp_debug_log;

class Options extends Settings
{
	private $current_plugin_domain;

	public function __construct()
	{
		wp_debug_log();
		$this->current_plugin_domain = get_current_plugin_domain();
		$setting_section = ( isset( $_GET['tab'] ) ) ? $_GET['tab'] : "currency";
		parent::__construct(H4A_OPTIONS_GROUP, $setting_section );
	}

	protected function add_settings(){

		/*
		 * Currency
		 */
		$currency_section_id = H4A_SETTING_SECTION . "currency";

		add_settings_section(
			$currency_section_id, // ID
			__( "Currency", $this->current_plugin_domain ), // Title
			array( $this, "print_section_info" ), // Callback
			H4A_WGS_PAGE_SETTINGS // Page
		);

		add_settings_field(
			"currency", // ID
			__("Currency", $this->current_plugin_domain ), // Title
			array( $this, "currency_callback" ), // Callback
			H4A_WGS_PAGE_SETTINGS, // Page
			$currency_section_id // Section
		);

		add_settings_field(
			"currency_position", // ID
			__("Currency position", $this->current_plugin_domain ), // Title
			array( $this, "currency_position_callback" ), // Callback
			H4A_WGS_PAGE_SETTINGS, // Page
			$currency_section_id // Section
		);

		/*
		 * Paypal
		 */
		$paypal_section_id = H4A_SETTING_SECTION . "paypal";

		add_settings_section(
			$paypal_section_id, // ID
			__( "Paypal", $this->current_plugin_domain ), // Title
			array( $this, "print_section_info" ), // Callback
			H4A_WGS_PAGE_SETTINGS // Page
		);

		add_settings_field(
			"paypal_renew", // ID
			__("Renew payment Paypal option", $this->current_plugin_domain ), // Title
			array( $this, "paypal_renew_callback" ), // Callback
			H4A_WGS_PAGE_SETTINGS, // Page
			$paypal_section_id // Section
		);

		add_settings_field(
			"paypal_environment", // ID
			__("Paypal environment", $this->current_plugin_domain ), // Title
			array( $this, "paypal_environment_callback" ), // Callback
			H4A_WGS_PAGE_SETTINGS, // Page
			$paypal_section_id // Section
		);

		add_settings_field(
			"paypal_email", // ID
			__("Paypal email address", $this->current_plugin_domain ), // Title
			array( $this, "paypal_email_callback" ), // Callback
			H4A_WGS_PAGE_SETTINGS, // Page
			$paypal_section_id // Section
		);

		add_settings_field(
			"paypal_pdt_token", // ID
			__("Paypal PDT identity token", $this->current_plugin_domain ), // Title
			array( $this, "paypal_identity_token_callback" ), // Callback
			H4A_WGS_PAGE_SETTINGS, // Page
			$paypal_section_id// Section
		);

		add_settings_field(
			"paypal_api_username", // ID
			__("Paypal API Username", $this->current_plugin_domain ), // Title
			array( $this, "paypal_api_username_callback" ), // Callback
			H4A_WGS_PAGE_SETTINGS, // Page
			$paypal_section_id// Section
		);

		add_settings_field(
			"paypal_api_password", // ID
			__("Paypal API Password", $this->current_plugin_domain ), // Title
			array( $this, "paypal_api_password_callback" ), // Callback
			H4A_WGS_PAGE_SETTINGS, // Page
			$paypal_section_id// Section
		);

		add_settings_field(
			"paypal_api_signature", // ID
			__("Paypal API Signature", $this->current_plugin_domain ), // Title
			array( $this, "paypal_api_signature_callback" ), // Callback
			H4A_WGS_PAGE_SETTINGS, // Page
			$paypal_section_id// Section
		);

		/*
		 * Profile Page
		 */

		$profile_section_id = H4A_SETTING_SECTION . "profile-page";

		add_settings_section(
			$profile_section_id, // ID
			__( "Profile Page", $this->current_plugin_domain ), // Title
			array( $this, "print_section_info" ), // Callback
			H4A_WGS_PAGE_SETTINGS // Page
		);

		add_settings_field(
			"profile_page", // ID
			__("Page", $this->current_plugin_domain ), // Title
			array( $this, "profile_page_callback" ), // Callback
			H4A_WGS_PAGE_SETTINGS, // Page
			$profile_section_id // Section
		);

		/*
		 * Premium
		 */
		$premium_section_id = H4A_SETTING_SECTION . "premium";

		add_settings_section(
			$premium_section_id, // ID
			__( "Premium", $this->current_plugin_domain ), // Title
			array( $this, "print_section_info" ), // Callback
			H4A_WGS_PAGE_SETTINGS // Page
		);

		add_settings_field(
			"wgs_license_key", // ID
			__("License key", $this->current_plugin_domain ), // Title
			array( $this, "wgs_license_key_callback" ), // Callback
			H4A_WGS_PAGE_SETTINGS, // Page
			$premium_section_id // Section
		);


	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 *
	 * @return array
	 */
	public function sanitize( $input )
	{
		$new_input = array();
		if( isset( $input['currency'] ) )
			$new_input['currency'] = sanitize_text_field( $input['currency'] );
		if( isset( $input['currency_position'] ) )
			$new_input['currency_position'] = sanitize_text_field( $input['currency_position'] );
		$new_input['paypal_environment'] = $input['paypal_environment'];
		if( isset( $input['paypal_environment'] ) )
			$new_input['paypal_environment'] = sanitize_text_field( $input['paypal_environment'] );
		if( isset( $input['paypal_email'] ) )
			$new_input['paypal_email'] = sanitize_email( $input['paypal_email'] );
		if( isset( $input['paypal_pdt_token'] ) )
			$new_input['paypal_pdt_token'] =  sanitize_text_field( $input['paypal_pdt_token'] );
		if( isset( $input['paypal_api_username'] ) )
			$new_input['paypal_api_username'] = sanitize_text_field( $input['paypal_api_username'] );
		if( isset( $input['paypal_api_password'] ) )
			$new_input['paypal_api_password'] = sanitize_text_field( $input['paypal_api_password'] );
		if( isset( $input['paypal_api_signature'] ) )
			$new_input['paypal_api_signature'] = sanitize_text_field( $input['paypal_api_signature'] );
		if( isset( $input['profile_page'] ) )
			$new_input['profile_page'] = $input['profile_page']; //number
		if( isset( $input['wgs_license_key'] ) )
			$new_input['wgs_license_key'] = sanitize_text_field( $input['wgs_license_key'] );

		$new_input = apply_filters_ref_array( "h4a_settings_sanitize", array( $new_input, $input ) );
		return $new_input;
	}

	/**
	 * Print the Section text
	 */
	public function print_section_info()
	{
		//print "Enter your settings below:";
		/*$wp_currency_options = get_option( "wgs-currency-options");
		$wp_paypal_options = get_option( "wgs-paypal-options");
		$wp_recaptcha_options = get_option( "wgs-recaptcha-options");
		var_dump( $wp_currency_options );
		var_dump( $wp_paypal_options );
		var_dump( $wp_recaptcha_options );
		global $wp_settings_sections;
		var_dump( $wp_settings_sections[H4A_WGS_PAGE_SETTINGS] );*/
	}

	public function currency_callback()
	{
		echo '<select id="currency" name="wgs-currency-options[currency]" >';
		$currencies = Currencies::getCurrencies();
		foreach( $currencies as $currency ){
			$is_selected = ( isset( $this->current_options['currency'] ) && $this->current_options['currency'] === $currency['iso'] ) ? true : false ;
			printf ( '<option value="%s" %s >%s</option>', $currency['iso'],  $is_selected  ? 'selected="true"' : null, $currency['name'] );
		}
		echo "</select>";
	}

	public function currency_position_callback()
	{
		$this->current_options['currency_position'] = ( !empty( $this->current_options['currency_position']  ) ) ? $this->current_options['currency_position'] : "before";
		printf( '<input type="radio" id="before_currency" name="wgs-currency-options[currency_position]" value="before" %s/>', $this->current_options['currency_position'] === "before"  ? 'checked="checked"' : null );
		echo '<label for="before_currency">'.__( "Before the amount", $this->current_plugin_domain ).'</label>';
		printf( '<input type="radio" id="after_currency" name="wgs-currency-options[currency_position]" value="after" style="margin-left: 15px;" %s/>', $this->current_options['currency_position'] === "after"  ? 'checked="checked"' : null );
		echo '<label for="after_currency">'.__( "After the amount", $this->current_plugin_domain ).'</label>';
	}

	public function paypal_renew_callback()
	{
		$this->current_options['paypal_renew'] = ( !empty( $this->current_options['paypal_renew']  ) ) ? $this->current_options['paypal_renew'] : "";
		printf( '<input type="checkbox" id="paypal_renew" name="wgs-paypal-options[paypal_renew]" value="true" %s/>', $this->current_options['paypal_renew'] === "true"  ? 'checked="checked"' : null );
		echo '<label for="paypal_renew">'.__( "Display in 'My Profile' for subscribers.", $this->current_plugin_domain ).'</label>';
	}

	public function paypal_environment_callback()
	{
		$this->current_options['paypal_environment'] = ( !empty( $this->current_options['paypal_environment']  ) ) ? $this->current_options['paypal_environment'] : "test";
		printf( '<input type="radio" id="test_environment" name="wgs-paypal-options[paypal_environment]" value="test" %s/>', $this->current_options['paypal_environment'] === "test"  ? 'checked="checked"' : null );
		echo '<label for="test_environment">'.__( "Test (sandbox)", $this->current_plugin_domain ).'</label>';
		printf( '<input type="radio" id="prod_environment" name="wgs-paypal-options[paypal_environment]" value="prod" style="margin-left: 15px;" %s/>', $this->current_options['paypal_environment'] === "prod"  ? 'checked="checked"' : null );
		echo '<label for="prod_environment">'.__( "Production mode", $this->current_plugin_domain ).'</label>';
	}

	public function paypal_email_callback()
	{
		printf(
			'<input type="email" id="paypal_email" name="wgs-paypal-options[paypal_email]" value="%s" style="width: 200px;"/>',
			!empty( $this->current_options['paypal_email'] ) ? esc_attr( $this->current_options['paypal_email']) : ""
		);
	}

	public function paypal_identity_token_callback(){
		printf(
			'<input type="text" id="paypal_pdt_token" name="wgs-paypal-options[paypal_pdt_token]" value="%s" style="width: 500px;"/>',
			!empty( $this->current_options['paypal_pdt_token'] ) ? esc_attr( $this->current_options['paypal_pdt_token']) : ""
		);
	}

	public function paypal_api_username_callback()
	{
		printf(
			'<input type="text" id="paypal_api_username" name="wgs-paypal-options[paypal_api_username]" value="%s" style="width: 300px;"/>',
			!empty( $this->current_options['paypal_api_username'] ) ? esc_attr( $this->current_options['paypal_api_username']) : ""
		);
	}

	public function paypal_api_password_callback()
	{
		printf(
			'<input type="text" id="paypal_api_password" name="wgs-paypal-options[paypal_api_password]" value="%s" style="width: 200px;"/>',
			!empty( $this->current_options['paypal_api_password'] ) ? esc_attr( $this->current_options['paypal_api_password']) : ""
		);
	}

	public function paypal_api_signature_callback()
	{
		printf(
			'<input type="text" id="paypal_api_signature" name="wgs-paypal-options[paypal_api_signature]" value="%s" style="width: 500px;"/>',
			!empty( $this->current_options['paypal_api_signature'] ) ? esc_attr( $this->current_options['paypal_api_signature']) : ""
		);
	}

	public function profile_page_callback()
	{
		$pages = get_pages();
		//var_dump( $this->current_options );
		echo '<select id="profile-page" name="wgs-profile-page-options[profile_page]" >';
		echo '<option value="0">' . __( "Default Plugin Page", $this->current_plugin_domain ) . '</option>';
		foreach( $pages as $page ){
			$is_selected = ( isset( $this->current_options['profile_page'] ) && (int) $this->current_options['profile_page'] === $page->ID ) ? true : false ;
			printf ( '<option value="%d" %s >%s</option>', $page->ID,  $is_selected  ? 'selected="true"' : null, $page->post_title );
		}
		echo "</select>";
	}

	public function wgs_license_key_callback()
	{
		$is_license_activated =  is_license_activated();
		printf(
			'<span id="wgs-license-icon-check" class="dashicons dashicons-%s" title="%s"> </span>',
			( $is_license_activated ) ? "yes" : "no",
			( $is_license_activated ) ? __( "Activated", $this->current_plugin_domain ) : __( "Deactivated", $this->current_plugin_domain )

		); //Notice : let space for XML Loading
		printf(
			'<input type="text" id="wgs_license_key" name="wgs-premium-options[wgs_license_key]" value="%s" style="width: 500px;" %s />',
			!empty( $this->current_options['wgs_license_key'] ) ? esc_attr( $this->current_options['wgs_license_key']) : "",
			( $is_license_activated ) ? 'readonly="readonly"' : null
		);
		if( !empty( $this->current_options['wgs_license_key'] ) ){
			if( $is_license_activated ){
				printf( '<input type="button" id="btn_deactivate" name="submit" value="%s" class="button-primary" />',
					__( "Deactivate", $this->current_plugin_domain )
				);
			}else{
				printf( '<input type="button" id="btn_activate" name="submit" value="%s" class="button-primary" />',
					__( "Activate", $this->current_plugin_domain )
				);
			}
		}
	}


}