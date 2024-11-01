<?php

namespace H4APlugin\Core\Admin;


use function H4APlugin\Core\addHTMLinDOMDocument;
use H4APlugin\Core\Common\H4AObjectTrait;
use function H4APlugin\Core\format_str_to_kebabcase;
use function H4APlugin\Core\get_current_plugin_prefix;
use function H4APlugin\Core\wp_admin_build_url;
use function H4APlugin\Core\wp_debug_log;
use function H4APlugin\Core\wp_error_log;

class ListTableFromDBTemplate extends Template {

	use H4AObjectTrait;

	public $item_params; //Mandatory

	protected $class = "H4APlugin\Core\Admin\H4A_List_Table";

	protected $is_search = false;

	protected $search = array(); //Mandatory if $is_search = true

	protected $is_editable = false;

	protected $is_ajax; //Optional - not implemented

	public $views = array(); //Optional

	public $primary; //Optional

	public $query; //Mandatory

	public $output; //Mandatory

	public $columns; //Mandatory

	public $table;

	public function __construct( $data ) {
		wp_debug_log();
		parent::__construct( $data );
		$mandatory_params = array( "item_params", "query", "output", "columns" );
		$this->setObject( $mandatory_params, $data );
		if( !empty( $data['primary'] ) )
			$this->primary = $data['primary'];
		if( !empty( $data['class'] ) )
			$this->class = $data['class'];
		if( !empty( $data['is_search'] ) )
			$this->is_search = $data['is_search'];
		if( !empty( $data['is_editable'] ) )
			$this->is_editable = $data['is_editable'];
		if( !empty( $data['is_ajax'] ) )
			$this->is_ajax = $data['is_ajax'];
		if( !empty( $data['views'] ) )
			$this->views = $data['views'];
		if( $this->is_search ){
			if( empty( $data['search'] ) ){
				$error_message = "The array 'search' is mandatory and cannot be empty.";
				wp_error_log( $error_message, "Config" );
				exit;
			}else{
				$this->search = $data['search'];
			}
		}
		$data_list_table = $this->set_data_list_table();
		$this->table = new $this->class( $data_list_table );
		$this->process_action_to_redirect();
	}

	protected function process_action_to_redirect(){
		wp_debug_log();
		if( isset( $_POST ) && isset( $_POST['submit_type'] ) ){
			$this->process_search();
		}
	}

	protected function process_search(){
		wp_debug_log();
		if( !isset( $_GET['noheader'] ) )
			return;

		$args = array();
		$key_view = format_str_to_kebabcase( $this->item_params->singular ) . "_view";
		if( isset( $_GET[ $key_view ] ) )
			$args[ $key_view ] = $_GET[ $key_view ];
		if( !empty( $_POST['s'] ) ){
			$args['s'] = wp_unslash( urlencode ( $_POST['s'] ) );
			if( !empty( $_POST['column_search'] ) )
				$args['column_search'] = $_POST['column_search'];
		}
		wp_redirect( wp_admin_build_url( $this->slug, false, $args ) );
		exit;
	}

	protected function set_data_list_table(){
		wp_debug_log();
		$data = array(
			"item_params"   => $this->item_params,
			"views"         => $this->views,
			"query"         => $this->query,
			"output"        => $this->output,
			"columns"       => $this->columns,
			"is_ajax"       => $this->is_ajax,
			"primary"       => $this->primary,
			"search"        => $this->search,
		);
		if( !empty( $this->text_domain ) ){
			$data['text_domain'] = $this->text_domain;
		}
		return $data;
	}

	public function write( &$htmlTmpl ) {
		wp_debug_log();
		$this->writeTableList( $htmlTmpl );
	}

	protected function writeViews( &$htmlTmpl ){
		wp_debug_log();
		if( $this->table instanceof H4A_List_Table || $this->table instanceof H4A_Editable_List_Table ){
			$html = $this->table->views();
			addHTMLinDOMDocument($htmlTmpl, $html, "ul" );
		}
	}

	protected function writeTableList( &$htmlTmpl ){
		wp_debug_log();
		$html = "";
		if( !empty( $this->views ) )
			$this->writeViews( $htmlTmpl );
		if ( $this->table instanceof H4A_List_Table || $this->table instanceof H4A_Editable_List_Table ) {

			$args = array(
				'noheader' => true
			);
			$param_view = $this->table->item_params->singular . "_view";
			if( isset( $_GET[ $param_view ]) ){
				$args[ $param_view ] = $_GET[ $param_view ];
			}
			//Caution the form methos MUST BE "POST" because of the search and bulk actions
			$form_action = wp_admin_build_url( $_GET['page'], false, $args );
			$html               .= sprintf( '<form id="h4a-form-list-%s" method="post" action="%s">',
				$this->slug,
				str_replace( "&", "&amp;" , $form_action )
			);
			if( $this->is_search ) {
				$search_label = sprintf( "Search %s", $this->table->item_params->plural );
				$html .= $this->table->search_box( _x( $search_label, "search-list-item", $this->current_plugin_domain ), get_current_plugin_prefix() . 'search_' . $this->table->item_params->plural, $this->current_plugin_domain  );
			}
			if ( ! class_exists( "H4APlugin\\Core\\Admin\\H4A_List_Table_Base" ) ){
				wp_error_log( "H4A_List_Table_Base was not included" );
			}else if ( !class_exists( $this->class ) ){
				wp_error_log( sprintf( "'%s' was not included", $this->class ) );
			}
			else{
				$html .= $this->table->prepare_items();
				$html .= $this->table->display();
			}
			$html .= "</form>";
			addHTMLinDOMDocument($htmlTmpl, $html, "form" );
		}
	}

	public function set_template_scripts() {
		wp_debug_log();
		//JS
		wp_enqueue_script( "h4aadminlisttablescript", $this->current_plugin_dir_url . "core/admin/features/page/template/js/admin-list-table-script.js" );
		//CSS
		wp_enqueue_style( "h4aadminlisttabletemplate", $this->current_plugin_dir_url . "core/admin/features/page/template/css/admin-list-table-template.css" );
		$this->set_additional_scripts();
	}

	/**
	 * can be overwritten
	 */
	protected function set_additional_scripts(){
		return null;
	}
}