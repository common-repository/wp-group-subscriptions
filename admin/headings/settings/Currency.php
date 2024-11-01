<?php

namespace H4APlugin\WPGroupSubs\Admin\Settings;

use H4APlugin\Core\Admin\SettingsTemplate;
use function H4APlugin\Core\wp_debug_log;

class Currency extends SettingsTemplate {

    public function set_additional_scripts() {
		wp_debug_log();

		wp_enqueue_script( "wgssettings", $this->current_plugin_dir_url . "admin/headings/settings/js/settings.js" );
	    wp_localize_script( "wgssettings", "wgsSettingTranslation", self::getWGSSettingTranslation() );

	    wp_enqueue_style( "wgsmodalpremiumstyle", $this->current_plugin_dir_url . "admin/headings/settings/css/wgs-settings.css" );
	}


    protected function write_aside_content()
    {
        return;
    }
}