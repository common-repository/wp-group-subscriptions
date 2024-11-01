<?php

namespace H4APlugin\Core\Admin;


use function H4APlugin\Core\format_str_to_kebabcase;
use function H4APlugin\Core\format_str_to_underscorecase;
use function H4APlugin\Core\wp_admin_build_url;
use function H4APlugin\Core\wp_error_log;
use function H4APlugin\Core\wp_debug_log;

class H4A_Editable_List_Table extends H4A_List_Table {

	public $bulk_actions = array();
	public $row_actions = array();

	/*
	 * Constructor function
	 */
	public function __construct( $data ){

		parent::__construct( $data );

		// Bulk actions
		if( !empty( $data['bulk_actions'] ) )
			$this->bulk_actions = $data['bulk_actions'];
		$this->row_actions = $data['row_actions'];

		add_filter( "bulk_actions-{$this->screen->id}", array( $this, "editable_item_bulk_actions" ) );

	}

	protected function make_primary_column() {
		$functionName  = "column_" . $this->primary;
		$$functionName = function ( $item ) {
			if ( method_exists( $this, 'column_' . $this->primary ) ) {
				$output = call_user_func( array( $this, 'column_' . $this->primary ), $item );
			}else{
				$output = $this->get_basic_output_primary_column( $item );
			}
			$output = sprintf( "<strong>%s</strong>", $output );
			if( $this->item_params->editable && !empty( $this->row_actions ) ){
				$output .= '<div class="row-actions">';
				$nbr_row_actions = 1;
				foreach ( $this->row_actions as $key_row_action => $row_action_label ){
					$action_label = __( $row_action_label, $this->current_plugin_domain );
					if( in_array( $key_row_action, array( "read", "edit" ) ) ){
						$args_action  = array(
							'action' => $key_row_action,
							$this->item_params->slug => $item[ $this->item_params->getter ]
						);
						$item_name = format_str_to_kebabcase( $this->item_params->name );
						$action_url = wp_admin_build_url( "edit-" . $item_name, false, $args_action );
						$f_action_url = str_replace( "&", "&amp;",
							$action_url
						);
					}else{
						$args_action  = array();
						$item_singular = format_str_to_underscorecase( $this->item_params->singular );
						$item_status_key = $item_singular . "_view";
						if( isset( $_GET[ $item_status_key ] ) )
							$args_action[ $item_status_key ] = $_GET[ $item_status_key ];
						$args_action[ 'action' ] = $key_row_action;
						$args_action[ $this->item_params->slug ] = $item[ $this->item_params->getter ];
						$args_action[ 'noheader' ] = "true";
						$action_url = wp_admin_build_url( format_str_to_kebabcase( $this->item_params->plural ), false, $args_action);
						$f_action_url = str_replace( "&", "&amp;",
							$action_url
						);
						$nonce_action = $this->get_nonce_action( $key_row_action, $item[ $this->item_params->getter ] );
						$f_action_url  = wp_nonce_url( $f_action_url, $nonce_action );
					}
					$output     .= sprintf( '<span class="%s"><a href="%s" aria-label="%s">%s</a> %s </span>',
						$key_row_action,
						$f_action_url,
						esc_attr( sprintf( __( "%s &#8220;%s&#8221;" ), $action_label, $item[ $this->primary ] ) ),
						$action_label,
						( $nbr_row_actions < count( $this->row_actions ) ) ? "|" : null
					);
					$nbr_row_actions++;
				}
				$output .= "</div>";
			}
			return $output;
		};
		return $$functionName;
	}

	protected function get_nonce_action( $action, $getter ){
		return $action . "-" . format_str_to_kebabcase( $this->item_params->name ) . "_" . $getter;
	}

	public function editable_item_bulk_actions( $actions ){
		return $actions;
	}

	/*
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk actions available on this table.
	 *
	 * @return array
	 */
	protected function get_bulk_actions(){
		wp_debug_log(  serialize( $this->bulk_actions ) );
		return $this->bulk_actions;

	}

	protected function get_columns(){
		$columns = array();

		foreach ( $this->columns as $column ){
			$attrs_column = $column['@attributes'];
			$slug = $attrs_column['slug'];
			if( $slug === "cb" ){
				$columns[ 'cb' ] = '<input type="checkbox" />';
			}else{
				$label = $column['value'];
				$columns[ $slug ] = _x( $label, format_str_to_underscorecase( $this->item_params->plural ), $this->text_domain );
			}
		}
		return apply_filters( "h4a_" . format_str_to_underscorecase( $this->item_params->plural ) . "_list_table_columns", $columns );
	}

	public function column_cb( $item ) {
		wp_debug_log();
		foreach ( $this->columns as $c_column ){
			$attr_column = $c_column['@attributes'];
			if( (string) $attr_column['slug'] === "cb" ){
				if( !isset( $attr_column['bind'] ) ){
					$error_message = "the 'bind' attribute is mandatory for the 'column' tag with 'slug' => 'cb'";
					wp_error_log( $error_message, "Config" );
					exit;
				}else{
					if( !array_key_exists($attr_column['bind'], $item ) ){
						$error_message = "the 'bind' attribute must match with the 'slug' attribute in one of 'output'->'data' tag";
						wp_error_log( $error_message, "Config" );
						exit;
					}else if( !isset( $item[ (string) $attr_column['bind'] ] ) ){
						$error_message = sprintf( "'item['%s'] is null, the cause can be because there is no result from the %s:get_item_to_list()",
							(string) $attr_column['bind'],
							get_called_class()
						);
						wp_error_log( $error_message, "Config" );
						exit;
					}else{
						return sprintf(
							'<input type="checkbox" name="%1$s[]" value="%2$s" />',
							$this->_args['singular'],
							$item[ (string) $attr_column['bind'] ] );
					}
				}
			}
		}
		return null;
	}
}