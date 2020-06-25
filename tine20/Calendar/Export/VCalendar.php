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
 */
class Calendar_Export_VCalendar extends Tinebase_Export_VObject
{
    /**
     * @var Calendar_Convert_Event_VCalendar_Tine
     */
    protected $_converter = null;
    protected $_defaultExportname = 'cal_default_vcalendar';
    protected $_format = 'ics';

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
            Calendar_Convert_Event_VCalendar_Tine::OPTION_ADD_ATTACHMENTS_MAX_SIZE => 1024 * 1024, // 1 MB
        ]);
        $this->_exportRecords();

        return $this->_returnExportFilename();
    }

    protected function _addRecordToFile(Tinebase_Record_Interface $_record)
    {
        $this->_checkMaxFileSize();

        $document = $this->_createDocument($_record, true);
        $this->_converter->addEventToVCalendar($document, $_record);

        if ($this->_exportFileHandle === null) {
            $this->_createExportFilehandle();
            fwrite($this->_exportFileHandle, $document->serialize());
        } else if ($document instanceof \Sabre\VObject\Component\VCalendar) {
            $objects = $document->select('VEVENT');
            foreach ($objects as $object) {
                $this->_addComponentToEndOfFile($object);
            }
        }
    }

    protected function _createDocument(Tinebase_Record_Interface $_record)
    {
        return $this->_converter->createVCalendar($_record);
    }

    protected function _addRecordToDocument(Tinebase_Record_Interface $_record)
    {
        $this->_converter->addEventToVCalendar($this->_document, $_record);
    }

    protected function _addComponentToEndOfFile(Sabre\VObject\Component $component)
    {
        // rewind to just before END:VCALENDAR
        fseek($this->_exportFileHandle, ftell($this->_exportFileHandle) - strlen("END:VCALENDAR") - 2);
        fwrite($this->_exportFileHandle, $component->serialize());
        fwrite($this->_exportFileHandle, "END:VCALENDAR\r\n");
    }
}
