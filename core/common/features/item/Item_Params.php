<?php

namespace H4APlugin\Core\Common;


class Item_Params
{
    public $singular; //Optional
    public $plural; //Optional

    public function __construct( $data )
    {
        if( !empty( $data['singular'] ) )
            $this->singular = $data['singular'];
        if( !empty( $data['plural'] ) )
            $this->plural = $data['plural'];
    }

}