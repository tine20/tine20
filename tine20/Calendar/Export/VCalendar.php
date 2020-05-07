<?php
/**
 * Tine 2.0
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * @package     Calendar
 *
 * @todo create generic "SabreDAV" export class in Tinebase?
 */
class Calendar_Export_VCalendar extends Tinebase_Export_Abstract
{
    /**
     * @var Calendar_Convert_Event_VCalendar_Tine
     */
    protected $_converter = null;

    /**
     * @var \Sabre\VObject\Component\VCalendar
     */
    protected $_vcalendar = null;

    protected $_defaultExportname = 'cal_default_vcalendar';

    protected $_format = 'ics';

    protected $_exportFileHandle = null;

    /**
     * get download content type
     *
     * @return string
     */
    public function getDownloadContentType()
    {
        return 'text/calendar';
    }

    /**
     * @return string|null
     */
    public function generate()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Generating VCALENDAR export ...');
        }

        $this->_converter = new Calendar_Convert_Event_VCalendar_Tine();

        $this->_converter->setOptions([
            Calendar_Convert_Event_VCalendar_Tine::OPTION_ADD_ATTACHMENTS_BINARY => true,
        ]);
        $this->_exportRecords();

        return $this->_writeToFile() ? $this->_config->filename : null;
    }

    protected function _writeToFile()
    {
        return (boolean) $this->_config->filename;
    }

    /**
     * set generic data
     *
     * @param array $result
     */
    protected function _onAfterExportRecords(/** @noinspection PhpUnusedParameterInspection */ array $result)
    {
        parent::_onAfterExportRecords($result);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Exported '
                . $result['totalcount'] . ' records.');
        }

        if ($this->_exportFileHandle !== null) {
            fclose($this->_exportFileHandle);
        }
    }

    /**
     * @param Tinebase_Record_Interface $_record
     */
    public function _processRecord(Tinebase_Record_Interface $_record)
    {
        Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($_record);

        if ($this->_writeToFile()) {
            $this->_addRecordToFile($_record);
        } else {
            $this->_addRecordToVCalendar($_record);
        }
    }

    protected function _createVCalendar(Calendar_Model_Event $_record)
    {
        return $this->_converter->createVCalendar($_record);
    }

    protected function _addRecordToVCalendar(Calendar_Model_Event $_record)
    {
        if ($this->_vcalendar === null) {
            $this->_vcalendar = $this->_createVCalendar($_record);
        }

        $this->_converter->addEventToVCalendar($this->_vcalendar, $_record);
    }

    protected function _addRecordToFile(Calendar_Model_Event $_record)
    {
        $vcalendar = $this->_createVCalendar($_record);
        $this->_converter->addEventToVCalendar($vcalendar, $_record);

        if ($this->_exportFileHandle === null) {
            $this->_exportFileHandle = fopen($this->_config->filename, 'w');
            if (! $this->_exportFileHandle) {
                throw new Tinebase_Exception('could not open export file: ' . $this->_config->filename);
            }
            fwrite($this->_exportFileHandle, $vcalendar->serialize());
        } else {
            // add only VEVENT & VALARMs here
            $vevents = $vcalendar->select('VEVENT');
            foreach ($vevents as $vevent) {
                $this->_addComponentToEndOfFile($vevent);
            }
        }
    }

    protected function _addComponentToEndOfFile(Sabre\VObject\Component $component)
    {
        // rewind to just before END:VCALENDAR
        fseek($this->_exportFileHandle, ftell($this->_exportFileHandle) - strlen("END:VCALENDAR") - 2);
        fwrite($this->_exportFileHandle, $component->serialize());
        fwrite($this->_exportFileHandle, "END:VCALENDAR\r\n");
    }

    /**
     * @param string filename
     *
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     *
     * @todo add function signature to Tinebase_Export_Abstract
     */
    public function write($filename = null)
    {
        if ($filename) {
            // TODO use fopen + fpassthru?
            echo file_get_contents($filename);
        } else if ($this->_vcalendar !== null) {
            echo $this->_vcalendar->serialize();
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' no records exported');
            }
        }
    }
}
