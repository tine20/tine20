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
     * 
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
    
    /**
     * update to 8.2
     * 
     *  - add lastday_date and days_count field to freetime
     */
    public function update_1()
    {
        $field = '<field>
                <name>lastday_date</name>
                <type>date</type>
            </field>
        ';
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('humanresources_freetime', $declaration);
        
        $field = '<field>
                <name>days_count</name>
                <type>integer</type>
                <notnull>false</notnull>
                <default>0</default>
            </field>
        ';
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('humanresources_freetime', $declaration);
    
        $this->setTableVersion('humanresources_freetime', '7');
        $this->setApplicationVersion('HumanResources', '8.2');
    }
    
    /**
     * update to 8.3 normalize contracts to have fields from update_1 set
     */
    public function update_2()
    {
        $c = HumanResources_Controller_FreeTime::getInstance();
        $d = HumanResources_Controller_FreeDay::getInstance();
        
        $allC = $c->getAll();
        $allD = $d->getAll();
        
        if ($allC->count() > 0) {
            try {
                $this->promptForUsername();
                
                foreach($allC as $ct) {
                    $ct->freedays = $allD->filter('freetime_id', $ct->id)->toArray();
                    $c->update($ct);
                }
            } catch (Exception $e) {
                // do nothing, update not important
            }
        }
        $this->setApplicationVersion('HumanResources', '8.3');
    }
}
