<?php

namespace H4APlugin\Core\Admin;

use H4APlugin\Core\Common\CommonForm;
use function H4APlugin\Core\error_log_array;
use function H4APlugin\Core\format_attrs;
use function H4APlugin\Core\format_str_to_kebabcase;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\get_current_plugin_prefix;
use function H4APlugin\Core\wp_format_i18n;
use function H4APlugin\Core\wp_error_log;
use function H4APlugin\Core\wp_debug_log;

class AdminForm extends CommonForm {

	private $can_edit;

	public function __construct( $id_or_data = null, $form_type = "", $is_both = false  ) {
		wp_debug_log();
		$args = array(
			'class' => __CLASS__,
			'office' => ( $is_both ) ? "both" : "back",
			'form_type' => $form_type
		);
		if( isset( $_GET['action'] ) ){
			$this->can_edit =  ( $_GET['action'] === "edit" )  ? true : false;
		}else{
			$this->can_edit = true;
		}
		wp_debug_log( "can_edit : " . $this->can_edit );
		wp_debug_log( "id_or_data : " . serialize( $id_or_data ) );
		parent::__construct( $id_or_data, $args );
	}

	/*
	 * Getters
	 */



	/**
	 * Template functions
	 */

	/**
	 * @param bool $echo
	 *
	 * @return null|string
	 */
	public function writeForm( $echo = true ){
		wp_debug_log();

		$html = '<div id="h4a-edit-form">';

		$html .= $this->writeTopForm();

		$text_domain = ( !empty( $this->options ) && !empty( $this->options['text_domain'] ) ) ? $this->options['text_domain'] : null;

		$html .= $this->writeInputTitle( $echo, $text_domain);

		$html .= '<div id="post-body-content">';
		if( !empty( $this->content ) ){
			//Form -> wrappers
			$html .= '<section class="h4a-section-wrappers">';
			$html .= $this->writeFormWrappers();
			$html .= '</section>';
		}
		$html .= '</div>';  // post-body-content - End

		if( $this->can_edit ){
			$html .= '<div id="postbox-container-1" class="postbox-container">';
			if( !empty( $this->options['submitBox'] ) )
				$html .= $this->writeBackEndSubmitBox();
			if( !empty( $this->options['postboxes'] ) )
				$html .= $this->writePostBoxes();
			$html .= '</div>'; // postbox-container-1 - End
		}

		$html .= $this->writeBottomForm();
		$html .= "</div>";
		if( $echo ){
			echo $html;
		}else{
			return $html;
		}
		return null;
	}


	/* Main block functions */

	public function writeInputTitle( $echo = false, $domain = null ){
		wp_debug_log();
		if( isset( $this->options['title_display'] ) ){
			$html = '';
			$html .= '<div id="titlediv">';
			$html .= '<div id="titlewrap">';
			$text_domain = ( !empty( $domain ) ) ? $domain :  $this->current_plugin_domain;
			$html .= sprintf( '<label class="screen-reader-text" id="title-prompt-text" for="title">' .
			                  esc_html( __( 'Enter the %s name here', $this->current_plugin_domain ) ) . '</label>',
				_x( $this->options['item_name'], "title_display_placeholder", $text_domain )
			);

			$item_title_atts = array(
				'type' => 'text',
				'name' => sprintf( '%s_title', format_str_to_kebabcase( $this->options['item_type'] )  ),
				'size' => 30,
				'id' => 'title',
				'spellcheck' => 'true',
				'autocomplete' => 'off',
				'required' => true,
				'disabled' => ( $this->can_edit ) ? false : true
			);

			if( !empty( $this->options['title_display'] ) )
				$item_title_atts['value'] = $this->options['title_display'];

			$html .= sprintf( '<input %s />', format_attrs( $item_title_atts ) );

			$html .= '</div>';  // titlewrap - End
			$html .= '</div>';  // titlediv - End
			if( $echo ){
				echo $html;
			}else{
				return $html;
			}
		}
		return null;
	}

	public function writeTopForm( $echo = false ){
		wp_debug_log();
		$html = '';
		//Form - Begin
		if( $this->can_edit ){
			$attr_action = ( strpos( $this->action, '?') !== false ) ? $this->action . "&noheader=true" : $this->action . "?noheader=true";
			$form_atts = array(
				'id'     => $this->html_id,
				'action' => $attr_action,
				'enctype'=> $this->enctype,
				'method' => "post",
				'class'  => "h4a-form"
			);
			$html .= sprintf( '<form %s >', format_attrs( $form_atts ) );
			$html .= wp_nonce_field( $this->options['action_wpnonce'], '_wpnonce', true, false );
		}
		$html .= '<div id="poststuff">';
		$html .= sprintf('<div id="post-body" class="metabox-holder %s">', ( $this->can_edit ) ? 'columns-2' : null ) ;
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
		$str_submit .= "<p class=\"submit\">";
		$str_submit .= "<button id=\"submit\" class=\"button button-primary\">";
		$str_submit .= $label;
		$str_submit .= "</button>";
		$str_submit .= "</p>";
		return $str_submit;
	}

	public function writeBottomForm( $echo = false ){
		wp_debug_log();
		$html = "";
		$html .= "</div>";  // post-body - End
		$html .= "</div>";  // poststuff - End
		//Form - End
		if( $this->can_edit ){
			$html .= '</form>';
		}
		if( $echo ){
			echo $html;
		}else{
			return $html;
		}
		return null;
	}

	protected function override_field( $field ){
		wp_debug_log();
		if( !$this->can_edit ){
			if( $field['type'] === "link" )
				$field['show'] = false;
			if( $field['type'] === "radio" && ( !isset( $field['checked'] ) || !$field['checked'] ) ){
				$field['show'] = false;
			}
			if( $field['type'] === "checkbox" ){
				if( !isset( $field['checked'] ) || !$field['checked'] ){
					error_log_array( $field );
					$field['value'] = $field['label'];
					$field['label'] = null;
					$field['class'] = "h4a-strikethrough";
					$field['type'] = "info";
				}
			}
			if ( in_array( $field['type'], array( "text", "number", "email", "password", "date" ) ) ){
				if( ( $field['type'] === "date" && $field['value'] === "0000-00-00" ) || empty( $field['value'] )  ){
					$field['show'] = false;
				}
			}if( in_array( $field['type'], array( "text", "number", "email", "date", "select" ) ) ){
				if( $field['type'] === "select" ){
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
						foreach ( $a_options as $value => $label ) {
							$isSelected = false;
							if( !empty( $field['selected'] ) ){
								$isSelected = ( (string) $field['selected'] === (string) $value ) ? 'selected="selected"' : null;
							}
							if( $isSelected ){
								$field['value'] = (string) $label;
							}
						}
					}else{
						$field['value'] = $field['selected'];
					}
				}
				$field['type'] = "info";
			}
		}
		return $field;
	}

	/* PostBoxes functions */

	private function writePostBoxes( $echo = false ){
		wp_debug_log();
		$html = '';
		foreach ( $this->options['postboxes'] as $postbox ){
			$html .= $this->writePostBox( $postbox['key'], $postbox['title'], $postbox['content'] );
		}
		if( $echo ){
			echo $html;
		}else{
			return $html;
		}
		return null;
	}

	private static function writePostBox( $postBoxKey, $submitTitle, $content = null, $echo = false ){
		$html   = '';
		$html   .= sprintf( '<div id="%sdiv" class="postbox %s">',
			$postBoxKey,
			( !empty ( $content['major-actions'] ) ) ? "postbox-with-major-actions" : null
		);
		$current_plugin_domain = get_current_plugin_domain();
		$html   .= '<h2 class="hndle">' . esc_html( _x( $submitTitle, 'title_postbox', $current_plugin_domain ) ) . '</h2>';
		$html   .= '<div class="inside">';
		$html   .= sprintf( '<div class="%sbox" id="%spost">', $postBoxKey, $postBoxKey );
		if( is_string( $content ) ){
			$html .= $content;
		}else if( is_array( $content ) ){
			if( !empty( $content['minor-actions'] ) ){
				$html .= '<div id="minor-publishing-actions">';
				$html .=  $content['minor-actions'];
				$html .= '<div class="clear"> </div>'; // Caution : let a space to correctly work with DOMDocument
				$html .= '</div>'; // minor-publishing-actions - End
			}
			if( !empty( $content['misc-actions'] ) ){
				$html .= '<div id="misc-publishing-actions">';
				foreach ( $content['misc-actions'] as $misc_action ){
					$html .= self::write_misc_publishing_action( $misc_action );
				}
				$html .= '<div class="clear"> </div>'; // Caution : let a space to correctly work with DOMDocument
				$html .= '</div>'; // misc-publishing-actions - End
			}
			if( !empty( $content['major-actions'] ) ){
				$html .= '<div id="major-publishing-actions">';
				if( !empty ( $content['major-actions']['delete'] ) ){
					if( empty( $content['major-actions']['delete']['label'] ) )
						wp_error_log( 'the label for the delete link is blank !' );
					if( empty( $content['major-actions']['delete']['href'] ) )
						wp_error_log( 'href for the delete link is blank !' );
					if( !empty( $content['major-actions']['delete']['label'] ) && !empty( $content['major-actions']['delete']['href'] ) ){
						$html .= '<div id="delete-action">';
						$delete_href = ( strpos( $content['major-actions']['delete']['href'], '?') !== false ) ? $content['major-actions']['delete']['href'] . "&amp;noheader=true" : $content['major-actions']['delete']['href'] . "?noheader=true";
						$html .= sprintf( '<a class="%sdelete deletion" href="%s" >%s</a>',
							$postBoxKey,
							$delete_href,
							$content['major-actions']['delete']['label'],
							$current_plugin_domain );
						$html .= '</div>'; // delete-action - End
					}
				}
				if( !empty ( $content['major-actions']['save'] ) ){
					$html .= '<div id="publishing-action">';
					$html .= '<span class="spinner"> </span>'; // Caution : let a space to correctly work with DOMDocument
					$html .= sprintf( '<input name="%s" type="submit" class="button button-primary button-large" id="publish" value="%s" />',
						$content['major-actions']['save']['name'],
						$content['major-actions']['save']['value']
					);
					$html .= '<div class="clear"> </div>'; // Caution : let a space to correctly work with DOMDocument
					$html .= '</div>'; // publishing-action - End
				}
				if( !empty ( $content['major-actions']['button'] ) ){
					$html .= '<div id="publishing-action">';
					$html .= sprintf( '<button id="%s" type="button" class="button button-primary button-large" >%s</button>',
						$content['major-actions']['button']['id'],
						$content['major-actions']['button']['value']
					);
					$html .= '<div class="clear"> </div>'; // Caution : let a space to correctly work with DOMDocument
					$html .= '</div>'; // publishing-action - End
				}
				$html .= '<div class="clear"> </div>'; // Caution : let a space to correctly work with DOMDocument
				$html .= '</div>'; // major-publishing-actions - End
			}
		}
		$html .= '</div>'; // submitpost - End
		$html .= '</div>'; // .inside - End
		$html .= '</div>'; // #{$postBoxKey}div - End
		if( $echo ){
			echo $html;
			return false;
		}else{
			return $html;
		}
	}

	public static function write_misc_publishing_action( $misc_action = array(), $echo = false ){
		$html = "";
		if( !empty( $misc_action ) ){
			if( isset( $misc_action['html'] ) ){
				$html .= $misc_action['html'];
			}else{
				$html   .= sprintf( '<div class="misc-pub-section misc-pub-%s-%s">', $misc_action['keys'][0], $misc_action['keys'][1]  );
				$current_plugin_domain = get_current_plugin_domain();
				$html   .= _x( $misc_action['label'], 'misc_postbox_label', $current_plugin_domain ) . " : ";
				$html   .= sprintf(
					' <span id="%s-%s-display" >%s</span>',
					$misc_action['keys'][0],
					$misc_action['keys'][1],
					__( ucfirst( $misc_action['value'] ) , $current_plugin_domain )
				);
				if( !empty( $misc_action['modify'] ) && !empty( $misc_action['modify']['options'] ) && isset( $misc_action['modify']['selected'] ) ){
					$html .= sprintf( ' <a href="#%s_%s" class="edit-%s-%s hide-if-no-js" role="button" ><span aria-hidden="true">%s</span> <span class="screen-reader-text">%s</span></a>',
						$misc_action['keys'][0],
						$misc_action['keys'][1],
						$misc_action['keys'][0],
						$misc_action['keys'][1],
						__( 'Edit' ),
						sprintf( __( 'Edit %s' ), __( $misc_action['label'], $current_plugin_domain ) )
					);
					$html .= sprintf( '<div id="%s-%s-select" class="hide-if-js">',
						$misc_action['keys'][0],
						$misc_action['keys'][1]
					);
					$html .= sprintf( '<input type="hidden" name="hidden_%s_%s" id="hidden_%s_%s" value="%s" />',
						$misc_action['keys'][0],
						$misc_action['keys'][1],
						$misc_action['keys'][0],
						$misc_action['keys'][1],
						$misc_action['modify']['selected']
					);
					$select_name = ( isset( $misc_action['modify']['name'] ) ) ? $misc_action['modify']['name'] : $misc_action['keys'][0] . "_" . $misc_action['keys'][1];
					$select_id = ( isset( $misc_action['modify']['id'] ) ) ? $misc_action['modify']['id'] : $misc_action['keys'][0] . "_" . $misc_action['keys'][1];
					$html .= sprintf( '<select name="%s" id="%s" autocomplete="off">',
						$select_name,
						$select_id
					);
					foreach ( $misc_action['modify']['options'] as $option ){
						$html .= sprintf( '<option %s value="%s">%s</option>',
							( (string) $misc_action['modify']['selected'] === (string) $option['value'] ) ? 'selected="selected"': null,
							$option['value'],
							$option['label']
						);
					}
					$html .= '</select>';
					$html .= sprintf('<a href="#%s_%s" class="save-%s-%s hide-if-no-js button">%s</a>',
						$misc_action['keys'][0],
						$misc_action['keys'][1],
						$misc_action['keys'][0],
						$misc_action['keys'][1],
						__('OK') );
					$html .= sprintf('<a href="#%s_%s" class="cancel-%s-%s hide-if-no-js button-cancel">%s</a>',
						$misc_action['keys'][0],
						$misc_action['keys'][1],
						$misc_action['keys'][0],
						$misc_action['keys'][1],
						__('Cancel') );

					$html .= '</div>';
				}else{
					if( !empty( $misc_action['data-id'] ) ){
						$prefix = get_current_plugin_prefix();
						$html .= sprintf( '<input type="hidden" name="%sf_%s_%s" id="%sf_%s_%s" value="%s" />',
							$prefix,
							$misc_action['keys'][0],
							$misc_action['keys'][1],
							$prefix,
							$misc_action['keys'][0],
							$misc_action['keys'][1],
							$misc_action['data-id']
						);
					}
					$html .= sprintf( '<input type="hidden" name="hidden_%s_%s" id="hidden_%s_%s" value="%s" />',
						$misc_action['keys'][0],
						$misc_action['keys'][1],
						$misc_action['keys'][0],
						$misc_action['keys'][1],
						$misc_action['value']
					);
				}
				$html .= '</div>';// misc-pub-section - End
			}
		}
		if( $echo ){
			echo $html;
		}else{
			return $html;
		}
		return null;
	}

	public function writeBackEndSubmitBox( $echo = false ){
		if( is_array( $this->options['crud'] ) & !empty( $this->options['crud'] ) & !empty( $this->options['item_type'] ) ){
			$str_item_name = format_str_to_kebabcase( $this->options['item_type'] );
			$postBoxKey = "submit";
			$submitTitle = ( empty( $this->options['submitBox']['title'] ) ) ? "Publish" : $this->options['submitBox']['title'];
			$submitButtonLabel = ( empty( $this->options['submitBox']['button'] ) ) ? "Publish" : $this->options['submitBox']['button'];
			/* HTML Content */
			$content = array(
				'minor-actions' =>  null,
				'misc-actions' => null,
				'major-actions' =>  array(
					'delete' => array(),
					'save' => array()
				)
			);
			if( $this->options['crud']['u'] !== false && is_string( $this->options['crud']['u'] ) ) {

				$minor_actions = '<div id="save-action">';
				if( isset( $this->options['draft'] ) && $this->options['draft'] && ( $this->options['crud']['c'] || $this->options['crud']['u'] === 'draft' ) ){
					$minor_actions .= sprintf( '<input type="submit" name="save" id="save-%s" value="%s" class="button" />', $str_item_name ,__( 'Save Draft' ) );
				}else{
					$minor_actions .= " "; // Caution : let a space to correctly work with DOMDocument
				}
				$minor_actions .= "</div>"; // save-action - End

				$content['minor-actions'] = $minor_actions;

				$content['misc-actions'] = array(
					0 => array(
						'keys' => array( $str_item_name, "status"),
						'label' => "Status",
						'value' => _x( ucfirst( $this->options['crud']['u'] ), 'edition', $this->current_plugin_domain )
					)
				);

				if( $this->options['crud']['c'] === false ) {
					$content['misc-actions'][0]['modify'] = array(
						'selected' => $this->options['crud']['u'],
						'options' => array(
							array( 'label' => __( "Published" ), 'value' => "published" ),
							array( 'label' => __( "Draft" ), 'value' => "draft" )
						)
					);
				}
			}

			if( $this->options['crud']['d'] ){
				$content['major-actions'] = array(
					'delete' => array(
						'label' => __( 'Move to Trash' ),
						'href' => $this->options['delete_href']
					)
				);

			}
			if( $this->options['crud']['c'] || $this->options['crud']['u'] === "draft"  ){
				$content['major-actions']['save']['value'] = __( $submitButtonLabel );
				$content['major-actions']['save']['name'] = "publish";
			}else{
				$content['major-actions']['save']['value'] = __( 'Update' );
				$content['major-actions']['save']['name'] = "save";
			}

			/* End - HTMLContent */
			$html = $this->writePostBox( $postBoxKey, $submitTitle, $content );
			if( $echo ){
				echo $html;
			}else{
				return $html;
			}
		}
		return null;
	}
}