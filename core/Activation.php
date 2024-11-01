<?php
namespace H4APlugin\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Activation{

	public static function init() {
		include_dir_r( dirname( __FILE__ ) . "/../common/cron" );
		wp_debug_log();
		$h4a_config = Config::getConfig();
		if( !empty( $h4a_config['cron'] ) ){
			foreach ( $h4a_config['cron'] as $c_cron_task ){
				$attr_task = $c_cron_task['@attributes'];
				add_action( $attr_task['hook'], $attr_task['call']);
			}
		}
		register_activation_hook( get_current_plugin_basename(), [ __CLASS__, 'activation' ] );
		register_deactivation_hook( get_current_plugin_basename(), [ __CLASS__, 'deactivation' ] );
	}

	public static function activation(){
		wp_debug_log();
		// Install needed components on plugin activation
		self::install();
		self::cron_activation();
	}

	public static function deactivation(){
		wp_debug_log();
		$h4a_config = Config::getConfig();
		if( !empty( $h4a_config['cron'] ) ){
			foreach ( $h4a_config['cron'] as $c_cron_task ){
				$attr_task = $c_cron_task['@attributes'];
				wp_clear_scheduled_hook( $attr_task['hook'] );
			}
		}
	}

	private static function cron_activation(){
		wp_debug_log();
		$h4a_config = Config::getConfig();
		if( !empty( $h4a_config['cron'] ) ){
			foreach ( $h4a_config['cron'] as $c_cron_task ){
				$attr_task = $c_cron_task['@attributes'];
				if( function_exists( $attr_task['function'] ) ){
					$attr_task['function']();
				}
			}
		}
	}

	private static function install(){
		wp_debug_log();
		$h4a_config = Config::getConfig();
		$c_install =  $h4a_config['install'];
		if( !empty( $c_install ) && !empty( $c_install['database'] ) ){
			$c_database = $c_install['database'];
			if( !empty( $c_database['tables'] ) ){
				$c_tables = $c_database['tables'];
				global $wpdb;
				$c_first_table_attrs = $c_tables[0]['@attributes'];
				$first_table_key_name = $c_first_table_attrs['name'];
				$table_name = $wpdb->prefix . get_current_plugin_prefix() . $first_table_key_name;
				if( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
					self::create_tables();
					if( !empty( $c_database['inserts'] ) ){
						self::insert_data();
					}
					if( !empty( $c_database['posts'] ) ){
						self::insert_posts();
					}
				}
			}
		}
	}


	/**********/
	/* POSTS  */
	/**********/


	private static function insert_posts(){
		wp_debug_log();
		$h4a_config = Config::getConfig();
		$posts = $h4a_config['install']['database']['posts'];
		if( !empty( $posts ) ){
			foreach ( $posts as $post ){
				$post_attrs = $post['@attributes'];
				if( !isset( $post_attrs['type'] ) ){
					wp_error_log( "Post Config imcomplete, 'type' attribute is mandatory", "Config" );
				}else{
					$author_attr = $post['author']['@attributes'];
					$id_author = ( !empty( $author_attr['auto'] ) && (boolean) $author_attr['auto'] ) ? get_current_user_id() : (int) $author_attr->id;
					$post_status = ( !empty( (string) $post_attrs['status'] ) ) ? (string) $post_attrs['status'] : null;
					$comment_status = ( !empty( (string) $post_attrs['comment_status'] ) ) ? (string) $post_attrs['comment_status'] : null;
					$ping_status = ( !empty( (string) $post_attrs['ping_status'] ) ) ? (string) $post_attrs['ping_status'] : null;
					if( !empty( (string) $post['guid'] ) ){
						if( (string) $post['guid'] === "null" ){
							$guid = null;
						}else{
							$guid = (string) $post['guid'];
						}
					}else{
						$guid = null;
					}
					$new_post_data = array(
						'post_type'       => (string) $post_attrs['type'],
						'post_status'     => $post_status,
						'post_title'      => __( (string) $post['title'], get_current_plugin_domain() ),
						'post_content'    => (string) $post['content'],
						'comment_status'  => $comment_status,
						'ping_status'     => $ping_status,
						'post_author'     => $id_author,
						'guid'            => $guid
					);
					$post_id = wp_insert_post( $new_post_data, true);
					if( is_wp_error( $post_id ) ) {
						wp_error_log( $post_id->get_error_message(), "Insert Posts " . "[" . __CLASS__ . "]" );
					}
				}
			}
		}
	}

	/***************/
	/*   DATABASE  */
	/***************/

	private static function create_tables(){
		wp_debug_log();
		$h4a_config = Config::getConfig();

		// Add / Update the tables as needed
		$c_tables = $h4a_config['install']['database']['tables'];
		foreach ( $c_tables as $a_table ){
			create_table_db( $a_table );
		}
	}

	private static function insert_data(){
		wp_debug_log();
		$h4a_config = Config::getConfig();
		global $wpdb;
		$inserts = $h4a_config['install']['database']['inserts'];
		if( !empty( $inserts ) ){
			foreach ( $inserts as $insert ){
				$insert_attrs = $insert['@attributes'];
				$table_key_name = $insert_attrs['table'];
				$table_name = $wpdb->prefix . get_current_plugin_prefix() . $table_key_name;
				foreach ( $insert['data'] as $data ){
					$data_item_attrs = (array) $data['@attributes'];
					insert_data_db( $table_name, $data_item_attrs );
				}
			}
		}
	}
}


