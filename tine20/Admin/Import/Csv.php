<?php
/**
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * additional config options
     * 
     * @var array
     */
    protected $_additionalConfig = array(
        'group_id'                      => '',
        'password'                      => '',
        'accountLoginNamePrefix'        => '',
        'accountHomeDirectoryPrefix'    => '',
        'accountEmailDomain'            => '',
        'samba'                         => '',
    );
    
    /**
     * constructs a new importer from given config
     * 
     * @param array $_config
     */
    public function __construct(array $_config = array())
    {
        parent::__construct($_config);
        
        switch($this->_config['model']) {
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
     * @param  array                                 $_config
     * @return Calendar_Import_Ical
     * 
     * @todo move this to abstract when we no longer need to be php 5.2 compatible
     */
    public static function createFromDefinition(Tinebase_Model_ImportExportDefinition $_definition, array $_config = array())
    {
        return new Admin_Import_Csv(self::getConfigArrayFromDefinition($_definition, $_config));
    }
    
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
        if ($this->_config['model'] == 'Tinebase_Model_FullUser' && $this->_controller instanceof Admin_Controller_User) {
        
            $record = new $this->_config['model']($_recordData, TRUE);
            
            // create valid login name
            if (! isset($record->accountLoginName)) {
                $record->accountLoginName = Tinebase_User::getInstance()->generateUserName($record);
            }
            
            // add prefix to login name if given 
            if (! empty($this->_config['accountLoginNamePrefix'])) {
                $record->accountLoginName = $this->_config['accountLoginNamePrefix'] . $record->accountLoginName;
            }
            
            // add home dir if empty and prefix is given (append login name)
            if (empty($record->accountHomeDirectory) && ! empty($this->_config['accountHomeDirectoryPrefix'])) {
                $record->accountHomeDirectory = $this->_config['accountHomeDirectoryPrefix'] . $record->accountLoginName;
            }
            
            // create email address if accountEmailDomain if given
            if (empty($record->accountEmailAddress) && ! empty($this->_config['accountEmailDomain'])) {
                $record->accountEmailAddress = $record->accountLoginName . '@' . $this->_config['accountEmailDomain'];
            }
            
            if (! empty($this->_config['samba'])) {
                $this->_addSambaSettings($record);
            }
            
            Tinebase_Event::fireEvent(new Admin_Event_BeforeImportUser($record, $this->_config));
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($record->toArray(), true));
            
            // generate passwd (use accountLoginName or password from options or password from csv in this order)
            $password = $record->accountLoginName;
            if (! empty($this->_config['password'])) {
                $password = $this->_config['password'];
            }
            if (isset($_recordData['password']) && !empty($_recordData['password'])) {
                $password = $_recordData['password'];
            }
            
            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Adding record: ' . print_r($record->toArray(), TRUE));
                
            // try to create record with password
            if ($record->isValid()) {   
                if (!$this->_config['dryrun']) {
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
        $sambaConfig = $this->_config['samba'];
        
        $samUser = new Tinebase_Model_SAMUser(array(
            'homePath'      => $sambaConfig['homePath'] . $_record->accountLoginName,
            'homeDrive'     => $sambaConfig['homeDrive'],
            'logonScript'   => $sambaConfig['logonScript'],
            'profilePath'   => $sambaConfig['profilePath'] . $_record->accountLoginName,
            'pwdCanChange'  => isset($sambaConfig['pwdCanChange'])  ? $sambaConfig['pwdCanChange']  : new Tinebase_DateTime('@1'),
            'pwdMustChange' => isset($sambaConfig['pwdMustChange']) ? $sambaConfig['pwdMustChange'] : new Tinebase_DateTime('@2147483647')
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
        if ($this->_config['model'] == 'Tinebase_Model_FullUser') {
            if (! empty($this->_config['group_id'])) {
                $groupId = $this->_config['group_id'];
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
