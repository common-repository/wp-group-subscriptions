<?php

namespace H4APlugin\Core\Admin;


use function H4APlugin\Core\get_current_plugin_domain;

class SubMenu {

	private $text_domain;

	public $parent_slug;

	public $page_title;

	public $menu_title;

	public $capability;

	public $menu_slug;

	public $priority;

	public function __construct( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $text_domain = null, $priority = 10 ) {
		$this->text_domain = ( !isset( $text_domain ) ) ? get_current_plugin_domain() : $text_domain;
		$this->parent_slug = $parent_slug;
		$this->page_title  = _x( $page_title, "menu_title", $this->text_domain );
		$this->menu_title  = $menu_title;
		$this->capability  = $capability;
		$this->menu_slug   = $menu_slug;
		$this->priority    = $priority;
		// Initialize the component
		$this->add_submenu();
	}

	/*
	 * Adds the sub-menu page
	 *
	 */
	public function add_submenu() {
		
		if( $this->parent_slug === "options-general.php"){
			add_options_page(
				_x( $this->page_title, "page_title", $this->text_domain ),
				_x( $this->menu_title, "menu_title", $this->text_domain ),
				$this->capability,
				$this->menu_slug,
				array( "H4APlugin\\Core\\Config", "get_page_by_config" )
			);
		}else{
			add_submenu_page(
				$this->parent_slug,
				_x( $this->page_title, "page_title", $this->text_domain ),
				_x( $this->menu_title, "menu_title", $this->text_domain ),
				$this->capability,
				$this->menu_slug,
				array( "H4APlugin\\Core\\Config", "get_page_by_config" )
			);
		}
	}
}