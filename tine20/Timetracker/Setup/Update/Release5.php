<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Timetracker_Setup_Update_Release5 extends Setup_Update_Abstract
{
    /**
     * update to 5.1
     * - enum -> text
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>status</name>
                <type>text</type>
                <length>32</length>
                <default>not yet billed</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('timetracker_timeaccount', $declaration);
        
        $this->setTableVersion('timetracker_timeaccount', 7);
        $this->setApplicationVersion('Timetracker', '5.1');
    }
    
    /**
     * update favorites to new filter syntax and update to 5.2
     * 
     * @return void
     */
    public function update_1()
    {
        $filters = Tinebase_PersistentFilter::getInstance()->getAll();
        $timetrackerFilters = $filters->filter('application_id', Tinebase_Application::getInstance()->getApplicationByName('Timetracker')->getId());
        $pfBackend = new Tinebase_PersistentFilter_Backend_Sql();
        
        foreach ($timetrackerFilters as $pfilter) {
            foreach ($pfilter->filters as $filter) {
                if (in_array($filter->getField(), array('timeaccount_id')) && $filter instanceof Tinebase_Model_Filter_ForeignId) {
                    $values = array();
                    foreach ($filter->getValue() as $idx => $subfilter) {
                        $values[$idx] = $subfilter;
                        if (in_array($subfilter['field'], array('id'))) {
                            $values[$idx]['field'] = ':' . $subfilter['field'];
                        }
                    }
                    $filter->setValue($values);
                    $pfBackend->update($pfilter);
                }
            }
        }
        
        $this->setApplicationVersion('Timetracker', '5.2');
    }
}
