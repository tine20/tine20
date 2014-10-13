<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Jan Evers <j.evers@metaways.de>
 */

/**
 * updates for major release 8
 *
 * @package     ActiveSync
 * @subpackage  Setup
 */
class ActiveSync_Setup_Update_Release8 extends Setup_Update_Abstract
{
    /**
     * update to 8.1
     *
     * @see 0010752: update script for android 5.0 / lollipop devices
     *
     * @return void
     */
    public function update_0()
    {
        $from = SQL_TABLE_PREFIX . 'acsync_device';
        $where = array($this->_db->quoteIdentifier('useragent') . ' LIKE ?' => 'Android/5%');
        $this->_db->delete($from, $where);

        $this->setApplicationVersion('ActiveSync', '8.1');
    }

    /**
     * update to 8.2
     *
     * @see 0010752: update script for android 4.0 / lollipop devices
     *
     * @return void
     */
    public function update_1()
    {
        $from = SQL_TABLE_PREFIX . 'acsync_device';
        $where = array($this->_db->quoteIdentifier('useragent') . ' LIKE ?' => 'Android/4%');
        $this->_db->delete($from, $where);

        $this->setApplicationVersion('ActiveSync', '8.2');
    }
    
    /**
     * update to 9.0
     *
     * @return void
     */
    public function update_2()
    {
        $this->setApplicationVersion('ActiveSync', '9.0');
    }
}
