<?php

use H4APlugin\WPGroupSubs\Common\Plan;
use H4APlugin\WPGroupSubs\Common\FormPages;
use H4APlugin\Core\Common\Notices;
use H4APlugin\Core\FrontEnd\FrontEndNotice;
use function H4APlugin\Core\wp_build_url;
$current_plugin_domain = \H4APlugin\Core\get_current_plugin_domain();

if( Notices::isNoErrors() )
	echo '<h4>' . sprintf( __( "Welcome to %s!", $current_plugin_domain ), get_bloginfo( 'name' ) ) . '</h4>';

$transient_name = FrontEndNotice::gen_transient_name();
$transient = get_transient( $transient_name );

if( !empty( $transient ) && !empty( $transient[ 'front-end' ] ) ){
	Notices::displayAll();
}

if( Notices::isNoErrors() ){
	if( empty( $plan ) ){
		$plan_id = Plan::getPlanIdByName( $_GET["item_name"] ) ;
		$plan = new Plan( $plan_id, "read" );
	}
	if( $plan->plan_type === "single" ){
		echo "<p>" . __( "You can now access all the contents by logging in.", $current_plugin_domain ) . "</p>";
		$args = array(
			'registered' => "true"
		);
		$href = wp_build_url( "wgs-login", H4A_WGS_PLUGIN_LABEL_LOG_IN, $args );
		printf( "<button onclick=\"window.location.href='%s'\">%s</button>",
			$href,
			__( 'Go to Login Page', $current_plugin_domain )
		);
	}
	else if( $plan->plan_type === "multiple" ) {
		echo __( "<br/>You can now create all member accounts.", $current_plugin_domain ) . '</p>';
		$href = get_site_url()."?post_type=wgs-form-page";
		$page_id = FormPages::getIdFormPageByTitle( $_GET["item_name"] );
		$href .= "&p=" . $page_id;
		$href .= "&step=3";
		$href .= "&tx=" . $_GET['tx'];
		printf( "<button onclick=\"window.location.href='%s'\">%s</button>", $href, __( 'Last step : create all member accounts', $current_plugin_domain ) );
	}
}