<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Alarm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * alarms record interface
 *  
 * @package     Tinebase
 * @subpackage  Alarm
 */
interface Tinebase_Record_Alarm_Interface
{
    /**
     * returns alarm datetime field
     *
     * @return string
     */
    public function getAlarmDateTimeField();
}
