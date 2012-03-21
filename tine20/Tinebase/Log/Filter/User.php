<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Log
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Gökmen Ciyiltepe <g.ciyiltepe@metaways.de>
 * 
 */

/**
 * user filter for Zend_Log logger
 * 
 * @package     Tinebase
 * @subpackage  Log
 */
class Tinebase_Log_Filter_User implements Zend_Log_Filter_Interface
{
    /**
     * @var string
     */
    protected $_name;
    
    /**
     * Filter out any log messages not matching $name.
     *
     * @param  string  $name     Username to log message
     * @throws Zend_Log_Exception
     */
    public function __construct($name)
    {
        if (! is_string($name)) {
            require_once 'Zend/Log/Exception.php';
            throw new Zend_Log_Exception('Name must be a string');
        }

        $this->_name = $name;
    }
    
    /**
     * Returns TRUE to accept the message, FALSE to block it.
     *
     * @param  array    $event    event data
     * @return boolean            accepted?
     */
    public function accept($event)
    {
        $username = Tinebase_Core::getUser()->accountLoginName;
        return strtolower($this->_name) == strtolower($username) ? true : false;
    }
}
