<?php
/**
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Admin csv import class
 * 
 * @package     Admin
 * @subpackage  Import
 * 
 */
class Admin_Import_User_Csv extends Tinebase_Import_Csv_Abstract
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
        'accountLoginShell'             => '',
        'userNameSchema'                => 1
    );
    
    /**
     * set controller
     *
     * @throws Tinebase_Exception_InvalidArgument
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

        if (empty($this->_options['accountEmailDomain'])) {
            $config = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP)->toArray();
            if (isset($config['primarydomain'])) {
                $this->_options['accountEmailDomain'] = $config['primarydomain'];
            }
        }
    }
    
    /**
     * import single record (create password if in data)
     *
     * @param Tinebase_Record_Interface $_record
     * @param string $_resolveStrategy
     * @param array $_recordData
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _importRecord($_record, $_resolveStrategy = NULL, $_recordData = array())
    {
        if ($_record instanceof Tinebase_Model_FullUser && $this->_controller instanceof Admin_Controller_User) {
            
            $record = $_record;
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                . ' record Data' . print_r($_recordData, true));
            if (isset($_recordData['smtpUser'])) {
                $record->smtpUser = new Tinebase_Model_EmailUser($_recordData['smtpUser']);
            }
            if (isset($_recordData['imapUser'])) {
                $record->imapUser = new Tinebase_Model_EmailUser($_recordData['imapUser']);
            }
            if (isset($_recordData['samba']) && (!isset($this->_options['samba']) || empty($this->_options['samba']))) {
                $this->_options['samba'] = $_recordData['samba'];
            }
            if (isset($_recordData['accountHomeDirectoryPrefix'])) {
                $this->_options['accountHomeDirectoryPrefix'] = $_recordData['accountHomeDirectoryPrefix'];
            }
            
            $password = $record->applyOptionsAndGeneratePassword($this->_options, (isset($_recordData['password'])) ? $_recordData['password'] : NULL);
            Tinebase_Event::fireEvent(new Admin_Event_BeforeImportUser($record, $this->_options));
            
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
    
    protected function _doMapping($_data)
    {
        $result = parent::_doMapping($_data);
            $result['smtpUser'] = array(
                'emailForwardOnly' => isset($result['emailForwardOnly']) ? $result['emailForwardOnly'] : true,
                'emailForwards'    => isset($result['emailForwards']) && !empty($result['emailForwards']) ? explode(' ', trim($result['emailForwards'])) : array(),
                'emailAliases'     => isset($result['emailAliases']) ? explode(' ', trim($result['emailAliases'])) : array()
                            );
            $result['groups'] = !empty($result['groups']) ? array_map('trim',explode(",",$result['groups'])) : array();
            
            $result['samba'] = array(
                'homePath'      => (isset($result['homePath'])) ? stripslashes($result['homePath']) : '',
                'homeDrive'     => (isset($result['homeDrive'])) ? stripslashes($result['homeDrive']) : '',
                'logonScript'   => (isset($result['logonScript'])) ? $result['logonScript'] : '',
                'profilePath'   => (isset($result['profilePath'])) ? stripslashes($result['profilePath']) : '',
                'pwdCanChange'  => isset($result['pwdCanChange'])  ? new Tinebase_DateTime($result['pwdCanChange']) : '',
                'pwdMustChange' => isset($result['pwdMustChange']) ? new Tinebase_DateTime($result['pwdMustChange']) : ''
                            );
        return $result;
    }
}
