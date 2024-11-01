<?php

namespace H4APlugin\Core\Admin;


abstract class Template_Base
{
    abstract public function write( &$htmlTmpl );
    abstract public function set_template_scripts();
}