<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2014-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Addressbook_Setup_Update_Release9 extends Setup_Update_Abstract
{
    /**
     * update to 9.1: add list_roles table
     *
     * @return void
     */
    public function update_0()
    {
        $table = Setup_Backend_Schema_Table_Factory::getSimpleRecordTable('addressbook_list_role');
        $this->_backend->createTable($table, 'Addressbook', 'addressbook_list_role');
        $this->setApplicationVersion('Addressbook', '9.1');
    }
}
