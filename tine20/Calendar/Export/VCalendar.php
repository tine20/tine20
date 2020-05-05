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

    /**
     * get download content type
     *
     * @return string
     */
    public function getDownloadContentType()
    {
        return 'text/calendar';
    }

    public function generate()
    {
        $this->_converter = new Calendar_Convert_Event_VCalendar_Tine();
        $this->_converter->setOptions([
            Calendar_Convert_Event_VCalendar_Tine::OPTION_ADD_ATTACHMENTS_BINARY => true,
        ]);
        $this->_exportRecords();
        return $this->_vcalendar !== null;
    }

    /**
     * @param Tinebase_Record_Interface $_record
     */
    public function _processRecord(Tinebase_Record_Interface $_record)
    {
        if ($this->_vcalendar === null) {
            $this->_vcalendar = $this->_createVCalendar($_record);
        }

        Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($_record);
        $this->_converter->addEventToVCalendar($this->_vcalendar, $_record);
    }

    protected function _createVCalendar(Calendar_Model_Event $_record)
    {
        return $this->_converter->createVCalendar($_record);
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     */
    public function write()
    {
        if ($this->_vcalendar === null) {
            throw new Tinebase_Exception_NotFound('empty export');
        }

        $vcalSerialized = $this->_vcalendar->serialize();
        if ($this->_config->filename) {
            if (file_exists($this->_config->filename)) {
                throw new Tinebase_Exception_AccessDenied('Could not overwrite existing file');
            }
            file_put_contents($this->_config->filename, $vcalSerialized);
        } else {
            echo $vcalSerialized;
        }
    }
}
