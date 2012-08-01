<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * cli server for humanresources
 *
 * This class handles cli requests for the humanresources
 *
 * @package     HumanResources
 * @subpackage  Frontend
 */
class HumanResources_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     * @var string
     */
    protected $_applicationName = 'HumanResources';

    protected $_help = array(
        'transfer_user_accounts' => array(
            'description'   => 'Transfers all Tine 2.0. User-Accounts to Employee Records. If feast_calendar_id and working_time_model_id is given, a contract will be generated for each employee.',
                'params' => array(
                    'delete_private'        => "removes private information of the contact-record of the imported account",
                    'feast_calendar_id'     => 'the id of the contracts\' feast calendar (container)',
                    'working_time_model_id' => 'use this working time model for the contract',
                    'vacation_days'         => 'use this amount of vacation days for the contract'
            )
        ),
    );

    /**
     * transfers the account data to employee data
     * @param unknown_type $_opts
     */
    public function transfer_user_accounts($_opts)
    {
        $args = $this->_parseArgs($_opts, array());
        $deletePrivateInfo = in_array('delete_private', $args['other']);
        $workingTimeModelId = array_key_exists('working_time_model_id', $args) ? $args['working_time_model_id'] : NULL;
        $feastCalendarId = array_key_exists('feast_calendar_id', $args) ? $args['feast_calendar_id'] : NULL;
        $vacationDays = array_key_exists('vacation_days', $args) ? $args['vacation_days'] : NULL;
        HumanResources_Controller_Employee::getInstance()->transferUserAccounts($deletePrivateInfo, $feastCalendarId, $workingTimeModelId, $vacationDays, true);
    }
}