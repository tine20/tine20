<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class HumanResources_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * update 0.1 -> 0.2
     * - add cost_center_id
     */
    public function update_1()
    {
        $field = '<field>
            <name>cost_center_id</name>
            <type>text</type>
            <length>40</length>
            <notnull>true</notnull>
        </field>';

        $declaration = new Setup_Backend_Schema_Field_Xml($field);

        $this->_backend->addCol('humanresources_contract', $declaration);
        $this->_backend->dropCol('humanresources_contract', 'cost_centre');

        $this->setTableVersion('humanresources_contract', '2');
        $this->setApplicationVersion('HumanResources', '0.2');
    }
    
    /**
     * update from 0.2 to 6.0
     * - update import export defs
     *
     * @return void
     */
    public function update_2()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('HumanResources'));
        $this->setApplicationVersion('HumanResources', '6.0');
    }
}
