<?php

class Core_DbMapper_Type_Serialized extends Core_DbMapper_Type
{
    public static $_adapterType = 'text';

    /**
     * Cast given value to type required
     */
    public function load($value)
    {
        if(is_string($value)) {
            $value = @unserialize($value);
        } else {
            $value = null;
        }
        return $value;
    }

    public function dump($value)
    {
        return serialize($value);
    }
}
