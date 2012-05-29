<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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

    /**
    * create default favorites and update to 5.3
    *
    * @return void
    */
    public function update_2()
    {
        // Timeaccounts
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Timetracker')->getId(),
            'model'             => 'Timetracker_Model_TimeaccountFilter',
        );
        
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $pfe->create(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'              => "Timeaccounts to bill", // _('Timeaccounts to bill')
                'description'       => "Timeaccounts to bill",
                'filters'           => array(
                    array(
                        'field'     => 'status',
                        'operator'  => 'equals',
                        'value'     => 'to bill',
                    )
                ),
            ))
        ));
        
        $pfe->create(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'              => "Timeaccounts not yet billed", // _('Timeaccounts not yet billed')
                'description'       => "Timeaccounts not yet billed",
                'filters'           => array(
                    array(
                        'field'     => 'status',
                        'operator'  => 'equals',
                        'value'     => 'not yet billed',
                    )
                ),
            ))
        ));

        $pfe->create(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'              => "Timeaccounts already billed", // _('Timeaccounts already billed')
                'description'       => "Timeaccounts already billed",
                'filters'           => array(
                    array(
                        'field'     => 'status',
                        'operator'  => 'equals',
                        'value'     => 'billed',
                    )
                ),
            ))
        ));
        
        $this->setApplicationVersion('Timetracker', '5.3');
    }
    
    /**
    * rename timesheet favorites and update to 5.4
    *
    * @return void
    */
    public function update_3()
    {
        $rename = array("Timesheets today", "Timesheets this week", "Timesheets last week", "Timesheets this month", "Timesheets last month");
        foreach ($rename as $name) {
            $this->_db->update(SQL_TABLE_PREFIX . 'filter', array(
                'name'        => 'My ' . $name,
                'description' => 'My ' . $name
            ), "`name` = '{$name}' and account_id IS NULL");
        }
        
        $this->setApplicationVersion('Timetracker', '5.4');
    }
    
    /**
    * update to 6.0
    *
    * @return void
    */
    public function update_4()
    {
        $this->setApplicationVersion('Timetracker', '6.0');
    }
}
