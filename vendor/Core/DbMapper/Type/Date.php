<?php

class Core_DbMapper_Type_Date extends Core_DbMapper_Type_Datetime
{
    public static $_adapterType = 'date';
    public static $_format = 'Y-m-d';
}
