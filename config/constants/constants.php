<?php

namespace H4APlugin\WPGroupSubs\Config;

//Labels


define( "H4A_WGS_PLUGIN_LABEL_RETURN", "Payment return" );
define( "H4A_WGS_PLUGIN_LABEL_LOG_IN", "Sign in" );
define( "H4A_WGS_PLUGIN_LABEL_MY_PROFILE", "My Profile" );

//Sessions
if( ! defined( "WP_SESSION_COOKIE" ) ) {
	define( "WP_SESSION_COOKIE", "_wp_session" );
}

//Settings 
define( "H4A_WGS_PAGE_SETTINGS", "settings-wp-group-subscriptions" );

//Length standard inputs
define( "H4A_WGS_LENGTH_EMAIL", "40" );
define( "H4A_WGS_MIN_LENGTH_PASSWORD", "6" );
define( "H4A_WGS_LENGTH_PEOPLE_NAME", "35" );
define( "H4A_WGS_LENGTH_FULL_PHONE", "20" );
define( "H4A_WGS_LENGTH_PHONE_CODE", "5" );
define( "H4A_WGS_LENGTH_STREET_NAME", "100" );
define( "H4A_WGS_LENGTH_STREET_NUMBER", "6" );
define( "H4A_WGS_LENGTH_ZIP_CODE", "15" );
define( "H4A_WGS_LENGTH_CITY", "50" );
define( "H4A_WGS_LENGTH_COUNTRY", "80" );
define( "H4A_WGS_LENGTH_PLAN_NAME", "50" );
define( "H4A_WGS_LENGTH_FORM_ITEM_REF", "40" );
define( "H4A_WGS_LENGTH_PLAN_TAG", "30" );

//Length WGS inputs
define( "H4A_WGS_LENGTH_GROUP_NAME", "40" );