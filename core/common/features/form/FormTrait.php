<?php
namespace H4APlugin\Core\Common;


use function H4APlugin\Core\get_current_plugin_prefix;
use function H4APlugin\Core\wp_debug_log;

trait FormTrait {
	public $form_type_id;
	public $form_type;
	public $user_id;
	public $text;
	public $html_id;
	public $office;
	public $name;
	public $title_display;
	public $start_date;
	public $user_nicename;
	public $action;
	public $enctype;
	public $options = array();
	public $content = array();
	protected $format;

	protected function getFormWrappers(){

		global $wpdb;

		$wrapper_query_string = "SELECT * FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "form_wrappers";
		$wrapper_query_string .= " WHERE form_type_id = " . $this->form_type_id;
		$wrapper_query_string .= " AND form_type = '" . $this->form_type . "'";
		$wrapper_query_string .= " ORDER BY form_order";
		wp_debug_log( $wrapper_query_string );
		// Return results
		$results = $wpdb->get_results( $wrapper_query_string, ARRAY_A );

		return $results; // It's possible to return an array empty.

	}
}