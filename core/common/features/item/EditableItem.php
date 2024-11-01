<?php

namespace H4APlugin\Core\Common;

use function H4APlugin\Core\asBoolean;
use H4APlugin\Core\Config;
use function H4APlugin\Core\format_kamelcase_to_kebabcase;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\get_current_plugin_prefix;
use function H4APlugin\Core\getCalledClassWihtoutNamespace;
use function H4APlugin\Core\is_number;
use function H4APlugin\Core\wp_error_log;
use function H4APlugin\Core\wp_debug_log;
use function H4APlugin\Core\wp_get_error_back_end_system;

abstract class EditableItem extends Item {

	public $current_plugin_domain;
	public $current_plugin_prefix;

	public $form;
	public $nonce;

	public function __construct( $id_or_data = null, $format = "edit", $args = array() ) {
		wp_debug_log( "Object : " . get_called_class() . ", Format : " . $format );
		$this->current_plugin_domain = get_current_plugin_domain();
		$this->current_plugin_prefix = get_current_plugin_prefix();
		/*if( $format === "read" ){
			$message_error = sprintf( "Impossible to init '%s' as EditableItem, the 'item' tag with name : '%s' has got the 'editable' attribute set to 'true'.", $args['class'], $args['name'] );
			wp_log_error_format( $message_error, "Config " .  "[" . __CLASS__ . "]" );
			exit;
		}*/
		parent::__construct( $id_or_data, $format, $args );
	}

	/*
	 * Initializers
	 */

	protected function init( $id_or_data, $format, $args ){
		wp_debug_log( get_called_class() . " - format : " . $format );
		if( !in_array( $format, [ "read", "edit", "list-table" ] ) ){
			wp_error_log( get_called_class() ." - Invalid format : " . $format . " - format allowed : 'read', 'edit', 'list-table' " );
			exit;
		}else{
			if( !in_array( gettype( $id_or_data ), [ "NULL", "integer", "array" ]) ){
				wp_error_log( get_called_class() ." - Invalid data format : (" . gettype( $id_or_data ) . ") " . $id_or_data . " - format allowed : null, integer, array " );
				exit;
			}else{
				//Notice : For "list-table" : new DB_Item_Params is run one time in Config.php
				//         and set in $args for each item in the list table ( see H4A_List_Table::get_items )
				if( empty( $args ) ){ //automatic system to add args
					$ref = format_kamelcase_to_kebabcase( getCalledClassWihtoutNamespace( get_called_class() ) );
					$args = Config::get_item_by_ref( $ref );
				}
				$this->params = new DB_Item_Params( $args );

				if( is_admin() ){
					if( in_array( $format, array( "edit", "list-table" ) ) )
						$this->nonce = $this->get_nonce();

					if( $id_or_data === null || $id_or_data === 0 ){
						$this->get_blank( $args );
					}else if ( is_number( $id_or_data ) ){
						$this->get_item( (int) $id_or_data );
					}else if( is_array( $id_or_data ) ){
						if( $format === "edit" ){
							$this->get_item_to_edit( $id_or_data );
						}else if( $format === "read" ){
							$this->get_item_to_read( $id_or_data );
						}else{ // $format === "list-table"
							$this->get_item_to_list( $id_or_data );
						}
					}
				}else{ //Front end
					if( is_number( $id_or_data ) ){
						$this->get_item( $id_or_data );
					}else{ // array
						if( $format === "edit" ){
							$this->get_item_to_edit( $id_or_data );
						}else{
							$this->get_item_to_read( $id_or_data );
						}
					}

				}
				if( $format === "edit" ){
					$this->get_editable_item_form();
				}
			}
		}
	}

	abstract public function initForm();

	/*
	 * Getters
	 */

	protected function get_editable_item_form() {
		wp_debug_log( $this->params->name );
		if( !isset( $this->params->ref ) ){
			$message_error = "Impossible to get the editable item form, params->ref does not exist.";
			wp_error_log( $message_error, "[" . __CLASS__ . "]" );
			Notices::setNotice( wp_get_error_back_end_system(), "error" );
		}else{
			$res_form   = CommonForm::getEditFormByItemRef( $this->params->ref );
			if ( $res_form['success'] ) {
				if( $res_form['data'] instanceof CommonForm ){
					$this->form = $res_form['data'];
					$this->form->options['action_wpnonce'] = $this->nonce;
				}else{
					$message_error = "res_form['data'] is not a form";
					wp_error_log( $message_error );
					Notices::setNotice( wp_get_error_back_end_system(), "error" );
				}
			}
		}
	}

	abstract protected function get_blank( $args = array() );

	protected function get_nonce( $action = "" ) {
		if( empty( $action ) ){
			$action = ( !empty( $_GET['action'] ) ) ? $_GET['action'] : "edit" ;
		}
		$nonce  = get_current_plugin_prefix() . $action . "-" . $this->params->ref;
		if( isset( $_GET[ $this->params->slug ] ) )
			$nonce .= "-" . $_GET[ $this->params->slug ];
		$nonce .=  "_nonce";
		wp_debug_log( $nonce );
		return $nonce;
	}

	/*
	 * CRUD + Trash
	 */

	public function update_item( $table_name = "", $data = array(), $where_id = 0, $where_others = array() ){
		$output = array(
			'success' => false
		);

		$errors = array();

		global $wpdb;

		$where = array(  $this->params->getter => $where_id );
		if( !empty( $where_others ) ){
			$where_tmp = array_merge( $where, $where_others );
			$where = $where_tmp;
		}
		$res_update = $wpdb->update( $wpdb->prefix . get_current_plugin_prefix() . $table_name, $data, $where );
		if ( $res_update === false ) { //Notice : cannot be !$res_update_subs because if can return 0 if update modify anything
			$message_error = sprintf( _x( "The %s ( id : '%s' ) cannot be updated!", "edition-item", $this->current_plugin_domain ), $this->params->name, $where_id );
			wp_error_log( $message_error );
			$errors[] = $message_error;
		}

		if( empty( $errors ) ){
			$output['success'] = true;
			$output['data'] = $where_id;
		}else{
			$output['errors'] = $errors;
		}
		return $output;
	}

	abstract public function save();
	abstract public function update();
	abstract public function trash();
	abstract public function untrash();
	abstract public function delete();

	public function updateStatus( $status, $message_error ){
		$output = array(
			'success' => false
		);

		$errors = array();

		global $wpdb;

		$data = array(
			'status' => $status
		);
		$item_getter = $this->params->getter;
		$where = array (
			$item_getter => $this->$item_getter
		);
		$res_update = $wpdb->update( $wpdb->prefix . get_current_plugin_prefix() . $this->params->dbtable, $data, $where );
		if( $res_update === false || $res_update === 0 ){
			wp_error_log( $message_error );
			$errors[] = $message_error;
		}
		if( empty( $errors ) ){
			$output['success'] = true;
			$output['data'] = $this->$item_getter;
		}else{
			$output['errors'] = $errors;
		}

		return $output;
	}

	/*
	 * Getters
	 */

	abstract protected function get_item_to_edit( $data ); //to edit

	/*
	 * Checking
	 */

	public static function check_editable_item( $item_name ){
		$h4a_config = Config::getConfig();
		if( !isset( $h4a_config['items'] ) || count( $h4a_config['items']['children'] ) < 1 ){
			$message_error = "No items ! Please check if items.xml exists and there are 'item' tags in 'items'";
			wp_error_log( $message_error, "Config" );
			exit;
		}else{
			foreach ( $h4a_config['items']['children'] as $c_item ){
				$attrs_item = $c_item['@attributes'];
				if( empty( $attrs_item['ref'] ) || empty( $attrs_item['name'] ) ){
					$message_error = "the 'ref' and 'name' attributes are mandatories for the 'item' tag";
					wp_error_log( $message_error, "Config" );
					exit;
				}else{
					if( (string) $attrs_item['ref'] === $item_name ){
						if( empty( $attrs_item['editable'] ) ){
							return false;
						}else{
							return asBoolean( (string) $attrs_item['editable'] );
						}
					}
				}
			}
			wp_die( printf( "Impossible to find the item with the ref '%s' in items.xml", $item_name ) );
			return false;
		}
	}
}