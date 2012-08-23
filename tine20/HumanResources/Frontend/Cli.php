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
 * cli frontend for humanresources
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
     * 
     * @param Zend_Console_Getopt $_opts
     * @return integer
     */
    public function transfer_user_accounts($_opts)
    {
        $args = $this->_parseArgs($_opts, array());
        $deletePrivateInfo = in_array('delete_private', $args['other']);
        $workingTimeModelId = array_key_exists('working_time_model_id', $args) ? $args['working_time_model_id'] : NULL;
        $feastCalendarId = array_key_exists('feast_calendar_id', $args) ? $args['feast_calendar_id'] : NULL;
        $vacationDays = array_key_exists('vacation_days', $args) ? $args['vacation_days'] : NULL;
        HumanResources_Controller_Employee::getInstance()->transferUserAccounts($deletePrivateInfo, $feastCalendarId, $workingTimeModelId, $vacationDays, true);
        
        return 0;
    }
    
    /**
     * import employee data from csv file
     * 
     * @param Zend_Console_Getopt $opts
     * @return integer
     */
    public function importEmployee($opts)
    {
        $args = $opts->getRemainingArgs();
        array_push($args, 'definition=' . 'hr_employee_import_csv');
        if ($opts->d) {
            array_push($args, '--dry');
        }
        if ($opts->v) {
            array_push($args, '--verbose');
        }
        $opts->setArguments($args);
        
        $result = $this->_import($opts);
        
        if (empty($result)) {
            return 2;
        }
        
        foreach ($result as $filename => $importResult) {
            $importedEmployee = $this->_getImportedEmployees($importResult);
            
            foreach ($importedEmployee as $employee) {
                $this->_sanitizeEmployee($employee);
                $currentEmployee = $this->_getCurrentEmployee($employee);
                
                if ($currentEmployee) {
                    $employee = $this->_updateImportedEmployee($opts, $employee, $currentEmployee);
                } else {
                    $employee = $this->_createImportedEmployee($opts, $employee);
                }
                
                if ($opts->v) {
                    print_r($employee->toArray());
                }
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
                    . print_r($employee->toArray(), TRUE));
            }
        }
        
        return 0;
    }
    
    /**
     * returns imported employee (incl. duplicates)
     * 
     * @param array $importResult
     * @return Tinebase_Record_RecordSet
     */
    protected function _getImportedEmployees($importResult)
    {
        $result = $importResult['results'];
        $result->id = NULL;
        
        foreach ($importResult['exceptions'] as $exceptionData) {
            $exception = $exceptionData['exception'];
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
                . print_r($exception, TRUE));
            if (isset($exception['clientRecord'])) {
                $recordData = $exception['clientRecord'];
                $duplicate = (isset($exception['duplicates']) && isset($exception['duplicates'][0])) ? $exception['duplicates'][0] : NULL;
                if ($duplicate) {
                    $recordData['id'] = $duplicate['id'];
                }
                $recordData['created_by'] = $recordData['created_by']['accountId'];
                $result->addRecord(new HumanResources_Model_Employee($recordData));
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
            . print_r($result->toArray(), TRUE));
        
        return $result;
    }
    
    /**
     * sanitize employee data
     * - fix n_fn + street(2) + number
     * 
     * @param HumanResources_Model_Employee $employee
     */
    protected function _sanitizeEmployee($employee)
    {
        if (preg_match('/([\w\s]+), ([\w\s]+)/u', $employee->n_fn, $matches)) {
            $employee->n_fn = $matches[2] . ' ' . $matches[1];
        }
        if (! empty($employee->street2)) {
            $employee->street = $employee->street . ' ' . $employee->street2;
            $employee->street2 = '';
        }
    }
    
    /**
     * get existing employee
     * 
     * @param HumanResources_Model_Employee $employee
     * @return HumanResources_Model_Employee
     */
    protected function _getCurrentEmployee($employee)
    {
        if ($employee->getId()) {
            try {
                $currentEmployee = HumanResources_Controller_Employee::getInstance()->get($employee->getId());
            } catch (Tinebase_Exception_NotFound $tenf) {
                $currentEmployee = NULL;
            }
        } else {
            $currentEmployee = HumanResources_Controller_Employee::getInstance()->search(new HumanResources_Model_EmployeeFilter(array(array(
                'field'     => 'query',
                'operator'  => 'contains',
                'value'     => $employee->n_fn
            ))))->getFirstRecord();
        }
        
        return $currentEmployee;
    }
    
    /**
     * update employee
     * 
     * @param Zend_Console_Getopt $opts
     * @param HumanResources_Model_Employee $employee
     * @param HumanResources_Model_Employee $currentEmployee
     * @return HumanResources_Model_Employee
     */
    protected function _updateImportedEmployee($opts, $employee, $currentEmployee)
    {
        if ($opts->v) {
            echo "Updating employee " . $employee->n_fn . ".\n";
        }
        // update only some fields
        $fieldsToUpdate = array(
            'postalcode',
            'locality',
            'street',
            'bday',
            'employment_begin',
            'employment_end',
            'bank_account_number',
            'bank_name',
            'bank_code_number',
            'health_insurance',
            'number',
        );
        $changed = FALSE;
        foreach ($fieldsToUpdate as $field) {
            if (! empty($employee->{$field}) && $currentEmployee->{$field} !== $employee->{$field}) {
                $currentEmployee->{$field} = $employee->{$field};
                $changed = TRUE;
            }
        }
        
        if ($opts->d || ! $changed) {
            $result = $currentEmployee;
        } else {
            $result = HumanResources_Controller_Employee::getInstance()->update($currentEmployee);
        }
        
        return $result;
    }
    
    /**
     * insert new (+check if user account exists) or update existing employee
     * 
     * @param Zend_Console_Getopt $opts
     * @param HumanResources_Model_Employee $employee
     * @return HumanResources_Model_Employee
     */
    protected function _createImportedEmployee($opts, $employee)
    {
        try {
            $user = Tinebase_User::getInstance()->getUserByProperty('accountFullName', $employee->n_fn);
            $employee->account_id = $user->getId();
            if ($opts->v) {
                echo "User " . $employee->n_fn . " found and added account_id.\n";
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
            if ($opts->v) {
                echo "User " . $employee->n_fn . " no found\n";
            }
        }
        if (! $opts->d) {
            $employee = HumanResources_Controller_Employee::getInstance()->create($employee);
        }
        if ($opts->v) {
            echo "Created new employee " . $employee->n_fn . ".\n";
        }
        
        return $employee;
    }
}
