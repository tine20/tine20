<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Felamimail_Setup_Update_Release12 extends Setup_Update_Abstract
{
    /**
     * update to 12.1
     *
     * add felamimail_message_filelocation table
     */
    public function update_0()
    {
        Setup_SchemaTool::updateSchema([Felamimail_Model_MessageFileLocation::class]);
        $this->setApplicationVersion('Felamimail', '12.1');
    }

    /**
     * update to 12.2
     *
     * update felamimail_message_filelocation table and add delete observer
     */
    public function update_1()
    {
        Setup_SchemaTool::updateSchema([Felamimail_Model_MessageFileLocation::class]);
        Felamimail_Setup_Initialize::addDeleteNodeObserver();
        $this->setApplicationVersion('Felamimail', '12.2');
    }

    /**
     * update to 12.3
     *
     * ensure vacation template folder is present
     */
    public function update_2()
    {
        $release11 = new Felamimail_Setup_Update_Release11($this->_backend);
        $release11->update_1();
        $this->setApplicationVersion('Felamimail', '12.3');
    }
}
