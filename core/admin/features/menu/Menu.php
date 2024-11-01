<?php

namespace H4APlugin\Core\Admin;

use H4APlugin\Core\Config;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\wp_error_log;

class Menu {

	private $text_domain;

	private $page_title;

	private $menu_title;

	private $capability;

	private $menu_slug;

	//private $function;

	private $menu_icon;

	private $position;

	public function __construct( $menu_slug, $menu_title, $page_title, $capability, $menu_icon, $position, $text_domain = null ){
		$this->text_domain = ( !isset( $text_domain ) ) ? get_current_plugin_domain() : $text_domain;
		$this->page_title  = $page_title;
		$this->menu_title  = $menu_title;
		$this->capability  = $capability;
		$this->menu_slug   = $menu_slug;
		$this->menu_icon   = $menu_icon;
		$this->position    = $position;
		// Initialize the component
		$this->add_menu();
		
	}

	public function add_menu(){
		add_menu_page(
			_x( $this->page_title, "page_title", $this->text_domain ),
			_x( $this->menu_title, "menu_title", $this->text_domain ),
			$this->capability,
			$this->menu_slug, 
			array( "H4APlugin\\Core\\Config", "get_page_by_config" ),
			$this->menu_icon,
			$this->position
		);
	}

	public static function add_admin_menu_separator( $position ) {

		global $menu;

		$menu[ $position ] = array(
			0	=>	"",
			1	=>	"read",
			2	=>	"separator" . $position,
			3	=>	"",
			4	=>	"wp-menu-separator"
		);

	}
	
	public static function getIconBySlug( $slug ){
		$h4a_config = Config::getConfig();
		if( empty( $h4a_config['menus'] ) || empty( $h4a_config['menus']['menu_items'] ) ){
			wp_error_log( "Impossible to get icon by the slug, menus config is empty!", "Config" );
			return false;
		}else{
			$c_menus = $h4a_config['menus']['menu_items'];
			foreach ( $c_menus as $c_menu ){
				$attrs_menu = $c_menu['@attributes'];
				if( empty( $attrs_menu['slug'] ) ){
					wp_error_log( "Impossible to get icon by the slug, the slug is undefined!", "Config" );
					return false;
				}else if( $attrs_menu['slug'] === $slug ){
					if( !empty( $c_menu['icon'] ) ){
						return  $c_menu['icon'];
					}
				}
			}
			return false;
		}
	}
	
}
