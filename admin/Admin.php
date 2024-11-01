<?php

namespace H4APlugin\WPGroupSubs\Admin;

use H4APlugin\Core\Common\Notices;
use H4APlugin\Core\Admin\H4AAdminPlugin;
use H4APlugin\Core\Config;
use H4APlugin\Core\FrontEnd\FrontEndStorage;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\get_current_plugin_short_title;
use function H4APlugin\Core\wp_debug_log;
use H4APlugin\WPGroupSubs\Common\FormPages;
use H4APlugin\WPGroupSubs\Common\Plan;
use function H4APlugin\Core\get_protocol;
use function H4APlugin\Core\wp_admin_build_url;

class Admin extends H4AAdminPlugin {

	private $current_plugin_domain;

	private $current_plugin_short_title;

	public function __construct() {
		wp_debug_log();
		$this->current_plugin_domain = get_current_plugin_domain();
		$this->current_plugin_short_title = get_current_plugin_short_title();
		parent::__construct();
		add_action( "wp_ajax_getEmailSubscriberByAjax", array( $this, "getEmailSubscriberByAjax" ) );
		add_action( "wp_ajax_nopriv_getEmailSubscriberByAjax", array( $this, "getEmailSubscriberByAjax" ) );
	}

	protected function admin_init(){
        wp_debug_log();

        add_action( "admin_menu", array( $this, "rename_users") );

	    add_action( "admin_notices", array( $this, "display_notices") );

        add_action( "admin_enqueue_scripts", array( $this , "replace_core_jquery_version_and_styles" ) );

    }

    function rename_users() {
        //Rename "users" as "administrators"

        global $menu;
        global $submenu;
        $menu[70][0] = __( "Administrators", $this->current_plugin_domain );
        $menu[70][6] = "dashicons-businessman";
        $submenu['users.php'][5][0] = __( "All administrators", $this->current_plugin_domain );

    }

    public function display_notices(){
    	$this->display_warning_no_ssl();
    	$this->display_test_paypal_environment();
    	$this->display_warning_no_paypal_email();
    	$this->display_warning_no_paypal_identity_token();
    	$this->display_warning_no_currency();
    	$this->display_warning_no_recaptcha();
    	$this->display_warning_no_paypal_identity_token();
    	Notices::displayAll();
    }

    private function display_warning_no_ssl() {
        wp_debug_log();
        $formPages = FormPages::getFormPages();
        if( !empty( $formPages ) ){
            foreach ( $formPages as $formPage ){
                //pretty_var_dump( $formPage );
                //echo "<br/><br/>";
                $plan_id = Plan::getPlanIdByName( $formPage['post_title'] );
                $args = Config::get_item_by_ref( "plan" );
                $plan = new Plan( (int) $plan_id, "read", $args );
                if( !empty( $plan ) && (int) $plan->price > 0 ){
                    $permalink = get_post_permalink( $formPage['ID'] );
                    $protocol = get_protocol( $permalink );
                    if($protocol !== "https"){
	                    $message = sprintf(
	                            esc_html__( "Enabling the HTTPS protocol for the page : « %s » is not mandatory but strongly advised for security issues for your users!", $this->current_plugin_domain ),
                                $permalink
                        );
                        Notices::setNotice( $message, "warning", true );
                    }
                }
            }
        }
    }

    private function display_warning_no_paypal_email() {
	    wp_debug_log();
	    /**
         * Detect plugin. For use in Admin area only.
         */
        $wgs_paypal_options = get_option( "wgs-paypal-options" );
        if ( empty ( $wgs_paypal_options['paypal_email'] ) ) {
	        $args = array();
	        global $wp_settings_sections;
	        if( count( $wp_settings_sections[H4A_WGS_PAGE_SETTINGS] ) > 1 ){
		        $args['tab'] = "paypal";
	        }
	        $args['inp']                    = "paypal_email";
	        $message                        = sprintf(
		        __('Please enter %s in <a id="go-to-paypal-email-address-settings" href="%s">Settings >%s</a>', $this->current_plugin_domain ),
		        __("your paypal address email", $this->current_plugin_domain),
		        wp_admin_build_url( H4A_WGS_PAGE_SETTINGS, true, $args ),
		        $this->current_plugin_short_title
	        );
	        Notices::setNotice( $message, "warning" );
        }
    }

    private function display_warning_no_paypal_identity_token() {
	    wp_debug_log();
	    /**
         * Detect plugin. For use in Admin area only.
         */
        $wgs_paypal_options = get_option( "wgs-paypal-options" );
        if ( empty ( $wgs_paypal_options['paypal_pdt_token'] ) ) {
	        $args = array();
	        global $wp_settings_sections;
	        if( count( $wp_settings_sections[H4A_WGS_PAGE_SETTINGS] ) > 1 ){
		        $args['tab'] = "paypal";
	        }
	        $args['inp'] = "paypal_pdt_token";
            $message = sprintf(
	            __('Please enter %s in <a id="go-to-paypal-identity-token-settings" href="%s">Settings >%s</a>', $this->current_plugin_domain ),
	            __("the paypal identity token", $this->current_plugin_domain),
	            wp_admin_build_url( H4A_WGS_PAGE_SETTINGS, true, $args ),
	            $this->current_plugin_short_title
            );
	        Notices::setNotice( $message, "warning" );
        }
    }

    private function display_test_paypal_environment() {
	    wp_debug_log();
	    /**
         * Detect plugin. For use in Admin area only.
         */
        $wgs_paypal_options = get_option( "wgs-paypal-options" );
        if ( isset( $wgs_paypal_options['paypal_environment'] ) && $wgs_paypal_options['paypal_environment'] === "test" ) {
	        $args = array();
	        global $wp_settings_sections;
	        if( count( $wp_settings_sections[H4A_WGS_PAGE_SETTINGS] ) > 1 ){
		        $args['tab'] = "paypal";
	        }
	        $args['inp'] = "test_environment";
	        $message = sprintf(
		        __('Paypal environment : %s (sandbox) - To activate real financial transactions : <a id="go-to-paypal-environment" href="%s">Settings >%s</a>', $this->current_plugin_domain ),
		        $wgs_paypal_options['paypal_environment'],
		        wp_admin_build_url( H4A_WGS_PAGE_SETTINGS, true, $args ),
		        $this->current_plugin_short_title );
            Notices::setNotice( $message, "info", true );
        }
    }

    private function display_warning_no_currency() {
	    wp_debug_log();
	    /**
         * Detect plugin. For use in Admin area only.
         */
        $wgs_currency_options = get_option( "wgs-currency-options" );
        if ( empty ( $wgs_currency_options['currency'] ) ) {
	        $args = array();
	        global $wp_settings_sections;
	        if( count( $wp_settings_sections[H4A_WGS_PAGE_SETTINGS] ) > 1 ){
		        $args['tab'] = "currency";
	        }
	        $args['inp'] = "currency";
	        $message = sprintf(
		        __('Please choose a currency in <a id="go-to-currency-settings" href="%s">Settings >%s</a>', $this->current_plugin_domain ),
		        wp_admin_build_url( H4A_WGS_PAGE_SETTINGS, true, $args ),
		        $this->current_plugin_short_title
	        );
	        Notices::setNotice( $message, "warning" );
        }
    }

    private function display_warning_no_recaptcha() {
	    wp_debug_log();
	    /**
         * Detect plugin. For use in Admin area only.
         */
        if ( ! is_plugin_active( "wgs-recaptcha-addon/WGSRecaptchaAddon.php" ) ) {
            $addon_name = "WGS Recaptcha Addon";
            //plugin is activated
	        $message = sprintf( __( 'For the security of your users, you should use recaptcha thanks the addon « <a href="%s">%s</a> »', $this->current_plugin_domain), wp_admin_build_url( H4A_WGS_PAGE_SETTINGS, true, array( 'tab' => "recaptcha" ) ) , $addon_name );
	        Notices::setNotice( $message, "warning", true );
        }
    }

    public function replace_core_jquery_version_and_styles() {

        wp_admin_css();

        wp_deregister_script( "jquery-config" );
        wp_register_script( "jquery-config", "https://code.jquery.com/jquery-3.3.1.min.js", array(), "3.1.1" );
        wp_deregister_script( "jquery-migrate" );
        wp_register_script( "jquery-migrate", "https://code.jquery.com/jquery-migrate-1.4.0.min.js", array(), "3.0.0" );
    }

	public static function getEmailSubscriberByAjax(){
		wp_debug_log( "", "", "users" );
		if( !class_exists( "H4APlugin\Core\FrontEnd\FrontEndStorage" ) )
			include_once dirname( __FILE__ ) . "/../core/front-end/features/storage/FrontEndStorage.php";
		$front_end_storage = FrontEndStorage::get_user_data();
		$json = json_encode($front_end_storage);
		echo $json;
		wp_die();
	}
}
