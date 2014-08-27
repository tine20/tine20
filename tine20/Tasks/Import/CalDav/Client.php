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
 * @todo        finish update
 */
class Tasks_Import_CalDav_Client extends Calendar_Import_CalDav_Client
{
    protected $component = 'VTODO';
    protected $skipComonent = 'VEVENT';
    protected $modelName = 'Tasks_Model_Task';
    protected $appName = 'Tasks';
    protected $webdavFrontend = 'Tasks_Frontend_WebDAV_Task';
    protected $_uuidPrefix = 'aa-';
    
    /**
     * 
     * @param string $onlyCurrentUserOrganizer
     * @return boolean
     * 
     * @todo generalize
     * @todo implement for tasks
     */
    public function updateAllCalendarData($onlyCurrentUserOrganizer = false)
    {
//         if (count($this->calendarICSs) < 1 && ! $this->findAllCalendarICSs()) {
//             if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
//                 Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' no calendars found for: ' . $this->userName);
//             return false;
//         }
        
//         $newICSs = array();
//         $calendarEventBackend = Tasks_Controller_Task::getInstance()->getBackend();
        
//         foreach ($this->calendarICSs as $calUri => $calICSs) {
//             $start = 0;
//             $max = count($calICSs);
//             $etags = array();
//             do {
//                 $requestEnd = '';
//                 for ($i = $start; $i < $max && $i < ($this->maxBulkRequest+$start); ++$i) {
//                     $requestEnd .= '  <a:href>' . $calICSs[$i] . "</a:href>\n";
//                 }
//                 $start = $i;
//                 $requestEnd .= '</b:calendar-multiget>';
//                 $result = $this->calDavRequest('REPORT', $calUri, self::getEventETagsRequest . $requestEnd, 1);
                
//                 foreach ($result as $key => $value) {
//                     if (isset($value['{DAV:}getetag'])) {
//                         $name = explode('/', $key);
//                         $name = end($name);
//                         $id = $this->_getEventIdFromName($name);
//                         $etags[$key] = array( 'id' => $id, 'etag' => $value['{DAV:}getetag']);
//                     }
//                 }
//             } while($start < $max);
            
//             //check etags
//             if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
//                 . ' Got ' . count($etags) . ' etags');
//             if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
//                 . ' etags: ' . print_r($etags, true));
            
//             // @todo find out deleted events
//             foreach ($etags as $ics => $data) {
//                 try {
//                     $etagCheck = $calendarEventBackend->checkETag($data['id'], $data['etag']);
//                     if ($etagCheck) {
//                         continue; // same
//                     } else {
//                         $eventExists = true; // different
//                     }
//                 } catch (Tinebase_Exception_NotFound $tenf) {
//                     $eventExists = false;
//                 }
                
//                 if (!isset($newICSs[$calUri])) {
//                     $newICSs[$calUri] = array();
//                     $this->existingRecordIds[$calUri] = array();
//                 }
//                 $newICSs[$calUri][] = $ics;
//                 if ($eventExists) {
//                     $this->existingRecordIds[$calUri][] = $data['id'];
//                 }
//             }
//         }
        
//         if (($count = count($newICSs)) > 0) {
//             if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ' 
//                     . $count . ' calendar(s) changed for: ' . $this->userName);
//             if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ' 
//                     . ' events changed: ' . print_r($newICSs, true));
//             $this->calendarICSs = $newICSs;
//             $this->importAllCalendarData($onlyCurrentUserOrganizer, /* $update = */ true);
//         } else {
//             if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
//                 Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' no changes found for: ' . $this->userName);
//         }
    }
    
    /**
     * (non-PHPdoc)
     * @see Calendar_Import_CalDav_Client::_setEtags()
     * 
     * @todo implement
     */
    protected function _setEtags($etags)
    {
//         $calendarEventBackend = Tinebase_Core::getApplicationInstance($this->appName, $this->modelName)->getBackend();
//         $calendarEventBackend->setETags($etags);
    }

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
