<?php

namespace H4APlugin\WPGroupSubs\Admin\Accounting;

use H4APlugin\Core\Admin\ListTableFromDBTemplate;
use H4APlugin\Core\Config;
use function H4APlugin\Core\get_current_plugin_short_title;
use function H4APlugin\Core\get_today_as_datetime;
use function H4APlugin\Core\wp_admin_build_url;
use function H4APlugin\Core\wp_debug_log;
use H4APlugin\Core\Common\Paypal;

class Payments extends ListTableFromDBTemplate {

	protected function process_action_to_redirect(){
		wp_debug_log();
		if( isset( $_GET['run'] )
		    && $_GET['run'] === "download_paypal_payments"
		    && !empty( $_POST['wgs-start-date'] )
		){
			$paypal = new Paypal();
			$paypal->downloadTransactionsInPayments( $_POST['wgs-start-date'] );
			wp_redirect( wp_admin_build_url( $this->slug, false ) );
			exit;
		}else if( isset( $_GET['s'] ) ){
			$this->process_search();
		}
	}

	public function write(&$htmlTmpl)
	{
		$is_paypal_data_completed = false;
		$wgs_paypal_options = get_option( Config::gen_options_name( "paypal" ) );
		if( !empty( $wgs_paypal_options )
		    && !empty( $wgs_paypal_options['paypal_api_username'] )
		    && !empty( $wgs_paypal_options['paypal_api_password'] )
		    && !empty( $wgs_paypal_options['paypal_api_signature'] )
		){
			$is_paypal_data_completed = true;
		}
		if( current_user_can( 'manage_options' ) ){
			$html_advanced_options = sprintf( '<a href="#" class="wgs-options-advanced-link closed" >%s</a>',
				__( "Advanced Options", $this->current_plugin_domain )
			);

			$html_advanced_options .= '<section class="wgs-options-advanced-section">';
			if( !$is_paypal_data_completed ){
				$args = array();
				global $wp_settings_sections;
				if( count( $wp_settings_sections[H4A_WGS_PAGE_SETTINGS] ) > 1 ){
					$args['tab'] = "paypal";
				}
				$args['inp'] = "paypal_api_username";
				$message = sprintf(
					__( 'To import Paypal payments, please fill in Paypal API settings : <a id="go-to-paypal-api-username" href="%s">Settings &gt; %s</a>', $this->current_plugin_domain ),
					wp_admin_build_url( H4A_WGS_PAGE_SETTINGS, true, $args ),
					get_current_plugin_short_title() );
				$html_advanced_options .= sprintf(
					'<div class="notice-internal notice-internal-warning"><p>%s</p></div>',
					$message = str_replace( "&", "&amp;", htmlspecialchars_decode( $message ) )
				);
			}
			$args_action = array(
				'run' => "download_paypal_payments",
				'noheader' => "true"
			);
			$html_advanced_options .= sprintf ('<form method="post" action="%s">',
				str_replace( "&", "&amp;", wp_admin_build_url("payments", false, $args_action ) )
			);
			$html_advanced_options .= sprintf ('<input type="date" id="wgs-start-date" name="wgs-start-date" value="%s"/>',
				get_today_as_datetime( "Y-m-d")
			);
			$html_advanced_options .= sprintf ('<button id="btn_download_payments" type="submit" class="add-new-h2 h4a-button" %s >%s</button>',
				( !$is_paypal_data_completed ) ? 'disabled="disabled"' : null,
				__( "Download Paypal Payments", $this->current_plugin_domain )
			);
			$html_advanced_options .= '</form>';
			$html_advanced_options .= '</section>';

			if ( $htmlTmpl instanceof \DOMDocument ) {
				$tmpl_advanced_options = $htmlTmpl->createDocumentFragment();

				$tmpl_advanced_options->appendXML( $html_advanced_options );

				//$nodeLine = $htmlTmpl->getElementsByTagName("hr")->item(0);
				$parentNode = $htmlTmpl->getElementsByTagName("div")->item(0);
				//$parentNode->insertBefore( $section_advanced_options, $nodeLine );
				//$parentNode->insertBefore( $link_advanced_options, $section_advanced_options );
				$parentNode->insertBefore( $tmpl_advanced_options );
			}
		}
		parent::write($htmlTmpl);
	}

	protected function set_additional_scripts(){
		wp_enqueue_style( "wsgpayments", H4A_WGS_PLUGIN_DIR_URL . "admin/headings/accounting/views/css/wgs-payments.css" );
		wp_enqueue_script( "wsgpayments", H4A_WGS_PLUGIN_DIR_URL . "admin/headings/accounting/views/js/wgs-payments.js" );
	}
}