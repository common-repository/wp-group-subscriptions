<?php
use function \H4APlugin\Core\get_current_plugin_domain;
use H4APlugin\Core\Common\Notices;
use H4APlugin\Core\FrontEnd\FrontEndNotice;

$current_plugin_domain = \H4APlugin\Core\get_current_plugin_domain();
$transient_name = FrontEndNotice::gen_transient_name();
$transient = get_transient( $transient_name );

if( in_array( (int) $_GET["step"], array( 1, 2, 3 ) ) ){
	if( !empty( $transient ) && !empty( $transient[ 'front-end' ] ) ){
		Notices::displayAll();
	}
	if( !empty( $form ) )
		$form->writeForm();
}else if( (int) $_GET["step"] === 4 ){
	$current_plugin_domain = get_current_plugin_domain();
	echo '<h2>' . __( "Subscription completed", $current_plugin_domain ) . '</h2>';
	if( !empty( $transient ) && !empty( $transient[ 'front-end' ] ) ){
		Notices::displayAll();
	}
}

