<?php
/**
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * additional config options
     * 
     * @var array
     */
    protected $_additionalOptions = array(
        'group_id'                      => '',
        'password'                      => '',
        'accountLoginNamePrefix'        => '',
        'accountHomeDirectoryPrefix'    => '',
        'accountEmailDomain'            => '',
        'samba'                         => '',
        'userNameSchema'			    => 1,
    );
    
    /**
     * set controller
     */
    protected function _setController()
    {
        switch($this->_options['model']) {
            case 'Tinebase_Model_FullUser':
                $this->_controller = Admin_Controller_User::getInstance();
                break;
            default:
                throw new Tinebase_Exception_InvalidArgument(get_class($this) . ' needs correct model in config.');
        }
    }
    
    /**
     * creates a new importer from an importexport definition
     * 
     * @param  Tinebase_Model_ImportExportDefinition $_definition
     * @param  array                                 $_options
     * @return Calendar_Import_Ical
     * 
     * @todo move this to abstract when we no longer need to be php 5.2 compatible
     */
    public static function createFromDefinition(Tinebase_Model_ImportExportDefinition $_definition, array $_options = array())
    {
        return new Admin_Import_Csv(self::getOptionsArrayFromDefinition($_definition, $_options));
    }
    
    /**
     * import single record (create password if in data)
     *
     * @param Tinebase_Record_Abstract $_record
     * @param string $_resolveStrategy
     * @param array $_recordData
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _importRecord($_record, $_resolveStrategy = NULL, $_recordData = array())
    {
        if ($_record instanceof Tinebase_Model_FullUser && $this->_controller instanceof Admin_Controller_User) {
            
            $record = $_record;
        
            // create valid login name
            if (! isset($record->accountLoginName)) {
                $record->accountLoginName = Tinebase_User::getInstance()->generateUserName($record, $this->_options['userNameSchema']);
            }
            
            // add prefix to login name if given 
            if (! empty($this->_options['accountLoginNamePrefix'])) {
                $record->accountLoginName = $this->_options['accountLoginNamePrefix'] . $record->accountLoginName;
            }
            
            // add home dir if empty and prefix is given (append login name)
            if (empty($record->accountHomeDirectory) && ! empty($this->_options['accountHomeDirectoryPrefix'])) {
                $record->accountHomeDirectory = $this->_options['accountHomeDirectoryPrefix'] . $record->accountLoginName;
            }
            
            // create email address if accountEmailDomain if given
            if (empty($record->accountEmailAddress) && ! empty($this->_options['accountEmailDomain'])) {
                $record->accountEmailAddress = $record->accountLoginName . '@' . $this->_options['accountEmailDomain'];
            }
            
            if (! empty($this->_options['samba'])) {
                $this->_addSambaSettings($record);
            }
            
            Tinebase_Event::fireEvent(new Admin_Event_BeforeImportUser($record, $this->_options));
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($record->toArray(), true));
            
            // generate passwd (use accountLoginName or password from options or password from csv in this order)
            $password = $record->accountLoginName;
            if (! empty($this->_options['password'])) {
                $password = $this->_options['password'];
            }
            if (isset($_recordData['password']) && ! empty($_recordData['password'])) {
                $password = $_recordData['password'];
            }
            
            $this->_addEmailUser($record, $password);
            
            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Adding record: ' . print_r($record->toArray(), TRUE));
                
            // try to create record with password
            if ($record->isValid()) {   
                if (!$this->_options['dryrun']) {
                    $record = $this->_controller->create($record, $password, $password);
                } else {
                    $this->_importResult['results']->addRecord($record);
                }
                $this->_importResult['totalcount']++;
            } else {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Record invalid: ' . print_r($record->getValidationErrors(), TRUE));
                throw new Tinebase_Exception_Record_Validation('Imported record is invalid.');
            }
        } else {
            $record = parent::_importRecord($_record, $_resolveStrategy, $_recordData);
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
        $sambaOptions = $this->_options['samba'];
        
        $samUser = new Tinebase_Model_SAMUser(array(
            'homePath'      => $sambaOptions['homePath'] . $_record->accountLoginName,
            'homeDrive'     => $sambaOptions['homeDrive'],
            'logonScript'   => $sambaOptions['logonScript'],
            'profilePath'   => $sambaOptions['profilePath'] . $_record->accountLoginName,
            'pwdCanChange'  => isset($sambaOptions['pwdCanChange'])  ? $sambaOptions['pwdCanChange']  : new Tinebase_DateTime('@1'),
            'pwdMustChange' => isset($sambaOptions['pwdMustChange']) ? $sambaOptions['pwdMustChange'] : new Tinebase_DateTime('@2147483647')
        ));
        
        $_record->sambaSAM = $samUser;
    }
    
    /**
     * add email users to record (if email set + config exists)
     * 
     * @param Tinebase_Model_FullUser $_record
     * @param string $_password
     */
    protected function _addEmailUser(Tinebase_Model_FullUser $_record, $_password)
    {
        if (! empty($_record->accountEmailAddress)) {
            $_record->imapUser = new Tinebase_Model_EmailUser(array(
                'emailPassword' => $_password
            ));
            $_record->smtpUser = new Tinebase_Model_EmailUser(array(
                'emailPassword' => $_password
            ));
        }
    }
    
    /**
     * add some more values (primary group)
     *
     * @return array
     */
    protected function _addData()
    {
        if ($this->_options['model'] == 'Tinebase_Model_FullUser') {
            if (! empty($this->_options['group_id'])) {
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
