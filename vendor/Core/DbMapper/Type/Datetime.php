<?php

class Core_DbMapper_Type_Datetime extends Core_DbMapper_Type
{
    public static $_adapterType = 'datetime';
    public static $_format = 'Y-m-d H:i:s';

    /**
     * Cast given value to type required
     */
    public function cast($value)
    {
        if(is_string($value) || is_numeric($value)) {
            // Create new \DateTime instance from string value
            if (is_numeric($value)) {
              $value = new DateTime('@' . $value);
            } else if ($value) {
              $value = new DateTime($value);
            } else {
              $value = null;
            }
        }
        return $value;
    }

    public function dump($value)
    {
        $value = $this->cast($value);
        if ($value) {
            $value = $value->format(self::$_format);
        }
        return $value;
    }
}
