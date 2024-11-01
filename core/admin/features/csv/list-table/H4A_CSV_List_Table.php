<?php

namespace H4APlugin\Core\Admin;


use H4APlugin\Core\Common\H4AObjectTrait;
use function H4APlugin\Core\format_str_to_underscorecase;
use function H4APlugin\Core\get_current_plugin_dir_path;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\sortBy;
use function H4APlugin\Core\wp_debug_log;

class H4A_CSV_List_Table extends H4A_List_Table_Base {

    use H4AObjectTrait;
    
    private $current_plugin_domain;

    public $item_params;

    public $columns = array();

    /*
	 * Constructor function
	 */
    public function __construct( $data ){
        wp_debug_log();
        
        $this->current_plugin_domain = get_current_plugin_domain();

        $mandatory_params = array( "item_params", "columns" );
        $this->setObject( $mandatory_params, $data );

        parent::__construct( array(
            'singular'  => $this->item_params->singular,
            'plural'    => $this->item_params->plural
        ));

    }

    public function prepare_items(){
        $columns        = $this->get_columns();
        $hidden_columns = $this->get_hidden_columns();
        $sortable       = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden_columns, $sortable );
        $this->items = $this->get_items();

    }

    public function set_table_data(){}

    public function get_items($args = array())
    {
	    $output = null;
    	$filename = get_current_plugin_dir_path() . $this->item_params->file;
        if( file_exists($filename) && filesize( $filename ) < H4A_WGS_LIST_TABLE_LOGS_MAX ){
            $importer = new CsvImporter( $filename, true );
            $data = $importer->get();
            if( !empty( $_GET['orderby'] )){
                switch ( $_GET['orderby'] ){
                    case 'level' :
                        sortBy( "code", $data, $_GET['order'] );
                        $output =  $data;
                        break;
                    case 'date' :
                        $output = ( isset( $_GET['order'] ) && $_GET['order'] === "desc"  ) ? array_reverse( $data ) : $data ;
                        break;
                }
            }else{
                $output =  $data;
            }
        }
        return $output;
    }

    protected function get_columns()
    {
        $columns = array();
        foreach ( $this->columns as $column ){
            $attrs_column = $column['@attributes'];
            $slug = $attrs_column['slug'];
            if( $slug !== "cb" ){
	            $label = $column['value'];
	            $columns[ $slug ] = _x( $label, $this->item_params->plural, $this->current_plugin_domain );
            }
        }
        return apply_filters( "h4a_" . format_str_to_underscorecase( $this->item_params->plural ) . "_list_table_columns", $columns );
    }

    public function get_hidden_columns()
    {
        return array();
    }

    public function column_level( $item ) {
        return sprintf( '<span class="row-level row-level-%s">'.__( $item['level'], $this->current_plugin_domain).'</span>', strtolower( $item['level'] ) ) ;
    }

}