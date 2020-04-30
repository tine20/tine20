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

        $this->_converter->addEventToVCalendar($this->_vcalendar, $_record);
    }

    protected function _createVCalendar(Calendar_Model_Event $_record)
    {
        return $this->_converter->createVCalendar($_record);
    }

    public function write()
    {
        if ($this->_config->filename) {
            // TODO implement
        } else {
            echo $this->_vcalendar->serialize();
        }
    }
}
