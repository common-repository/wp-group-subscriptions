<?php

namespace H4APlugin\Core\Common;


use function H4APlugin\Core\asBoolean;
use function H4APlugin\Core\wp_debug_log;
use function H4APlugin\Core\wp_error_log;

class DB_Item_Params extends Item_Params {

	public $ref; //Mandatory
	public $name; //Mandatory
	public $class; //Mandatory
	public $dbtable; //Mandatory
	public $getter; //Mandatory
	public $editable = false; //default : false
	public $slug; //default : generated based on $name

	public function __construct( $data ) {
	    wp_debug_log();
		$mandatory_params = array( "ref", "name", "class", "dbtable", "getter" );
		foreach( $mandatory_params as $mandatory_param  ){
			if( is_array( $data ) ){
				if( empty( $data[ $mandatory_param ] ) ){
					$error_message = sprintf( "'%s' not found but it's a mandatory param", $mandatory_param );
					wp_error_log( $error_message, "Config" );
					wp_error_log( serialize( $data ), "Config" );
					exit;
				}else{
					$this->$mandatory_param = $data[ $mandatory_param ];
				}
			}else if( is_object( $data ) ){
				if( empty( $data->$mandatory_param ) ){
					$error_message = sprintf( "'%s' not found but it's a mandatory param", $mandatory_param );
					wp_error_log( $error_message, "Config" );
					exit;
				}else{
					$this->$mandatory_param = $data->$mandatory_param;
				}
			}else{
				$error_message = sprintf( "'data' must be an array or an object - current type 'data' : ", gettype( $data ) );
				wp_error_log( $error_message, "Config" );
				exit;
			}
		}
		$this->editable = ( !empty( $data['editable'] ) ) ? asBoolean( $data['editable'] ) : false ;
		$this->slug = ( !empty( $data['slug'] ) ) ? $data['slug'] : self::gen_item_slug( $this->name );
		$f_data = array();
		if( !empty( $data['singular'] ) )
            $f_data['singular'] = $data['singular'];
		if( !empty( $data['plural'] ) )
            $f_data['plural'] = $data['plural'];
        parent::__construct( $f_data );
	}

	private static function gen_item_slug( $name ){
		return substr( $name, 0, 1 );
	}
}