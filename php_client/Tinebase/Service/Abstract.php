<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     New BSD License
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Abstract Service Class
 * 
 * @package     Tinebase
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
    protected $_connection = NULL;
    
    /**
     * constructs new Service
     *
     * @param Tinebase_Connection $_connection
     */
    public function __construct($_connection = NULL)
    {
        $this->_connection = $_connection ? $_connection : Tinebase_Connection::getDefaultConnection();
        if (! $this->_connection instanceof Tinebase_Connection) {
            throw new Exception('Could not create service. No connection available.');
        }
    }

}