<?php

class Core_DbMapper_Type_String extends Core_DbMapper_Type
{
    /**
     * Cast given value to type required
     */
    public function cast($value)
    {
        if(null !== $value) {
            return (string) $value;
        }
        return $value;
    }
}
