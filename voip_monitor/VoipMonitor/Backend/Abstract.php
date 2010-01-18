<?php
/**
 * VoIP Monitor Deamon
 * 
 * @package     VoipMonitor
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * abstract class for VoipMonitor frontends
 * 
 * @package     VoipMonitor
 */
abstract class VoipMonitor_Backend_Abstract
{
    protected $_config;
    
    protected $_stdErr;
    
    /**
     * the constructor
     * 
     * @param string  $_host
     * @param int     $_port
     */
    public function __construct(Zend_Config $_config)
    {
        $this->_config = $_config;
        
        $this->_stdErr = fopen('php://stderr', 'w');
    }
    
    abstract public function update(array $_event);
    
    abstract public function logout();
}

