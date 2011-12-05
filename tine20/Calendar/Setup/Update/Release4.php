<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

class Calendar_Setup_Update_Release4 extends Setup_Update_Abstract
{
    /**
     * - empty rrule -> NULL
     */
    public function update_0()
    {
        $tablePrefix = SQL_TABLE_PREFIX;
        
        $this->_db->query("UPDATE {$tablePrefix}cal_events SET `rrule`=NULL WHERE `rrule` LIKE ''");
        
        $this->setApplicationVersion('Calendar', '4.1');
    }
}