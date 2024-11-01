<?php

namespace H4APlugin\WPGroupSubs\Admin\Accounting;

use H4APlugin\Core\Admin\H4A_Editable_List_Table;
use function H4APlugin\Core\return_datetime;
use function H4APlugin\Core\wp_admin_build_url;
use function H4APlugin\Core\wp_debug_log;

class Subscribers_List_Table extends H4A_Editable_List_Table {

	public function column_last_name( $item ) {
		wp_debug_log();
		$str = $item['last_name'].' '.$item['first_name'];
		if( isset($_GET['subscriber_view']) && $_GET['subscriber_view'] !== "trash" ){
			$args_edit  = array(
				'action' => "edit",
				$this->item_params->slug => $item[ $this->item_params->getter ]
			);
			$edit_link = wp_admin_build_url( "edit-" . $this->item_params->name, false, $args_edit );

			$output = sprintf( '<a class="row-title" href="%1$s" title="%2$s">%3$s</a>',
				esc_url( $edit_link ),
				esc_attr( sprintf( __( "Edit &#8220;%s&#8221;", $this->current_plugin_domain ), $item[ $this->primary ] ) ),
				$str
			);
		}else{
			$output = $str;
		}
		return $output;
	}

	public function column_group_name( $item ) {
		return ( !empty( $item["group_name"] ) ) ? $item["group_name"] : ' - ';
	}

	public function column_last_subscription_date( $item ) {
		$utc_start_date = $item["last_subscription_date"];
		return ( !empty($utc_start_date) ) ? return_datetime( $utc_start_date ) : ' - ';
	}

}
