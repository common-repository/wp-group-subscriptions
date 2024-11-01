<?php

namespace H4APlugin\Core\Admin;


use H4APlugin\Core\Common\Item_Params;
use function H4APlugin\Core\wp_debug_log;
use function H4APlugin\Core\wp_error_log;

class CSV_Item_Params extends Item_Params
{
    public $file; //Mandatory
    public $singular; //Mandatory
    public $plural; //Mandatory

    public function __construct( $data ) {
        wp_debug_log();
        $mandatory_params = array( "file", "singular", "plural" );
        foreach( $mandatory_params as $mandatory_param  ){
            if( empty( $data[ $mandatory_param ] ) ){
                $error_message = sprintf( "'%s' not found but it's a mandatory param", $mandatory_param );
                wp_error_log( $error_message, "Config" );
                exit;
            }else if( !in_array( $mandatory_param, array( "singular", "plural" ) ) ){
                $this->$mandatory_param = $data[ $mandatory_param ];
            }
        }
        parent::__construct( array( 'singular' => $data['singular'], 'plural' => $data['plural'] ) );
    }
}