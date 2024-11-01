<?php

namespace H4APlugin\Core;


use H4APlugin\Core\Admin\CSV_Item_Params;
use H4APlugin\Core\Common\DB_Item_Params;
use H4APlugin\Core\Common\EditableItem;

class Config {

	private $current_plugin_config_path;

	//Config.ini
	private $general = array(
		'plugin_info' => null,
		'offices' => null,
		'rights' => null,
		'modules' => null,
	);
	//install.xml
	private $install;
	//items.xml
	private $items;
	//pages.xml
	private $pages;
	//menus.xml
	private $menus;
	//shortcodes.xml
	private $shortcodes;
	//post-types.xml
	private $post_types;
	//settings.xml
	private $settings;
	//addons.xml
	private $addons;
	//cron.xml
	private $cron;

	public function __construct()
	{
		$this->current_plugin_config_path = dirname( __FILE__) . "/../config/";
		// Load general config
		$this->general = parse_ini_file($this->current_plugin_config_path . "config.ini", TRUE );

		//1. Load post types config
		$str_post_types     = file_get_contents($this->current_plugin_config_path . "post-types.xml" );
		$this->post_types   = xmlAsStringToArray( $str_post_types );;

		//2. Load addons config
		$str_addons      = file_get_contents($this->current_plugin_config_path . "addons.xml" );
		$this->addons    = xmlAsStringToArray( $str_addons );

		//3.Eventual general modification by addons
		if( !empty( $this->addons ) && isset( $this->addons['addon'] ) ){
			if( !empty( $this->addons['addon']['@attributes'] ) ){ //Case : only one addon
				$addon_config_filepath = WP_PLUGIN_DIR . "/" . $this->addons['addon']['@attributes']['dir'] . "/config/config.ini" ;
				$this->override_config_general( $addon_config_filepath );
			}else{
				foreach ( $this->addons['addon'] as $addon ){
					$addon_config_filepath = WP_PLUGIN_DIR . "/" . $addon['@attributes']['dir'] . "/config/config.ini" ;
					$this->override_config_general( $addon_config_filepath );
				}
			}
		}


		//4. Load cron config
		$str_cron      = file_get_contents($this->current_plugin_config_path . "cron.xml" );
		$this->cron   = xmlAsStringToArray( $str_cron, true );

		//5. Load items config
		/*$str_items       = file_get_contents($this->current_plugin_config_path . "items.xml" );
		$this->items     = xmlAsStringToArray( $str_items );*/
		$this->load_xml_files_config( "items.xml","items", "item"  );
		/*pretty_var_dump( $this->items  );
		exit;*/

		if( is_admin() ){
			//6. Load install config
			$this->load_install_config();

			//7. Load pages
			$this->load_xml_files_config( "pages.xml","pages", "page"  );

			//8. Load menus
			$this->load_xml_files_config( "menus.xml", "menus", "menu"  );

			//9. Load settings
			$this->load_xml_files_config( "settings.xml", "settings", "options"  );
		}else{
			//6. Load shortcodes config
			$this->load_xml_files_config( "shortcodes.xml", "shortcodes", "shortcode"  );
		}
		$a_config = array(
			'plugin_info' => $this->general['plugin_info'],
			'offices' => $this->general['offices'],
			'rights' => $this->general['rights'],
			'modules' => $this->general['modules'],
			'addons' => $this->addons['addon'],
			'cron' => $this->cron['children'],
			'post_types' => $this->post_types['post_type'],
			'items' => $this->items
		);

		if( is_admin() ){
			$a_config['install'] = $this->install;
			$a_config['pages'] = $this->pages;
			$a_config['menus'] = $this->menus;
			$a_config['settings'] = $this->settings;
		}else{
			$a_config['shortcodes'] = $this->shortcodes;
		}

		//Constants
		$initials = $a_config['plugin_info']['initials'];
		$config_const_name = sprintf( "H4A_%s_CONFIG", $initials );
		define( $config_const_name, serialize( $a_config ) );

		$plugin_title = sprintf( "H4A_%s_PLUGIN_TITLE", $initials );
		define( $plugin_title, $a_config['plugin_info']['title'] );
		$plugin_short_title = sprintf( "H4A_%s_PLUGIN_SHORT_TITLE", $initials );
		define( $plugin_short_title, $a_config['plugin_info']['title_short']);
		$plugin_version = sprintf( "H4A_%s_PLUGIN_VERSION", $initials );
		define( $plugin_version, $a_config['plugin_info']['version'] );

	}

	public static function getConfig(){
		if( defined( "H4A_WGS_CONFIG" ) ){
			return unserialize( H4A_WGS_CONFIG );
		}else{
			return false;
		}
	}

	/**
	 * load install xml data in $h4a_config
	 */
	private function load_install_config() {
		$array_install_xml   = array();
		$array_install_xml[] = $this->current_plugin_config_path . "install.xml";
		if ( $this->general['modules']['form'] ) {
			$array_install_xml[] = dirname( __FILE__ ) . "/common/features/form/db/install.xml";
		}
		if ( $this->general['modules']['countries'] ) {
			$array_install_xml[] = dirname( __FILE__ ) . "/common/features/countries/db/install.xml";
		}
		if ( $this->general['modules']['currencies'] ) {
			$array_install_xml[] = dirname( __FILE__ ) . "/common/features/currencies/db/install.xml";
		}
		if ( count( $array_install_xml ) > 1 ) {

			$output_filename = $this->current_plugin_config_path . "tmp/install.xml";
			$array_root_tags = array( 'table' => "name", 'insert' => "table" );
			array_merge_xml( $array_install_xml, $array_root_tags, $output_filename );
			$str_install = file_get_contents( $output_filename );
		}else{
			$str_install = file_get_contents( $this->current_plugin_config_path . "install.xml" );
		}

		$a_install = xmlAsStringToArray( $str_install );

		$f_install = array(
			'database' => array(
				'tables' => array(),
				'inserts' => array(),
				'posts' => array()
			)
		);
		//Caution : Do not factorize because if we add in the future attributes for tables, inserts, or posts
		//          That will break code.

		if( isset( $a_install['database']['tables']['table']['@attributes'] ) ){ //Fix xml when there is only one table tag
			$f_install['database']['tables'][0] = $a_install['database']['tables']['table'];
		}else{
			foreach ( $a_install['database']['tables']['table'] as $table ){
				$f_install['database']['tables'][] = $table;
			}
		}

		if( isset( $a_install['database']['inserts']['insert']['@attributes'] ) ){ //Fix xml when there is only one insert tag
			$f_install['database']['inserts'][0] = $a_install['database']['inserts']['insert'];
		}else{
			foreach ( $a_install['database']['inserts']['insert'] as $insert ){
				$f_install['database']['inserts'][] = $insert;
			}
		}

		if( isset( $a_install['database']['posts']['post']['@attributes'] ) ){ //Fix xml when there is only one post tag
			$f_install['database']['posts'][0] = $a_install['database']['posts']['post'];
		}else{
			foreach ( $a_install['database']['posts']['post'] as $post ){
				$f_install['database']['posts'][] = $post;
			}
		}

		$this->install = $f_install;
		//error_log_array( $output_filename );
	}

	private function load_xml_files_config( $xml_filename, $root_tag, $tag_to_insert ){
		if( $xml_filename !== "pages" && !empty( $this->$root_tag ) ){
			$error_message = sprintf( "%s data are already loaded in config.", ucfirst( $root_tag ) );
			wp_error_log( $error_message, "Config" );
		}else{
			$array_items_xml   = array();
			$array_items_xml[] = $this->current_plugin_config_path . $xml_filename;
			//Modules
			if ( $this->general['modules']['log_reports'] ) {
				$module_file_path = dirname(__FILE__) . "/admin/features/log_reports/config/" . $xml_filename;
				if( file_exists( $module_file_path ) )
					$array_items_xml[] = $module_file_path;
			}
			//Addons
			$is_license_activated = is_license_activated();

			if( !empty( $this->addons ) && isset( $this->addons['addon'] ) ){
				if( !empty( $this->addons['addon']['@attributes'] ) ){ //Case : only one addon
					$attr_addon = $this->addons['addon']['@attributes'];
					if( $is_license_activated || $attr_addon['main'] === "WGSLicenseKeyAddon.php" ){
						$array_items_xml = $this->load_addon_config_xml_file( $xml_filename, $attr_addon, $array_items_xml );
					}
				}else{
					foreach ( $this->addons['addon'] as $addon ){
						$attr_addon = $addon['@attributes'];
						if( $is_license_activated || $attr_addon['main'] === "WGSLicenseKeyAddon.php" ){
							$array_items_xml = $this->load_addon_config_xml_file( $xml_filename, $attr_addon, $array_items_xml );
						}
					}
				}
			}

			if ( count( $array_items_xml ) > 1 ) {
				$output_filename = $this->current_plugin_config_path . "tmp/" . $xml_filename;
				$array_root_tags = array( $tag_to_insert => "slug" );
				array_merge_xml( $array_items_xml, $array_root_tags, $output_filename );
				$str_xml = file_get_contents( $output_filename );
			}else{
				$str_xml = file_get_contents($this->current_plugin_config_path . $xml_filename );
			}
			$this->$root_tag = xmlAsStringToArray( $str_xml, true );
		}
	}

	public static function get_page_by_config(){
		$h4a_config = self::getConfig();
		if( !empty( $h4a_config['pages'] ) ){
			$c_pages = $h4a_config['pages'];
			$position = 1;
			foreach ( $c_pages['children'] as $c_page ){
				$page_slug = self::get_page_slug($c_page);
				if( empty( $page_slug ) ){
					$error_message = sprintf( "Page %s error : slug attribute missing", $position );
					wp_error_log( $error_message, "Config" );
					break;
				}
				if( $page_slug === $_GET['page'] ){
					$page_data = array(
						'slug' => $page_slug
					);
					$attrs_page = $c_page['@attributes'];
					if( isset( $attrs_page['text_domain'] ) )
						$page_data['text_domain'] = $attrs_page['text_domain'];
					if( !empty( $attrs_page['parent'] ) ){
						//SubPage
						$className = "SubPage";
						$page_data['parent_slug'] = (string) $attrs_page['parent'];
						$page_data['page_title'] = self::get_subpage_title( $c_page );
					}else{
						//Page
						$className = "Page";
						$page_data['page_title'] = self::get_page_title( $c_page );
					}
					$page_data['templates'] = self::get_templates_as_class( $c_page );
					$classPath = "H4APlugin\Core\Admin\\" . $className;
					new $classPath( $page_data );
					break;
				}
				$position++;
			}
		}
	}

	/**
	 * @param array $c_page
	 *
	 * @return array
	 */
	public static function get_templates_as_array( array $c_page ){
		$c_templates = self::getChildrenItem( $c_page, "template" );
		if( count( $c_templates ) === 0 ){
			$attrs_page = $c_page['@attributes'];
			$error_message = sprintf( "'%s' page error : template missing", $attrs_page['slug'] );
			wp_error_log( $error_message, "Config" );
			exit;
		}else{
			return $c_templates;
		}
	}

	/**
	 * @param array $c_page
	 *
	 * @return array of Template object
	 */
	public static function get_templates_as_class( array $c_page ){
		$output = array();
		$page_slug = self::get_page_slug($c_page);
		$number_template = 0;
		$c_templates = self::getChildrenItem( $c_page, "template" );
		foreach( $c_templates as $c_template ){
			if( !empty( $c_template['@attributes'] ) ){
				$attrs_template = $c_template['@attributes'];
				$tmpl_key = ( !empty( $attrs_template['slug'] ) ) ? self::f_str( $attrs_template['slug'] )  : $number_template;
			}else{
				$tmpl_key = $number_template;
			}
			$output[ $tmpl_key ] = self::get_template_as_class( $c_template, $page_slug, ( $number_template + 1 ) );
			$number_template++;
		}
		return $output;
	}

	/**
	 * @param array $c_template
	 * @param string $page_slug
	 * @param int $tmpl_number
	 *
	 * @return mixed ( Template class)
	 */
	public static function get_template_as_class( array $c_template, $page_slug, $tmpl_number ){
		$className = "";
		if( isset( $c_template['@attributes'] ) )
			$attrs_template = $c_template['@attributes'];

		if(  !isset( $c_template['@attributes'] ) ){
			$class_args['slug'] = $page_slug;
		}else{
			$attrs_template = $c_template['@attributes'];
			if( !isset( $attrs_template['slug'] ) ){
				$class_args['slug'] = $page_slug;
			}else{
				$class_args['slug'] = self::f_str( $attrs_template['slug'] );
			}
		}
		$c_template_title = self::getChildItem( $c_template, "title" );
		if( !isset( $c_template_title ) ){
			$error_message = sprintf( "The 'title' attribute is mandatory for the 'template' tag - page '%s', template n° %d", $page_slug, $tmpl_number );
			wp_error_log( $error_message, "Config" );
			exit;
		}else{
			$class_args['title'] = self::get_template_title( $c_template, $page_slug, $tmpl_number );
		}
		$c_template_item = self::getChildItem( $c_template, "item" );
		$c_template_list = self::getChildItem( $c_template, "list" );
		$c_page = self::get_page_data_by_slug($page_slug);
		$attrs_page = $c_page['@attributes'];
		if( $c_template_item !== false ){
			$className = "EditItemTemplate";
			$attrs_item = $c_template_item['@attributes'];
			if( empty( self::f_str( $attrs_item['ref'] ) ) ){
				$error_message = sprintf( "The 'ref' attribute is mandatory for the 'item' tag - page '%s', template n° %d", $page_slug, $tmpl_number );
				wp_error_log( $error_message, "Config" );
				exit;
			}else{
				$c_item = self::get_item_by_ref( $attrs_item['ref'] );
				$item_class = self::f_str( $c_item['class'] );
				$item_format = "edit";
				$is_config_init = ( isset( $c_item['configInit'] ) ) ? asBoolean( $c_item['configInit'] ) : true ;
				if( $is_config_init ){
					$editable_item = new $item_class(
						0,
						$item_format,
						self::get_item_params_by_item_ref( $attrs_item['ref'], $page_slug )
					);
					$class_args['editable_item'] = $editable_item;
				}
				$list_parent_page = $attrs_page['parent'];
				if( empty($list_parent_page) ){
					$error_message = sprintf( "The 'parent' attribute is mandatory for the 'page' tag when it has got an 'item' tag as child - page '%s', template n° %d", $page_slug, $tmpl_number );
					wp_error_log( $error_message, "Config" );
					exit;
				}else{
					$class_args['list_page_slug'] = $list_parent_page;
				}
			}
		}else if( $c_template_list !== false ){
			$attr_list = $c_template_list['@attributes'];
			$attr_data_list = self::f_str($attr_list['data']);
			if( empty( $attr_list['data'] )
			    || !in_array( $attr_data_list, array( "db", "csv" ) )
			){
				$error_message = sprintf( "The 'data' attribute is mandatory for the 'list' tag with only one of this values : 'db' or 'csv' - page '%s', template n° %d", $page_slug, $tmpl_number );
				wp_error_log( $error_message, "Config" );
				exit;
			}else{
				$c_template_list_columns = self::getChildItem( $c_template_list, "columns" );
				$c_template_list_item = self::getChildItem( $c_template_list, "item" );
				if( $attr_data_list === "db" ){
					$className = "ListTableFromDBTemplate";
					$c_template_list_query = self::getChildItem( $c_template_list, "query" );
					$c_template_list_output = self::getChildItem( $c_template_list, "output" );
					if( !isset( $c_template_list_item )
					    || empty( $c_template_list_query )
					    || empty( $c_template_list_output )
					    || empty( $c_template_list_columns )
					){
						$error_message = sprintf( "The tags 'item', 'query', 'output' and 'column' are mandatory inside the 'list' tag - page '%s', template n° %d", $page_slug, $tmpl_number );
						wp_error_log( $error_message, "Config" );
						exit;
					}else{
						$attrs_list_item = $c_template_list_item['@attributes'];
						if( empty( $attrs_list_item['ref'] )
						    || empty( $attrs_list_item['singular'] )
						    || empty( $attrs_list_item['plural'] )
						){
							$error_message = sprintf( "'ref', 'singular' and 'plural' attributes are mandatory for the 'item' tag - page '%s', template n° %d", $page_slug, $tmpl_number );
							wp_error_log( $error_message, "Config" );
							exit;
						}else{
							$data_item_params = self::get_item_params_by_item_ref( (string) $attrs_list_item['ref'], $page_slug );
							$data_item_params['singular'] = (string) $attrs_list_item['singular'];
							$data_item_params['plural']   = (string) $attrs_list_item['plural'];
							if( !empty( $attrs_page['text_domain'] ) )
								$class_args['text_domain'] = $attrs_page['text_domain'];
							$class_args['item_params'] = new DB_Item_Params( $data_item_params ) ;
							$class_args['query']   = $c_template_list_query;
							$class_args['output']  = $c_template_list_output;
							$class_args['columns'] = self::getChildrenItem( $c_template_list_columns, "column" );

							foreach ($class_args['columns'] as $c_column) {
								$attr_column = $c_column['@attributes'];
								if ( !isset( $attr_column['slug'] ) ) {
									$error_message = sprintf("the 'slug' attribute is mandatory for 'column' tag - page '%s', template n° %d", $page_slug, $tmpl_number);
									wp_error_log($error_message, "Config");
									exit;
								} else {
									if ( isset( $attr_column['primary'] ) && asBoolean( (string)$attr_column['primary'] ) ) {
										$class_args['primary'] = self::f_str( $attr_column['slug'] );
									}
								}
							}
							$c_template_list_views = self::getChildItem( $c_template_list, "views" );
							if( !empty( $c_template_list_views ) )
								$class_args['views'] = $c_template_list_views;

							if( !empty( $attr_list['class'] ) )
								$class_args['class'] = self::f_str( $attr_list['class'] );

							if( isset( $attr_list['search'] ) && asBoolean( (string) $attr_list['search'] ) ) {
								$class_args['is_search'] = (string) $attr_list['search'];
							}else{
								$class_args['is_search'] = false;
							}
							if( $class_args['is_search'] ){
								$class_args['search'] = array();
								foreach ( $class_args['query']['children'] as $select ){
									if( isset( $select['@attributes']['search'] ) && asBoolean( $select['@attributes']['search'] ) )
										if( !isset( $select['@attributes']['column'] )
										    || !isset( $select['@attributes']['slabel'] )
										){
											$error_message = sprintf( "The column and slabel attributes are mandatories for a select tag if 'search = \"true\"'. - page '%s', template n° %d", $page_slug, $tmpl_number );
											wp_error_log( $error_message, "Config" );
											exit;
										}else{
											$a_search = array(
												"column" => $select['@attributes']['column'],
												"slabel" => $select['@attributes']['slabel']
											);
											if( isset( $select['@attributes']['alias'] ) )
												$a_search['alias'] = $select['@attributes']['alias'];
											$class_args['search'][] = $a_search;
										}
								}
								if( empty( $class_args['search'] ) ){
									$error_message = sprintf( "The search cannot works without set as 'true' on a select tag. Please add 'search=\"true\"' on minimum one select tag to use it - page '%s', template n° %d", $page_slug, $tmpl_number );
									wp_error_log( $error_message, "Config" );
									exit;
								}
							}
						}
						if( isset( $attr_list['editable'] ) && asBoolean( (string) $attr_list['editable'] ) ){
							if( ! $c_template_list_views ){
								$error_message = sprintf( "'views' tag wrapping at least 'default' tag is mandatory for an editable list. Please add 'views' and 'default' - page '%s', template n° %d", $page_slug, $tmpl_number );
								wp_error_log( $error_message, "Config" );
								exit;
							}else{
								$className = "Editable" . $className;
								$class_args['actions'] = self::get_list_actions_by_views( $c_template_list_views, $page_slug );
							}
						}
					}
				}
				else if( $attr_data_list === "csv" ){
					$className = "ListTableFromCSVTemplate";
					$attrs_list_item = $c_template_list_item['@attributes'];
					if( empty( $attrs_list_item['file'] )
					    || empty( $attrs_list_item['singular'] )
					    || empty( $attrs_list_item['plural'] )
					){
						$error_message = sprintf( "'file', 'singular' and 'plural' attributes are mandatory for the 'item' tag - page '%s', template n° %d", $page_slug, $tmpl_number );
						wp_error_log( $error_message, "Config" );
						exit;
					}else{
						$data_item_params['file'] = (string) $attrs_list_item['file'];
						$data_item_params['singular'] = (string) $attrs_list_item['singular'];
						$data_item_params['plural']   = (string) $attrs_list_item['plural'];
						$class_args['item_params'] = new CSV_Item_Params( $data_item_params );
						$c_columns = self::getChildItem( $c_template_list, "columns" );
						$class_args['columns'] = $c_columns['children'];
					}
				}
			}
		}else{
			if( isset( $attrs_page['parent'] ) && !in_array( $attrs_page['parent'], unserialize( H4A_ARRAY_NATIVE_MENUS_SLUGS ) ) ){
				//SubPage
				$error_message = sprintf( "Default template for subpages is not supported yet - page '%s', template n° %d", $page_slug, $tmpl_number );
				wp_error_log( $error_message, "Config" );
				exit;
			}else{
				//Page
				$class_args['subpages'] =  self::get_all_subpages_by_slug( $page_slug );
				if( !empty( $class_args['subpages'] ) ){
					$className = "MenuTemplate";
				}else{
					$className = "Template";
				}
			}
		}
		//If attribute bind - Custom Template
		if( !empty( $attrs_template['bind'] ) ){
			$class_path = self::f_str( $attrs_template['bind'] );
			if( !class_exists( $class_path ) ){
				$error_message = sprintf( "The class '%s' did not include - page '%s', template n° %d", $class_path, $page_slug, $tmpl_number );
				wp_error_log( $error_message, "Config" );
				exit;
			}
			$c_page = self::get_page_data_by_slug( $page_slug );
			$attrs_page = $c_page['@attributes'];
			if( isset( $attrs_page['parent'] ) ){
				$list_parent_page = self::f_str($attrs_page['parent']);
				if( !empty( $attrs_page['parent'] ) && $list_parent_page === "options-general.php" ){
					$settings_class_path = "H4APlugin\Core\Admin\SettingsTemplate";
					if ( get_parent_class( $class_path ) !== $settings_class_path ){
						$error_message = sprintf( "The class '%s' must extend '%s' - page '%s', template n° %d", $settings_class_path, $className, $page_slug, $tmpl_number );
						wp_error_log( $error_message, "Config" );
						exit;
					}
				}else if ( !empty( $className ) && get_parent_class( $class_path ) !== "H4APlugin\Core\Admin\\" . $className ) {
					$error_message = sprintf( "The class '%s' must extend '%s' - page '%s', template n° %d", $class_path, $className, $page_slug, $tmpl_number );
					wp_error_log( $error_message, "Config" );
					exit;
				}else if( empty( $className ) ){
					wp_error_log( $className );
					$error_message = sprintf( "className is null." );
					wp_error_log( $error_message );
					exit;
				}else{
					wp_debug_log( $className );
				}
			}
		}else{ //Defined Templates
			$class_path = "H4APlugin\Core\Admin\\" . $className;
		}
		return new $class_path( $class_args );
	}

	/**
	 * @param $c_views
	 * @param $page_slug
	 *
	 * @return array
	 */
	private static function get_list_actions_by_views( $c_views, $page_slug ){
		$output = array();
		$c_default_view = Config::getChildItem( $c_views, "default" );
		$c_views = Config::getChildrenItem( $c_views, "view" );
		if( !empty( $c_default_view ) ){
			self::get_action_by_view( $output, "default", $c_default_view, $page_slug );
		}
		if( !empty( $c_views ) ){
			foreach ( $c_views as $c_view ){
				$attr_view = $c_view['@attributes'];
				if( empty( $attr_view['slug'] ) ){
					$error_message = sprintf( "The 'slug' attribute for the 'view' tag is mandatory : page '%s'", $page_slug );
					wp_error_log( $error_message, "Config" );
					exit;
				}else{
					$view_slug = self::f_str( $attr_view['slug'] );
					self::get_action_by_view( $output, $view_slug, $c_view, $page_slug );
				}
			}
		}
		return $output;
	}

	/**
	 * @param array $output
	 * @param $key
	 * @param $c_view
	 * @param $page_slug
	 *
	 * @return array
	 */
	private static function get_action_by_view( array &$output, $key, $c_view, $page_slug ){
		$output[ $key ] = array(
			'bulk_actions' => array(),
			'row' => array()
		);
		$c_bulk_actions = Config::getChildItem( $c_view, "bulk_actions" );
		if( !empty( $c_bulk_actions ) ){
			$c_b_actions = Config::getChildrenItem( $c_bulk_actions, "action" );
			if( empty( $c_b_actions ) ){
				$error_message = sprintf( "The 'action' tag inside 'bulk_actions' is mandatory for an editable list : page '%s'", $page_slug );
				wp_error_log( $error_message, "Config" );
				exit;
			}else{
				foreach ( $c_b_actions as $c_b_action ){
					$output[ $key ]['bulk_actions'][] = self::get_action_by_config( $c_b_action, $page_slug );
				}
			}
		}
		$c_row_actions = Config::getChildItem( $c_view, "row" );
		if( !empty( $c_row_actions ) ){
			$c_r_actions = Config::getChildrenItem( $c_row_actions, "action" );
			if( empty( $c_r_actions ) ){
				$error_message = sprintf( "The 'action' tag inside 'row' is mandatory for an editable list : page '%s'", $page_slug );
				wp_error_log( $error_message, "Config" );
				exit;
			}else{
				foreach ( $c_r_actions as $c_r_action ){
					$output[ $key ]['row'][] = self::get_action_by_config( $c_r_action, $page_slug );
				}
			}
		}
		return $output;
	}

	/**
	 * @param $c_action
	 * @param $page_slug
	 *
	 * @return array
	 */
	private static function get_action_by_config( $c_action, $page_slug ){
		$attr_action = $c_action['@attributes'];
		if( empty( $c_action ) ){
			$error_message = sprintf( "The 'action' tag needs a string as a label inside the tag : page '%s'", $page_slug );
			wp_error_log( $error_message, "Config" );
			exit;
		}else if( empty( $attr_action['value'] ) ){
			$error_message = sprintf( "The 'value' attribute for the 'action' tag is mandatory : page '%s'", $page_slug );
			wp_error_log( $error_message, "Config" );
			exit;
		}else{
			$output = array(
				'label' => self::f_str( $c_action['value'] ),
				'value' => self::f_str( $attr_action['value'] )
			);
			return $output;
		}
	}

	/**
	 * @param $ref
	 * @param $page_slug
	 *
	 * @return array
	 */
	protected static function get_item_params_by_item_ref( $ref, $page_slug ){
		$current_item = self::get_item_by_ref( (string) $ref );
		if( !$current_item ){
			$error_message = sprintf( "Any 'item' tag found with the attribute 'name' value : '%s' - page '%s'", $ref, $page_slug );
			wp_error_log( $error_message, "Config" );
			exit;
		}else{
			if( empty( (string) $current_item['ref'] )
			    || empty( (string) $current_item['name'] )
			    || empty( (string) $current_item['class'] )
			    || empty( (string) $current_item['dbtable'] )
			    || empty( (string) $current_item['getter'] )
			){
				$error_message = sprintf( "'ref', 'name', 'class', 'dbtable' and 'getter' attributes are mandatory for the 'item' tag - page '%s'", $page_slug );
				wp_error_log( $error_message, "Config" );
				exit;
			}else {
				$output = array(
					'ref'     => (string) $current_item['ref'],
					'name'    => (string) $current_item['name'],
					'class'   => (string) $current_item['class'],
					'dbtable' => (string) $current_item['dbtable'],
					'getter'  => (string) $current_item['getter']
				);
				if( !empty( $current_item['editable'] ) )
					$output['editable'] = (string) $current_item['editable'];
				if( !empty( $current_item['slug'] ) )
					$output['slug'] = (string) $current_item['slug'];
				return $output;
			}
		}
	}

	/**
	 * @param $ref
	 *
	 * @return array|bool
	 */
	public static function get_item_by_ref( $ref ){
		if( empty( $ref ) ){
			wp_error_log( "the attribute 'ref' for 'item' tag is empty", "Config" );
			exit;
		}else{
			$h4a_config = self::getConfig();
			if( !isset( $h4a_config['items'] ) || count( $h4a_config['items']['children'] ) < 1 ){
				wp_error_log( "'item' tag is mandatory inside the 'items' tag.", "Config" );
				exit;
			}else{
				foreach ( $h4a_config['items']['children'] as $c_item ){
					$attrs_item = $c_item['@attributes'];
					if( empty( $attrs_item['ref'] ) || empty( $attrs_item['name'] ) ){
						$message_error = "the 'ref' and 'name' attributes are mandatories for the 'item' tag";
						wp_error_log( $message_error, "Config" );
						exit;
					}else if( $ref === self::f_str( $attrs_item['ref'] ) ){
						$args = array(
							'ref' => self::f_str( $attrs_item['ref'] ),
							'name' => self::f_str( $attrs_item['name'] )
						);
						$mandatory_params = array( "class", "dbtable", "getter" );
						foreach( $mandatory_params as $mandatory_param  ){
							if( empty( $attrs_item[ $mandatory_param ] ) ){
								$error_message = sprintf("the '%s' attribute is mandatory for the 'item' tag", $mandatory_param );
								wp_error_log( $error_message, "Config" );
								exit;
							}else{
								$args[ $mandatory_param ] = self::f_str( $attrs_item[ $mandatory_param ] );
							}
						}
						if( !empty( $attrs_item['editable'] ) )
							$args['editable'] =  self::f_str( $attrs_item['editable'] );
						if( !empty( $attrs_item['slug'] ) )
							$args['slug'] =  self::f_str( $attrs_item['slug'] );
						return $args;
					}
				}
				return false;
			}
		}
	}

	/**
	 * @param $slug
	 *
	 * @return array|bool
	 */
	public static function get_page_data_by_slug( $slug ){
		$h4a_config = Config::getConfig();
		if( empty( $h4a_config['pages'] ) ){
			wp_error_log( "Impossible to get page_title by the slug, pages config is empty!", "Config" );
			return false;
		}else{
			$c_pages = $h4a_config['pages']['children'];
			foreach ( $c_pages as $c_page ){
				$attrs_page = $c_page['@attributes'];
				$c_page_slug = ( !empty( $attrs_page['slug'] ) ) ? self::f_str( $attrs_page['slug'] ) : null;
				if( $c_page_slug === null ){
					wp_error_log( "Impossible to get the slug for the following page :", "Config" );
					error_log_array( $c_page );
					return false;
				}else if( (string) $c_page_slug === (string) $slug ){
					return (array) $c_page;
				}
			}
			wp_die( printf("Impossible to find the page with the slug '%s'", $slug ) );
			return false;
		}
	}

	/**
	 * @param $page_slug
	 *
	 * @return array|bool
	 */
	public static function get_all_subpages_by_slug( $page_slug ){
		$h4a_config = Config::getConfig();
		if( empty( $h4a_config['pages'] ) ){
			wp_error_log( "Impossible to get page_title by the slug, pages config is empty!", "Config" );
			return false;
		}else{
			$c_pages = self::getChildrenItem( $h4a_config['pages'], "page" );
			$a_subpages = array();
			foreach ( $c_pages as $c_page ){
				$attrs_page = $c_page['@attributes'];
				$c_parent_slug = ( !empty( $attrs_page['parent'] ) ) ? (string) $attrs_page['parent'] : null;
				if( !empty( $c_parent_slug ) && $c_parent_slug === $page_slug ){
					$a_subpages[] = $c_page;
				}
			}
			return $a_subpages;
		}
	}

	/**
	 * @param array $menu
	 *
	 * @return false|string
	 */
	public static function get_menu_title( array $menu ){
		return self::getValueItem( $menu, 'title' );
	}

	/**
	 * @param array $menu
	 *
	 * @return false|string
	 */
	public static function get_menu_icon( array $menu ){
		return self::getValueItem( $menu, 'icon' );
	}

	/**
	 * @param array $c_submenu
	 * @param $c_parent_menu_slug
	 *
	 * @return null|string
	 */
	public static function get_submenu_title( array $c_submenu, $c_parent_menu_slug ){
		$no_slug_error_message = sprintf( "Any attribute for a submenu tag, the 'slug' attribute is mandatory." );
		if( empty( $c_submenu['@attributes'] ) ){
			wp_error_log( $no_slug_error_message, "Config" );
			exit;
		}else{
			$attr_submenu = $c_submenu['@attributes'];
			if( empty( $attr_submenu['slug'] ) ){
				wp_error_log( $no_slug_error_message, "Config" );
				exit;
			}else{
				$submenu_slug        = self::f_str( $attr_submenu['slug'] );
				$c_submenu_title     = self::getChildItem( $c_submenu, "title" );
				$submenu_title_value = self::getValueItem( $c_submenu, 'title' );
				$submenu_title       = ( is_string( $submenu_title_value ) ) ? self::f_str( $submenu_title_value ) : null ;
				$attrs_submenu_title = ( isset( $c_submenu_title['@attributes'] ) ) ? $c_submenu_title['@attributes'] : null;
				if( !isset( $attrs_submenu_title ) || !$attrs_submenu_title['auto'] ){
					if( !$c_submenu_title ){
						$error_message = sprintf(
							"Impossible to get the title the submenu '%s'. Neither auto attribute set to 'true', nor title value",
							$submenu_slug
						);
						wp_error_log( $error_message, "Config" );
						exit;
					}else{
						return $submenu_title;
					}
				}else{ //auto = true
					$menu_slug = ( self::warn_editable_item( $submenu_slug ) ) ? null : self::f_str( $c_parent_menu_slug );
					if ( $submenu_slug === $menu_slug ) {
						$c_parent_page = self::get_page_data_by_slug( $menu_slug );
						$page_title = self::get_page_title( $c_parent_page );
						$submenu_title = "All " . $page_title;
					}else {
						$c_page = self::get_page_data_by_slug( $submenu_slug );
						$c_first_template = self::get_templates_as_array( $c_page )[0];
						$c_template_item = self::getChildItem( $c_first_template, "item" );
						$attrs_subpage_item = $c_template_item['@attributes'];
						$current_item = self::get_item_by_ref( (string) $attrs_subpage_item['ref'] );
						if( !$current_item ){
							$error_message = sprintf( "Any 'item' tag found with the attribute 'name' value : '%s' - page '%s', template n° %d", $attrs_subpage_item['ref'], $c_parent_menu_slug, 0 );
							wp_error_log( $error_message, "Config" );
							exit;
						}else{
							$submenu_title = sprintf( "New %s", ucfirst( (string) $current_item['name'] ) );
						}
					}
					if( !$submenu_title ){
						$warning_message = sprintf(
							"The title value in the submenu '%s' was overwritten by the auto attribute.",
							$submenu_slug
						);
						wp_warning_log( $warning_message, "Config" );
					}
					return $submenu_title;
				}
			}
		}
	}

	public static function warn_editable_item( $submenu_slug ) {
		$is_warn = false;
		$is_editable_item = startsWith( $submenu_slug, "edit-" );
		if ( $is_editable_item ) {
			$item_name           = str_replace( "edit-", "", $submenu_slug );
			$check_editable_item = EditableItem::check_editable_item( $item_name );
			if ( ! $check_editable_item ) {
				$warning_message = sprintf( "The item '%s' is not editable. To show the submenu, you must set the 'editable' attribute to 'true' for the 'item' tag with name '%s' ", $submenu_slug, $item_name );
				wp_warning_log( $warning_message, "Config" );
				$is_warn = true; // To make page without menu access
			}
		}

		return $is_warn;
	}

	public static function get_submenu_capability( array $submenu ){
		$attr_submenu = $submenu['@attributes'];
		return ( ! empty( $attr_submenu['capability'] ) ) ? $attr_submenu['capability'] : "manage_options";
	}

	/**
	 * @param array $item
	 * @param $key
	 *
	 * @return false|string
	 */
	private static function getValueItem( array $item, $key ){
		foreach ( $item['children'] as $child ){
			if( $child['type'] === $key && isset( $child['value'] ) ){
				return (string) $child['value'];
			}
		}
		return false;
	}

	/**
	 * @param array $item
	 * @param $key
	 *
	 * @return array|false
	 */
	public static function getChildItem( array $item, $key ){
		$output = self::getChildrenItem( $item, $key );
		if( empty( $output ) ){
			return false;
		}
		else if( count( $output ) > 1 ){
			$error_message = sprintf( "There is more than one '%s'", $key );
			wp_error_log( $error_message, "Config" );
			exit;
		}else{
			return (array) $output[0];
		}
	}

	/**
	 * @param array $item
	 * @param $key
	 *
	 * @return array
	 */
	public static function getChildrenItem( array $item, $key ){
		$output = array();
		if( isset( $item['children'] ) ){
			foreach ( $item['children'] as $child ){
				if( $child['type'] === $key ){
					$output[] = $child;
				}
			}
		}
		return $output;
	}

	/**
	 * @param $c_page
	 *
	 * @return string
	 */
	public  static function get_page_title( $c_page ){
		$title = self::getValueItem( $c_page, 'title' );
		if( is_string( $title ) ){
			return self::f_str( $title );
		}else{
			$c_first_template = self::get_templates_as_array( $c_page )[0];
			$page_slug = self::get_page_slug( $c_page );
			return self::get_template_title( $c_first_template, $page_slug, 0 );
		}
	}

	/**
	 * @param $c_template
	 * @param $page_slug
	 * @param $tmpl_number
	 * @param string $parent_page_slug
	 *
	 * @return string
	 */
	public static function get_template_title($c_template, $page_slug, $tmpl_number, $parent_page_slug = "" ){
		$output = "";
		$c_template_title = self::getChildItem( $c_template, 'title' );
		if( !$c_template_title ){
			$error_message = sprintf( "The 'title' tag inside template is mandatory - page '%s', template n° %d", $page_slug, $tmpl_number );
			wp_error_log( $error_message, "Config" );
			exit;
		}else if( isset( $c_template_title['@attributes'] ) ){
			$attrs_template_title = $c_template_title['@attributes'];
			if( !empty( $attrs_template_title ) && isset( $attrs_template_title['auto'] ) && asBoolean( $attrs_template_title['auto'] ) ){
				//$parent_page = self::get_page_data_by_slug( $page_slug );
				$c_template_list = self::getChildItem( $c_template, "list" );
				$c_template_item = self::getChildItem( $c_template, "item" );
				if( is_array( $c_template_item ) ){
					if( !in_array( $parent_page_slug, unserialize( H4A_ARRAY_NATIVE_MENUS_SLUGS ) ) ){
						if( empty( $c_template_item['@attributes'] ) ) {
							$error_message = sprintf( "The attribute ref is mandatory for the 'item' tag inside 'template' - page '%s', template n° %d", $page_slug, $tmpl_number );
							wp_error_log( $error_message, "Config" );
							exit;
						}else{
							$attrs_subpage_item = $c_template_item['@attributes'];
							$current_item = self::get_item_by_ref( (string) $attrs_subpage_item['ref'] );
							if( !$current_item ){
								$error_message = sprintf( "Any 'item' tag found with the attribute 'name' value : '%s' - page '%s', template n° %d", $attrs_subpage_item['ref'], $page_slug, $tmpl_number );
								wp_error_log( $error_message, "Config" );
								exit;
							}else{
								if( isset( $_GET['action'] ) && $_GET['action'] === "edit" ){
									$str = sprintf( "Edit %s", ucfirst( $current_item['name'] ) );
								}else{
									$str = sprintf( "New %s", ucfirst( $current_item['name'] ) );
								}
								$output = $str;
							}
						}
					}
				}else if( is_array( $c_template_list ) ){
					//TODO
					$error_message = sprintf( "Automatic 'title' for ListTableFromDBTemplate is not supported yet - page '%s', template n° %d", $page_slug, $tmpl_number );
					wp_error_log( $error_message, "Config" );
					exit;
				}
			}else{
				$output = self::f_str( $c_template_title['value'] );
			}
		}else{
			$output = self::f_str( $c_template_title['value'] );
		}
		return $output;
	}

	/**
	 * @param array $c_subpage
	 *
	 * @return string
	 */
	public static function get_subpage_title( array $c_subpage ){

		$title = self::getValueItem( $c_subpage, 'title' );
		if( !$title ){
			$c_first_template = self::get_templates_as_array( $c_subpage )[0];
			$subpage_slug = self::get_page_slug( $c_subpage );
			$attrs_subpage = $c_subpage['@attributes'];
			$parent_page_slug = ( !empty( $attrs_subpage['parent'] ) ) ? $attrs_subpage['parent'] : "" ;
			return self::get_template_title( $c_first_template, $subpage_slug, 0, $parent_page_slug );
		}else{
			return self::f_str( $title );
		}
	}

	/**
	 * @param $str
	 *
	 * @return string
	 */
	public static function f_str( $str ){
		if( !is_string( $str ) ){
			$error_message = sprintf( "str is not a string." );
			wp_error_log( $error_message, "Config" );
			exit;
		}
		$h4a_config = Config::getConfig();
		$regex = "{title}|{title_short}|{version}|{domain}|{initials}|{prefix}";
		$pattern = sprintf( "#%s#", $regex );
		if( preg_match_all( $pattern, $str, $a_keywords ) ){
			foreach ( $a_keywords as $keywords ){
				foreach ( $keywords as $keyword ){
					$keyword_without_brace = str_replace( "{", "", $keyword );
					$keyword_without_brace = str_replace( "}", "", $keyword_without_brace );
					$keyword_pattern = "/" . $keyword . "/";
					$str  = (string) preg_replace( $keyword_pattern, (string) $h4a_config['plugin_info'][$keyword_without_brace], $str);
				}
			}

		}
		return $str;
	}

	/**
	 * @param $c_page
	 *
	 * @return string
	 */
	public static function get_page_slug($c_page)
	{
		$attrs_page = $c_page['@attributes'];
		$page_slug = self::f_str($attrs_page['slug']);
		return $page_slug;
	}

	public static function get_primary_column_title() {
		if( empty( $_GET['page'] ) ){
			$error_message = sprintf( "var 'GET page' is mandatory." );
			wp_error_log( $error_message, "Config" );
			exit;
		}else{
			$c_page = self::get_page_data_by_slug( $_GET['page'] );
			$c_templates = self::getChildrenItem( $c_page, "template" );
			if( count( $c_templates ) > 1 ){
				$error_message = sprintf( "Impossible to get the primary column title. The function does not support page with different templates." );
				wp_error_log( $error_message, "Config" );
				exit;
			}else{
				$c_template = $c_templates[0];
				$c_template_list = self::getChildItem( $c_template, "list" );
				$c_columns = self::getChildItem( $c_template_list, "columns" );
				foreach ( $c_columns['children'] as $c_column ){
					$attr_column = $c_column['@attributes'];
					if( !empty( $attr_column['primary'] ) && asBoolean( $attr_column['primary'] ) ){
						return $c_column['value'];
					}
				}
				$error_message = sprintf( "Impossible to get the primary column title. No primary attribute set as true." );
				wp_error_log( $error_message, "Config" );
				exit;
			}
		}
	}

	private function override_config_general( string $addon_config_filepath ) {
		if( file_exists( $addon_config_filepath ) ){
			$a_addon_config = parse_ini_file( $addon_config_filepath, TRUE );
			foreach ( $this->general as $category => $cat_value ){
				if( isset( $a_addon_config[$category] ) ){
					foreach ( $this->general[$category] as $property => $prop_value ){
						if( !empty( $a_addon_config[$category][$property] ) )
							$this->general[$category][$property] = $a_addon_config[$category][$property];
					}
				}
			}
		}
	}

	/**
	 * @param $xml_filename
	 * @param $attr_addon
	 * @param $array_items_xml
	 *
	 * @return array
	 */
	private function load_addon_config_xml_file( $xml_filename, $attr_addon, $array_items_xml ): array {
		$is_plugin_active = is_plugin_active_before_admin_init( $attr_addon['dir'] . "/" . $attr_addon['main'] );
		if ( $is_plugin_active ) {
			$plugin_file_path = ABSPATH . "wp-content/plugins/" . $attr_addon['dir'] . "/config/" . $xml_filename;
			if ( file_exists( $plugin_file_path ) ) {
				$array_items_xml[] = $plugin_file_path;
			}
		}

		return $array_items_xml;
	}

	public static function get_options_names( $only_slug = false ){
		$h4a_config = Config::getConfig();
		$options_names = array();
		if( !empty( $h4a_config['settings']['children'] )){
			foreach ( $h4a_config['settings']['children'] as $child ){
				if( !isset( $child['@attributes'] ) && !isset( $child['@attributes']['slug'] ) ){
					wp_error_log( "The options tag must have a slug attribute", "Config" );
				}else{
					$attr_child = $child['@attributes'];
					$slug = $attr_child['slug'];
					if( $only_slug ){
						$options_names[] = $slug;
					}else{
						$options_names[] = Config::gen_options_name( $slug );
					}
				}
			}
			return $options_names;
		}
		return null;
	}

	public static function gen_options_name( $key ){
		$initials = strtolower( get_current_plugin_initials() );
		$prefix = $initials . "-";
		return $prefix . $key . "-options";
	}

}