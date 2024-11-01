<?php

namespace H4APlugin\Core\Admin;


use H4APlugin\Core\Common\CommonForm;
use H4APlugin\Core\Common\EditableItem;
use H4APlugin\Core\Common\H4AObjectTrait;

use function H4APlugin\Core\addHTMLinDOMDocument;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\wp_admin_build_url;
use function H4APlugin\Core\wp_debug_log;

abstract class EditItemTemplate extends Template {

    use H4AObjectTrait;

    protected $current_plugin_domain;

	public $editable_item; //set in init_template_content;

    public $list_page_slug; //Mandatory

	public function __construct( $data ) {
		wp_debug_log();
		$this->current_plugin_domain = get_current_plugin_domain();
		parent::__construct($data);
		$mandatory_params = array( "list_page_slug" );
		$this->setObject( $mandatory_params, $data );
		if( isset( $data['editable_item'] ) )
            $this->editable_item = $data['editable_item'];
	}

    public function init_template_content() {
		wp_debug_log();
	    // Save|Update|Trash and redirection
        if( ( isset( $_GET['action'] ) && $_GET['action'] === "trash" )
            || ( isset( $_POST ) && ( array_key_exists('save', $_POST ) || array_key_exists('publish', $_POST ) ) ) ){
            if ( isset( $_POST ) && ( array_key_exists('save', $_POST ) || array_key_exists('publish', $_POST ) ) ){
                $res_item = $this->saveOrUpdateItem();
                //Redirection
	            $args_action = array(
		            'action' => "edit"
	            );
                if( $res_item['success'] ){
                	$args_action = $this->set_url_args( $args_action, $res_item );
                }
	            $url = wp_admin_build_url( $this->slug, false, $args_action );
	            wp_redirect( $url );
	            exit;
            }else if( $_GET['action'] === "trash" ){
                wp_debug_log( 'Trash' );
                $this->setEditableItem( "edit" );
                $res_item = $this->trashItem();
                if( !$res_item['success'] ){
	                $args_action = array(
		                'action' => "edit",
		                $this->editable_item->params->slug => $_GET[ $this->editable_item->params->slug ]
	                );
	                wp_redirect( wp_admin_build_url( "edit-"  . $this->editable_item->params->ref, false, $args_action ) );
                }else{
                    //var_dump( $this->list_page_slug );
                    wp_redirect( wp_admin_build_url( $this->list_page_slug ) );
                    exit();
                }
            }
        }else{ // Show editable item
            if( isset( $_GET['action'] ) && in_array( $_GET['action'], array( "edit", "read" ) ) && !empty( $_GET[ $this->editable_item->params->slug ] ) ){  // Show item
            	$this->setEditableItem( "edit" );
            	if( method_exists( get_called_class(), "initEditableForm" ) )
		            get_called_class()::initEditableForm();
                if( $this->editable_item->status === "trash" ) { // Lock if the plan is trash
                    wp_redirect( wp_admin_build_url( $this->list_page_slug ) );
                    exit;
                }
            }
            else{ // Show blank item
                wp_debug_log("setBlankEditableItem - ". $this->editable_item->params->slug );
                $this->setBlankEditableItem();
            }
        }
    }

	public function write( &$htmlTmpl ) {
		$a_vars = get_object_vars( $this->editable_item );
		$first_key = key( $a_vars );
        $html = "";
		if( empty( $this->editable_item )
		    || ( $_GET['page'] === $this->slug && isset( $_GET[ $this->editable_item->params->slug ] ) && empty( $a_vars[$first_key] ) ) ){
            $html .= '<div class="notice notice-error"><p>' . esc_html( sprintf( __( '%s not found !', $this->current_plugin_domain ), _x( $this->editable_item->params->name, "edit-error", $this->current_plugin_domain ) ) ) . '</p></div>';
            addHTMLinDOMDocument($htmlTmpl, $html, "div" );
		}elseif( $this->editable_item instanceof EditableItem ){
			$this->editable_item->initForm();
			$form = $this->editable_item->form;
			if( $form instanceof CommonForm ){
				$html .= $form->writeForm( false );
				addHTMLinDOMDocument($htmlTmpl, $html, "div" );
			}
		}
	}

    protected function setEditableItem( $format ) {
	    wp_debug_log();
        $args = $this->get_mandatory_args();
        $this->editable_item = new $this->editable_item->params->class( (int) $_GET[ $this->editable_item->params->slug ], $format, $args );
        $this->editable_item->form->options['action_wpnonce'] = $this->editable_item->nonce;
    }

    protected function setBlankEditableItem() {
        wp_debug_log();
        $args = $this->get_mandatory_args();
        $this->editable_item = new $this->editable_item->params->class( 0, "edit", $args );
        $this->editable_item->form->options['action_wpnonce'] = $this->editable_item->nonce;
    }

    protected function get_mandatory_args() {
        $args = array(
            'ref'     => $this->editable_item->params->ref,
            'name'     => $this->editable_item->params->name,
            'class'    => $this->editable_item->params->class,
            'dbtable' => $this->editable_item->params->dbtable,
            'getter'   => $this->editable_item->params->getter,
            'slug'   => $this->editable_item->params->slug,
        );
        return $args;
    }

    public function set_template_scripts() {
        wp_debug_log();
	    wp_enqueue_script( "h4acommonformplugin", $this->current_plugin_dir_url . "core/common/features/form/js/common-form-plugin.js" );
	    wp_localize_script( "h4acommonformplugin", "commonFormTranslation", array(
		    'msg_must_match' => __( "It is must match with the previous input", $this->current_plugin_domain ),
	    ) );
	    wp_enqueue_style( "h4acommonformstyle", $this->current_plugin_dir_url . "core/common/features/form/css/common-form-style.css" );
        wp_enqueue_style( "h4aadminform", $this->current_plugin_dir_url . "core/admin/features/form/css/admin-form-style.css" );
        wp_enqueue_style( "h4aadminedititemtemplate", $this->current_plugin_dir_url . "core/admin/features/page/template/css/admin-edit-item-template.css" );
        $this->set_additional_scripts();
    }

    abstract protected function set_additional_scripts();

    abstract protected function set_url_args( $arg_actions, $res = array() );

    abstract protected function saveOrUpdateItem();

    abstract protected function trashItem();
}