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
class FastAGI_HelloWorld extends FastAGI_Abstract
{
    public function processRequest()
    {
        $this->_fastAGI->answer();
        #$this->_fastAGI->verbose('Test');
        $this->_fastAGI->sayTime(mktime());
        #$this->_fastAGI->sendText('Hallo');
        #$this->_fastAGI->recordFile('records/test', 'wav', '#', 20000, true);
        $this->_fastAGI->streamFile('records/test');
        $digit = $this->_fastAGI->waitForDigit(5000);
        if($digit !== null) {
            $this->_fastAGI->sayDigits($digit);
        }
        #$this->_fastAGI->hangup();
    }
}
