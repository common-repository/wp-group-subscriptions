<?php

namespace H4APlugin\Core\FrontEnd;

use H4APlugin\Core\Common\CommonForm;
use function H4APlugin\Core\format_attrs;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\wp_error_log;
use function H4APlugin\Core\wp_format_i18n;

class FrontEndForm extends CommonForm {

	public function __construct( $id_or_data = null, $form_type = null, $is_both = false ) {
		$args = array(
			'class' => __CLASS__,
			'office' => ( $is_both ) ? "both" : "front",
			'form_type' => $form_type
		);
		parent::__construct( $id_or_data, $args );
	}

	/**
	 * Template functions
	 *
	 */

	/**
	 * @param bool $echo
	 *
	 * @return null|string
	 */
	public function writeForm( $echo = true ){
		//Text introduction
		$html = '';
		if( !empty( $this->options['text_introduction'] ) ){
			if( is_array( $this->options['text_introduction'] ) ){
				$html .= sprintf( '<p class="h4a-alert h4a-alert-%s">%s</p>', $this->options['text_introduction']['type'], __( $this->options['text_introduction']['text'], $this->current_plugin_domain ) );
			}else if( is_string( $this->options['text_introduction'] ) ){
				$html .= '<p>' . __( $this->options['text_introduction'], $this->current_plugin_domain ) . '</p>';
			}else{
				wp_error_log( "\$this->options['text_introduction'] is neither a string nor an array!" );
			}

		}

		//Step Title
		$step = ( !empty( $this->options['step'] ) ) ? sprintf( __( "Step %d", $this->current_plugin_domain ), $this->options['step'] ) . " : " : null;
		//Subtitle
		$title_display = ( !empty( $this->options['title_display'] ) ) ? _x( $this->options['title_display'], 'title_display', $this->current_plugin_domain )  : null;
		if( !empty( $this->options['step'] ) && !empty( $this->options['title_display'] ) )
			$html .= sprintf( '<h2>%s%s</h2>', $step, $title_display );

		//Form - Begin
		$form_attrs = array(
			'id' => $this->html_id,
			'class' => "h4a-form",
			'action' => $this->action,
			'method' => "post",
			'enctype' => ( !empty ( $this->enctype ) ) ? $this->enctype : null,
			'target' => ( !empty ( $this->options['target'] ) ) ? $this->options['target'] : null
		);
		$html .= sprintf( '<form %s >', format_attrs( $form_attrs ) );

		//Form -> wrappers
		foreach ( $this->content as $wrapper ) {
			if( $wrapper['wrapper_type'] === 'fieldset' ){
				$html .= $this->add_form_fieldset( $wrapper );
			}else if( $wrapper['wrapper_type'] === 'div' ){
				ksort( $wrapper['rows'] );
				foreach( $wrapper['rows'] as $row){
					$html .= $this->add_form_row( $row );
				}
			}else if( $wrapper['wrapper_type'] === 'hidden' ){
				ksort( $wrapper['items'] );
				foreach( $wrapper['items'] as $input){
					//pretty_var_dump( $input );
					$html_input = sprintf( '<input %s />', format_attrs( $input ) );
					$html   .= $html_input;
				}
			}
		}

		//Form -> recaptcha ( optional )
		if( !is_admin() && ! function_exists( 'is_plugin_active' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php');
		if ( is_plugin_active( 'wgs-recaptcha-addon/WGSRecaptchaAddon.php' ) ) {
			if( isset ( $this->options['recaptcha'] ) && $this->options['recaptcha'] ){
				$html .= apply_filters("set_client_side_recaptcha", $html );
			}
		}

		//Form -> submit button
		$html .= $this->add_submit_button();

		//Form - End
		$html .= '</form>';

		if( $echo ){
			echo $html;
		}else{
			return $html;
		}
		return null;
	}

	protected function add_submit_button(){
		$str_submit = "";
		$label = wp_format_i18n( $this->options['submitBox']['button'], $this->current_plugin_domain );
		$str_submit .= '<div class="h4a-form-group h4a-form-inline h4a-col-12">';
		if( $this->options['has_required_fields'] ){
			$str_submit .= '<div class="h4a-col-6"><span class="h4a-form-info">* <em>'.__( "Field required", $this->current_plugin_domain)."</em></span></div>";
			$str_submit .= '<div class="btn-group h4a-col-6 h4a-right">';
			$str_submit .= '<button id="btn_submit" class="btn btn-primary btn-lg">';
			$str_submit .= $label;
			$str_submit .= "</button>";
			$str_submit .= "</div>";
		}else{
			$str_submit .= '<div class="btn-group h4a-col-6 h4a-left">';
			$str_submit .= '<button id="btn_submit" class="btn btn-primary btn-lg">';
			$str_submit .= $label;
			$str_submit .= "</button>";
			$str_submit .= "</div>";
		}
		$str_submit .= "</div>";

		return $str_submit;
	}

	/**
	 * Checking functions
	 *
	 * @param array $data
	 *
	 * @return array
	 */

	public static function checkDataMemberActivation( $data = array() ){
		$current_plugin_domain = get_current_plugin_domain();
		$check_data = array(
			0 => array(
				'function' => "checkName",
				'data' => array(
					$data['wgs_f_first_name'],
					__( 'first name', $current_plugin_domain )
				)
			),
			1 => array(
				'function' => "checkName",
				'data' => array(
					$data['wgs_f_last_name'],
					__( 'last name', $current_plugin_domain)
				)
			),
			2 => array(
				'function' => "checkPassword",
				'data' => array(
					$data['wgs_f_password'],
					$data['wgs_f_password_r']
				)
			)
		);

		//Results
		$results = self::get_form_results( $check_data );

		//Results
		if( $results['success'] ){
			$f_data = array(
				'first_name' => htmlspecialchars( $data['wgs_f_first_name'] ),
				'last_name' => htmlspecialchars( $data['wgs_f_last_name'] ),
				'password' => htmlspecialchars( $data['wgs_f_password'] )
			);
			$results['data'] = $f_data;
		}

		return $results;
	}

	protected function override_field( $field ) {
		return $field;
	}

}