<?php

/**
 * @package Core\DbMapper
 */
class Core_DbMapper_Config implements Serializable
{
    protected $_defaultConnection;
    protected $_connections = array();
    protected static $_typeHandlers = array();

    public function __construct()
    {
        // Setup default type hanlders
		self::_loadTypeHandler();
    }

    /**
     * Add database connection
     *
     * @param string $name Unique name for the connection
     * @param string $dsn DSN string for this connection
     * @param array $options Array of key => value options for adapter
     * @param boolean $defaut Use this connection as the default? The first connection added is automatically set as the default, even if this flag is false.
     * @return Spot_Adapter_Interface Spot adapter instance
     * @throws Spot_Exception
     */
    public function addConnection($name, $dsn, array $options = array(), $default = false)
    {
        // Connection name must be unique
        if(isset($this->_connections[$name])) {
            throw new Exception("Connection for '" . $name . "' already exists. Connection name must be unique.");
        }

        $dsnp = Core_DbMapper_Adapter_AdapterAbstract::parseDSN($dsn);
        $adapterClass = "Core_DbMapper_Adapter_" . ucfirst($dsnp['adapter']);
        $adapter = new $adapterClass($dsn, $options);

        // Set as default connection?
        if(true === $default || null === $this->_defaultConnection) {
            $this->_defaultConnection = $name;
        }

        // Store connection and return adapter instance
        $this->_connections[$name] = $adapter;
        return $adapter;
    }


    /**
     * Get connection by name
     *
     * @param string $name Unique name of the connection to be returned
     * @return Spot_Adapter_Interface Spot adapter instance
     * @throws Spot_Exception
     */
    public function connection($name = null)
    {
        if(null === $name) {
            return $this->defaultConnection();
        }

        // Connection name must be unique
        if(!isset($this->_connections[$name])) {
            return false;
        }

        return $this->_connections[$name];
    }

	/**
	 * Get type handler class by type
	 *
	 * @param string $type Field type (i.e. 'string' or 'int', etc.)
	 * @return Spot_Adapter_Interface Spot adapter instance
	 */
	public static function typeHandler($type, $class = null) {
		if (null === $class) {
			if(empty(self::$_typeHandlers)) {
				self::_loadTypeHandler();
			}

			if (! isset(self::$_typeHandlers[$type])) {
				throw new InvalidArgumentException("Type '$type' not registered. Register the type class handler with Core_DbMapper_Config::typeHanlder('$type', '\Namespaced\Path\Class').");
			}
			return self::$_typeHandlers[$type];
		}

		if (! class_exists($class)) {
			throw new InvalidArgumentException("Second parameter must be valid className with full namespace. Check the className and ensure the class is loaded before registering it as a type handler.");
		}

		self::$_typeHandlers[$type] = new $class();

		return self::$_typeHandlers[$type];
	}

    /**
     * Get default connection
     *
     * @return Spot_Adapter_Interface Spot adapter instance
     * @throws Spot_Exception
     */
    public function defaultConnection()
    {
        return $this->_connections[$this->_defaultConnection];
    }

    public function setDefaultConnection($conn) {
    	$this->_defaultConnection = 'default';
    	$this->_connections[$this->_defaultConnection] = $conn;
    }

    /**
     * Default serialization behavior is to not attempt to serialize stored
     * adapter connections at all (thanks @TheSavior re: Issue #7)
     */
    public function serialize()
    {
        return serialize(array());
    }

    public function unserialize($serialized)
    {
    }

    protected static function _loadTypeHandler() {
    	self::typeHandler('string', 'Core_DbMapper_Type_String');
    	self::typeHandler('text', 'Core_DbMapper_Type_Text');

    	self::typeHandler('int', 'Core_DbMapper_Type_Integer');
    	self::typeHandler('integer', 'Core_DbMapper_Type_Integer');
    	self::typeHandler('smallint', 'Core_DbMapper_Type_Integer');
    	self::typeHandler('tinyint', 'Core_DbMapper_Type_Integer');

    	self::typeHandler('float', 'Core_DbMapper_Type_Float');
    	self::typeHandler('double', 'Core_DbMapper_Type_Float');
    	self::typeHandler('decimal', 'Core_DbMapper_Type_Float');

    	self::typeHandler('bool', 'Core_DbMapper_Type_Boolean');
    	self::typeHandler('boolean', 'Core_DbMapper_Type_Boolean');

    	self::typeHandler('datetime', 'Core_DbMapper_Type_Datetime');
    	self::typeHandler('date', 'Core_DbMapper_Type_Datetime');
    	self::typeHandler('timestamp', 'Core_DbMapper_Type_Integer');
    	self::typeHandler('year', 'Core_DbMapper_Type_Integer');
    	self::typeHandler('month', 'Core_DbMapper_Type_Integer');
    	self::typeHandler('day', 'Core_DbMapper_Type_Integer');
    	self::typeHandler('time', 'Core_DbMapper_Type_Time');

    	self::typeHandler('serialized', 'Core_DbMapper_Type_Serialized');
    }
}
