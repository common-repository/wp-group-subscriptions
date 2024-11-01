<?php

namespace H4APlugin\WPGroupSubs\Admin\Members;

use H4APlugin\Core\Admin\EditableListTableFromDBTemplate;

class Members extends EditableListTableFromDBTemplate {

	public function __construct( $data ){
		$data['item_params']->getter = "member_id";
		parent::__construct( $data );
	}

}