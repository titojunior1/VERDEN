<?php

class Core_DbMapper_Type_Time extends Core_DbMapper_Type_DateTime
{
    public static $_adapterType = 'time';
    public static $_format = 'H:i:s';
}
