<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Christian Feitl<c.feitl@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * csv import class for the Timetracker
 *
 * @package     Timetracker
 * @subpackage  Import
 *
 */
class Timetracker_Import_Timesheet_Csv extends Tinebase_Import_Csv_Generic
{
    /**
     * additional config options
     *
     * @var array
     */
    protected $_additionalOptions = array(
        'container_id'      => '',
        'dates'             => array('billed_in','start_date')
    );
    
    /**
     * add some more values (container id)
     *
     * @return array
     */
    protected function _addData()
    {
        $result['container_id'] = $this->_options['container_id'];
        return $result;
    }

    /**
     * do conversions
     *
     * @param array $_data
     * @return array
     */
    protected function _doConversions($_data)
    {
        $result = parent::_doConversions($_data);

        $timeaccount_Controller = Timetracker_Controller_Timeaccount::getInstance();
        $timeaccount_data = $timeaccount_Controller->getAll();
        foreach ($timeaccount_data as $timeaccount_id)
        {
            $result['timeaccount_id'] = $timeaccount_id['id'];
        }        
        
        $user_LoginName = Tinebase_Core::getUser();
        $user = Tinebase_User::getInstance()->getUserByLoginName($user_LoginName);
        $result['account_id'] = $user->getId();


        return $result;
    }
}