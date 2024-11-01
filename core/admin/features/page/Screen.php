<?php

namespace H4APlugin\Core\Admin;


use H4APlugin\Core\Config;
use function H4APlugin\Core\format_attrs;
use H4APlugin\Core\Common\H4AObjectTrait;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\get_current_plugin_prefix;
use function H4APlugin\Core\wp_admin_build_url;
use function H4APlugin\Core\wp_debug_log;
use function H4APlugin\Core\wp_error_log;

abstract class Screen {

	use H4AObjectTrait;
	
	protected $current_plugin_domain;

	protected $text_domain; //For addon translation.

	protected $current_plugin_prefix;

	public $page_title; //Mandatory

	public $slug; //Mandatory

	public $templates = array(); //Mandatory

	protected $current_template; //Set thanks to init_template

	public function __construct( $data ){
		wp_debug_log();
		if( isset( $data['slug'] ) && isset( $_GET['page'] ) && $data['slug'] === $_GET['page'] ){
			$mandatory_params = array( "page_title", "slug", "templates" );
			$this->setObject( $mandatory_params, $data );
			$this->current_plugin_domain = get_current_plugin_domain();
			$this->text_domain = ( !empty( $data['text_domain'] ) ) ? $data['text_domain'] : $this->current_plugin_domain;
			$this->current_plugin_prefix = get_current_plugin_prefix();
			// Initialize the component
			$this->init_template(); //inside admin_menu hook - cannot be admin_init
		}
	}

	protected function init_template(){
		wp_debug_log();
		if( !empty( $_GET['tab'] ) && isset( $this->templates[ $_GET['tab'] ] ) ){
			$this->current_template = $this->templates[ $_GET['tab'] ];
		}else{
			$templates_by_index = array_values( $this->templates );
			$t = 0;
			$this->current_template = $templates_by_index[$t];
		};
		$this->current_template->set_template_scripts(); //inside admin_menu hook - cannot be admin_enqueue_scripts
		if( method_exists( $this->current_template, "init_template_content" ) ){
			$this->current_template->init_template_content();
		}
		$this->html_page_template();
	}

	/**
	 * can be overwritten
	 */
	protected function html_page_template(){
		wp_debug_log();
		$htmlTmpl = new \DOMDocument;
		$html = '<div class="wrap">';
		$html .= $this->writeTopPage();
		$html .= '<hr class="wp-header-end" />';
		if( count( $this->templates ) > 1 ){
			$html .= $this->writeTabs();
		}
		$html .= "</div>";
		//$searchPage = mb_convert_encoding($html, 'HTML-ENTITIES', "UTF-8");
		$htmlTmpl->loadXML($html);
		if( $this->current_template instanceof Template )
			$this->current_template->write( $htmlTmpl );
		echo $htmlTmpl->saveXML();
	}

	/**
	 * can be overwritten
	 */
	protected function writeTabs(){
		wp_debug_log();
		$tab_headers = array();
		$current_screen = get_current_screen();
		if( $current_screen->parent_base === "options-general" ){
			$h4a_config = Config::getConfig();
			if( !$h4a_config['modules']['settings'] ){
				$error_message = sprintf( "The settings module is not active. Please set 'settings' as true in config.ini - page '%s'", $this->slug );
				wp_error_log( $error_message, "Config" );
				exit;
			}else if( $this->current_template instanceof SettingsTemplate ){
				$tab_headers = $this->current_template->setTabsHeader();
			}
		}else{
			foreach ( $this->templates as $template ) {
				if ( $template->title ){
					$atts = array(
						'class'    => "nav-tab",
						'id'       => strtolower( $template->slug . "-menu" ),
						'href' => wp_admin_build_url( $this->slug, false, array( "tab" => $template->slug ) )
					);
					$tab_header = array(
						'atts' => $atts,
						'title' => $template->title
					);
					$tab_headers[] = $tab_header;
				}
			}
			if( $this->current_template instanceof SettingsTemplate )
				$this->current_template->set_tabs_scripts();
		}
		$html = "";
		$html .= '<div class="nav-tab-wrapper">';
		foreach ( $tab_headers as $tab_header ){
			$html .= sprintf( '<a %s >%s</a>',
				format_attrs( $tab_header['atts'] ),
				$tab_header['title']
			);
		}
		$html .= "</div>";
		return $html;
	}

	/**
	 * can be overwritten
	 */
	protected function writeTopPage(){
		$html = "<h1>";
		if( ( get_class( $this->current_template ) === "H4APlugin\Core\Admin\EditItemTemplate" )
		    || ( get_parent_class( $this->current_template ) === "H4APlugin\Core\Admin\EditItemTemplate" )
		){
			//Page or Subpage of edition
			$context = ( isset( $_GET['action'] ) && $_GET['action'] === "edit" && !empty( $_GET[ $this->current_template->editable_item->params->slug ] ) ) ? "edit_page_title" : "new_page_title";
		}else{
			//Page or Subpage
			$context = "page_title";
		}
		$html .= _x( $this->page_title, $context , $this->text_domain );
		$html .= "</h1>";
		return $html;
	}

}