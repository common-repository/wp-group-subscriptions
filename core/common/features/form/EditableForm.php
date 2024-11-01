<?php
namespace H4APlugin\Core\Common;

abstract class EditableForm extends EditableItem {
	use FormTrait;

	protected function get_blank( $args = array() ){
		return null;
	}
	
	protected function get_item_to_edit( $data ){
		return $data;
	}
}