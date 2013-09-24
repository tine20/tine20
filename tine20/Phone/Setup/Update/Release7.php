<?php
/**
 * Tine 2.0
 *
 * @package     Phone
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */
class Phone_Setup_Update_Release7 extends Setup_Update_Abstract
{
    /**
     * update to 7.1
     *  - add default favorites
     *  
     * @return void
     */
    public function update_0()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Phone')->getId(),
            'model'             => 'Phone_Model_CallFilter',
        );
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Calls this week", // _("Calls this week")
            'description'       => "Incoming and outgoing calls from this week",
            'filters'           => array(array(
                'field'     => 'start',
                'operator'  => 'within',
                'value'     => 'weekThis',
            )),
        ))));
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Calls last week", // _("Calls last week")
            'description'       => "Incoming and outgoing calls from last week",
            'filters'           => array(array(
                'field'     => 'start',
                'operator'  => 'within',
                'value'     => 'weekLast',
            )),
        ))));
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Calls this month", // _("Calls this month")
            'description'       => "Incoming and outgoing calls from this month",
            'filters'           => array(array(
                'field'     => 'start',
                'operator'  => 'within',
                'value'     => 'monthThis',
            )),
        ))));
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Calls last month", // _("Calls last month")
            'description'       => "Incoming and outgoing calls from last month",
            'filters'           => array(array(
                'field'     => 'start',
                'operator'  => 'within',
                'value'     => 'monthLast',
            )),
        ))));
        
        $this->setApplicationVersion('Phone', '7.1');
    }

    /**
     * update to 8.0
     *
     * @return void
     */
    public function update_1()
    {
        $this->setApplicationVersion('Phone', '8.0');
    }
}
