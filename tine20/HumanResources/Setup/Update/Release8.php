<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */
class HumanResources_Setup_Update_Release8 extends Setup_Update_Abstract
{
    /**
     * update to 8.1
     *  - add position field to employee
     */
    public function update_0()
    {
        $field = '<field>
                    <name>position</name>
                    <type>text</type>
                    <length>128</length>
                </field>';
    
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        
        $this->_backend->addCol('humanresources_employee', $declaration);
        
        $this->setTableVersion('humanresources_employee', '14');
        $this->setApplicationVersion('HumanResources', '8.1');
    }
}
