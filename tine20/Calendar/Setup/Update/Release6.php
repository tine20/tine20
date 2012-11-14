<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * update to 7.0
     * 
     * @return void
     */
    public function update_1()
    {
        $this->setApplicationVersion('Calendar', '7.0');
    }
}
