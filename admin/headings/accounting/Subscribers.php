<?php

namespace H4APlugin\WPGroupSubs\Admin\Accounting;

use H4APlugin\Core\Admin\EditableListTableFromDBTemplate;

class Subscribers extends EditableListTableFromDBTemplate {

    protected function set_additional_scripts() {
        wp_enqueue_style( "", H4A_WGS_PLUGIN_DIR_URL . "admin/headings/accounting/views/css/wgs-subscribers.css" );
    }

}