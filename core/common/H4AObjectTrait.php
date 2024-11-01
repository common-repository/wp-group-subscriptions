<?php

namespace H4APlugin\Core\Common;

use function H4APlugin\Core\wp_error_log;

trait H4AObjectTrait {

	protected function setObject( $mandatory_params, $params ){
		foreach( $mandatory_params as $mandatory_param  ){
			if( empty( $params[ $mandatory_param ] ) ){
				$error_message = sprintf( "'%s' not found but it's a mandatory param", $mandatory_param );
				wp_error_log( $error_message, "Config" );
                wp_error_log( $params, "Config"  );
				exit();
			}else{
				$this->$mandatory_param = $params[ $mandatory_param ];
			}
		}
	}
}