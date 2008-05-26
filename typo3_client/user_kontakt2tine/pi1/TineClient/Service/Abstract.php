<?php
/**
 * Tine 2.0
 *
 * @package     Tineclient
 * @subpackage  Service
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id: Abstract.php 1313 2008-03-22 08:08:49Z lkneschke $
 */
require_once( PATH_site . 'typo3conf/ext/user_kontakt2tine/pi1/TineClient/Connection.php');
abstract class TineClient_Service_Abstract
{
    protected static $_defaultConnection = NULL;
    
    protected $_connection = NULL;
    
    public function __construct($_connection = NULL)
    {
        if($_connection instanceof TineClient_Connection) {
            $this->_connection = $_connection;
        }
    }
    
    public function setConnection(TineClient_Connection $_connection)
    {
        $this->_connection = $_connection;
        
        return $this;
    }
    
    /**
     * Enter description here...
     *
     * @return TineClient_Connection
     */
    public function getConnection()
    {
        if($this->_connection instanceof TineClient_Connection) {
            return $this->_connection;
        } else {
            return self::$_defaultConnection;
        }
    }
    
    public static final function setDefaultConnection(TineClient_Connection $_connection)
    {
        self::$_defaultConnection = $_connection;
    }

    /**
     * get the default connection
     *
     * @return TineClient_Connection
     */
    public static final function getDefaultConnection()
    {
        return self::$_defaultConnection;
    }
}