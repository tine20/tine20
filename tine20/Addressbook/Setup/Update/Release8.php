<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2014-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stinting <a.stintzing@metaways.de>
 */
class Addressbook_Setup_Update_Release8 extends Setup_Update_Abstract
{
    /**
     * update to 8.1
     * 
     * @return void
     */
    public function update_0()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Addressbook'));
        $this->setApplicationVersion('Addressbook', '8.1');
    }

    /**
     * update to 8.2
     *
     * @see 0011000: Cannot accept invitation to meeting when organiser email is too long
     *
     * @return void
     */
    public function update_1()
    {
        $colsToChange = array('email', 'email_home');
        foreach ($colsToChange as $col) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>' . $col . '</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>');
            $this->_backend->alterCol('addressbook', $declaration);
        }
        $this->setTableVersion('addressbook', 18);
        $this->setApplicationVersion('Addressbook', '8.2');
    }
    
    /**
     * update to 9.0
     *
     * @return void
     */
    public function update_2()
    {
        $this->setApplicationVersion('Addressbook', '9.0');
    }
}
