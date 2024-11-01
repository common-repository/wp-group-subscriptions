<?php
$plugin_domain = \H4APlugin\Core\get_current_plugin_domain();
?>
<h1><?php _e( "My profile", $plugin_domain ); ?></h1>
<section>
    <?php
    echo do_shortcode( "[wgs-profile-account][/wgs-profile-account]" );
    echo do_shortcode( "[wgs-profile-subscription][/wgs-profile-subscription]" );
	?>
</section>

