<?php
namespace H4APlugin\Core\Common;


use H4APlugin\Core\Config;
use function H4APlugin\Core\get_current_plugin_prefix;
use function H4APlugin\Core\getCalledClassWihtoutNamespace;
use function H4APlugin\Core\is_column_exists;
use function H4APlugin\Core\is_float_as_string;
use function H4APlugin\Core\is_number;
use function H4APlugin\Core\wp_debug_log;
use function H4APlugin\Core\wp_error_log;
use function H4APlugin\Core\wp_warning_log;

abstract class Item {

    public $params; //mandatory when $format = "read" or "edit"

    /*
     * Constructor
     */

    public function __construct( $id_or_data = null, $format = "read", $args = array() ) {
        $this->init( $id_or_data, $format, $args );
    }

    protected function init( $id_or_data, $format, $args ){
        wp_debug_log( get_called_class() );
        if( !in_array( $format, [ "save", "read", "list-table" ] ) ){
            wp_error_log( get_called_class() . " - Invalid format : " . $format . " - format allowed : 'save', 'read', 'list-table' " );
            exit;
        }else{
            if( in_array( $format, array( "save", "read" ) ) ){
                if( empty( $args ) ){ //automatic system to add args
                    $ref = strtolower( getCalledClassWihtoutNamespace( get_called_class() ) );
                    $args = Config::get_item_by_ref( $ref );
                }

	            $this->params = new DB_Item_Params( $args );
            }
            if ( is_number( $id_or_data ) ){
                $this->get_item( (int) $id_or_data );
            }else if( is_array( $id_or_data ) ){
                if( $format === "save" ){
                    $this->get_item_to_save( $id_or_data );
                }else if( $format === "read" ){
                    $this->get_item_to_read( $id_or_data );
                }else{ // $format === "list-table"
                    $this->get_item_to_list( $id_or_data );
                }

            }else{
                wp_error_log(   get_called_class() . " - Invalid format : " . $id_or_data . " - format allowed : integer, array " );
                return null;
            }
        }
    }

    /*
     * Getters
     */
    protected function get_item( $id ){ //Get exhaustive infos from the DB Table
        wp_debug_log();
        global $wpdb;
        $res_getter = is_column_exists( $this->params->getter, $this->params->dbtable );

        if( !$res_getter ){
            $error_message = sprintf("The getter '%s' is not a column name for the table '%s'!", $this->params->getter, $this->params->dbtable );
            wp_error_log( $error_message, "Config" );
        }else{
            // Start query string
            $query_string = "SELECT * FROM {$wpdb->prefix}" . get_current_plugin_prefix() . $this->params->dbtable . " WHERE " . $this->params->getter . " = " . $id ;
            // Return results
            $results = $wpdb->get_results( $query_string, ARRAY_A );

            if(count($results) === 0){
                $error_message = sprintf( "%s not found!", ucfirst( $this->params->name ) );
                wp_error_log( $error_message );
            }else if( count( $results ) > 1 ){
                $error_message = sprintf("The getter '%s' is not a column name for the table '%s' with an unique key!", $this->params->getter, $this->params->dbtable );
                wp_error_log( $error_message, "Config" );
            }else{
                foreach ( $results[0] as $column_name => $value ){
	                if( is_float_as_string( $value ) ){
		                $value = (float) $value;
	                }else if( is_number( $value ) ){
		                $value = (int) $value;
	                }
                    $this->$column_name = $value;
                }
            }
        }
    }

    /*
     * Can be overwritten.
     */
    protected function get_item_to_read( $data ){
	    wp_debug_log( get_called_class() );
	    foreach ( $data as $attr => $value ){
	    	if( is_float_as_string( $value ) ){
	    		//wp_info_log( "float : " . $value );
			    $value = (float) $value;
		    }else if( is_number( $value ) ){
			    //wp_info_log( "number : " . $value );
			    $value = (int) $value;
		    }/*else if( is_string( $value ) ){
			    //wp_info_log( "string : " . $value );
		    }*/
		    $this->$attr = $value;
        }
    }

    abstract protected function get_item_to_list( $data ); //to display on the list table

	/**
	 * Can be overwritten
	 *
	 * @param $id_or_data
	 */
	protected function get_item_to_save( $id_or_data ){
		$message = sprintf( "Should be overwritten - data : '%s'", serialize( $id_or_data ) );
		wp_warning_log( $message );
	}

}