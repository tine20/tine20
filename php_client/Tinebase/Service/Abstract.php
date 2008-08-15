<?php
/**
 * Tine 2.0
 *
 * @package     Tineclient
 * @subpackage  Service
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

abstract class Tinebase_Service_Abstract
{
	/**
	 * @var Tinebase_Connection
	 */
    protected static $_defaultConnection = NULL;
    
    /**
	 * @var Tinebase_Connection
	 */
    protected $_connection = NULL;
    
    public function __construct($_connection = NULL)
    {
        if($_connection instanceof Tinebase_Connection) {
            $this->_connection = $_connection;
        }
    }
    
    public function setConnection(Tinebase_Connection $_connection)
    {
        $this->_connection = $_connection;
        
        return $this;
    }
    
    /**
     * Enter description here...
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
    
    public static final function setDefaultConnection(Tinebase_Connection $_connection)
    {
        self::$_defaultConnection = $_connection;
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