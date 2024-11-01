<?php

namespace H4APlugin\WPGroupSubs\Admin\Members;

use H4APlugin\Core\Admin\H4A_Editable_List_Table;
use function H4APlugin\Core\return_datetime;
use function H4APlugin\Core\wp_admin_build_url;
use function H4APlugin\Core\wp_debug_log;

class Members_List_Table extends H4A_Editable_List_Table {

	private $is_exist_crud_item;

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
							$this->item_params->slug => $item[ $this->item_params->getter ],
							'subs' => $item[ 'subscriber_id' ]
						);
						$action_url = wp_admin_build_url( "edit-" . $this->item_params->ref, false, $args_action );
						$f_action_url = str_replace( "&", "&amp;",
							$action_url
						);
					}else{
						$args_action  = array();
						$item_status_key = $this->item_params->singular . "_view";
						if( isset( $_GET[ $item_status_key ] ) )
							$args_action[ $item_status_key ] = $_GET[ $item_status_key ];
						$args_action[ 'action' ] = $key_row_action;
						$args_action[ $this->item_params->slug ] = $item[ $this->item_params->getter ];
						$args_action[ 'noheader' ] = "true";
						$action_url = wp_admin_build_url( $this->item_params->dbtable, false, $args_action);
						$f_action_url = str_replace( "&", "&amp;",
							$action_url
						);
						$nonce_action = $this->get_nonce_action( $key_row_action, $item[ $this->item_params->getter ] );
						$f_action_url  = wp_nonce_url( $f_action_url, $nonce_action );
					}
					if( $key_row_action !== "trash" || !empty( $item['group_name'] ) ){
						$output     .= sprintf( '<span class="%s"><a href="%s" aria-label="%s">%s</a> %s </span>',
							$key_row_action,
							$f_action_url,
							esc_attr( sprintf( __( "%s &#8220;%s&#8221;" ), $action_label, $item[ $this->primary ] ) ),
							$action_label,
							( !empty( $item['group_name'] ) && $nbr_row_actions < count( $this->row_actions )  ) ? "|" : null
						);
						$nbr_row_actions ++;
					}
				}
				$output .= "</div>";
			}
			return $output;
		};
		return $$functionName;
	}

	public function column_cb( $item ) {
		$html = "";
		if( !empty( $item['group_name'] ) ){
			$html = sprintf(
				'<input type="checkbox" name="%1$s[]" value="%2$s" />',
				$this->_args['singular'],
				$item["member_id"] );
		}
		return $html;

	}

	public function column_last_name( $item ) {
		wp_debug_log();
		$str = $item['last_name'].' '.$item['first_name'];
		if( !isset( $_GET['member_view'] ) || $_GET['member_view'] !== "trash" ){
			$args_edit  = array(
				'action' => "edit",
				$this->item_params->slug => $item[ 'member_id' ],
				'subs' => $item['subscriber_id']
			);
			$edit_link = wp_admin_build_url( "edit-" . $this->item_params->name, false, $args_edit );

			$output = sprintf( '<a class="row-title" href="%1$s" title="%2$s">%3$s</a>',
				esc_url( $edit_link ),
				esc_attr( sprintf( __( "Edit &#8220;%s&#8221;", $this->current_plugin_domain ), $item[ $this->primary ] ) ),
				$str
			);
		}else{
			$output = $str;
		}
		return $output;
	}

	public function column_group_name( $item ) {
		return ( !empty( $item["group_name"] ) ) ? $item["group_name"] : ' - ';
	}

	public function column_last_connection( $item ) {
		$utc_start_date = $item["last_connection"];
		return ( !empty($utc_start_date) ) ? return_datetime( $utc_start_date ) : ' - ';
	}

	public function column_start_date( $item ) {
		$utc_start_date = $item["start_date"];
		return return_datetime( $utc_start_date );
	}

	public function column_last_activation( $item ) {
		$utc_start_date = $item["last_activation"];
		return ( !empty($utc_start_date) ) ? return_datetime( $utc_start_date ) : ' - ';
	}

	/*
	 * Generate the table navigation above or below the table
	 *
	 * @param string $which
	 */
	protected function display_tablenav( $which, $echo = false ){
		wp_debug_log();
		$html = "";

		if ( 'top' === $which ) {
			$html .= '<input type="hidden" id="page" name="page" value="' . $this->_args['plural'] . '" />';
			if( isset( $_GET["{$this->_args['singular']}_view"] ) )
				$html .= sprintf( '<input type="hidden" id="%s" name="%s" value="%s" />',
					$this->_args['singular'] . "_view",
					$this->_args['singular'] . "_view",
					$_GET["{$this->_args['singular']}_view"]
				);
			$html .= '<input type="hidden" id="noheader" name="noheader" value="true" />';
			$html .= wp_nonce_field( 'bulk-' . $this->_args['plural'] );
		}
		$html .= sprintf('<div class="tablenav %s">', esc_attr( $which ));

		$this->is_exist_crud_item = false;

		foreach ( $this->items as $item ){
			if( !empty( $item['group_name'] ) ){
				$this->is_exist_crud_item = true;
			}
		}

		if ( $this->has_items() && $this->is_exist_crud_item ):
			$html .= '<div class="alignleft actions bulkactions">';
			$html .= $this->bulk_actions( $which, $echo );
			$html .= "</div>";
		endif;
		//$html .= $this->extra_tablenav( $which, $echo );
		$html .= $this->pagination( $which, $echo );
		$html .= '<br class="clear" />';
		$html .= "</div>";

		if( $echo ){
			echo $html;
			return true;
		}else{
			return $html;
		}
	}

	public function print_column_headers( $with_id = true, $echo = false ){
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();
		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$current_url = remove_query_arg( 'paged', $current_url );
		if ( isset( $_GET['orderby'] ) ) {
			$current_orderby = $_GET['orderby'];
		} else {
			$current_orderby = '';
		}

		if ( isset( $_GET['order'] ) && 'desc' === $_GET['order'] ) {
			$current_order = 'desc';
		} else {
			$current_order = 'asc';
		}
		$is_crud_item = false;

		foreach ( $this->items as $item ){
			if( !empty( $item['group_name'] ) )
				$is_crud_item = true;
		}
		if ( !empty( $columns['cb'] ) && count( $this->items ) > 0 && $is_crud_item ) {
			static $cb_counter = 1;
			$columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __( 'Select All' ) . '</label>'
			                 . '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
			$cb_counter++;
		}else{
			unset( $columns['cb'] );
		}
		$output = "";
		foreach ( $columns as $column_key => $column_display_name ) {

			$class = array( 'manage-column', "column-$column_key" );

			if ( in_array( $column_key, $hidden ) ) {
				$class[] = 'hidden';
			}

			if ( 'cb' === $column_key )
				$class[] = 'check-column';
			elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) )
				$class[] = 'num';

			if ( $column_key === $primary ) {
				$class[] = 'column-primary';
			}

			if ( isset( $sortable[$column_key] ) ) {
				list( $orderby, $desc_first ) = $sortable[$column_key];
				if ( $current_orderby === $orderby ) {
					$order = 'asc' === $current_order ? 'desc' : 'asc';
					$class[] = 'sorted';
					$class[] = $current_order;
				} else {
					$order = $desc_first ? 'desc' : 'asc';
					$class[] = 'sortable';
					$class[] = $desc_first ? 'asc' : 'desc';
				}
				$column_display_name = sprintf('<a href="%s"><span>%s</span><span class="sorting-indicator"></span></a>',
					esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ),
					$column_display_name
				);
			}

			$tag = ( 'cb' === $column_key ) ? 'td' : 'th';
			$scope = ( 'th' === $tag ) ? 'scope="col"' : '';
			$id = $with_id ? "id='$column_key'" : '';

			if ( !empty( $class ) )
				$class = "class='" . join( ' ', $class ) . "'";

			$output .= "<$tag $scope $id $class>$column_display_name</$tag>";

		}
		if ( $echo ){
			echo $output;
			return true;
		}else{
			return $output;
		}
	}

	/*
	 * Generates the columns for a single row of the table
	 *
	 * @param object $item The current item
	 */
	protected function single_row_columns( $item, $echo = false ){
		wp_debug_log();
		//list( $columns, $hidden, $sortable ) = $this->get_column_info();
		list( $columns, $hidden, $primary ) = $this->get_column_info();
		$html = "";
		$is_crud_item = false;
		if( !empty( $item['group_name'] ) ){
			$is_crud_item = true;
		}
		foreach ( $columns as $column_name => $column_display_name ) {
			$classes = "$column_name column-$column_name";
			if ( $primary === $column_name ) {
				$classes .= ' has-row-actions column-primary';
			}

			if ( in_array( $column_name, $hidden ) ) {
				$classes .= ' hidden';
			}

			// Comments column uses HTML in the display name with screen reader text.
			// Instead of using esc_attr(), we strip tags to get closer to a user-friendly string.
			$data = 'data-colname="' . wp_strip_all_tags( $column_display_name ) . '"';

			$attributes = "class='$classes' $data";


			if ( 'cb' === $column_name ) {
				if( $this->is_exist_crud_item ){
					$html .= '<th scope="row" class="check-column">';
					if( $is_crud_item ){
						$html .= $this->column_cb( $item );
					}
					$html .= "</th>";
				}
			} elseif ( method_exists( $this, '_column_' . $column_name ) ) {

				$html .= call_user_func(
					array( $this, '_column_' . $column_name ),
					$item,
					$classes,
					$data,
					$primary
				);
			}elseif( !empty( $this->primary_column_method ) && $column_name === $this->primary  ){
				$html .= "<td $attributes>";
				$html .= call_user_func( $this->primary_column_method, $item );
				//echo $this->handle_row_actions( $item, $column_name, $primary );
				$html .= "</td>";
			}elseif ( method_exists( $this, 'column_' . $column_name ) ) {
				$html .= "<td $attributes>";
				$html .= call_user_func( array( $this, 'column_' . $column_name ), $item );
				//echo $this->handle_row_actions( $item, $column_name, $primary );
				$html .= "</td>";
			}else {
				$html .= "<td $attributes>";
				$html .= $this->column_default( $item, $column_name );
				//echo $this->handle_row_actions( $item, $column_name, $primary );
				$html .= "</td>";
			}
		}
		if( $echo ){
			echo $html;
			return true;
		}else{
			return $html;
		}
	}

}
