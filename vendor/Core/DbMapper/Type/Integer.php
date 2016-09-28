<?php

class Core_DbMapper_Type_Integer extends Core_DbMapper_Type
{
    public static $_adapterType = 'integer';

    /**
     * Cast given value to type required
     */
    public function cast($value)
    {
        if(strlen($value)) {
            return (int) $value;
        }
        return null;
    }
}
