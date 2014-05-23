<?php
/**
 * DownloadLink controller for Filemanager application
 *
 * @package     Filemanager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * DownloadLink controller class for Filemanager application
 *
 * @package     Filemanager
 * @subpackage  Controller
 */
class Filemanager_Controller_DownloadLink extends Tinebase_Controller_Record_Abstract
{
    /**
     * check for container ACLs
     *
     * @var boolean
     *
     * @todo rename to containerACLChecks
     */
    protected $_doContainerACLChecks = false;
    
    /**
     * the constructor
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = 'Filemanager';
        $this->_modelName = 'Filemanager_Model_DownloadLink';
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName, 
            'tableName' => 'filemanager_downloadlink',
        ));
    }
    
    /**
     * holds the instance of the singleton
     * @var Filemanager_Controller_DownloadLink
     */
    private static $_instance = NULL;
    
    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        $this->_sanitizeUserInput($_record);
    }
    
    /**
     * inspect creation of one record (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $this->_sanitizeUserInput($_record);
    }
    
    /**
     * sanitize user input
     * 
     * @param Tinebase_Record_Interface $record
     */
    protected function _sanitizeUserInput($record)
    {
        // access_count can only be increased when file is downloaded or directory listing is shown
        unset($record->access_count);
    }
    
    /**
     * check if user has the right to manage download links
     *
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkRight($_action)
    {
        switch ($_action) {
            case 'create':
            case 'update':
            case 'delete':
                $this->checkRight('MANAGE_DOWNLOADLINKS');
                break;
            default;
            break;
        }
    }
    
    /**
     * the singleton pattern
     * @return Filemanager_Controller_DownloadLink
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Filemanager_Controller_DownloadLink();
        }
        
        return self::$_instance;
    }
}
