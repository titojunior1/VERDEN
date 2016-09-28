<?php

class Core_DbMapper_Type_Boolean extends Core_DbMapper_Type
{
    public static $_adapterType = 'boolean';

    /**
     * Cast given value to type required
     */
    public function cast($value)
    {
        return (bool) $value;
    }

    /**
     * Core_DbMapper_Boolean is generally persisted as an integer
     */
    public function dump($value)
    {
        return (int) $value;
    }
}
