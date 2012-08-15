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
        'accountLoginShell'             => '',
        'userNameSchema'                => 1,
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
            $password = $record->applyOptionsAndGeneratePassword($this->_options, (isset($_recordData['password'])) ? $_recordData['password'] : NULL);

            Tinebase_Event::fireEvent(new Admin_Event_BeforeImportUser($record, $this->_options));

            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($record->toArray(), true));
            
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
}
