<?php

namespace H4APlugin\WPGroupSubs\Shortcodes;

use function H4APlugin\Core\wp_debug_log;

abstract class Shortcode implements iShortcode{

	public $tag;
	public $attrs;
	public $function;

	public function __construct( $tag ) {
		wp_debug_log( get_called_class() );
		$this->attrs = array();
		$this->tag = $tag;
		$this->function = get_called_class().'::getCallBack';

		add_shortcode( $this->tag, $this->function );

		$this->init();
	}

	protected function init(){
		if( !is_admin() ){
			//Front-end
			add_action('wp',  array( $this , 'check_page' ) );
		}
	}

	abstract public function check_page();

	public static function page_template( $template = "" ) {
		if( empty( $template ) ){
			$template = locate_template( array( 'page.php' ) );
		}
		return $template;
	}

}