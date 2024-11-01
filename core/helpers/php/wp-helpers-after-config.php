<?php

namespace H4APlugin\Core;

/**
 * Table of Contents
 *
 * 1.0 - Strings & Constants
 * 2.0 - Currencies
 * 3.0 - Urls & paths
 * 4.0 - Database
 * 5.0 - Logs
 *  5.1 - Debug
 *  5.2 - Info
 *  5.3 - Warnings
 *  5.4 - Errors
 * 6.0 - WP Settings
 * -----------------------------------------------------------------------------
 */

/**
 * 1.0 - Strings & Constants
 * -----------------------------------------------------------------------------
 */

if( !function_exists( "H4APlugin\Core\wp_format_i18n" ) ){
	function wp_format_i18n( $label, $domain ){
		if( is_array( $label ) ){
			if( is_array( $label[1] ) ){
				return vsprintf( __( $label[0], $domain ), $label[1] );
			}else if( is_string( $label[1] ) || is_int( $label[1] ) ){
				return sprintf( __( $label[0], $domain ), $label[1] );
			}
		}else if( is_string( $label )){
			return __( $label, $domain );
		}
		return '';
	}
}

if( !function_exists( "H4APlugin\Core\get_current_plugin_initials" ) ){
	function get_current_plugin_initials(){
		$h4a_config = Config::getConfig();
		return $h4a_config['plugin_info']['initials'];
	}
}

if( !function_exists( "H4APlugin\Core\get_current_plugin_prefix" ) ){
	function get_current_plugin_prefix(){
		$h4a_config = Config::getConfig();
		return $h4a_config['plugin_info']['prefix'];
	}
}

if( !function_exists( "H4APlugin\Core\get_current_plugin_domain" ) ){
	function get_current_plugin_domain(){
		$h4a_config = Config::getConfig();
		return $h4a_config['plugin_info']['domain'];
	}
}

if( !function_exists( "H4APlugin\Core\get_current_plugin_basename" ) ){
	function get_current_plugin_basename(){
		$initials = get_current_plugin_initials();
		return constant( "H4A_" . $initials . "_PLUGIN_BASENAME" );
	}
}

if( !function_exists( "H4APlugin\Core\get_current_plugin_dir_path" ) ){
	function get_current_plugin_dir_path(){
		$initials = get_current_plugin_initials();
		return constant( "H4A_" . $initials . "_PLUGIN_DIR_PATH" );
	}
}

if( !function_exists( "H4APlugin\Core\get_current_plugin_dir_url" ) ){
	function get_current_plugin_dir_url(){
		$initials = get_current_plugin_initials();
		return constant( "H4A_" . $initials . "_PLUGIN_DIR_URL" );
	}
}

if( !function_exists( "H4APlugin\Core\get_current_plugin_title" ) ){
	function get_current_plugin_title(){
		$initials = get_current_plugin_initials();
		return constant( "H4A_" . $initials . "_PLUGIN_TITLE" );
	}
}

if( !function_exists( "H4APlugin\Core\get_current_plugin_short_title" ) ){
	function get_current_plugin_short_title(){
		$initials = get_current_plugin_initials();
		return constant( "H4A_" . $initials . "_PLUGIN_SHORT_TITLE" );
	}
}

if( !function_exists( "H4APlugin\Core\get_current_plugin_version" ) ){
	function get_current_plugin_version(){
		$initials = get_current_plugin_initials();
		return constant( "H4A_" . $initials . "_PLUGIN_VERSION" );
	}
}



/**
 * 2.0 - Currencies
 * -----------------------------------------------------------------------------
 */

if( !function_exists( "H4APlugin\Core\wp_get_symbol_currency" ) ){
	function wp_get_symbol_currency( $currency ){
		$locale= get_locale();
		$fmt = new \NumberFormatter( $locale."@currency=$currency", \NumberFormatter::CURRENCY );
		$symbol = $fmt->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
		return ( !empty( $symbol ) ) ? $symbol : '?' ;
	}
}

/**
 * 3.0 - Urls & paths
 * -----------------------------------------------------------------------------
 */

if( !function_exists( "H4APlugin\Core\wp_redirect_404" ) ){
	function wp_redirect_404(){
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		get_template_part( 404 ); exit();
	}
}

// wp_plugin_url - Maybe useful for https - need to see !
/*
if( !function_exists( "H4APlugin\Core\wp_plugin_url" ) ){
function wp_plugin_url( $relative_path = '', $plugin_path = '' ) {
   $url = plugins_url( $relative_path, $plugin_path );

   if ( is_ssl() && 'http:' == substr( $url, 0, 5 ) ) {
	   $url = 'https:' . substr( $url, 5 );
   }

   return $url;
   }
}
*/

if( !function_exists( "H4APlugin\Core\disallowed_admin_pages" ) ){
	function disallowed_admin_pages( $url ) {
		wp_redirect( admin_url( $url ), 301 );
		exit;
	}
}

if( !function_exists( "H4APlugin\Core\wp_get_base_dir_path" ) ) {
	function wp_get_base_dir_path( $file = '', $a_folders = array() ) {
		$ipAddress = gethostbyname( $_SERVER['SERVER_NAME'] );
		if ( $ipAddress === "127.0.0.1" ) {
			$base_path = dirname( $file );
			foreach ( $a_folders as $folder ) {
				$base_path .= "\\" . $folder . "\\";
			}
		} else {
			$base_path = plugin_dir_path( $file );
			foreach ( $a_folders as $folder ) {
				$base_path .= $folder . "/";
			}
		}

		return $base_path;
	}
}

if( !function_exists( "H4APlugin\Core\wp_build_url" ) ) {
	function wp_build_url( $post_type, $page_title, $args = array() ){
		$page_title = __( $page_title, get_current_plugin_domain() );
		$page = get_page_by_title( $page_title, OBJECT, $post_type );
		$str_args = null;
		if( !empty( $args ) ){
			foreach ( $args as $arg_name => $value ){
				$str_args .= "&".$arg_name."=".$value;
			}
		}
		$url = sprintf( get_site_url()."?post_type=%s&p=%s%s", $post_type, $page->ID, $str_args );
		return $url;
	}
}

if( !function_exists( "H4APlugin\Core\wp_admin_build_url" ) ) {
	function wp_admin_build_url( $page_title, $is_settings = false, $args = array() ){
		$str_args = null;
		if( !empty( $args ) ){
			foreach ( $args as $arg_name => $value ){
				$str_args .= "&".$arg_name."=".$value;
			}
		}
		$page = "page=".$page_title;
		if($is_settings){
			$path = 'options-general.php?'.$page.$str_args;
		}else{
			$path = 'admin.php?'.$page.$str_args;
		}
		$url = get_admin_url( null, $path );
		return $url;
	}
}

/**
 * 4.0 - Database
 * -----------------------------------------------------------------------------
 */

if( !function_exists( "H4APlugin\Core\wpdb_get_column_names_from_table" ) ) {
	function wpdb_get_column_names_from_table( $table_name_without_prefix, $excludes = array() ){
		global $wpdb;

		$query_select = "SELECT COLUMN_NAME from information_schema.columns";
		$query_where = " WHERE table_schema = '" . DB_NAME . "'
					and table_name = '{$wpdb->prefix}" . get_current_plugin_prefix() . $table_name_without_prefix . "'";
		$query_string = $query_select . $query_where;
		// Return results
		$results = $wpdb->get_results( $query_string, ARRAY_N );
		$results = flatten_array( $results );
		if( !empty( $excludes ) ){
			$results = array_filter( $results, function( $v ) use ( $excludes ) {
				if( !in_array( $v, $excludes ) ){
					return $v;
				}
				return null;
			}, ARRAY_FILTER_USE_BOTH );
		}
		return $results;
	}
}

if( !function_exists( "H4APlugin\Core\is_column_exists" ) ) {
	function is_column_exists( $column_name, $table_name_without_prefix ){
		global $wpdb;
		$query_string  = "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '{$wpdb->prefix}" . get_current_plugin_prefix() . "{$table_name_without_prefix}' AND COLUMN_NAME = '{$column_name}'";
		$result = $wpdb->get_results( $query_string, ARRAY_N  );
		$exists = ( count( $result ) > 0 ) ? true : false;
		return $exists;
	}
}

if( !function_exists( "H4APlugin\Core\is_table_db_exists" ) ) {
	function is_table_db_exists( $table_name_without_prefix ) {
		$current_plugin_prefix = get_current_plugin_prefix();
		global $wpdb;
		$query  = "SHOW TABLES LIKE '{$wpdb->prefix}{$current_plugin_prefix}{$table_name_without_prefix}'";
		$result = $wpdb->query( $query );
		if ( $result === 1 ) {
			return true;
		} else {
			return false;
		}

	}
}

if( !function_exists( "H4APlugin\Core\is_data_db_exists" ) ) {
	function is_data_db_exists( $data, $table_name_without_prefix ){
		global $wpdb;
		$query_string  = "SELECT * FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "{$table_name_without_prefix}";
		$query_string .= " WHERE ";
		$d = 0;
		foreach ( $data as $column_name => $value ){
			if( !in_array( $value, array( "CURRENT", "NOW" )) ){
				if( $d > 0 )
					$query_string .= " AND ";
				$query_string .= $column_name;
				if( is_number( $value ) ){
					$query_string .= "=" . $value;
				}else if( $value === "NULL" ){
					$query_string .= " IS NULL";
				}else{
					$query_string .= "='" . $value . "'";
				}
			}
			$d++;
		}
		//wp_warning_log( $query_string );
		$result = $wpdb->get_results( $query_string, ARRAY_N  );
		$exists = ( count( $result ) > 0 ) ? true : false;
		return $exists;
	}
}

if( !function_exists( "H4APlugin\Core\create_table_db" ) ) {
	function create_table_db( array $table ){
		$upgrade_file_path = ABSPATH . "wp-admin/includes/upgrade.php";
		wp_debug_log( $upgrade_file_path );
		if( !@require_once( $upgrade_file_path ) ){
			wp_debug_log( "before" );
			require_once( $upgrade_file_path );
			wp_debug_log( "after" );
		}
		wp_debug_log( "1" );
		global $wpdb;
		wp_debug_log( "2" );
		$charset_collate = $wpdb->get_charset_collate();
		wp_debug_log( "3" );
		$table_attrs = $table['@attributes'];
		if(  !isset( $table_attrs['name'] ) ){
			$error_message = "The 'name' attribute is mandatory for the 'table' tag.";
			wp_error_log( $error_message, "Config" );
			exit;
		}else{
			$table_name = $table_attrs['name'];
			$query = "CREATE TABLE {$wpdb->prefix}" . get_current_plugin_prefix() . $table_name . "( ";
			for( $k_col = 0; $k_col < count( $table['column'] ); $k_col++ ){
				$column = $table['column'][$k_col];
				$col_attrs = $column['@attributes'];
				$query .= $col_attrs['name'] . ' ' . $col_attrs['type'] ;
				if( !empty( $col_attrs['a_i'] ) && $col_attrs['a_i'] )
					$query .= " " . 'AUTO_INCREMENT';
				if( !empty( $col_attrs['not_null'] ) && $col_attrs['not_null'] )
					$query .= " " . 'NOT NULL';
				if( isset( $col_attrs['default'] ) ){
					$query .= " DEFAULT ";
					if( is_number( $col_attrs['default'] ) ){
						$query .= $col_attrs['default'];
					}else{
						$query .= ( strtoupper( $col_attrs['default'] ) === "NULL") ? "NULL" : "'" . $col_attrs['default'] . "'";
					}
				}
				if( !empty( $col_attrs['key'] ) ){
					$query .= ( (string) $col_attrs['key'] === "primary" ) ? " PRIMARY KEY" : " " . $col_attrs['key'];
				}
				if( !empty( $col_attrs->unsigned ) && $col_attrs->unsigned )
					$query .= " UNSIGNED";
				if( ( $k_col + 1 ) < count( $table['column'] ) )
					$query .= ", ";
			}
			if( isset( $table['keys'] ) ){
				$keys = $table['keys'];
				if( !isset( $keys['index'] ) && !isset( $keys['unique'] ) ){
					$error_message = sprintf("'keys' tag must have minimum a child tag - tag allowed values : 'index' and 'unique' - table name = '%s'", $table_name );
					wp_error_log( $error_message, "Config" );
					exit;
				}else{
					foreach ( $keys as $keyName => $key ){
						if( !in_array( $keyName,  array( "index", "unique" )) ){
							$error_message = sprintf("Tag allowed values inside 'keys' : 'index' and 'unique' - table name = '%s'", $table_name );
							wp_error_log( $error_message, "Config" );
							exit;
						}else{
							$key_attrs = $key['@attributes'];
							if( $keyName === "index" ){
								if( !isset( $key_attrs['col_name'] ) ){
									$error_message = sprintf("The 'col_name' attribute is mandatory for the 'index' tag - table name = '%s'", $table_name );
									wp_error_log( $error_message, "Config" );
									exit;
								}else{
									if( isset( $key_attrs['col_name'] ) ){
										$query .= sprintf( ", INDEX %s ( %s )", (string) $key_attrs['name'], (string) $key_attrs['col_name'] ) ;
									}else{
										$query .= sprintf( ", INDEX ( %s )", $key_attrs['col_name'] ) ;
									}
								}
							}else{ //For the moment : "unique"
								if( !isset( $key_attrs['name'] ) ){
									$error_message = sprintf("The 'name' attribute is mandatory for the 'unique' tag - table name = '%s'", $table_name );
									wp_error_log( $error_message, "Config" );
									exit;
								}else if( !isset( $key['column'] ) || count( $key['column'] ) < 1 ){
									$error_message = sprintf("'unique' tag must have minimum a 'column' tag - table name = '%s'", $table_name );
									wp_error_log( $error_message, "Config" );
									exit;
								}else{
									$compound_unique_str = "";
									$k_c = 0;
									foreach ( $key['column'] as $key_col ){
										$attr_key_col = $key_col['@attributes'];
										if( !isset( $attr_key_col['ref'] ) ){
											$error_message = sprintf("The 'ref' attribute is mandatory for the 'column' tag inside 'key' tag - table name = '%s'", $table_name );
											wp_error_log( $error_message, "Config" );
											exit;
										}else{
											$compound_unique_str .= (string) $attr_key_col['ref'];
											if( $k_c < ( count( $key['column'] ) - 1 ) )
												$compound_unique_str .= ", ";
										}
										$k_c++;
									}
									$query .= sprintf( ", CONSTRAINT %s UNIQUE ( %s )", $key_attrs['name'], $compound_unique_str );
								}
							}
						}
					}
				}
			}
			$query .= " ){$charset_collate};";
			//wp_debug_log( $query );
			\dbDelta( $query );
		}
	}
}

if( !function_exists( "H4APlugin\Core\action_tables_db" ) ) {
	/**
	 * @param array $a_tables
	 * @param string $action
	 *
	 * To delete/insert column in an existing DB tables
	 */
	function action_tables_db( array $a_tables, string $action ){
		wp_debug_log();
		global $wpdb;
		foreach ( $a_tables as $table ){
			$table_attrs = $table['@attributes'];
			$table_without_prefix = $table_attrs['name'];
			$table_name = $wpdb->prefix . get_current_plugin_prefix() . $table_without_prefix;
			if( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
				$message_error = sprintf( __( "Error : The table '%s' does not exist.", get_current_plugin_domain() ), $table_name );
				if( $action === "insert"){
					wp_die( $message_error );
					exit;
				}else{
					error_log( $message_error );
				}
			}else{
				if( isset( $table['column'] ) ){
					$columns = $table['column'];
					if( isset( $columns['@attributes'] ) ){
						/* case 1 : only one column tag*/
						$unique_column = $columns;
						$column_attrs = $unique_column['@attributes'];
						securized_db_column_action( $action, $table_without_prefix, $column_attrs );
					}else{
						/* case 2 : several column tag*/
						foreach( $columns as $column ){
							$column_attrs = $column['@attributes'];
							securized_db_column_action( $action, $table_without_prefix, $column_attrs );
						}
					}
				}
				/*if( $action === "delete" )*/
				//H4APlugin\Core\delete_table_db(); /* Case table is empty - Not useful yet*/
			}
		}
	}

}

if( !function_exists( "H4APlugin\Core\securized_db_column_action" ) ) {
	/**
	 * @param string $action
	 * @param $table_name_without_prefix
	 * @param $column_attrs
	 */
	function securized_db_column_action( string $action, $table_name_without_prefix, $column_attrs ) {
		wp_debug_log();
		$is_already_column = is_column_exists( $column_attrs['name'], $table_name_without_prefix );
		$function_name = "H4APlugin\Core\\" . $action . "_table_column_db";
		global $wpdb;
		$table_name = $wpdb->prefix . get_current_plugin_prefix() . $table_name_without_prefix;
		if ( $action === "insert" ) {
			if ( ! $is_already_column ) {
				wp_debug_log( $function_name );
				$function_name( $table_name, $column_attrs );
			} else {
				$message_warning = sprintf( __( "Impossible to insert a new database column '%s' because it´s already exists in the table '%s'.", get_current_plugin_domain() ), $column_attrs['name'], $table_name );
				wp_warning_log( $message_warning );
			}
		}else if ( $action === "delete" ) {
			if ( $is_already_column ) {
				$function_name( $table_name, $column_attrs );
			} else {
				$message_warning = sprintf( __( "Impossible to delete the column '%s' because it does not exist in the table '%s'.", get_current_plugin_domain() ), $column_attrs['name'], $table_name );
				wp_warning_log( $message_warning );
			}
		}
	}
}

if( !function_exists( "H4APlugin\Core\securized_db_data_action" ) ) {
	/**
	 * @param string $action
	 * @param $data_item_attrs
	 * @param $table_name_without_prefix
	 */
	function securized_db_data_action( string $action, $data_item_attrs, $table_name_without_prefix ) {
		$is_data_db_exists = is_data_db_exists( $data_item_attrs, $table_name_without_prefix );
		$function_name = "H4APlugin\Core\\" .$action . "_data_db";
		global $wpdb;
		$table_name = $wpdb->prefix . get_current_plugin_prefix() . $table_name_without_prefix;
		if ( $action === "insert" ) {
			if ( !$is_data_db_exists ) {
				$function_name( $table_name, $data_item_attrs );
			}else {
				$message_warning = sprintf( __( "Impossible to insert the new data '%s' because it´s already exists in the table '%s'.", get_current_plugin_domain() ), serialize( $data_item_attrs ), $table_name );
				wp_warning_log( $message_warning );
			}
		}else if ( $action === "delete" ) {
			if ( $is_data_db_exists ) {
				$function_name( $table_name, $data_item_attrs );
			}else {
				$message_warning = sprintf( __( "Impossible to delete the data '%s' because it does not exist in the table '%s'.", get_current_plugin_domain() ), serialize( $data_item_attrs ), $table_name );
				wp_warning_log( $message_warning );
			}
		}
	}
}

if( !function_exists( "H4APlugin\Core\action_data_db" ) ) {
	/**
	 * @param array $a_inserts
	 * @param string $action
	 *
	 * To delete/insert data in an existing DB tables
	 */
	function action_data_db( array $a_inserts, string $action ){
		wp_debug_log();
		global $wpdb;
		foreach ( $a_inserts as $insert ){
			$insert_attrs = $insert['@attributes'];
			$table_name_without_prefix = $insert_attrs['table'];
			$table_name = $wpdb->prefix . get_current_plugin_prefix() . $table_name_without_prefix;
			if( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
				$message_error = sprintf( __( "Error : The table '%s' does not exist.", get_current_plugin_domain() ), $table_name );
				if( $action === "insert"){
					wp_die( $message_error );
					exit;
				}else{
					error_log( $message_error );
				}

			}else{
				if( isset( $insert['data'] ) ){
					$data = $insert['data'];
					if( isset( $data['@attributes'] ) ){
						/* case 1 : only one data_item tag*/
						$unique_data = $data;
						$data_item_attrs = $unique_data['@attributes'];
						securized_db_data_action( $action, $data_item_attrs, $table_name_without_prefix );
					}else{
						/* case 2 : several data_item tag*/
						foreach( $data as $data_item ){
							$data_item_attrs = $data_item['@attributes'];
							securized_db_data_action( $action, $data_item_attrs, $table_name_without_prefix );
						}
					}
				}
			}
		}
	}
}

if( !function_exists( "H4APlugin\Core\insert_table_column_db" ) ) {
	function insert_table_column_db( $table_name, $column_attrs ){
		global $wpdb;
		//Check if column exists
		//SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = "WCqOPsdXwgs_form_item_links" AND column_name = "label_ref"
		$row = $wpdb->get_results(
			"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$table_name}' AND column_name = '{$column_attrs['name']}'"
		);
		if( empty( $row ) ){
			$res = $wpdb->query("ALTER TABLE {$table_name} ADD {$column_attrs['name']} {$column_attrs['type']}" );
			if( $res === false ){
				error_log( sprintf("The column '%s' could not be inserted for the table '%s'", $column_attrs['name'], $table_name ) );
				return false;
			}else{
				return true;
			}
		}else{
			return true;
		}
	}
}
if( !function_exists( "H4APlugin\Core\get_enum_values_table_column_db" ) ) {
	function get_enum_values_table_column_db( $table_without_prefix, $column_name ) {
		wp_debug_log();
		global $wpdb;
		$current_plugin_prefix = get_current_plugin_prefix();
		$query                 = "SHOW COLUMNS FROM {$wpdb->prefix}{$current_plugin_prefix}{$table_without_prefix} WHERE Field = '{$column_name}'";
		$res                   = $wpdb->get_results( $query, ARRAY_A );
		$type                  = $res[0]['Type'];
		preg_match( "/^enum\(\'(.*)\'\)$/", $type, $matches );
		$enum = explode( "','", $matches[1] );

		return $enum;
	}
}

if( !function_exists( "H4APlugin\Core\delete_table_db" ) ) {
	function delete_table_db(){
		//TODO
	}
}

if( !function_exists( "H4APlugin\Core\delete_table_column_db" ) ) {
	function delete_table_column_db( $table_name, $column_attrs ){
		global $wpdb;

		$query              = "SELECT COUNT(*) AS no_of_columns FROM information_schema.columns WHERE table_name = '{$table_name}'";
		$check_column_count = $wpdb->get_results( $query, ARRAY_A );
		$nbr_columns = (int) $check_column_count[0]['no_of_columns'];
		if( $nbr_columns === 1 ){
			$res = $wpdb->query( "DROP TABLE {$table_name}"  );
		}else{
			$res = $wpdb->query( "ALTER TABLE {$table_name} DROP {$column_attrs['name']}" );
		}
		if( $res === false ){
			error_log( sprintf("The column %s could not be deleted for the table %s", $column_attrs['name'], $table_name ) );
		}
	}
}

if( !function_exists( "H4APlugin\Core\insert_data_db" ) ) {
	function insert_data_db( string $table_name, array $data_item_attrs ){
		global $wpdb;
		$row = $data_item_attrs;
		$str_column_names = "(";
		$str_values = "(";
		$d = 1;
		foreach ( $row as $col => $val ){
			if( $val === "NOW" ){
				$str_values .= "'" . get_today_as_datetime() . "'";
			}else if( $val === "CURRENT" ){
				$str_values .= get_current_user_id();
			}else if( is_number( $val ) ){
				$str_values .= $val;
			}else if( $val === "NULL" ){
				$str_values .= $val;
			}else{
				$str_values .= "'" . $val . "'";
			}
			$str_column_names .= $col;
			if( $d < count( $row ) ){
				$str_column_names .= ",";
				$str_values .= ",";
			}
			$d++;
		}
		$str_column_names .= ")";
		$str_values .= ")";
		// Notice: $wpdb->insert does not support NULL value
		$query_string = "INSERT INTO {$table_name} {$str_column_names} VALUES {$str_values}";

		$is_data_inserted = $wpdb->query( $query_string );

		if( !$is_data_inserted ){
			$message_error = sprintf( "impossible to insert the data %s for the table %s!",
				serialize( $data_item_attrs ),
				$table_name
			);
			$wpdb->show_errors();
			wp_error_log( $message_error, "Insert Data Error" );
			return false;
		}
		return true;
	}
}

if( !function_exists( "H4APlugin\Core\delete_data_db" ) ) {
	function delete_data_db( $table_name, $data_item_attrs ){
		global $wpdb;
		$row = $data_item_attrs;
		$str_where = "";
		$d = 0;
		foreach ( $row as $column_name => $value ){
			if( !in_array( $value, array( "CURRENT", "NOW" )) ){
				if( $d > 0 )
					$str_where .= " AND ";
				$str_where .= $column_name;
				if( is_number( $value ) ){
					$str_where .= "=" . $value;
				}else if( $value === "NULL" ){
					$str_where .= " IS NULL";
				}else{
					$str_where .= "='" . $value . "'";
				}
			}
			$d++;
		}

		// Notice: $wpdb->delete does not support NULL value
		$query_string = "DELETE FROM {$table_name} WHERE {$str_where}";

		$is_data_deleted = $wpdb->query( $query_string );

		if( !$is_data_deleted){
			wp_error_log( sprintf( "Impossible to delete the data '%s' for the table %s!", serialize( $data_item_attrs ), $table_name ) );
			return false;
		}else{
			return true;
		}

	}
}


/**
 *
 * To SANITIZE, VALIDATE and ESCAPE data
 */

if( !function_exists( "H4APlugin\Core\h4a_delete_option" ) ) {
	/**
	 * @param string $option_name
	 *
	 * @return bool
	 */
	function h4a_delete_option( string $option_name ) {
		return delete_option( maybe_serialize( esc_attr( sanitize_key( $option_name ) ) ) );
	}
}

/**
 *
 * To SANITIZE, VALIDATE and ESCAPE data
 */

if( !function_exists( "H4APlugin\Core\h4a_delete_transient" ) ) {
	/**
	 * @param string $option_name
	 *
	 * @return bool
	 */
	function h4a_delete_transient( string $option_name ) {
		return delete_transient( maybe_serialize( esc_attr( sanitize_key( $option_name ) ) ) );
	}
}

//if( !function_exists( "H4APlugin\Core\h4a_set_transient" ) ) {
//	/**
//	 * @param string $transient_name
//	 * @param $value
//	 *
//	 * @return bool
//	 */
//	function h4a_set_transient( string $transient_name, $value, $expiration_time = MINUTE_IN_SECONDS ) {
//		$f_transient_name = maybe_serialize( esc_attr( sanitize_key( $transient_name ) ) );
//		$f_value = ( !is_scalar( $value ) ) ? maybe_serialize( $value ) : esc_attr( sanitize_text_field( $value ) );
//		return set_transient(  $f_transient_name, $f_value, $expiration_time );
//	}
//}

if( !function_exists( "H4APlugin\Core\h4a_delete_transient" ) ) {
	/**
	 * @param string $transient_name
	 *
	 * @return bool
	 */
	function h4a_delete_transient( string $transient_name ) {
		return delete_option( maybe_serialize( esc_attr( sanitize_key( $transient_name ) ) ) );
	}
}

if( !function_exists( "H4APlugin\Core\sanitize_text_or_array_field" ) ) {
	/**
	 * Recursive sanitation for text or array
	 *
	 * @param $array_or_string (array|string)
	 * @since  0.1
	 * @return mixed
	 */
	function sanitize_text_or_array_field($array_or_string) {
		if( is_string($array_or_string) ){
			$array_or_string = sanitize_text_field($array_or_string);
		}elseif( is_array($array_or_string) ){
			foreach ( $array_or_string as $key => &$value ) {
				if ( is_array( $value ) ) {
					$value = sanitize_text_or_array_field($value);
				}
				else {
					$value = sanitize_text_field( $value );
				}
			}
		}

		return $array_or_string;
	}
}

/**
 * 5.0 - Logs
 * -----------------------------------------------------------------------------
 */

if( !function_exists( "H4APlugin\Core\wp_notice_log" ) ) {
	function wp_notice_log( $str_or_array, $level, $type, $report_name = "", $log_code = null ){
		if( is_admin() && isset( $_GET['page'] ) && $_GET['page'] === "debug_log" ){
			return;
		}
		$function = "unknown function";
		$debug_trace = debug_backtrace();
		$h4a_config = Config::getConfig();
		$a_exclude_functions = array( "H4APlugin\Core\wp_notice_log", "H4APlugin\Core\wp_debug_log", "H4APlugin\Core\wp_info_log", "H4APlugin\Core\wp_warning_log", "H4APlugin\Core\wp_error_log" );
		foreach ( $debug_trace as $k_trace => $trace ){
			if( isset( $trace['function'] ) && !in_array( $trace['function'], $a_exclude_functions ) ){
				$function = $trace['function'];
				break;
			}
		}
		//error_log_array( debug_backtrace() );
		$class = "";
		foreach ( $debug_trace as $trace ){
			if( isset( $trace['class'] ) ){
				$class = $trace['class'];
				break;
			}
		}
		if( isset( $h4a_config['modules']['log_reports'] ) && $h4a_config['modules']['log_reports']
		    && in_array( $report_name, array( "admin", "users" ) )
		    && is_integer( $log_code) ){
			$log_dir_path = get_current_plugin_dir_path() . "logs/";
			if ( !file_exists( $log_dir_path )) {
				mkdir( $log_dir_path, 0777, true);
			}
			if( !is_string( $str_or_array ) ){
				wp_error_log( "The log message is not a string. To do this in debug.log, please set logs to 'false' in config.ini", "config");
			}else{
				$destination = $log_dir_path . "/" . $report_name . ".log";

				/*if( file_exists( $destination ) && filesize( $destination ) > 5000 ){
					error_log( filesize( $destination ) );
					unlink( $destination );
				}*/

				if( !file_exists( $destination )  ){
					$log = "level~date~class~function~code~type~message\r\n";
					error_log( $log, 3, $destination );
				}

				$f_str_or_array = preg_replace('/\s+/', ' ', $str_or_array );
				$f_str_or_array = htmlspecialchars ( $f_str_or_array );

				$log          = sprintf(
					"%s~%s~%s~%s~%s~%s~%s\r\n",
					$level,
					get_today_as_datetime(),
					$class,
					$function,
					$log_code,
					$type,
					$f_str_or_array
				);
				error_log( $log, 3, $destination );
			}
		}else{
			$current_plugin_short_title = get_current_plugin_short_title();
			if( is_string( $str_or_array ) ){
				$log = sprintf('<span class="notice-log-%s">%s [%s - %s] - %s : (%s) %s</span>', $level, $current_plugin_short_title, $class, $function, ucfirst( $level ), $type, $str_or_array );
				$log = preg_replace('/\t+/', '', $log );
				$log = preg_replace('/\n+/', '', $log );
				$log = preg_replace('/\r+/', '', $log );
				error_log( trim( $log ) );
			}else{
				$log = sprintf('<span class="notice-log-%s">%s [%s - %s] - %s : (%s) %s</span>', $level, $current_plugin_short_title, $class, $function, ucfirst( $level ), $type, gettype( $str_or_array ) );
				$log = preg_replace('/\t+/', '', $log );
				$log = preg_replace('/\n+/', '', $log );
				$log = preg_replace('/\r+/', '', $log );
				error_log( trim( $log ) );
				error_log( sprintf('<div class="notice-log-%s" >', $level ) );
				error_log( print_r( $str_or_array, true ) );
				error_log( '</div>' );
			}
		}
	}
}

/* 5.1 - Debug */
if( !function_exists( "H4APlugin\Core\wp_debug_log" ) ) {
	function wp_debug_log( $str_or_array = "", $type = "system", $report_name = "admin", $log_code = 1 ){
		$h4a_config = Config::getConfig();
		if( !isset( $_GET['page'] ) || ( $_GET['page'] !== "logs-wp-group-subscriptions" ) ){
			if( (int) $h4a_config['modules']['level_logs'] === 1 ){
				wp_notice_log( $str_or_array, "debug", $type, $report_name, $log_code );
			}
		}
	}
}

/* 5.2 - Info */
if( !function_exists( "H4APlugin\Core\wp_info_log" ) ) {
	function wp_info_log( $str_or_array = "", $type = "system", $report_name = "admin", $log_code = 2 ){
		$h4a_config = Config::getConfig();
		if( (int) $h4a_config['modules']['level_logs'] <= 2 ){
			wp_notice_log( $str_or_array, "info", $type, $report_name, $log_code );
		}
	}
}

/* 5.3 - Warnings */
if( !function_exists( "H4APlugin\Core\wp_warning_log" ) ) {
	function wp_warning_log( $str_or_array = "", $type = "system", $report_name = "admin", $log_code = 3 ){
		$h4a_config = Config::getConfig();
		if( (int) $h4a_config['modules']['level_logs'] <= 3 ){
			wp_notice_log( $str_or_array, "warning", $type, $report_name, $log_code );
		}
	}
}

/* 5.4 - Errors */
if( !function_exists( "H4APlugin\Core\wp_error_log" ) ) {
	function wp_error_log( $str_or_array = "", $type = "system", $report_name = "admin", $log_code = 4 ) {
		wp_notice_log( $str_or_array, "error", $type, $report_name, $log_code );
	}
}

if( !function_exists( "H4APlugin\Core\wp_get_error_system" ) ) {
	function wp_get_error_system() {
		return __( "Error system ! If you see this message, please contact the administrator of this Web site.", get_current_plugin_domain() );
	}
}

if( !function_exists( "H4APlugin\Core\wp_get_error_back_end_system" ) ) {
	function wp_get_error_back_end_system() {
		return sprintf( __( "Error system ! If you see this message, please contact the developer of %s.", get_current_plugin_domain() ), get_current_plugin_title() );
	}
}

if( !function_exists( "H4APlugin\Core\wp_get_setting_missing_error" ) ) {
	function wp_get_setting_missing_error(){
		return __( "Settings missing error - if you see this message, please contact the administrator" , get_current_plugin_domain() );
	}
}

/**
 * 6.0 - WP Settings
 * -----------------------------------------------------------------------------
 */

if( !function_exists( "H4APlugin\Core\get_settings_fields" ) ) {
	function get_settings_fields( $option_group ) {
		$html = "";
		$html .= '<input type="hidden" name="option_page" value="' . esc_attr( $option_group ) . '" />';
		$html .= '<input type="hidden" name="action" value="update" />';
		$html .= wp_nonce_field( "$option_group-options", "_wpnonce", true, false );

		return $html;
	}
}



