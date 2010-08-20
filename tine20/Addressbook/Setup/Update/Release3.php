<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

class Addressbook_Setup_Update_Release3 extends Setup_Update_Abstract
{
    /**
     * update from 3.0 -> 3.1
     * - add new import definition
     * 
     * @return void
     */
    public function update_0()
    {
        // get import export definitions and save them in db
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Addressbook'));
        
        $this->setApplicationVersion('Addressbook', '3.1');
    }
    
    /**
     * create default persistent filters
     */
    public function update_1()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $myEventsPFilter = $pfe->create(new Tinebase_Model_PersistentFilter(array(
            'name'              => Addressbook_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => "All contacts I have read grants for", // _("All contacts I have read grants for")
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model'             => 'Addressbook_Model_ContactFilter',
            'filters'           => array(),
        )));
        
        $this->setApplicationVersion('Addressbook', '3.2');
    }
    
    /**
     * lat & lon can be negative (change fields to unsigned float)
     */
    public function update_2()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>lon</name>
                <type>float</type>
                <unsigned>false</unsigned>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>lat</name>
                <type>float</type>
                <unsigned>false</unsigned>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration);

        $this->setTableVersion('addressbook', '8');
        $this->setApplicationVersion('Addressbook', '3.3');
    }
}
