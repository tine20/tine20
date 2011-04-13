<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Alarm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * alarms controller interface
 *  
 * @package     Tinebase
 * @subpackage  Alarm
 */
interface Tinebase_Controller_Alarm_Interface
{
    /**
     * sendAlarm - send an alarm and update alarm status/sent_time/...
     *
     * @param  Tinebase_Model_Alarm $_alarm
     * @return Tinebase_Model_Alarm
     */
    public function sendAlarm(Tinebase_Model_Alarm $_alarm);
    
}
