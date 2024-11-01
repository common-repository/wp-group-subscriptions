<?php

namespace H4APlugin\Core;

/**
 * Table of Contents
 *
 * 1.0 - Plugins
 * -----------------------------------------------------------------------------
 */

/**
 * 1.0 - Plugins
 * -----------------------------------------------------------------------------
 */

if( !function_exists( "H4APlugin\Core\is_plugin_active_before_admin_init" ) ) {
	/**
	 * @param $plugin
	 *
	 * @return bool
	 */
	function  is_plugin_active_before_admin_init( $plugin ){
		return in_array( $plugin, (array) get_option( 'active_plugins', array() ) );
	}
}