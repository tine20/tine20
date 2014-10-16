<?php
/**
 * Generic decorator for caldav
 *
 * Uses Calendar_Convert_Event_VCalendar_Tine for import.
 */
class Calendar_Import_CalDav_Decorator_Generic extends Calendar_Import_CalDav_Decorator_Abstract
{
    public function initCalendarImport()
    {
        $tbjf = new Tinebase_Frontend_Json();
        $registry = $tbjf->getRegistryData();

        $_SERVER['HTTP_USER_AGENT'] = 'Tine20/' . $registry['version']['packageString'];
    }
}