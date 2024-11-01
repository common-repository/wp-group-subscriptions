<?php

namespace H4APlugin\Core\Common;

use H4APlugin\Core\Admin\AdminForm;
use function H4APlugin\Core\flatten_array;
use function H4APlugin\Core\format_attrs;
use H4APlugin\Core\FrontEnd\FrontEndForm;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\get_current_plugin_prefix;
use function H4APlugin\Core\is_number;
use function H4APlugin\Core\ksort_r;
use function H4APlugin\Core\wp_debug_log;
use function H4APlugin\Core\wp_error_log;
use function H4APlugin\Core\wp_format_i18n;
use function H4APlugin\Core\wp_get_error_back_end_system;

abstract class CommonForm extends Item{
	use FormTrait;

	public $current_plugin_domain;

	public function __construct( $id_or_data = null, $args = array(), $format = "read" ) {
		wp_debug_log();
		$this->current_plugin_domain = get_current_plugin_domain();
		$mandatory_args = array( "class", "office", "form_type" );
		$is_args = true;
		foreach( $mandatory_args as $mandatory_arg ){
			if( empty( $args[ $mandatory_arg ] ) ){
				$error_message = sprintf( "'%s' not found but it's a mandatory param", $mandatory_arg );
				wp_error_log( $error_message, "Config" );
				$is_args= false;
			}
		}
		if( $is_args ){
			$f_args = array(
				'ref' => "form",
				'name' => "form",
				'class' => $args['class'],
				'dbtable' => "forms",
				'getter' => "form_type_id"
			);
			$this->office = $args['office'];
			$this->form_type = $args['form_type'];
			parent::__construct( $id_or_data, $format, $f_args );
		}
	}

	/**
	 * Getters
	 *
	 */

	/**
	 * @param $form_type_id
	 *
	 * @return bool|null|void
	 */
	protected function get_item( $form_type_id ){
		$form_info = $this->getFormInfoById( $form_type_id );
		if( !$form_info ){
			return;
		}else{
			$this->form_type_id  = $form_info['form_type_id'];
			$this->html_id  = $form_info['html_id'];
			$this->action   = "#";
			$this->enctype  = "multipart/form-data";
			$this->name     = $form_info['name'];
			$this->options  = array(
				'title_display' => !empty( $form_info['title_display'] ) ? $form_info['title_display'] : null,
				'text_introduction' => !empty( $form_info['text'] ) ? $form_info['text'] : null,
				'submit' => array( 'button' => "Submit" ),
				'has_required_fields' => true
			);
			$form_content   = $this->getFormContentById();
			if( !$form_content ){
				return;
			}else{
				$this->content = $form_content;
			}
		}
		return null;
	}

	protected function get_item_to_list( $data ){
		$this->form_type_id     = $data['form_type_id'];
		$this->name     = $data['name'];
		$this->start_date     = $data['start_date'];
		$this->user_nicename = $data['user_nicename'];
		return null;
	}

	protected function getFormInfoById( $form_type_id ){

		global $wpdb;
		$form_query_string = "SELECT * FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "forms";
		$form_query_string .= " WHERE form_type_id = " . $form_type_id;
		$form_query_string .= " AND office = '" . $this->office . "'";
		$form_query_string .= " AND form_type = '" . $this->form_type . "'";
		wp_debug_log( $form_query_string );
		// Return results
		$results = $wpdb->get_results( $form_query_string, ARRAY_A );
		if( count($results) === 0){
			wp_error_log( "Form not found!");
		}else if( count( $results ) > 1){
			wp_error_log( "Found several form with the same form_type_id!");
		}else{
			return $results[0];
		}
		return false;
	}

	protected function getFormContentById(){
		$wrappers    = $this->getFormWrappers();
		$form_items  = $this->getFormItems();
		return $this->formatWrappersForContent( $wrappers, $form_items );
	}

	private function formatWrappersForContent( $wrappers, $form_items ){
		if( !$wrappers ){
			return false;
		}else{
			foreach ( $wrappers as $wrapper ) {
				if( in_array( $wrapper['wrapper_type'], array( 'div', 'fieldset', 'table' ) ) ){
					$wrapper['rows'] = array();
				}else{
					$wrapper['items'] = array(); //For hidden wrapper
				}
			}
			foreach ( $form_items as $item ) {
				$i_wrapper = (int) $item['wrapper_id'] - 1;
				if( in_array( $wrappers[$i_wrapper]['wrapper_type'], array( 'div', 'fieldset', 'table' ) ) ){
					$row = (int) $item['form_item_row'] - 1;
					$f_item = array(
						'form_item_ref'    => $item['form_item_ref'],
						'col_size'         => $item['col_size'],
						'label'            => $item['html_label'],
						'type'             => $item['form_item_type'],
						'id'               => $item['html_id'],
						'name'             => $item['html_name'],
						'value'            => $item['html_value'],
						'href'            =>  $item['href'],
						'placeholder'      => $item['html_placeholder'],
						'required'         => $item['required'],
						'readonly'         => $item['readonly'],
						'pattern'          => $item['pattern'],
						'function_options' => $item['function_options']
					);
					if( !isset( $item['form_item_col'] ) ){
						$wrappers[ $i_wrapper ]['rows'][ $row ][] = $f_item;
					}else{
						$col = $item['form_item_col'] - 1;
						if( !isset( $wrappers[ $i_wrapper ]['rows'][ $row ]['columns'] ) ){
							$wrappers[ $i_wrapper ]['rows'][ $row ]['columns'] = array();
						}else if( !isset( $wrappers[ $i_wrapper ]['rows'][ $row ]['columns'][$col] ) ){
							$wrappers[ $i_wrapper ]['rows'][ $row ]['columns'][$col] = array(
								'items' => array()
							);
						}
						$wrappers[ $i_wrapper ]['rows'][ $row ]['columns'][$col]['items'][] = $f_item;
					}
				}else{
					$f_item = array(
						'type'             => $item['form_item_type'],
						'id'               => $item['html_id'],
						'name'             => $item['html_name'],
						'value'            => $item['html_value'],
					);
					$wrappers[ $i_wrapper ]['items'][$f_item['name']] = $f_item;
				}

			}
			return $wrappers;
		}
	}

	private function getFormItems(){

		if( $this->form_type_id === null ){
			wp_error_log( 'Impossible to get the query : form_type_id is null');
			return false;
		}else{
			global $wpdb;

			$query_select  =   "SELECT DISTINCT 
								l.form_item_link_id,
								l.string_ref,
								l.form_item_ref,
								l.wrapper_id, 
								l.form_item_row, 
								l.form_item_col,
								l.col_size,
								i.form_item_type, 
								i.pattern, 
								i.href, 
								i.required, 
								i.readonly, 
								i.function_options,
								s.html_label,
								s.html_id,
								s.html_name,
								s.html_value,
								s.html_placeholder";
			$query_from    =    " FROM 
								{$wpdb->prefix}" . get_current_plugin_prefix() . "form_item_links as l,
								{$wpdb->prefix}" . get_current_plugin_prefix() . "form_items as i,
								{$wpdb->prefix}" . get_current_plugin_prefix() . "form_strings as s";
			$query_where   =    " WHERE l.form_type = '" . $this->form_type . "'
								 AND i.form_item_ref = l.form_item_ref
								 AND l.string_ref = s.string_ref 
								 AND l.form_type_id = " . $this->form_type_id ;

			$query_order = " ORDER BY l.wrapper_id, l.form_item_link_id";

			// Concatenate query string
			$query_string = $query_select . $query_from . $query_where . $query_order;
			//wp_debug_log( $query_string );
			// Return results
			$results = $wpdb->get_results( $query_string, ARRAY_A );

			return $results; // It's possible to return an array empty.
		}
	}

	public static function get_form_results( $check_data ){
		wp_debug_log();

		$a_errors = array();

		foreach ( $check_data as $item_to_check ){
			$checkItem = forward_static_call_array( array( get_called_class(), $item_to_check['function'] ), $item_to_check['data'] );;
			if( !$checkItem['success'] ){
				$a_errors[] = flatten_array( $checkItem['errors'] );
			}
		}

		//Results
		$results = array();

		$a_errors = array_filter( $a_errors ); //remove all empty items

		if( !empty( $a_errors ) ){
			$results['success'] = false;
			$results['errors'] = flatten_array( $a_errors );
		}else{
			$results['success'] = true;
		}
		return $results;

	}

	/**
	 * @param $item_ref
	 *
	 * @return array
	 */
	public static function getEditFormByItemRef( $item_ref ){
		wp_debug_log();
		$output = array(
			"success" => false
		);

		global $wpdb;

		$office = ( is_admin() ) ? "back" : "front" ;

		$query_string = "SELECT form_type_id, form_type FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "forms 
							  WHERE name = 'edit-" . $item_ref . "' 
							  AND office IN ( '{$office}', 'both' )";

		// Return results
		$res_query = $wpdb->get_results( $query_string, ARRAY_A );
		if( empty( $res_query ) ){
			wp_error_log( sprintf( "Impossible to find the form with the name 'edit-%s' ! ", $item_ref ) );
			Notices::setNotice( wp_get_error_back_end_system(), "error" );
		}else if( count( $res_query ) > 1 ){
			wp_error_log( sprintf( " The form with the name 'edit-%s' is not unique ! ", $item_ref ) );
			Notices::setNotice( wp_get_error_back_end_system(), "error" );
		}else{
			$output['success'] = true;
			$form = ( is_admin() ) ? new AdminForm( (int) $res_query[0]['form_type_id'], $res_query[0]['form_type'] ) : new FrontEndForm( (int) $res_query[0]['form_type_id'], $res_query[0]['form_type'] );
			$output['data'] = $form;
		}
		return $output;
	}

	public static function getFormItemPositionByHTMLName( $html_name, $form_type, $form_type_id, $optional = false ){
		$output = array(
			"success" => false
		);

		global $wpdb;

		//Check if the field is mandatory or not
		if( !$optional ){
			$res_required = self::isRequiredFieldByHTMLName( $html_name, $form_type );
			if( $res_required['success'] ){
				$optional = ( (int) $res_required['required'] === 0 ) ? true : false;
			}
		}

		$query_select  =   "SELECT l.wrapper_id, 
								l.form_item_row, 
								l.form_item_col,
								i.form_item_type,
								s.html_value";

		$query_from    =    " FROM 
								{$wpdb->prefix}" . get_current_plugin_prefix() . "form_item_links as l,
								{$wpdb->prefix}" . get_current_plugin_prefix() . "form_items as i,
								{$wpdb->prefix}" . get_current_plugin_prefix() . "form_strings as s";

		$query_where   =    " WHERE l.form_type = '" . $form_type . "'";
		$query_where  .=	" AND l.form_type_id = " . $form_type_id;
		$query_where  .=	" AND i.form_item_ref = l.form_item_ref";
		$query_where  .=	" AND l.string_ref = s.string_ref";
		$query_where  .=	" AND s.html_name = '" . $html_name . "'";

		// Concatenate query string
		$query_string = $query_select . $query_from . $query_where ;

		// Return results
		$res_query = $wpdb->get_results( $query_string, ARRAY_A );

		if( $res_query === null || count( $res_query ) === 0  ){
			if( !$optional ){
				$message_error = sprintf( " '%s' position for the form id '%s' not found but the field is required ! ", $html_name, $form_type_id );
				wp_error_log( $message_error );
				Notices::setNotice( wp_get_error_back_end_system(), "error" );
			}
		}else if( count( $res_query ) > 1  && !in_array( $res_query[0]['form_item_type'], array( "checkbox", "radio" ) ) ){
			wp_error_log( $query_string );
			$message_error = sprintf( " we found %d different positions in the form with the id '%s' and the form item with this html name : '%s' ! ", count( $res_query ), $form_type_id, $html_name );
			wp_error_log( $message_error );
			Notices::setNotice( wp_get_error_back_end_system(), "error" );
		}else if( count( $res_query ) > 1 ){
			$data = array(
				'type' => $res_query[0]['form_item_type'],
				'fields' => array()
			);
			foreach ( $res_query as $value ){
				$a_value = array(
					'type' => $value['form_item_type'],
					'wrapper_pos' => $value['wrapper_id'] - 1,
					'row_pos' =>  $value['form_item_row'] - 1,
					'col_pos' =>  $value['form_item_col'] - 1,
					'value' => $value['html_value']
				);
				$data['fields'][] = $a_value;
			}
		}else{
			$data = array(
				'type' => $res_query[0]['form_item_type'],
				'wrapper_pos' => $res_query[0]['wrapper_id'] - 1,
				'row_pos' =>  $res_query[0]['form_item_row'] - 1,
				'col_pos' =>  $res_query[0]['form_item_col'] - 1
			);
		}
		if( Notices::isNoErrors() && !empty( $data ) ){
			$output['success'] = true;
			$output['data'] = $data;
		}else{
			$output['errors'] = Notices::getErrors();
		}
		return $output;
	}

	/**
	 * Template functions
	 *
	 * @param bool $echo
	 *
	 * @return string
	 */

	abstract public function writeForm( $echo = true );

	public function writeFormWrappers( $echo = false ){
		wp_debug_log();
		$html = '';
		foreach ( $this->content as $wrapper ) {
			if ( $wrapper['wrapper_type'] === 'table' ) {
				$table_atts = array(
					'id'    => ( ! empty( $wrapper['html_id'] ) ) ? $wrapper['html_id'] : null,
					'class' => "form-table widefat fixed"
				);
				$html       .= sprintf( '<table %s>', format_attrs( $table_atts ) );
				$html       .= '<tbody>';
				ksort_r( $wrapper['rows'] );
				foreach ( $wrapper['rows'] as $row ) {
					$html .= $this->add_form_row( $row );
				}
				$html .= '</tbody>';
				$html .= '</table>';
				if ( ! $this->options['crud'] ) {
					//Form -> submit button
					$html .= $this->add_submit_button();
				}
			} else if ( $wrapper['wrapper_type'] === 'fieldset' ) {
				$html .= $this->add_form_fieldset( $wrapper );
			} else if ( $wrapper['wrapper_type'] === 'hidden' ) {
				ksort( $wrapper['items'] );
				foreach ( $wrapper['items'] as $input ) {
					$html .= sprintf( '<input %s />', format_attrs( $input ) );
				}
			}
		}
		if( $echo ){
			echo $html;
		}else{
			return $html;
		}
		return null;
	}

	protected function add_form_fieldset( $wrapper, $echo = false ) {
		$html = '';
		//Form -> Fieldset
		$fieldset_atts = array(
			'id'    => ( ! empty( $wrapper['html_id'] ) ) ? $wrapper['html_id'] : null,
			'class' => "h4a-fieldset"
		);
		$html .= sprintf( '<fieldset %s>', format_attrs( $fieldset_atts ) );
		//Form -> Fieldset -> legend
		if ( ! empty( $wrapper['legend'] ) ) {
			$html .=  '<legend>' . __( $wrapper['legend'], $this->current_plugin_domain ) . '</legend>';
		}
		ksort_r( $wrapper['rows'] );
		foreach ( $wrapper['rows'] as $row ) {
			$html .= $this->add_form_row( $row );
		}
		$html .= '</fieldset>';
		if( $echo ){
			echo $html;
		}else{
			return $html;
		}
		return null;
	}

	protected function add_form_row ( $row ){
		wp_debug_log();
		$html = '';
		if( in_array( $this->office, array( "front", "both" ) ) ){
			$html .= '<div class="h4a-form-group h4a-form-inline h4a-col-12">';
			if( isset( $row['columns'] ) ){
				if( count( $row['columns'] ) === 1 ){
					foreach( $row['columns'][0]['items'] as $item ) {
						if( isset( $item['type'] ) && ( $item['type'] !== 'checkbox' ) && ( $item['type'] !== 'radio' )  ) {
							$html .= '<div class="h4a-form-group h4a-col-12">';
							$html .= $this->add_form_field( $item );
							$html .= '</div>';
						}else{
							$html .= $this->add_form_field( $item );
						}
					}
				}else{
					$col_size = null;
					foreach( $row['columns'] as $column ){
						if( count( $column['items'] ) > 1 ){
							foreach( $column['items'] as $item ) {
								$col_size = ( !empty( $item['col_size'] ) ) ? 'h4a-col-' . $item['col_size'] : null;
							}
							$html .= sprintf( '<div class="h4a-form-group %s">', $col_size );
							foreach( $column['items'] as $item ) {
								if( isset( $item['type'] ) ) {
									$html .= $this->add_form_field( $item );
								}
							}
							$html .=  '</div>';
						}else{
							$item = $column['items'][0];
							$col_size = ( !empty( $item['col_size'] ) ) ? 'h4a-col-' . $item['col_size'] : null;
							$html .= sprintf( '<div class="h4a-form-group %s">', $col_size );
							if( isset( $item['type'] ) ) {
								$html .= $this->add_form_field( $item );
							}
							$html .=  '</div>';
						}
					}
				}
			}else{
				foreach( $row as $field){
					$col_size = (!empty($field['col_size'])) ? 'h4a-col-'.$field['col_size'] : null ;
					$html .= sprintf( '<div class="h4a-form-group %s">', $col_size );
					if( isset( $field['type'] ) ) {
						$html .= $this->add_form_field( $field );
					}
					$html .=  '</div>';
				}
			}
			$html .= "</div>";
		}else if( $this->office === 'back'){
			$html .= "<tr>";
			foreach( $row['columns'] as $column ){
				if( count( $column['items'] ) > 1 ){
					$html .= "<td >";
					foreach ( $column['items'] as $field ){
						$html .= $this->add_form_field( $field );
					}
					$html .= "</td>";
				}else{
					foreach ( $column['items'] as $field ){
						if( isset( $field['type'] ) ) {
							if( $field['type'] === 'title' ){
								$html .= '<th scope="row">';
								$text_domain = ( !empty( $this->options['text_domain'] ) ) ? $this->options['text_domain'] : $this->current_plugin_domain ;
								$html .= __( $field['label'], $text_domain );
								$html .= "</th>";
							}else{
								$html .= sprintf('<td class="%s">',
									( !empty( $field['col_size'] ) ) ? "h4a-col-".$field['col_size'] : "h4a-col-6"
									);
								$html .= $this->add_form_field( $field );
								$html .= "</td>";
							}
						}
					}
				}
			}
			$html .= "</tr>";
		}

		return $html;
	}

	private function add_form_field( $field ){
		$html = '';
		$field['show'] = true;
		$field = $this->override_field( $field );
		if( $field['show'] ){
			if( isset( $field['type'] ) ){
				if( $field['type'] === "info" ){
					$html .= $this->add_form_field_info( $field );
				}
				if ( in_array( $field['type'], array( "text", "number", "email", "password", "date" ) ) ) {
					if( in_array( $this->office, array( "front", "both" ) ) ){
						$html .= $this->add_form_field_label( $field );
						if( $field['type'] === 'number' && empty( $field['step']) )
							$field['step'] = "any";
						$html .= $this->add_form_field_input( $field, '100%' );
					}else{
						$html .= '<span>';
						$html .= $this->add_form_field_label( $field );
						$html .= $this->add_form_field_input( $field, null );
						$html .= '</span>';
					}
				}else if ( in_array( $field['type'], array( "radio", "checkbox" ) ) ) {
					$html .= $this->add_form_field_radio_or_checkbox( $field );
				}else if ( in_array( $field['type'], array( "select" ) ) ) {
					$html .= $this->add_form_field_select( $field );
				}else if ( in_array( $field['type'], array( "button" ) ) ) {
					$html .= $this->add_form_field_button( $field );
				}else if ( in_array( $field['type'], array( "label" ) ) ) {
					$html .= $this->add_form_field_label( $field );
				}else if ( in_array( $field['type'], array( "link" ) ) ) {
					$html .= $this->add_form_field_link( $field );
				}else if ( in_array( $field['type'], array( "textarea" ) ) ) {
					$html .= $this->add_form_field_textarea( $field );
				}else if ( in_array( $field['type'], array( "file_upload" ) ) ) {
					$html .= $this->add_form_field_file_upload( $field );
				}
			}
		}
		return $html;
	}

	abstract protected function override_field( $field );

	private function add_form_field_info( $field ){
		$html = '';
		if ( ! empty( $field['value'] ) ) {
			$class =( $this->office === 'front' ) ? 'class="h4a-form-control-label"' : null;
			if( !empty( $field['class'] ) )
				$class = ( !empty( $class ) ) ? " " . $field['class'] : $field['class'];
			$label = array(
				'html' => '<span class="%s">%s</span>',
				'class' => $class,
				'text' => ( !empty( $field['label'] ) ) ? wp_format_i18n( $field['label'], $this->current_plugin_domain ) . " : " . wp_format_i18n( $field['value'], $this->current_plugin_domain ) : wp_format_i18n( $field['value'], $this->current_plugin_domain )
			);
			$html .= sprintf( $label['html'], $label['class'], $label['text'] );
		}
		return $html;
	}

	private function add_form_field_label( $field ){
		$html = '';
		if ( ! empty( $field['label'] ) ) {
			$label = array(
				'html' => '<label %s for="%s">%s%s</label>',
				'class' => ( $this->office === 'front' ) ? 'class="h4a-form-control-label"' : null,
				'text' => wp_format_i18n( $field['label'], $this->current_plugin_domain ),
				'required' => ( isset( $field['required'] ) && $field['required'] ) ? '<sup class="h4a-star-required">*</sup>' : '',
			);
			$html .= sprintf( $label['html'], $label['class'], $field['id'],  $label['text'], $label['required'] );
		}
		return $html;
	}

	private function add_form_field_link( $field ){
		$html = '';
		if ( ! empty( $field['label'] ) ) {
			$label = array(
				'html' => '<a href="%s">%s</a>',
				'text' => wp_format_i18n( $field['label'], $this->current_plugin_domain )
			);
			$html .= sprintf( $label['html'], str_replace( "&", "&amp;", $field['href'] ),  $label['text'] );
		}
		return $html;
	}

	private function add_form_field_file_upload( $field ){
		$html = '';
		$attrs = array(
			'id'    => esc_attr( $field['id'] ),
			'name'  => esc_attr( $field['name'] ),
			'class' => ( $this->office === 'front' ) ? "h4a-form-control " : null,
			//'style' => ( isset( $width ) ) ? sprintf( "width : %s", ( (int) $field['col_size'] * 12.5 ) . '%' ) : null,
			'style' => ( isset( $width ) ) ? sprintf( "width : %s", $width ) : null,
			'required' => ( ! empty( $field['required'] ) ) ? $field['required'] : null,
			'readonly' => ( ! empty( $field['readonly'] ) ) ? $field['readonly'] : null,
			'disabled' => ( ! empty( $field['disabled'] ) ) ? $field['disabled'] : null,
			'autocomplete' => "off"
		);
		if( !empty( $field['value'] ) ){
			$html .= sprintf( '<span>%s</span>', esc_attr( $field['value'] ) );
		}else{
			$attrs['type'] = "file";
			$html .= sprintf( '<input %s />', format_attrs( $attrs ) );
		}
		return $html;
	}

	private function add_form_field_textarea( $field ){
		$html = '';
		/*$h4a_config = Config::getConfig();
		if( $h4a_config['modules']['wp_editor'] && $field['form_item_ref'] === "textarea_wp_editor" ){
			$html .= H4A_WP_Editor::editor( $field['value'], $field['id'] );
		}else{*/
		$attrs = array(
			'id' => $field['id'],
			'name' => $field['name'],
		);
		$label = ( !empty( $field['label'] ) ) ? $field['label'] : "";
		$html .= sprintf( '<span>%s</span><textarea %s >%s</textarea>',
			$label,
			format_attrs( $attrs ),
			( !empty( $field['value'] ) ) ? esc_textarea( $field['value'] ) : " " /*It needs one space to avoid to break xml file*/
		);
		//}

		return $html;
	}
	private function add_form_field_input( $field, $width = '100%' ){
		$html = '';

		$value = null;
		if( isset( $field['value'] ) ){ //Caution : Don´t use "!empty" because the value 0, won´t be display
			$value =  $field['value'];
		}
		else if( isset( $_POST ) && !empty ( $_POST[ $field['name'] ] ) ){
			$value = stripslashes( $_POST[ $field['name'] ] );
		}

		//$width = ( isset( $field['col_size'] ) ) ? (int) $field['col_size'] * 12.5 : null;

		$attrs = array(
			'id'    => esc_attr( $field['id'] ),
			'name'  => esc_attr( $field['name'] ),
			'value' => $value,
			'type'  => $field['type'],
			'class' => ( $this->office === 'front' ) ? "h4a-form-control " : null,
			//'style' => ( isset( $width ) ) ? sprintf( "width : %s", ( (int) $field['col_size'] * 12.5 ) . '%' ) : null,
			'style' => ( isset( $width ) ) ? sprintf( "width : %s", $width ) : null,
			'placeholder' => ( ! empty( $field['placeholder'] ) ) ? __( $field['placeholder'], $this->current_plugin_domain ) : null,
			'pattern' => ( ! empty( $field['pattern'] ) ) ? $field['pattern'] : null,
			'required' => ( ! empty( $field['required'] ) ) ? $field['required'] : null,
			'readonly' => ( ! empty( $field['readonly'] ) ) ? $field['readonly'] : null,
			'disabled' => ( ! empty( $field['disabled'] ) ) ? $field['disabled'] : null,
			'step' => ( ! empty( $field['step'] ) ) ? $field['step'] : null,
			'min' => ( ! empty( $field['min'] ) ) ? $field['min'] : null,
			'max' => ( ! empty( $field['max'] ) ) ? $field['max'] : null,
			'autocomplete' => "off"
		);
		if( !empty( $field['class'] ) ){
			if( is_array( $field['class'] ) ){
				foreach ( $field['class'] as $class ){
					$attrs['class'] = $attrs['class'].$class;
				}
			}else if( is_string( $field['class'] ) ){
				$attrs['class'] = $attrs['class']." ".$field['class'];
			}
		}
		$html .= sprintf( '<input %s />', format_attrs( $attrs ) );
		return $html;
	}

	private function add_form_field_radio_or_checkbox( $field ){
		$html = '';
		$is_front = $this->office === 'front';
		if( $is_front){
			$html .= '<div class="h4a-form-check h4a-form-check-inline">';
		}
		$html .= sprintf( '<label %s>', ( $is_front ) ? 'class="h4a-form-check-label"' : null );

		$checked = null;
		if( !empty( $field['checked'] ) ){
			$checked = $field['checked'];
		}
		else if(
			( !empty ( $_POST[$field['name']] ) && $_POST[$field['name']] === $field['value'] )
			|| ( isset( $field['checked'] ) && $field['checked'] )
		){
			$checked = 'checked';
		}


		$attrs = array(
			'id' => $field['id'],
			'class' => ( $is_front ) ? 'h4a-form-check-input' : null,
			'name' => $field['name'],
			'type' => $field['type'],
			'value' => $field['value'],
			'checked' => $checked,
			'required' => ( ! empty( $field['required'] ) ) ? $field['required'] : null,
			'readonly' => ( ! empty( $field['readonly'] ) ) ? $field['readonly'] : null,
			'disabled' => ( ! empty( $field['disabled'] ) ) ? $field['disabled'] : null,
			'autocomplete' => "off"
		);

		if( !empty( $field['class'] ) ){
			if( is_array( $field['class'] ) ){
				foreach ( $field['class'] as $class ){
					$attrs['class'] = $attrs['class'].$class;
				}
			}else if( is_string( $field['class'] ) ){
				$attrs['class'] = $attrs['class']." ".$field['class'];
			}
		}

		$html .= sprintf( '<input %s />', format_attrs( $attrs ) );

		$html .= sprintf("<span>%s</span>", wp_format_i18n( $field['label'], $this->current_plugin_domain ) );
		if ( ( $is_front || $this->options['item_type'] === 'subscriber' ) && isset( $field['required'] ) && $field['required'] ) {
			$html .= '<sup class="h4a-star-required">*</sup>';
		}
		$html .= '</label>';
		if( $is_front){
			$html .= '</div>';
		}
		return $html;
	}

	private function add_form_field_select( $field ){
		$html = '';
		$is_front = $this->office === 'front';
		if ( ! empty( $field['label'] ) ) {
			$required = null;
			if ( isset( $field['required'] ) && $field['required'] ) {
				$required = '<sup class="h4a-star-required">*</sup>';
			}
			$label = array(
				'html' => '<label %s for="%s">%s%s</label>',
				'class' => ( $is_front ) ? 'class="h4a-form-control-label"' : null,
				'text' => wp_format_i18n( $field['label'], $this->current_plugin_domain )
			);
			$html .= sprintf( $label['html'], $label['class'], $field['id'], $label['text'], $required );
		}
		$atts = array(
			'id' => $field['id'],
			'name' => ( ! empty( $field['name'] ) ) ? $field['name'] : null,
			'class' => ( $is_front ) ? "h4a-form-control" : null,
			'value' => ( ! empty( $field['value'] ) ) ? $field['value'] : null,
			'required' => ( ! empty( $field['required'] ) ) ? $field['required'] : null,
			'disabled' => ( ! empty( $field['disabled'] ) ) ? $field['disabled'] : null,
			'autocomplete' => "off"
		);
		if( !empty( $field['class'] ) ){
			if( is_array( $field['class'] ) ){
				foreach ( $field['class'] as $class ){
					$atts['class'] = $atts['class'].$class;
				}
			}else if( is_string( $field['class'] ) ){
				$atts['class'] = $atts['class'].' '.$field['class'];
			}
		}
		$html .= sprintf( '<select %s >', format_attrs( $atts ) );
		$a_options = array();
		if( !empty( $field['function_options'] ) ){
			$a_function_options = explode( "#", $field['function_options']);
			$function =  $a_function_options[0];
			if( count( $a_function_options ) > 1){
				$args = array();
				for( $a = 1; $a < count( $a_function_options ); $a++ ){
					$args[] = $a_function_options[$a];
				}
				$a_options = call_user_func_array( $function, $args );
			} else{
				$a_options = call_user_func( $function );
			}
		}
		foreach ( $a_options as $value => $label ) {
			$isSelected = null;
			if( !empty( $field['selected'] ) ){
				$isSelected = ( (string) $field['selected'] === (string) $value ) ? 'selected="selected"' : null;
			}
			else if( isset( $field['name'] ) && isset( $_POST[$field['name'] ] ) ){
				$isSelected = ( (string) $_POST[ $field['name'] ] === (string) $value ) ? 'selected="selected"' : null;
			}
			$html .= sprintf( '<option value="'.$value.'" %s >'.$label.'</option>', $isSelected);
		}
		$html .= '</select>';
		return $html;
	}

	private function add_form_field_button( $field ){
		$html = '';
		$atts = array(
			'id' => $field['id'],
			'type' => $field['type'],
			'class' => ( is_admin() ) ? 'button' : null,
			'disabled' => isset( $field['disabled'] ) ? $field['disabled'] : null
		);
		if( !empty( $field['class'] ) ){
			if( is_array( $field['class'] ) ){
				foreach ( $field['class'] as $class ){
					$atts['class'] = $atts['class'] . " " . $class;
				}
			}else if( is_string( $field['class'] ) ){
				$atts['class'] = $atts['class'] . " " . $field['class'];
			}
		}
		if( !empty( $field['value'] ) )
			$atts['value'] = $field['value'];
		$html .= sprintf( '<button %s >%s</button>', format_attrs( $atts ), $field['label'] );
		return $html;
	}

	abstract protected function add_submit_button();

	/**
	 * checking functions
	 */

	/**
	 * @param array $data
	 * @param bool $isUpdate
	 * @param bool $checkInMembers
	 * @param bool $checkInSubscribers
	 *
	 * @return array
	 */
	public static function checkDataEmails( $data = array(), $isUpdate = false, $checkInMembers = true, $checkInSubscribers = true ){

		$f_data = array();

		$check_data = array();
		for( $e = 1; isset( $data['wgs_f_email'.$e] ); $e++ ){

			$check_data[] = array(
				'function' => "checkEmail",
				'data' => array(
					$data['wgs_f_email'.$e],
					$data['wgs_f_email_r'.$e],
					$isUpdate,
					$checkInMembers,
					$checkInSubscribers
				)
			);

			$f_data[] = htmlspecialchars( $data['wgs_f_email'.$e] );

		}

		//Results
		$results = self::get_form_results( $check_data );
		if( $results['success'] ){
			$results['data'] = $f_data;
		}

		return $results;

	}

	public static function checkEmail( $dataEmail, $dataRepeatEmail, $isUpdate = false, $checkInMembers = true, $checkInSubscribers = true ){

		$a_errors = array();

		$current_plugin_domain = get_current_plugin_domain();

		//Check Email

		//1 check length email
		$a_errors[] = self::checkMaxLengthItem( $dataEmail, H4A_WGS_LENGTH_EMAIL, __( 'email', $current_plugin_domain ) );

		//2 check if Email has right pattern
		$a_errors[] = self::checkPatternItem( $dataEmail, null, __( 'email', $current_plugin_domain ), FILTER_VALIDATE_EMAIL );

		//3 check if formRepeatEmail is similar
		if( !empty( $dataRepeatEmail ) )
			$a_errors[] = self::checkMatchItems( $dataEmail, $dataRepeatEmail, __( 'Email', $current_plugin_domain ) );

		//4 check if email does not exist in the database yet
		if( !$isUpdate ){
			if( $checkInSubscribers )
				$a_errors[] = self::checkUniqueItem( $dataEmail, 'subscribers', __( 'email', $current_plugin_domain ) );
			if( $checkInMembers )
				$a_errors[] = self::checkUniqueItem( $dataEmail, 'members', __( 'email', $current_plugin_domain ) );
		}

		//Results
		$results = array();

		$a_errors = array_filter( $a_errors ); //remove all empty items

		if( !empty( $a_errors ) ){
			$results['success'] = false;
			$results['errors'] = $a_errors;
		}else{
			$results['success'] = true;
		}
		return $results;
	}

	public static function checkName( $dataName, $label ){

		$pattern_name = "/[^![\]:;|=+*?<>\/\\,]+/";

		$a_errors = array();

		//Check name

		//1 check name length
		$a_errors[] = self::checkMaxLengthItem( $dataName, H4A_WGS_LENGTH_PEOPLE_NAME, $label );

		//2 check name pattern
		$a_errors[] = self::checkPatternItem( $dataName, $pattern_name, $label );

		$a_errors = array_filter( $a_errors ); //remove all empty items

		//Results
		$results = array();

		$a_errors = array_filter( $a_errors ); //remove all empty items

		if( !empty( $a_errors ) ){
			$results['success'] = false;
			$results['errors'] = $a_errors;
		}else{
			$results['success'] = true;
		}
		return $results;

	}

	public static function checkGroupName( $dataGroupName ){

		$a_errors = array();

		//TODO : group name should be unique ?

		//Check group name
		$a_errors[] = self::checkString( $dataGroupName, H4A_WGS_LENGTH_GROUP_NAME, __( 'group name', get_current_plugin_domain() ) );

		//Results
		$results = array();

		$a_errors = array_filter( $a_errors ); //remove all empty items

		if( !empty( $a_errors ) ){
			$results['success'] = false;
			$results['errors'] = $a_errors;
		}else{
			$results['success'] = true;
		}
		return $results;

	}

	public static function checkPhone( $dataPhoneCode, $dataPhoneNumber ){

		$a_errors = array();

		$current_plugin_domain = get_current_plugin_domain();

		//Check name

		//1 check if phone code is well-formed
		$pattern_phone_code = "/[+][0-9]{1,4}/";
		$a_errors[] = self::checkPatternItem( $dataPhoneCode, $pattern_phone_code, __( 'phone code', $current_plugin_domain ) );

		//2 check if phone number is a number
		$pattern_number = "/[0-9]*/";
		$a_errors[] = self::checkPatternItem( $dataPhoneNumber, $pattern_number, __( 'phone code', $current_plugin_domain ) );

		//3 check if full phone length are correct
		$a_errors[] = self::checkMaxLengthItem( $dataPhoneCode.$dataPhoneNumber , H4A_WGS_LENGTH_FULL_PHONE, __( 'phone number', $current_plugin_domain ) );

		//Results
		$results = array();

		$a_errors = array_filter( $a_errors ); //remove all empty items

		if( !empty( $a_errors ) ){
			$results['success'] = false;
			$results['errors'] = $a_errors;
		}else{
			$results['success'] = true;
		}
		return $results;

	}

	public static function checkAddress( $dataStreet, $dataZipCode, $dataCity, $dataCountry, $dataStreetNumber = null ){

		$a_errors = array();

		$current_plugin_domain = get_current_plugin_domain();

		//Check Address

		//1 check street number
		if( !empty( $dataStreetNumber ) )
			$a_errors[] = self::checkString( $dataStreetNumber, H4A_WGS_LENGTH_STREET_NUMBER, __( 'street number', $current_plugin_domain ) );

		//2 check street name
		$a_errors[] = self::checkString( $dataStreet, H4A_WGS_LENGTH_STREET_NAME, __( 'street name', $current_plugin_domain ) );

		//3 check zip code
		$a_errors[] = self::checkString( $dataZipCode, H4A_WGS_LENGTH_ZIP_CODE, __( 'zip code', $current_plugin_domain ) );

		//4 check zip city
		$a_errors[] = self::checkString( $dataCity, H4A_WGS_LENGTH_CITY, __( 'city', $current_plugin_domain ) );

		//5 check country iso
		$pattern_country_iso = "/[A-Z]{2}/";
		$a_errors[] = self::checkPatternItem( $dataCountry, $pattern_country_iso, __( 'country', $current_plugin_domain ) );

		//Results
		$results = array();

		$a_errors = array_filter( $a_errors ); //remove all empty items

		if( !empty( $a_errors ) ){
			$results['success'] = false;
			$results['errors'] = $a_errors;
		}else{
			$results['success'] = true;
		}
		return $results;

	}

	public static function checkPassword( $dataPassword, $dataRepeatPassword ){

		$a_errors = array();

		$current_plugin_domain = get_current_plugin_domain();

		//Check password

		//1 check password length
		$a_errors[] = self::checkMinLengthItem( $dataPassword, H4A_WGS_MIN_LENGTH_PASSWORD, __( 'password', $current_plugin_domain ) );

		//2 check if wgs_f_password_r is similar
		if( !empty( $dataRepeatPassword ) )
			$a_errors[] = self::checkMatchItems( $dataPassword, $dataRepeatPassword, __( 'password', $current_plugin_domain ) );

		//Results
		$results = array();

		$a_errors = array_filter( $a_errors ); //remove all empty items

		if( !empty( $a_errors ) ){
			$results['success'] = false;
			$results['errors'] = $a_errors;
		}else{
			$results['success'] = true;
		}
		return $results;

	}

	public static function checkString( $dataItem, $max_length, $label ){

		$pattern_string = "/[^![\]:;|=+*?<>]+/";

		$a_errors = array();

		//1 check length item
		$a_errors[] = self::checkMaxLengthItem( $dataItem, $max_length, $label );

		//2  check pattern item
		$a_errors[] = self::checkPatternItem( $dataItem, $pattern_string, $label );

		return $a_errors;

	}

	public static function checkMinLengthItem( $dataItem, $min_length, $label ){

		$a_errors = array();

		if ( strlen( $dataItem ) < $min_length){
			$a_errors[] = sprintf ( __( "Your %s is too short ( minimum %s characters )", get_current_plugin_domain() ), $label, $min_length );
		}

		return $a_errors;

	}

	public static function checkMaxLengthItem( $dataItem, $max_length, $label ){

		$a_errors = array();

		if( !empty( $dataItem ) && strlen( $dataItem ) > $max_length ){
			$a_errors[] =sprintf ( __( "Your %s is too long", get_current_plugin_domain() ), $label );
		}

		return $a_errors;

	}

	public static function checkPatternItem( $dataItem, $pattern, $label, $filter = null, $output_type = 'errors' ){

		$a_errors = array();
		$current_plugin_domain = get_current_plugin_domain();
		if( !empty( $pattern )){
			if ( !preg_match( $pattern, $dataItem )) {
				$a_errors[] = sprintf ( __( "Invalid %s format", $current_plugin_domain ), $label );
			}
		}else{
			if ( !filter_var( $dataItem, $filter )) {
				$a_errors[] = sprintf ( __( "Invalid %s format", $current_plugin_domain ), $label );
			}
		}

		if( $output_type === 'results' ){
			$results = array();

			if( !empty( $a_errors ) ){
				$results['success'] = false;
				$results['errors'] = $a_errors;
			}else{
				$results['success'] = true;
			}
			return $results;
		}else{
			return $a_errors;
		}



	}

	public static function checkIsNumber( $dataItem, $label, $output_type = 'errors' ){
		$a_errors = array();

		if( !is_number( $dataItem ) ){
			$a_errors[] = sprintf ( __( "The %s value is not a number format", get_current_plugin_domain() ), $label );
		}
		if( $output_type === 'results' ){
			$results = array();

			if( !empty( $a_errors ) ){
				$results['success'] = false;
				$results['errors'] = $a_errors;
			}else{
				$results['success'] = true;
			}
			return $results;
		}else{
			return $a_errors;
		}
	}

	public static function checkMatchItems( $dataItem, $dataRepeatItem, $label ){

		$a_errors = array();

		if( $dataItem !== $dataRepeatItem ){
			$a_errors[] = sprintf ( __( "%s does not match", get_current_plugin_domain() ), $label );
		}

		return $a_errors;

	}

	public static function checkUniqueItem( $dataItem, $table, $table_item, $label = null ){

		$a_errors = array();

		global $wpdb;

		$f_dataItem = htmlspecialchars( $dataItem );

		$query_string = "SELECT {$table_item} FROM {$wpdb->prefix}" . get_current_plugin_prefix() . "{$table} WHERE {$table_item} = '{$f_dataItem}';";

		$results = $wpdb->get_results( $query_string, ARRAY_A );

		if( !empty( $results ) ){
			$current_plugin_domain = get_current_plugin_domain();
			//TODO : change this code because Commonform should not know 'members' and 'subscribers'
			if( $table === 'members' ){
				$a_errors[] =  sprintf ( __( "The member %s already exists!", $current_plugin_domain ), $dataItem );
			}else if( $table === 'subscribers' ){
				$a_errors[] =  __( "This account already exists!", $current_plugin_domain );
			}else{
				$label      = __( $label, $current_plugin_domain );
				$a_errors[] = sprintf( __( 'This %s already exists!', $current_plugin_domain ), $label );
			}
		}
		return $a_errors;
	}

	/**
	 * @param $html_name
	 * @param $form_type
	 *
	 * @return array
	 */
	private static function isRequiredFieldByHTMLName( $html_name, $form_type ){
		$output = array(
			"success" => false
		);

		$errors = array();

		global $wpdb;

		$query_select  =   "SELECT DISTINCT
								i.required";

		$query_from    =    " FROM 
								{$wpdb->prefix}" . get_current_plugin_prefix() . "form_item_links as l,
								{$wpdb->prefix}" . get_current_plugin_prefix() . "form_items as i,
								{$wpdb->prefix}" . get_current_plugin_prefix() . "form_strings as s";

		$query_where   =    " WHERE l.form_type = '" . $form_type . "'
								AND i.form_item_ref = l.form_item_ref
								AND l.string_ref = s.string_ref 
								AND s.html_name = '" . $html_name . "'";

		// Concatenate query string
		$query_string = $query_select . $query_from . $query_where ;

		// Return results
		$res_query = $wpdb->get_results( $query_string, ARRAY_A );
		$isMandatory = null;
		if( $res_query === null || count( $res_query ) === 0  ){
			wp_error_log( sprintf( "Required information for the html name : '%s' not found ! ", $html_name ) );
			$errors[] = wp_get_error_back_end_system();
		}else if( count( $res_query ) > 1 ){
			wp_error_log( sprintf( "More than one result, we must have only one reult with the query '%s' ! ", $query_string ) );
			$errors[] = wp_get_error_back_end_system();
		}else{
			$isMandatory = $res_query[0]['required'];
		}

		if( empty( $errors ) ){
			$output['success'] = true;
			$output['required'] = $isMandatory;
		}else{
			$output['errors'] = $errors;
		}
		return $output;
	}
}