<?php

namespace H4APlugin\WPGroupSubs\Admin\Settings;

use H4APlugin\Core\Admin\SettingsTemplate;
use function H4APlugin\Core\wp_debug_log;
use H4APlugin\WPGroupSubs\Common\Payment;

class Paypal extends SettingsTemplate {

	public function set_additional_scripts() {
		wp_debug_log();

		wp_enqueue_script( "wgssettings", H4A_WGS_PLUGIN_DIR_URL . "admin/headings/settings/js/settings.js" );
		wp_localize_script( "wgssettings", "wgsSettingTranslation", self::getWGSSettingTranslation() );

		wp_enqueue_style( "wgsmodalpremiumstyle", H4A_WGS_PLUGIN_DIR_URL . "admin/headings/settings/css/wgs-settings.css" );
	}

	public function write_aside_content() {
        $return_url = str_replace( "&", "&amp;", Payment::getReturnPageUrl() );

        $aside = "<aside style='max-height: 500px; overflow: auto;'>";
        $aside .= "<h3>". __( "Paypal configuration", $this->current_plugin_domain )."</h3>";
        $aside .= "<p>". __( "To make the payment system work correctly with Paypal, please log in your Paypal account and follow the instructions below.", $this->current_plugin_domain )."</p>";
        $aside .= "<h4>". __( "How to enable payments with Paypal ?", $this->current_plugin_domain )."</h4>";
        $aside .= "<ul>";
        $aside .= "<li>". __( "First of all, check if you Paypal account is ready.", $this->current_plugin_domain )."</li>";
        $aside .= "<li>". __( "Then, configure the Website Preferences : Profile > Account Seettings > My selling tools > Website preferences", $this->current_plugin_domain );
        $aside .= "<ol>";
        $aside .= "<li>". __( "Under the Selling Online section, click the Update link in the row for Website Preferences. The Website Payment Preferences page appears.", $this->current_plugin_domain ) . "</li>";
        $aside .= "<li>". __( "Under Auto Return for Website Payments, click the On radio button to <strong>enable Auto Return</strong>.", $this->current_plugin_domain ) . "</li>";
        $aside .= "<li>". sprintf( __( 'In the Return URL field, <strong>please enter the URL : <input type="text" readonly="readonly" style="width: 400px;" value="%s"/></strong>', $this->current_plugin_domain ), $return_url) . "</li>";
        $aside .= "<li>". __( "Under Payment Data Transfer (optional), click the On radio button to <strong>enable Payment Data Transfer</strong>.", $this->current_plugin_domain) . "</li>";
        $aside .= "<li>". __( "Save it!", $this->current_plugin_domain) . "</li>";
        $aside .= "<li>". sprintf( __( "Under Payment Data Transfer (optional), <strong>your identity token appeared, please copy and paste it in %s</strong>.", $this->current_plugin_domain ), __( "Paypal PDT identity token", $this->current_plugin_domain) ) . "</li>";
        $aside .= "</ol>";
        $aside .= "</li>";
        $aside .= "</ul>";
        $aside .= "<h4>". __( "How to configure to get the option to import all payments from your Paypal account ?", $this->current_plugin_domain )."</h4>";
        $aside .= "<ul>";
        $aside .= "<li>". __( "To download all paypal payments, you need to fill these following fields up :", $this->current_plugin_domain )."</li>";
        $aside .= "<ol>";
        $aside .= "<li>". __( "Paypal API Username", $this->current_plugin_domain )."</li>";
        $aside .= "<li>". __( "Paypal API Password", $this->current_plugin_domain )."</li>";
        $aside .= "<li>". __( "Paypal API Signature", $this->current_plugin_domain )."</li>";
        $aside .= "</ol>";
        $aside .= "<li>". __( "You can find this information in your Paypal account in : Profile > Account Seettings > My selling tools > API access", $this->current_plugin_domain )."</li>";
        $aside .= "<ol>";
        $aside .= "<li>". __( "Under the Selling Online section, click the Update link in the row for API access.", $this->current_plugin_domain ) . "</li>";
        $aside .= "<li>". __( "Once on the API credentials page, click on the last option for NVP/SOAP API integration.", $this->current_plugin_domain ) . "</li>";
        $aside .= "<li>". __( "Copy and paste in this current page the API username, password and signature.", $this->current_plugin_domain ) . "</li>";
        $aside .= "</ol>";
        $aside .= "</ul>";
        $aside .= "</aside>";
        echo $aside;
	}
	
}