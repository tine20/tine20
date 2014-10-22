<?php
//./tine20.php --username unittest --method Tasks.importCalDav url="https://ical.metaways.de:8443" caldavuserfile=caldavuserfile.csv

/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Tasks_Import_CalDAV
 * 
 * @package     Tasks
 * @subpackage  Import
 * 
 * @todo        move generic parts from Calendar_Import_CalDav_Client to Tinebase and extend Tinebase_Import_CalDav_Client
 */
class Tasks_Import_CalDav_Client extends Calendar_Import_CalDav_Client
{
    protected $component = 'VTODO';
    protected $skipComonent = 'VEVENT';
    protected $modelName = 'Tasks_Model_Task';
    protected $appName = 'Tasks';
    protected $webdavFrontend = 'Tasks_Frontend_WebDAV_Task';
    protected $_uuidPrefix = 'aa-';
    
    protected function _getAllGrants()
    {
        return array(
            Tinebase_Model_Grants::GRANT_READ => true,
            Tinebase_Model_Grants::GRANT_ADD=> true,
            Tinebase_Model_Grants::GRANT_EDIT=> true,
            Tinebase_Model_Grants::GRANT_DELETE=> true,
            Tinebase_Model_Grants::GRANT_EXPORT=> true,
            Tinebase_Model_Grants::GRANT_SYNC=> true,
            Tinebase_Model_Grants::GRANT_ADMIN=> true,
        );
    }
    
    protected function _getFreeBusyGrants()
    {
        return array(
        );
    }
    
    protected function _getReadGrants()
    {
        return array(
            Tinebase_Model_Grants::GRANT_READ=> true,
            Tinebase_Model_Grants::GRANT_EXPORT=> true,
            Tinebase_Model_Grants::GRANT_SYNC=> true,
        );
    }
}
