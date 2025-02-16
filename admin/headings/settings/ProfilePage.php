<?php

namespace H4APlugin\WPGroupSubs\Admin\Settings;


use H4APlugin\Core\Admin\SettingsTemplate;
use function H4APlugin\Core\wp_debug_log;

class ProfilePage extends SettingsTemplate {

	public function set_additional_scripts() {
		wp_debug_log();

		wp_enqueue_script( "wgssettings", H4A_WGS_PLUGIN_DIR_URL . "admin/headings/settings/js/settings.js" );
		wp_localize_script( "wgssettings", "wgsSettingTranslation", self::getWGSSettingTranslation() );

		wp_enqueue_style( "wgsmodalpremiumstyle", H4A_WGS_PLUGIN_DIR_URL . "admin/headings/settings/css/wgs-settings.css" );
	}

	protected function write_aside_content() {
		// TODO: Implement write_aside_content() method.
	}
}