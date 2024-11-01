<?php

namespace H4APlugin\Core\Common;

use H4APlugin\Core\Config;
use function H4APlugin\Core\get_current_plugin_initials;
use function H4APlugin\Core\wp_debug_log;

define( 'H4A_SETTING_SECTION', 'setting_section_' );
define( 'H4A_OPTIONS_GROUP', get_current_plugin_initials() . '_options' );

trait SettingsTrait
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    protected $current_options;

    public $options_group;
    public $current_options_name;
    public $options_names = array();

    public function __construct( $options_group, $option_slug ){
        wp_debug_log();
    	$this->options_group = $options_group;
        $this->current_options_name = Config::gen_options_name( $option_slug );
    	$this->options_names = Config::get_options_names();
        add_action( "admin_init", array( $this, "init_options" ), 1 );
    }

    public function init_options(){
        foreach ( $this->options_names as $options_name ){
            register_setting(
                $this->options_group, // Option group
                $options_name, // Option name
                array( __CLASS__, "sanitize" ) // Sanitize
            );
        }
        $this->add_settings();
        $this->set_current_options();
    }

    abstract protected function add_settings();

    abstract protected function set_current_options();
}