<?php

namespace H4APlugin\WPGroupSubs\Admin\Accounting;

use H4APlugin\Core\Admin\H4A_List_Table;

class Payments_List_Table extends H4A_List_Table {

    public function column_payment_id( $item ) {
        $label = __( $item['payment_id'], $this->current_plugin_domain );
        $label .= " - ";
        $label .= ( !empty( $item['subscriber_id'] ) ) ? __( "Assigned", $this->current_plugin_domain ) : __( "Unassigned", $this->current_plugin_domain ) ;
        return sprintf( '<span class="row-status row-status-%s">%s</span>',
            strtolower( $item['payment_status'] ),
            $label
        ) ;

    }

 	public function column_subscriber( $item ) {

		$str = $item['first_name'].' '.$item['last_name'];
		$str .= ( !empty( $item['group_name'] ) ) ? " - " . $item['group_name']  : null ;
		return $str;
		
	}

    public function column_payment_type( $item ) {

        $str = $item['payment_type'];
        $str .= ( $item['payment_type'] === "paypal" ) ? " - (" . $item['txn_id'] . ")"  : null ;
        return $str;

    }

}
