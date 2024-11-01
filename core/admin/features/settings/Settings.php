<?php

namespace H4APlugin\Core\Admin;

use H4APlugin\Core\Common\SettingsTrait;

abstract class Settings
{

    use SettingsTrait;

    abstract protected function sanitize( $input );

    protected function set_current_options(){
        $this->current_options = get_option( $this->current_options_name );
    }
}