<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2014-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Addressbook_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * update to 10.1:
     * - add multiple sync backends / ldap implementation
     * - add addressbook_industry table and column
     *
     * @return void
     */
    public function update_0()
    {
        $release9 = new Addressbook_Setup_Update_Release9($this->_backend);
        $release9->update_9();
        $release9->update_10();

        $this->setApplicationVersion('Addressbook', '10.1');
    }

    /**
     * fixes adb table version (versions got mixed up in previous update scripts)
     *
     * @return void
     */
    public function update_1()
    {
        $this->setTableVersion('addressbook', 22);
        $this->setApplicationVersion('Addressbook', '10.2');
    }
}
