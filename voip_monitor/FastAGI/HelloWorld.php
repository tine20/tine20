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
class FastAGI_HelloWorld
{
    public function __construct(FastAGI $_fastAGI, array $_arguments)
    {
        $this->_fastAGI   = $_fastAGI;
        $this->_arguments = $_arguments;
        
        $this->_processRequest();
    }
    
    protected function _processRequest()
    {
        $this->_fastAGI->answer();
        $this->_fastAGI->verbose('Test');
        $this->_fastAGI->sayTime(mktime());
    }
}
