<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */
class Calendar_Setup_Update_Release6 extends Setup_Update_Abstract
{
    /**
     * update to 6.1
     * - apply status_id -> status if nessesary
     */
    public function update_0()
    {
        if ($this->getTableVersion('cal_events') < 6) {
            $update = new Calendar_Setup_Update_Release5($this->_backend);
            $update->update_5();
        }
        
        $this->setApplicationVersion('Calendar', '6.1');
    }
    
    /**
     * update to 6.2
     * 
     * @see 0008196: Preferences values contains translated value
     */
    public function update_1()
    {
        $prefBackend = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Preference', 
            'tableName' => 'preferences',
        ));
        $alarmPrefs = $prefBackend->search(new Tinebase_Model_PreferenceFilter(array(array(
            'field'    => 'name',
            'operator' => 'equals',
            'value'    => Calendar_Preference::DEFAULTALARM_MINUTESBEFORE
        ))));
        foreach ($alarmPrefs as $pref) {
            if (preg_match("/\((\d+)\)/", $pref->value, $matches)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Updating DEFAULTALARM_MINUTESBEFORE from ' . $pref->value . ' to ' . $matches[1]);
                $pref->value = $matches[1];
                $prefBackend->update($pref);
            }
        }
        
        $this->setApplicationVersion('Calendar', '6.2');
    }

    /**
     * update to 7.0
     * 
     * @return void
     */
    public function update_2()
    {
        $this->setApplicationVersion('Calendar', '7.0');
    }
}
