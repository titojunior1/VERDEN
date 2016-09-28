<?php

class Core_DbMapper_Type implements Core_DbMapper_Type_TypeInterface
{
    public static $_loadHandlers = array();
    public static $_dumpHandlers = array();
    public static $_adapterType = 'string';
    public static $_adapterOptions = array();


    /**
     * Cast given value to type required
     */
    public function cast($value)
    {
        return $value;
    }

    /**
     * Geting value off Core_DbMapper_Entity object
     */
    public function get(Core_DbMapper_Entity $entity, $value)
    {
        return $this->cast($value);
    }

    /**
     * Setting value on Core_DbMapper_Entity object
     */
    public function set(Core_DbMapper_Entity $entity, $value)
    {
        return  $this->cast($value);
    }

    /**
     * Load value as passed from the datasource
     * internal to allow for extending on a per-adapter basis
     */
    public function _load($value, $adapter = null) {
        if (isset(self::$_loadHandlers[$adapter]) && is_callable(self::$_loadHandlers[$adapter])) {
            return call_user_func(self::$_loadHandlers[$adapter], $value);
        }
        return $this->load($value);
    }

    /**
     * Load value as passed from the datasource
     */
    public function load($value) {
        return  $this->cast($value);
    }

    /**
     * Dumps value as passed to the datasource
     * internal to allow for extending on a per-adapter basis
     */
    public function _dump($value, $adapter = null) {
        if (isset(self::$_dumpHandlers[$adapter]) && is_callable(self::$_dumpHandlers[$adapter])) {
            return call_user_func(self::$_dumpHandlers[$adapter], $value);
        }
        return $this->dump($value);
    }

    /**
     * Dump value as passed to the datasource
     */
    public function dump($value) {
        return $this->cast($value);
    }

    /**
     * Array of adapter options with type
     *
     * @return array
     */
    public function adapterOptions() {
      return array_merge(self::$_adapterOptions, array(
          'type' => self::$_adapterType
      ));
    }
}
