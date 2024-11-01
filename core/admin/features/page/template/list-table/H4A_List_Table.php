<?php /** @noinspection ALL */

/** @noinspection ALL */

namespace H4APlugin\Core\Admin;

use H4APlugin\Core\Common\H4AObjectTrait;
use H4APlugin\Core\Config;
use function H4APlugin\Core\format_str_to_kebabcase;
use function H4APlugin\Core\format_str_to_underscorecase;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\pretty_var_dump;
use function H4APlugin\Core\wp_admin_build_url;
use function H4APlugin\Core\wp_debug_log;
use function H4APlugin\Core\wp_error_log;
use function H4APlugin\Core\wp_warning_log;

class H4A_List_Table extends H4A_List_Table_Base {

	use H4AObjectTrait;

	protected $current_plugin_domain;

	protected $text_domain; //for addon translation

	public $items_per_page;

	public $item_params;

	protected $views = array();

	public $views_count = array();

	public $query;

	public $output;

	protected $primary_column_method;

	/*
	 * Constructor function
	 */
	public function __construct( $data ){
		wp_debug_log( get_called_class() );
		$this->current_plugin_domain = get_current_plugin_domain();
		$this->text_domain = ( !empty( $data['text_domain'] ) ) ? $data['text_domain'] : $this->current_plugin_domain;
		$first_data = array(
			'item_params' => $data['item_params']
		);
		$mandatory_params = array( "item_params" );
		$this->setObject( $mandatory_params, $first_data );

		$this->views = ( !empty( $data['views'] ) ) ? $data['views'] : false;

		$mandatory_params = array( "query", "columns", "output" );
		$this->setObject( $mandatory_params, $data );
		// Change potentially columns or output inside a view tag
		$param_view =  format_str_to_underscorecase( $this->item_params->singular ) . "_view";
		if( isset( $_REQUEST[ $param_view ] )
		    && ( $this->views !== false )
		){
			$data['columns'] = $this->get_query_or_output_or_columns_by_view( "columns",  $_REQUEST[ $param_view ] );
			$data['output']  = $this->get_query_or_output_or_columns_by_view( "output",  $_REQUEST[ $param_view ] );
		}
		$this->primary = $data['primary'];
		$this->search = $data['search'];
		if( !empty( $this->primary ) ){
			$this->primary_column_method = $this->make_primary_column();
		}

		parent::__construct( array(
			'singular'  => $this->item_params->singular,
			'plural'    => $this->item_params->plural,
			'ajax'      => ( !empty( $data['is_ajax'] ) ) ? $data['is_ajax'] : false
		));

		// Set data
		$this->set_table_data();

		// Set shortcodes per page
		/*$items_per_page = get_user_meta( get_current_user_id(), "h4a_" . format_str_to_underscorecase( $this->item_params->plural ) . "_per_page", false );

		if( empty( $items_per_page ) ) {
			$screen     = get_current_screen();
			$per_page   = $screen->get_option("per_page");
			wp_debug_log( "tttt" );
			error_log_array( $screen );
			wp_debug_log( "tttt" );
			$items_per_page = $per_page['default'];
		}

		$this->items_per_page = $items_per_page;*/

	}

	/*
	 * Sets the table data
	 *
	 * @return array
	 */
	public function set_table_data(){
		wp_debug_log();
		$data = array();

		$args = array();

		// If it's a search query send search parameter through $args
		$first_attr_column = $this->columns[0]['@attributes'];

		if( !isset( $first_attr_column['slug'] ) ){
			$error_message = "the 'slug' attribute is mandatory for the first 'column' tag";
			wp_error_log( $error_message, "Config" );
			exit;
		}else{
			$first_column_slug = $first_attr_column['slug'];
		}
		if ( !empty($_REQUEST['s']) ) {
			$args = array(
				'order'                => "asc",
				'orderby'              => ( !empty( $this->primary ) ) ? $this->primary : $first_column_slug,
				'offset'               => "",
				'number'               => "",
				'search'               => $_REQUEST['s']
			);
		}
		$param_view = format_str_to_underscorecase( $this->item_params->singular ) . "_view";
		if( isset( $_REQUEST[ $param_view ] ) ){
			$view_slug = $_REQUEST[ $param_view ];
			if ( !empty( $view_slug ) ) {
				$args['status'] = ( $view_slug != 'all' ? $view_slug : '' );
				$query = $this->get_query_or_output_or_columns_by_view( "query", $view_slug );
			}
		}else{
			$query   = $this->query;
		}

		if ( !empty( $_REQUEST['orderby'] ) )
			$args['orderby'] = $_REQUEST['orderby'];
		if ( !empty( $_REQUEST['order'] ) )
			$args['order'] = $_REQUEST['order'];

		if( isset( $query ) )
			$this->items = $this->get_items( $args, $query );

		if( !empty( $this->views ) ){
			// Set views count array to 0, we use this to display the count
			// next to the views links ( all, published, draft, etc)
			$views = $this->get_views();
			foreach( $views as $view_slug => $view_link) {
				$args['status'] = ( $view_slug != "all" ? $view_slug : "" );

				if( $view_slug === "all" ){
					$count_query = $this->query;
				}else{
					$count_query = $this->get_query_or_output_or_columns_by_view( "query" , $view_slug );
				}
				$this->views_count[$view_slug] = $this->get_items( $args, $count_query, true );

			}
		}
		$output_data = Config::getChildrenItem( $this->output, "data" );
		if( empty( $output_data ) ){
			$error_message = sprintf("'data' tag is mandatory in 'output' - class : '%s' ", $this->item_params->class );
			wp_error_log( $error_message, "Config" );
			exit;
		}else{
			if( !empty( $this->items )){
				foreach( $this->items as $item ) {
					$f_data_item  = array();
					foreach ( $output_data  as $list_table_data_item ) {
						$attr_data = $list_table_data_item['@attributes'];
						if( empty( $attr_data['slug'] ) ){
							$error_message = sprintf("'slug' attribute is mandatory for the 'data' tag - class : '%s' ", $this->item_params->class );
							wp_error_log( $error_message, "Config" );
						}else{
							$property = (string) $attr_data['slug'];
							$f_data_item[ $property ] = $item->$property;
						}
					}
					$data[] = apply_filters( "h4a_" . format_str_to_underscorecase( $this->item_params->plural ) . "_list_table_entry_data", $f_data_item, $item );
				}
			}
		}

		$this->output = $data;

	}

	protected function get_views() {
		$get_view_arg = format_str_to_underscorecase( $this->item_params->singular ) . "_view";
		$a_views = array(
			'all' => sprintf( '<a href="%s" %s>%s <span class="count">(%s)</span></a>',
				remove_query_arg( array( $get_view_arg, "paged" ) ),
				( ! isset( $_GET[ $get_view_arg ] ) ) ? 'class="current"' : "",
				_x( "All", format_str_to_underscorecase( $this->item_params->plural ), $this->text_domain ),
				( isset( $this->views_count['all'] ) ) ? $this->views_count['all'] : 0
			)
		);
		$c_views = Config::getChildrenItem( $this->views, "view" );
		if( !empty( $c_views ) ){
			foreach ( $c_views as $c_view ){
				$attrs_view = $c_view['@attributes'];
				if( empty( $attrs_view['slug'] )
				    || empty( $attrs_view['singular'] )
				    || empty( $attrs_view['plural'] )
				){
					$error_message = "'slug', 'singular' and 'plural' attributes are mandatory for the 'view' tag.";
					wp_error_log( $error_message, "Config" );
					exit;
				}else{
					$view_slug = (string) $attrs_view['slug'];
					$count_view = ( isset( $this->views_count[ $view_slug ] ) ) ? $this->views_count[ $view_slug ] : 0;
					$str_view = sprintf( '<a href="%s" %s>%s <span class="count">(%s)</span></a>',
						add_query_arg( array( $get_view_arg => $view_slug, 'paged'    => 1 ) ),
						( isset( $_GET[ $get_view_arg ] ) && $_GET[ $get_view_arg ] === $view_slug ) ? 'class="current"' : "",
						_nx( (string) $attrs_view['singular'], (string) $attrs_view['plural'], $count_view, format_str_to_underscorecase( $this->item_params->plural ), $this->text_domain ),
						$count_view
					);
					$a_views[ $view_slug ] = $str_view;
				}
			}
		}
		//return $a_views;
		return apply_filters( format_str_to_underscorecase( $this->item_params->plural ) . "_list_table_get_views", $a_views );
	}

	/*
	 * Queries the database for item ids
	 *
	 * @param array $args   - arguments to modify the query and return different results
	 *
	 * @return array         - array with item objects
	 */
	public function get_items( $args = array(), $query = null, $count = false ){
		if( empty( $query ) )
			return false;

		global $wpdb;


		$defaults = array(
			'order'                => "ASC",
			'orderby'              => ( !empty( $this->primary ) ) ? $this->primary : "",
			'offset'               => "",
			'number'               => "",
			'search'               => ""
		);

		$args = apply_filters( "h4a_get_" . format_str_to_underscorecase( $this->item_params->plural ) . "_args", wp_parse_args( $args, $defaults ), $args, $defaults );

		// Get Payments for each ID passed
		$items = array();

		// Start query string
		if( !empty( $query ) ) {
			$query_string = "SELECT ";
			$query_select = Config::getChildrenItem( $query, "select" );
			if ( ! $count ) {
				$key_select = 0;
				foreach ( $query_select as $select_item ) {
					$select_alias = ( isset( $select_item['@attributes']['alias'] ) ) ? $select_item['@attributes']['alias'] : null;
					$select_column = ( isset( $select_item['@attributes']['column'] ) ) ? $select_item['@attributes']['column'] : null;
					if( empty( $select_column ) ){
						$error_message = sprintf( "No column attribute for the select %s, but it's mandatory.", $key_select );
						wp_error_log( $error_message, "Config" );
						break;
					}else if ( !empty( $select_alias ) ) {
						$query_string .= $select_alias . "." . $select_column;
					} else {
						$query_string .= $select_column;
					}
					if ( $key_select !== ( count( $query_select ) - 1 ) ) {
						$query_string .= ",";
					}
					$key_select++;
				}
			} else {
				$select_item = $query_select[0];
				$attrs_select = $select_item['@attributes'];
				$select_alias = ( isset( $attrs_select['alias'] ) ) ? $attrs_select['alias'] : null;
				$select_column = ( isset( $attrs_select['column'] ) ) ? $attrs_select['column'] : null;

				if( empty( $select_column ) ){
					$error_message = sprintf( "No column attribute for the select %s, but it's mandatory.", "0" );
					wp_error_log( $error_message, "Config" );
					return null;
				}else{
					$query_string .= sprintf( " COUNT( DISTINCT %s ) ",
						( !empty( $select_alias ) ) ? $select_alias . "." . $select_column : $select_column
					);
				}
			}
			$query_string .= " FROM ";
			$key_from = 0;
			$query_from = Config::getChildrenItem( $query, "from" );
			if( !isset( $query_from ) ){
				$error_message = "No from for the query, but it's mandatory.";
				wp_error_log( $error_message, "Config" );
				return null;
			}
			foreach ( $query_from as $from_item ){
				$attrs_from = $from_item['@attributes'];
				$from_alias = ( isset( $attrs_from['alias'] ) ) ? $attrs_from['alias'] : null;
				$from_table = ( isset( $attrs_from['table'] ) ) ? $attrs_from['table'] : null;
				if( empty( $from_table ) ){
					$error_message = sprintf( "No table attribute for the from %s, but it's mandatory.", $key_from );
					wp_error_log( $error_message, "Config" );
					return null;
				}else{
					$query_string .= $wpdb->prefix . $from_table;
					if( !empty( $from_alias ) ){
						$query_string .= " as " . $from_alias;
					}
					if( $key_from !== ( count( $query_from ) - 1 ) )
						$query_string .= ",";
				}
				$key_from++;
			}

			$query_where = Config::getChildrenItem( $query, "where" );
			if( $query_where !== false ){
				$key_where = 0;
				foreach ( $query_where as $where_item ){
					$attrs_where = $where_item['@attributes'];
					$where_condition = ( isset( $attrs_where['condition'] ) ) ? (string) $attrs_where['condition'] : null;
					$where_filter = ( isset( $attrs_where['filter'] ) ) ? (string) $attrs_where['filter'] : null;

					if( empty( $where_condition ) && empty( $where_filter ) ){
						$error_message = sprintf( "No condition and filter attribute for the where %s, but minimum one of them is mandatory.", $key_from );
						wp_error_log( $error_message, "Config" );
						exit;
					}else{
						$query_string .= ( $key_where === 0 ) ? " WHERE" : " AND";
						if( !empty( $where_filter ) ){
							if( !empty( $args['status'] ) ){
								$query_string .= " " . $where_filter . " = '" . sanitize_text_field( $args['status'] ) . "'";
							}else {
								$param_view = format_str_to_underscorecase( $this->item_params->singular ) . "_view";
								if( isset( $_GET[ $param_view ] ) ){
									if( $count && $_GET[ $param_view ] === "trash"  ){
										$query_string .= " " . $where_filter . " != 'trash'";
									}else{
										$query_string .= " " . $where_filter . " = '" . $_GET[ $param_view ] ."'";
									}
								}else{
									$query_string .= " " . $where_filter . " != 'trash'";
								}
							}
						}else{
							$query_string .= " " . $where_condition;
						}
					}
					$key_where++;
				}
			}
			if( !empty( $_GET['s'] ) ){
				if( empty( $this->search) ){
					$error_message = "The 'search' array cannot be empty.";
					wp_error_log( $error_message, "Config" );
					exit;
				}else{
					$query_string .= ( isset( $query_where ) ) ? " AND " : " WHERE ";

					$query_string .=  $_GET['column_search']. " LIKE '%" . $_GET['s'] . "%'";
				}
			}
			if( ! $count && !empty( $args['orderby'] ) ){
				$query_string .= " ORDER BY " . $args['orderby'] ;
				if( !empty( $args['order'] ) ){
					$query_string .= " " . strtoupper( $args['order'] );
				}
			}
			wp_debug_log( $query_string );

			// Return results
			if( ! $count ) {
				$results = $wpdb->get_results( $query_string, ARRAY_A );
			} else {
				$results = (int) $wpdb->get_var( $query_string );
			}
			if( ! $count ) {
				if ( !empty( $results ) ) {
					foreach ( $results as $item_data) {
						$className   = $this->item_params->class;
						$args = Config::get_item_by_ref( $this->item_params->ref );
						$item    = new $className( $item_data, "list-table", $args );
						$items[] = $item;
					}
				}
			}else{
				$items = $results;
			}

		}else{
			return null;
		}
		return apply_filters( "h4a_get_" . format_str_to_underscorecase( $this->item_params->plural ), $items, $args );

	}

	private function getAliasTableByPrimaryColumName( $query_select ){
		foreach ( $query_select as $sub_select ){
			if( $sub_select['@attributes']['column'] = $this->primary )
				return $sub_select['@attributes']['alias'];
		}
		$error_message = sprintf( "Impossible to get the alias for the primary column." );
		wp_error_log( $error_message, "Config" );
		exit;
	}

	/*
     * Populates the shortcodes for the table
     *
     * @param array $item           - data for the current row
     *
     * @return string
     */
	public function prepare_items(){
		$columns        = $this->get_columns();
		$hidden_columns = $this->get_hidden_columns();
		$sortable       = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden_columns, $sortable );
		$data = $this->output;
		usort( $data, array( $this, 'sort_data' ) );

		/*$paged = ( isset( $_GET['paged'] ) ? (int)$_GET['paged'] : 1 );

		$this->set_pagination_args( array(
			'total_items' => count( $data ),
			'per_page'    => $this->items_per_page
		) );

		$data = array_slice( $data, $this->items_per_page * ( $paged-1 ), $this->items_per_page );*/

		$this->items = $data;

	}

	/**
	 * It is overwritten by H4A_Editable_List_Table
	 */
	protected function get_columns(){
		$columns = array();

		foreach ( $this->columns as $column ){
			$attrs_column = $column['@attributes'];
			$slug = $attrs_column['slug'];
			if( $slug === "cb" ){
				$error_warning = "The slug 'cb' cannot be used because it´s not an editable table list. If you want to use it, please in the 'list' tag set 'editable' as 'true'.";
				wp_warning_log( $error_warning, "Config" );
			}else{
				$label = $column['value'];
				$columns[ $slug ] = _x( $label, $this->item_params->plural, $this->text_domain );
			}
		}
		return apply_filters( "h4a_" . format_str_to_underscorecase( $this->item_params->plural ) . "_list_table_columns", $columns );
	}

	/*
	 * Overwrites the parent class.
	 * Define which columns to hide
	 *
	 * @return array
	 */
	public function get_hidden_columns(){

		return array();

	}

	protected function make_primary_column() {
		$functionName  = "column_" . $this->primary;
		$$functionName = function ( $item ) {
			$output = $this->get_basic_output_primary_column( $item );
			$output = sprintf( "<strong>%s</strong>", $output );
			return $output;
		};
		return $$functionName;
	}

	protected function get_basic_output_primary_column( $item ) {
		$action = ( $this->item_params->editable ) ?  "edit" : "read" ;
		if ( isset( $item['status'] ) && $item['status'] !== "trash" ) {
			$args_edit               = array(
				'action'                 => $action,
				$this->item_params->slug => $item[ $this->item_params->getter ]
			);
			$item_singular = format_str_to_kebabcase( $this->item_params->singular );
			$edit_link               = wp_admin_build_url( "edit-" . $item_singular, false, $args_edit );

			$output = sprintf( '<a class="row-title" href="%1$s" title="%2$s">%3$s</a>', esc_url( $edit_link ), esc_attr( sprintf( __( "Edit &#8220;%s&#8221;", $this->current_plugin_domain ), $item[ $this->primary ] ) ), esc_html( $item[ $this->primary ] ) );

			if ( $item['status'] === "draft" && isset( $_GET[ $item_singular . "_view" ] ) && $_GET[ $item_singular . "_view" ] !== "draft" ) {
				$output .= sprintf( ' - <span class="item-state">%s</span>', __( "Draft" ) );
			}
		} else {
			$output = esc_html( $item[ $this->primary ] );
		}

		return $output;
	}

	/**
	 * @param $query_or_output_or_columns
	 * @param $view_slug
	 *
	 * @return bool|null
	 */
	public function get_query_or_output_or_columns_by_view( $query_or_output_or_columns, $view_slug )
	{
		$output = null;
		if ( isset( $this->views ) ) {
			$c_views = Config::getChildrenItem( $this->views, "view" );
			if( empty( $c_views ) )
				$c_views = Config::getChildrenItem( $this->views, "default" );
			if( empty( $c_views ) ) {
				$error_message = sprintf( "'default' tag is mandatory in 'views' - class : '%s' ", $this->item_params->class );
				wp_error_log( $error_message, "Config" );
				exit;
			}
			foreach ( $c_views as $c_view) {
				$attrs_view = $c_view['@attributes'];
				if ( empty( $attrs_view['slug'] )
				     || empty( $attrs_view['singular'] )
				     || empty( $attrs_view['plural'] )
				) {
					$error_message = "'slug', 'singular' and 'plural' attributes are mandatory for the 'view' and 'default' tag.";
					wp_error_log($error_message, "Config");
					exit;
				} else {
					if ( $attrs_view['slug'] === $view_slug ) {
						$c_query_or_output_or_columns = Config::getChildItem( $c_view, $query_or_output_or_columns );
						if ( !$c_query_or_output_or_columns ) {
							$output = $this->$query_or_output_or_columns;
						} else {
							$output = $c_query_or_output_or_columns;
						}
					}
				}
			}
			return $output;
		}else{
			return false;
		}
	}

}