<?php

class Core_DbMapper_Type_Float extends Core_DbMapper_Type
{
    public static $_adapterType = 'decimal';
    public static $_adapterOptions = array('precision' => 14, 'scale' => 10);

    /**
     * Cast given value to type required
     */
    public function cast($value)
    {
        if(strlen($value)) {
            return (float) $value;
        }
        return null;

    }
}
