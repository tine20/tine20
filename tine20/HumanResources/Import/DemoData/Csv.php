<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * csv import class for the HumanResources
 *
 * @package     HumanResources
 * @subpackage  Import
 */
class HumanResources_Import_DemoData_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * additional config options
     *
     * @var array
     */
    protected $_additionalOptions = array(
        'dates' => array('employment_begin','employment_end')
    );

    protected $_costCenter;

    protected function _doConversions($_data)
    {
        $result = parent::_doConversions($_data);

        $this->_costCenter = $result['costcenter'];

        if (!empty($result['supervisor'])) {
            foreach (HumanResources_Controller_Employee::getInstance()->getAll() as $supervisor) {
                if ($result['supervisor'] == $supervisor['n_fn']) {
                    $result['supervisor_id'] = $supervisor['id'];
                }
            }
        }
        If (!empty($result['division'])) {
            foreach (Sales_Controller_Division::getInstance()->getAll() as $division) {
                if ($result['division'] == $division['title']) {
                    $result['division_id'] = $division['id'];
                }
            }
        }

        $result = $this->_setUser($result);

        return $result;
    }

    protected function _inspectAfterImport($importedRecord)
    {
        try
        {
            $event_id = Tinebase_Container::getInstance()->getContainerByName('Calendar_Model_Event', 'Events','shared')['event_id'];
            $contract_Model = new HumanResources_Model_Contract(array(
                'start_date' => Tinebase_DateTime::now(),
                'employee_id' => $importedRecord['id'],
                'feast_calendar_id' => $event_id,
                'vacation_days' => '27',
                'workingtime_json' => '{"days":[8,8,8,8,5.5,0,0]}'
            ));
            HumanResources_Controller_Contract::getInstance()->create($contract_Model);
        }catch(Exception $e)
        {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' Dont exist Calendar Container: Events');
        }

        $translate = Tinebase_Translation::getTranslation('HumanResources');

        $contract_Model = new HumanResources_Model_Contract(array(
            'start_date' => Tinebase_DateTime::now(),
            'employee_id' => $importedRecord['id'],
            'feast_calendar_id' => $event_id,
            'vacation_days' => '27',
            'working_time_scheme' => HumanResources_Controller_WorkingTimeScheme::getInstance()->search(
                new HumanResources_Model_WorkingTimeSchemeFilter([
                    ['field' => 'title', 'operator' => 'equals', 'value' => $translate->_('Full-time 40 hours')]
                ]))->getFirstRecord()->getId(),
        ));
        
        HumanResources_Controller_Contract::getInstance()->create($contract_Model);
        

        foreach (Sales_Controller_CostCenter::getInstance()->getAll() as $costCenter)
        {
            if($costCenter['remark'] == $this->_costCenter )
            {
                $costCenter_id = $costCenter['id'];
            }
        }
        $costCenter_Model = new HumanResources_Model_CostCenter(array(
            'start_date' => Tinebase_DateTime::now(),
            'cost_center_id' => $costCenter_id,
            'employee_id' => $importedRecord['id']
        ));        
        HumanResources_Controller_CostCenter::getInstance()->create($costCenter_Model);

    }

    protected function _setUser($result)
    {
        if (!empty($result['user'])) {
            $fullUser = Tinebase_FullUser::getInstance()->getFullUserByLoginName($result['user']);
            $result['account_id'] = $fullUser->getId();
            $contact = Addressbook_Controller_Contact::getInstance()->get($fullUser['contact_id']);
            $conctact_data = array(
                'n_fn' => $contact['n_fn'],
                'n_family' => $contact['n_family'],
                'n_given' => $contact['n_given'],
                'tel_home' => $contact['tel_home'],
                'tel_cell' => $contact['tel_cell'],
                'countryname' => $contact['adr_one_countryname'],
                'locality' => $contact['adr_one_locality'],
                'postalcode' => $contact['adr_one_postalcode'],
                'region' => $contact['adr_one_region'],
                'street' => $contact['adr_one_street'],
                'email' => $contact['email'],
                'salutation' => $contact['salutation'],
                'bday' => $contact['bday'],
                'profession' => $contact['title'],
                'position' => $contact['org_unit'],
            );
            $result = array_merge($conctact_data, $result);
        }

        return $result;
    }

}
