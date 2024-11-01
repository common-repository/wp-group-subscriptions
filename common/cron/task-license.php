<?php

use function \H4APlugin\Core\wp_debug_log;
use function \H4APlugin\Core\is_license_activated;

function wgs_scheduled_task_license(){
	if ( ! wp_next_scheduled( 'wgs_scheduled_license_expirations_checking' ) ) {
		wp_schedule_event( time(), 'daily', 'wgs_scheduled_license_expirations_checking' );
	}
}

function wgs_license_expirations_checking(){
	wp_debug_log();
	is_license_activated( true );
}

