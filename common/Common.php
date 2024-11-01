<?php

namespace H4APlugin\WPGroupSubs\Common;

use H4APlugin\Core\Common\H4ACommonPlugin;
use function H4APlugin\Core\is_dir_empty;

class Common extends H4ACommonPlugin {

	protected function common_init(){
		if( !is_dir_empty( dirname( __FILE__ ) . "/classes/widgets" ) ){
			add_action( 'widgets_init', function() { register_widget( 'H4APlugin\WPGroupSubs\Common\Profile_Widget' ); } );
		}
	}
	
}