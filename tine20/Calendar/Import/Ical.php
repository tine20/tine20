<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        use more functionality of Tinebase_Import_Abstract (import() and other fns)
 */

/**
 * Calendar_Import_Ical
 * 
 * @package     Calendar
 * @subpackage  Import
 */
class Calendar_Import_Ical extends Calendar_Import_Abstract
{
    /**
     * @var Calendar_Controller_MSEventFacade
     */
    protected $_cc = null;

    /**
     * get import events
     *
     * @param mixed $_resource
     * @return Tinebase_Record_RecordSet
     * @throws Calendar_Exception_IcalParser
     */
    protected function _getImportEvents($_resource, $container)
    {
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        if (isset($this->_options['onlyBasicData'])) {
            $converter->setOptions(array('onlyBasicData' => $this->_options['onlyBasicData']));
        }

        try {
            $events = $converter->toTine20RecordSet($_resource);
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(
                __METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
            $isce = new Calendar_Exception_IcalParser('Can not parse ics file: ' . $e->getMessage());
            $isce->setParseError($e);
            throw $isce;
        }

        $this->_getCalendarController()->assertEventFacadeParams($container);

        return $events;
    }

    protected function _getCalendarController()
    {
        if ($this->_cc === null) {
            $this->_cc = Calendar_Controller_MSEventFacade::getInstance();
        }

        return $this->_cc;
    }
}
