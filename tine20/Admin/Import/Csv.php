<?php
/**
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Admin csv import class
 * 
 * @package     Admin
 * @subpackage  Import
 * 
 */
class Admin_Import_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * import single record (create password if in data)
     *
     * @param array $_recordData
     * @param array $_result
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _importRecord($_recordData, &$_result)
    {
        if ($this->_modelName == 'Tinebase_Model_FullUser' && $this->_controller instanceof Admin_Controller_User) {
        
            $record = new $this->_modelName($_recordData, TRUE);
            
            // create valid login name
            if (! isset($record->accountLoginName)) {
                $record->accountLoginName = Tinebase_User::getInstance()->generateUserName($record);
            }
            
            // add prefix to login name if given 
            if (isset($this->_options['accountLoginNamePrefix'])) {
                $record->accountLoginName = $this->_options['accountLoginNamePrefix'] . $record->accountLoginName;
            }
            
            // add home dir if empty and prefix is given (append login name)
            if (empty($record->accountHomeDirectory) && isset($this->_options['accountHomeDirectoryPrefix'])) {
                $record->accountHomeDirectory = $this->_options['accountHomeDirectoryPrefix'] . $record->accountLoginName;
            }
            
            // create email address if accountEmailDomain if given
            if (empty($record->accountEmailAddress) && isset($this->_options['accountEmailDomain']) && ! empty($this->_options['accountEmailDomain'])) {
                $record->accountEmailAddress = $record->accountLoginName . '@' . $this->_options['accountEmailDomain'];
            }
            
            if (isset($this->_options['samba']) && ! empty($this->_options['samba'])) {
                $this->_addSambaSettings($record);
            }
            
            Tinebase_Event::fireEvent(new Admin_Event_BeforeImportUser($record, $this->_options));
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($record->toArray(), true));
            
            // generate passwd (use accountLoginName or password from options or password from csv in this order)
            $password = $record->accountLoginName;
            if (isset($this->_options['password'])) {
                $password = $this->_options['password'];
            }
            if (isset($_recordData['password']) && !empty($_recordData['password'])) {
                $password = $_recordData['password'];
            }
            
            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Adding record: ' . print_r($record->toArray(), TRUE));
                
            // try to create record with password
            if ($record->isValid()) {   
                if (!$this->_options['dryrun']) {
                    $record = $this->_controller->create($record, $password, $password);
                } else {
                    $_result['results']->addRecord($record);
                }
                $_result['totalcount']++;
            } else {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Record invalid: ' . print_r($record->getValidationErrors(), TRUE));
                throw new Tinebase_Exception_Record_Validation('Imported record is invalid.');
            }
        } else {
            $record = parent::_importRecord($_recordData, $_result);
        }
        
        return $record;
    }
    
    /**
     * add samba settings to user
     * 
     * @param Tinebase_Model_FullUser $_record
     */
    protected function _addSambaSettings(Tinebase_Model_FullUser $_record)
    {
        $sambaConfig = $this->_options['samba'];
        
        $samUser = new Tinebase_Model_SAMUser(array(
            'homePath'    => $sambaConfig['homePath'] . $_record->accountLoginName,
            'homeDrive'   => $sambaConfig['homeDrive'],
            'logonScript' => $sambaConfig['logonScript'],
            'profilePath' => $sambaConfig['profilePath'] . $_record->accountLoginName
        ));
        
        $_record->sambaSAM = $samUser;
    }
    
    /**
     * add some more values (primary group)
     *
     * @return array
     */
    protected function _addData()
    {
        if ($this->_modelName == 'Tinebase_Model_FullUser') {
            if (isset($this->_options['group_id'])) {
                $groupId = $this->_options['group_id'];
            } else {
                // add default user group
                $defaultUserGroup = Tinebase_Group::getInstance()->getDefaultGroup();
                $groupId = $defaultUserGroup->getId();
            }
            $result = array(
                'accountPrimaryGroup'   => $groupId
            );
        } else {
            $result = parent::_addData();
        }
        
        return $result;
    }
}
