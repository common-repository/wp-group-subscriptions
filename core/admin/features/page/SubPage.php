<?php

namespace H4APlugin\Core\Admin;


use H4APlugin\Core\Common\H4AObjectTrait;

class SubPage extends Screen {

    use H4AObjectTrait;

	public $parent_slug;

	public function __construct( $data ) {
		$mandatory_params = array( "parent_slug" );
		$this->setObject( $mandatory_params, $data );
		parent::__construct( $data );
	}

}