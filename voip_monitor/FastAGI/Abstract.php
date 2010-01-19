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
 * VoIP Monitor Deamon
 * 
 * @package     VoipMonitor
 */
abstract class FastAGI_Abstract
{
    /**
     * 
     * @var FastAGI
     */
    protected $_fastAGI;
    
    public function __construct(FastAGI $_fastAGI, array $_arguments)
    {
        $this->_fastAGI   = $_fastAGI;
        $this->_arguments = $_arguments;
        
        $this->_processRequest();
    }
    
    abstract protected function _processRequest();
}
