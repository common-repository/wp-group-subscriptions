<?php

namespace H4APlugin\Core\Admin;


use function H4APlugin\Core\addHTMLinDOMDocument;
use H4APlugin\Core\Config;
use function H4APlugin\Core\format_attrs;
use H4APlugin\Core\Common\H4AObjectTrait;
use function H4APlugin\Core\wp_admin_build_url;
use function H4APlugin\Core\wp_debug_log;

class MenuTemplate extends Template {

    use H4AObjectTrait;

	public $subpages; //mandatory

    public function __construct( $data ) {
		wp_debug_log();
        $mandatory_params = array( "subpages" );
        $this->setObject( $mandatory_params, $data );
        parent::__construct( $data );
	}
	
	public function write( &$htmlTmpl ) {
		wp_debug_log();
		$html = "";
		if( !empty( $this->subpages ) ){
			$html .= '<ul class="h4a-admin-page-menu-list">';
			foreach ( $this->subpages as $subpage ){
				$attrs_subpage = $subpage['@attributes'];
				$subpage_slug = $attrs_subpage['slug'];
				if( ! Config::warn_editable_item( $subpage_slug ) ){
					$html .= "<li>";
					$menu_icon = Menu::getIconBySlug( $subpage_slug );
					$menu_icon = ( $menu_icon !== false ) ? $menu_icon : "dashicons-arrow-right";
					$atts = array(
						'class' => "dashicons " . $menu_icon
					);
					$html .= sprintf( "<span %s > </span>", //<-- Caution : space is necessary !
						format_attrs( $atts )
					);
					$href = 'href="' . wp_admin_build_url( $subpage_slug ) . '"';
					$subpage_title = Config::get_subpage_title( $subpage );
					$html .= sprintf( "<a %s>%s</a>",
						$href,
						_x(  $subpage_title, "menu_title", $this->current_plugin_domain )
					);
					$html .= "</li>";
				}
			}
			$html .= "</ul>";
		}
		addHTMLinDOMDocument($htmlTmpl, $html, "ul" );
	}

    public function set_template_scripts() {
        wp_debug_log();
        wp_enqueue_style( "h4aadmindefaulttemplate", $this->current_plugin_dir_url . "core/admin/features/page/template/css/admin-default-template.css" );
        $this->set_additional_scripts();
    }

    /**
     * can be overwritten
     */
    protected function set_additional_scripts(){
        return null;
    }

}