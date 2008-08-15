<?php
/**
 * Tine 2.0
 *
 * @package     Tineclient
 * @subpackage  Service
 * @license     yet unknown
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Abstract Service Class
 */
abstract class Tinebase_Service_Abstract
{
    /**
     * @var bool
     */
    public $debugEnabled = false;
    
	/**
	 * @var Tinebase_Connection
	 */
    protected static $_defaultConnection = NULL;
    
    /**
	 * @var Tinebase_Connection
	 */
    protected $_connection = NULL;
    
    /**
     * constructs new Service
     *
     * @param Tinebase_Connection $_connection
     */
    public function __construct($_connection = NULL)
    {
        if ($_connection instanceof Tinebase_Connection) {
            $this->_connection = $_connection;
        }
    }
    
    /**
     * sets connection
     *
     * @param  Tinebase_Connection $_connection
     * @return Tinebase_Service_Abstract
     */
    public function setConnection(Tinebase_Connection $_connection)
    {
        $this->_connection = $_connection;
        return $this;
    }
    
    /**
     * get connection
     *
     * @return Tinebase_Connection
     */
    public function getConnection()
    {
        if($this->_connection instanceof Tinebase_Connection) {
            return $this->_connection;
        } else {
            return self::$_defaultConnection;
        }
    }
    
    /**
     * sets the default connection
     *
     * @param  Tinebase_Connection $_connection
     * @return Tinebase_Connection
     */
    public static final function setDefaultConnection(Tinebase_Connection $_connection)
    {
        self::$_defaultConnection = $_connection;
        return self::$_defaultConnection;
    }

    /**
     * get the default connection
     *
     * @return Tinebase_Connection
     */
    public static final function getDefaultConnection()
    {
        return self::$_defaultConnection;
    }
}