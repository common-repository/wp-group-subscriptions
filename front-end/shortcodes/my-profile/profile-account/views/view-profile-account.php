<?php
use \H4APlugin\WPGroupSubs\Common\Subscriber;
use \H4APlugin\WPGroupSubs\Common\Member;
use function \H4APlugin\Core\asBoolean;
$plugin_domain = \H4APlugin\Core\get_current_plugin_domain();

if( isset( $attrs ) && ( !isset( $attrs['title'] ) || ( isset( $attrs['title'] ) && asBoolean( $attrs['title'] ) ) ) ) :
?>
<h2><?php _e( "Account", $plugin_domain ); ?></h2>
<?php
endif;

if( Subscriber::isLoggedIn() && isset( $subscriber_loggedIn ) ) {
	$subscriber_loggedIn->form->writeForm();
}else if( Member::isLoggedIn() ){
	$member_loggedIn = Member::getMemberLoggedIn( "edit" );
	if( $member_loggedIn->form instanceof \H4APlugin\Core\Common\CommonForm )
	    $member_loggedIn->form->writeForm();
}
?>
