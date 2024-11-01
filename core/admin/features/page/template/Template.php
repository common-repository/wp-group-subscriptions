<?php

namespace H4APlugin\Core\Admin;


use function H4APlugin\Core\addHTMLinDOMDocument;
use H4APlugin\Core\Common\H4AObjectTrait;
use function H4APlugin\Core\get_current_plugin_dir_url;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\get_current_plugin_prefix;
use function H4APlugin\Core\wp_debug_log;

class Template extends Template_Base {

    use H4AObjectTrait;

	protected $text_domain; //for addon translation

    protected $current_plugin_domain;

	protected $current_plugin_dir_url;

	protected $current_plugin_prefix;

    public $slug; //mandatory - template slug but default = page slug

    public $title; //mandatory

    public function __construct( $data ) {
        wp_debug_log();
        $this->current_plugin_domain = get_current_plugin_domain();
	    $this->text_domain = ( !empty( $data['text_domain'] ) ) ? $data['text_domain'] : $this->current_plugin_domain;
	    $this->current_plugin_dir_url = get_current_plugin_dir_url();
        $this->current_plugin_prefix = get_current_plugin_prefix();
        $mandatory_params = array( "slug", "title" );
        $this->setObject( $mandatory_params, $data );
    }

	public function set_tabs_scripts(){
        wp_enqueue_script( "h4aadmintabs", $this->current_plugin_dir_url . "core/admin/features/tabs/js/admin-tabs.js");
        wp_enqueue_style( "h4aadmintabsstyle", $this->current_plugin_dir_url . "core/admin/features/tabs/css/admin-tabs-style.css" );
    }

    public function set_modal_scripts(){
        //Modal
        wp_enqueue_script( "h4aadminmodal", $this->current_plugin_dir_url . "core/admin/features/modal/js/admin-modal-plugin.js" );
        wp_enqueue_style( "h4aadminmodalstyle", $this->current_plugin_dir_url . "core/admin/features/modal/css/admin-modal-style.css" );
    }

    public function set_dual_list_scripts(){
        //Modal
        wp_enqueue_script( "h4acommonduallist", $this->current_plugin_dir_url . "core/common/features/dual_list/js/dual-list.js" );
        wp_enqueue_style( "h4acommondualliststyle", $this->current_plugin_dir_url . "core/common/features/dual_list/css/dual-list.css" );
    }

    public function write(&$htmlTmpl)
    {
        $html = sprintf( '<section class="h4a-option-tab" id="%s-content">', $this->slug );
        $html .= "</section>";
        addHTMLinDOMDocument($htmlTmpl, $html, "section" );
    }

    public function set_template_scripts() {}
}