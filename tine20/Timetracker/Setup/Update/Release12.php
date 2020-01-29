<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Timetracker_Setup_Update_Release12 extends Setup_Update_Abstract
{
    /**
     * update to 12.1
     *
     * set default type for TA - Contract relations
     */
    public function update_0()
    {
        $release11 = new Timetracker_Setup_Update_Release11($this->_backend);
        $release11->update_0();

        $this->setApplicationVersion('Timetracker', '12.1');
    }

    /**
     * update to 12.2
     *
     * convert models to MCV2
     */
    public function update_1()
    {
        // remove old index + fks first
        try {
            $this->_backend->dropForeignKey('timetracker_timeaccount', 'timeaccount::container_id--container::id');
        } catch (Exception $e) {
            // already dropped?
            Tinebase_Exception::log($e);
        }

        try {
            $this->_backend->dropForeignKey('timetracker_timeaccount_fav', 'timesheet_favorites--timesheet_id::id');
        } catch (Exception $e) {
            // already dropped?
            Tinebase_Exception::log($e);
        }

        $this->updateSchema('Timetracker', array('Timetracker_Model_Timesheet', 'Timetracker_Model_Timeaccount'));
        $this->setApplicationVersion('Timetracker', '12.2');
    }

    /**
     * update to 12.3
     *
     * @return void
     */
    public function update_2()
    {
        $this->updateSchema('Timetracker', array('Timetracker_Model_Timesheet'));
        $this->setApplicationVersion('Timetracker', '12.3');
    }
}
