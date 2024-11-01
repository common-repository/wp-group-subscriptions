<?php

namespace H4APlugin\Core\Admin;

use H4APlugin\Core\Common\Notices;
use function H4APlugin\Core\format_str_to_kebabcase;
use function H4APlugin\Core\format_str_to_underscorecase;
use function H4APlugin\Core\wp_admin_build_url;
use function H4APlugin\Core\wp_debug_log;
use function H4APlugin\Core\wp_error_log;
use function H4APlugin\Core\wp_get_error_back_end_system;
use function H4APlugin\Core\wp_warning_log;

class EditableListTableFromDBTemplate extends ListTableFromDBTemplate {

	public $bulk_actions;

	public $row_actions;

	protected $class = "H4APlugin\Core\Admin\H4A_Editable_List_Table";

	public function __construct( $data ){
		wp_debug_log( get_called_class() );
		if( !empty( $data['class'] ) )
			$this->class = $data['class'];
		if( empty( $data['item_params'] ) && !is_object( $data['item_params'] ) ){
			$error_message = "'item_params' as a DB_Item_Params object is mandatory to init the editable list table.";
			wp_error_log( $error_message, "system" );
			exit;
		}else{
			$this->set_bulk_actions( $data['actions'], $data['item_params'] );
			$this->set_row_actions( $data['actions'], $data['item_params'] );

			$data['bulk_actions'] = $this->bulk_actions;

			parent::__construct( $data );
		}
	}

	protected function process_action_to_redirect(){
		wp_debug_log();
		if( isset( $_POST ) && isset( $_POST['submit_type'] ) ){
			if( $_POST['submit_type'] === "search"  ){
				$this->process_search();
			}else if( $_POST['submit_type'] === "bulk_actions" ){
				// Process the data received from the bulk actions
				if( !empty( $this->bulk_actions )
				    && isset( $_POST['_wpnonce'] )
				    && isset( $_POST['action'] )
				    && isset( $_POST['action2'] )
				){
					$this->process_custom_bulk_actions();
				}
			}
		}else if( !empty( $this->row_actions )
		          && isset( $_GET['action'] )
		          && array_key_exists( $_GET['action'], $this->row_actions )
		){
			$this->actionItem( $_GET['action'] ); // trash, untrash, delete
		}

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
			"bulk_actions"  => $this->bulk_actions,
			"row_actions"  => $this->row_actions
		);
		if( !empty( $this->text_domain ) ){
			$data['text_domain'] = $this->text_domain;
		}
		return $data;
	}

	private function set_bulk_actions( $actions, $item_params ){
		wp_debug_log();
		$this->bulk_actions = $this->set_actions( "bulk_actions", $actions, $item_params );
	}

	private function set_row_actions( $actions, $item_params ){
		wp_debug_log();
		$this->row_actions = $this->set_actions( "row", $actions, $item_params );
	}

	private function set_actions( $key, $actions, $item_params ){
		wp_debug_log();
		$a_actions  = array();
		$param_name = format_str_to_underscorecase( $item_params->name );
		$param_view = $param_name ."_view";
		$view       = isset( $_GET[ $param_view ] ) ? $_GET[ $param_view ] : 'default';
		if( !empty( $actions[ $view ][ $key ] ) ){
			foreach ( $actions[ $view ][ $key ] as $action ){
				$a_actions[ $action['value'] ] = __( $action['label'], $this->current_plugin_domain );
			}
		}
		return $a_actions;
	}

	/*
	 * Process what happens when a custom bulk action is applied.
	 *
	 */
	public function process_custom_bulk_actions() {
		wp_debug_log();
		// Verify nonce before anything
		if( !wp_verify_nonce( $_REQUEST['_wpnonce'], "bulk-" . format_str_to_kebabcase( $this->item_params->plural ) ) ){
			Notices::setNotice( __( "Sorry, your nonce did not verify.", $this->current_plugin_domain ), "error", true );
		}else if( !isset( $_REQUEST['action'] ) && !isset( $_REQUEST['action2'] ) ){
			$message_error = "action or action 2 in the global request is mandatory.";
			wp_error_log( $message_error );
			Notices::setNotice(  wp_get_error_back_end_system(), "error", true );
		}else{
			$action = ( (int) $_REQUEST['action'] !== -1 ) ? $_REQUEST['action'] : $_REQUEST['action2'];
			if( !array_key_exists( $action, $this->bulk_actions ) ){
				$message_warning = __( "Any action was not run! Please select an action in the bulk actions combobox.", $this->current_plugin_domain );
				Notices::setNotice( $message_warning, "warning", true );
			}else if( empty( $_REQUEST[ format_str_to_kebabcase( $this->item_params->name ) ] ) ){
				$message_error = sprintf( "This request '%s' is mandatory", $this->item_params->name );
				wp_error_log( $message_error );
				Notices::setNotice( wp_get_error_back_end_system(), "error", true );
			}else{

				$item_ids = $_REQUEST[ format_str_to_kebabcase( $this->item_params->name ) ];

				foreach( $item_ids as $item_id ) {
					$args = array(
						'ref'     => $this->item_params->ref,
						'name'     => $this->item_params->name,
						'class'    => $this->item_params->class,
						'dbtable' => $this->item_params->dbtable,
						'getter'   => $this->item_params->getter
					);
					$current_item = new $this->item_params->class( (int) $item_id, "edit", $args );
					$current_item->$action();
				}
			}
		}

		// Redirect arguments
		$redirect_args = array(
			'paged' => ( isset($_REQUEST['paged']) ? (int)$_REQUEST['paged'] : 1 )
		);

		$redirect = add_query_arg( $redirect_args, $_REQUEST['_wp_http_referer'] );

		wp_redirect( $redirect );
		exit;
	}

	/**
	 * @param $key_action | can be "trash", "untrash" or "delete"
	 */
	protected function actionItem( $key_action = "" ){
		wp_debug_log( $key_action );
		$action = $key_action . "-" . format_str_to_kebabcase( $this->item_params->name ) . "_".$_GET[ $this->item_params->slug ];
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], $action ) ) {
			Notices::setNotice( __( "Sorry, your nonce did not verify.", $this->current_plugin_domain ), "error", true );
		} else {
			$args = array(
				'ref'     => $this->item_params->ref,
				'name'    => $this->item_params->name,
				'class'   => $this->item_params->class,
				'dbtable' => $this->item_params->dbtable,
				'getter'  => $this->item_params->getter
			);
			$item = new $this->item_params->class( (int) $_GET[ $this->item_params->slug ], "edit", $args );
			$item->$key_action();
		}
		$args = array();
		$item_singular = format_str_to_underscorecase($this->item_params->singular);
		if( isset( $_GET["{$item_singular}_view"] ) )
			$args[ $item_singular . "_view" ] = $_GET["{$item_singular}_view"];
		wp_redirect( wp_admin_build_url(  $_GET['page'], false, $args ) );
		exit;
	}

	public function write( &$htmlTmpl ) {
		wp_debug_log();
		if( empty( $this->bulk_actions ) ){
			$warning_message = "There is no bulk actions - the 'list' is editable but you need to add 'action' tags inside a 'bulk_actions' tag inside 'view' and 'default' tag.";
			wp_warning_log( $warning_message, "Config");
		}
		if( empty( $this->row_actions ) ){
			$warning_message = "There is no row actions - the 'list' is editable but you need to add 'action' tags inside a 'row' tag inside 'view' and 'default' tag.";
			wp_warning_log( $warning_message, "Config");
		}

		if( current_user_can( 'manage_options' ) && !$this->table->item_params->editable ){
			$warning_message = "If you want to show the button 'Add new' - the 'list' is editable but you need to set the 'editable' attribute to 'true' for the 'item' tag.";
			wp_warning_log( $warning_message, "Config");
		}else if( $htmlTmpl instanceof \DOMDocument ){
			// Add button "Add new" after the heading
			$nodeTitle = $htmlTmpl->getElementsByTagName("h1")->item(0);
			$h1ClassAttribute = $htmlTmpl->createAttribute("class");
			$h1ClassAttribute->value = "wp-heading-inline";
			$nodeTitle->appendChild($h1ClassAttribute);
			$htmlTmpl->importNode($nodeTitle, true);

			$btn_element = $htmlTmpl->createElement("a", esc_html( __( "Add new", $this->current_plugin_domain ) ) );
			$btnClassAttribute = $htmlTmpl->createAttribute("class");
			$btnClassAttribute->value = "add-new-h2";
			$btnHrefAttribute = $htmlTmpl->createAttribute("href");
			$btnHrefAttribute->value = esc_url( menu_page_url( "edit-" . format_str_to_kebabcase( $this->table->item_params->name ), false ) );
			$btn_element->appendChild($btnClassAttribute);
			$btn_element->appendChild($btnHrefAttribute);
			$htmlTmpl->importNode($btn_element, true);

			$nodeLine = $htmlTmpl->getElementsByTagName("hr")->item(0);
			$parentNode = $htmlTmpl->getElementsByTagName("div")->item(0);
			$parentNode->insertBefore( $btn_element, $nodeLine );

			$this->writeTableList( $htmlTmpl );
		}
	}
}