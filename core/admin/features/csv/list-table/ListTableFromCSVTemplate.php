<?php

namespace H4APlugin\Core\Admin;


use function H4APlugin\Core\addHTMLinDOMDocument;
use H4APlugin\Core\Common\H4AObjectTrait;
use function H4APlugin\Core\get_current_plugin_dir_path;
use function H4APlugin\Core\get_current_plugin_prefix;
use function H4APlugin\Core\wp_admin_build_url;
use function H4APlugin\Core\wp_debug_log;

if( !defined( "H4A_WGS_LIST_TABLE_LOGS_MAX" ) )
	define( "H4A_WGS_LIST_TABLE_LOGS_MAX", 500000 );

class ListTableFromCSVTemplate extends Template {

    use H4AObjectTrait;
    
    public $current_plugin_domain;
    
	public $item_params; //Mandatory

	protected $class = "H4APlugin\Core\Admin\H4A_CSV_List_Table";

	protected $is_search = false;

	public $columns; //Mandatory

	public $table;

	public function __construct( $data ) {
		wp_debug_log();
		$mandatory_params = array( "item_params", "columns" );
		$this->setObject( $mandatory_params, $data );
		if( !empty( $data['class'] ) )
			$this->class = $data['class'];
		if( !empty( $data['is_search'] ) )
			$this->is_search = $data['is_search'];
		$data_list_table = $this->set_data_list_table();
		$this->table = new $this->class( $data_list_table );
        parent::__construct( $data );
	}

    private function set_data_list_table(){
	    wp_debug_log();
        $data = array(
            "item_params"   => $this->item_params,
            "columns"       => $this->columns
        );
        return $data;
    }
	
	public function write( &$htmlTmpl ) {
        parent::write( $htmlTmpl );
        $filename = get_current_plugin_dir_path() . $this->item_params->file;
        if( !file_exists($filename) || filesize( $filename ) < H4A_WGS_LIST_TABLE_LOGS_MAX ){
          	$this->writeTableList( $htmlTmpl );
        }else{

            $this->writeHeavyFile( $htmlTmpl, filesize( $filename ) );
        }

	}

    protected function writeTableList( &$htmlTmpl ){
		$html = "";
        $args = array();
        $action_url = wp_admin_build_url( $this->slug, false, $args ) ;
        $html .= sprintf( '<form id="h4a-form-list-%s" method="post" action="%s">', $this->slug, $action_url );
        if( $this->table instanceof H4A_CSV_List_Table  ){
	        if( $this->is_search ) {
		        $search_label = sprintf("Search %s", $this->table->item_params->plural  );
		        $html .= $this->table->search_box( _x( $search_label, "search-list-item", $this->current_plugin_domain ), get_current_plugin_prefix() . 'search_' . $this->table->item_params->plural, $this->current_plugin_domain  );
	        }
	        $html .= $this->table->prepare_items();
	        $html .= $this->table->display();
	        $html .= "</form>";
	        addHTMLinDOMDocument($htmlTmpl, $html, "form" );
        }
    }

    protected function writeHeavyFile( &$htmlTmpl, $fileweight ){
        wp_debug_log();
        $html = "<div>";
        $html .= "<p>";
        $html .= sprintf( __( "This log report is too heavy to display it as table list. File weight : %s bytes.", $this->current_plugin_domain ), $fileweight );
        $html .= "</p>";
        $html .= "<pre><code>";
        $html .= file_get_contents(get_current_plugin_dir_path() . $this->item_params->file );
        $html .= "</code></pre>";
        $html .= "</div>";
        addHTMLinDOMDocument($htmlTmpl, $html, "div" );
    }

    public function set_template_scripts() {
        wp_debug_log();
        $this->set_additional_scripts();
    }

    /**
     * can be overwritten
     */
    protected function set_additional_scripts(){
        return null;
    }
}