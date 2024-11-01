<?php

namespace H4APlugin\Core\Admin;

use function H4APlugin\Core\get_current_plugin_dir_path;
use function H4APlugin\Core\wp_admin_build_url;
use function H4APlugin\Core\wp_debug_log;

class LogTemplate extends ListTableFromCSVTemplate{

    public function __construct( $data ) {
        wp_debug_log();
        parent::__construct( $data );

    }

    public function write( &$htmlTmpl ) {
    	wp_debug_log();
        if( !empty( $_POST['delete-reports'] ) ){
            $admin_log_file = get_current_plugin_dir_path() . "logs/admin.log";
            $users_log_file = get_current_plugin_dir_path() . "logs/users.log";
            if( file_exists( $admin_log_file ) )
                unlink($admin_log_file);
            if( file_exists( $users_log_file ) )
                unlink( $users_log_file );
        }
        parent::write( $htmlTmpl );
		if( $htmlTmpl instanceof \DOMDocument ){
			$delete_input =  $htmlTmpl->createElement( "input" );
			$delete_input_name = $htmlTmpl->createAttribute( "name" );
			$delete_input_name->value = "delete-reports";
			$delete_input_value = $htmlTmpl->createAttribute( "value" );
			$delete_input_value->value = "all";
			$delete_input_type = $htmlTmpl->createAttribute( "type" );
			$delete_input_type->value = "hidden";
			$delete_input->appendChild($delete_input_name);
			$delete_input->appendChild($delete_input_value);
			$delete_input->appendChild($delete_input_type);

			$delete_button =  $htmlTmpl->createElement( "button", __( "Delete All Log Reports", $this->current_plugin_domain ) );
			$delete_button_type = $htmlTmpl->createAttribute( "type" );
			$delete_button_type->value = "submit";
			$delete_button_class = $htmlTmpl->createAttribute( "class" );
			$delete_button_class->value = "button button-primary";
			$delete_button->appendChild($delete_button_type);
			$delete_button->appendChild($delete_button_class);

			$delete_log_form = $htmlTmpl->createElement( "form" );
			$delete_log_form_method = $htmlTmpl->createAttribute("method");
			$delete_log_form_method->value = "post";
			$delete_log_form_action = $htmlTmpl->createAttribute("action");
			$delete_log_form_action->value = wp_admin_build_url( $_GET['page'] );

			$delete_log_form->appendChild($delete_log_form_action);
			$delete_log_form->appendChild($delete_log_form_method);
			$delete_log_form->appendChild($delete_input);
			$delete_log_form->appendChild($delete_button);

			$parentNode = $htmlTmpl->getElementsByTagName("hr")->item(0);
			$parentNode->insertBefore( $delete_log_form );
		}

    }

    public function set_template_scripts()
    {
        wp_debug_log();
    	wp_enqueue_script( "h4aadmintabsscript", $this->current_plugin_dir_url . "core/admin/features/tabs/js/admin-tabs.js" );
	    wp_enqueue_style( "h4aadmintabsstyle", $this->current_plugin_dir_url . "core/admin/features/tabs/css/admin-tabs-style.css"  );
    	wp_enqueue_style( "h4aadminlogtemplate", $this->current_plugin_dir_url . "core/admin/features/log_reports/css/admin-log-reports-style.css" );
        $this->set_additional_scripts();
    }

}