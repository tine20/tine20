<?php

class Calendar_Import_CalDav_Decorator_MacOSX extends Calendar_Import_CalDav_Decorator_Abstract
{
    public function preparefindAllCalendarsRequest($request)
    {
        $doc = new DOMDocument();
        $doc->loadXML($request);
        //$bulk = $doc->createElementNS('http://me.com/_namespace/', 'osxme:bulk-requests');
        $color = $doc->createElementNS('http://apple.com/ns/ical/', 'osxical:calendar-color');
        $prop = $doc->getElementsByTagNameNS('DAV:', 'prop')->item(0);
        //$prop->appendChild($bulk);
        $prop->appendChild($color);
        return $doc->saveXML();
    }
    
    public function processAdditionalCalendarProperties(array &$calendar, array $response)
    {
        /*if (isset($response['{http://apple.com/ns/ical/}calendar-color']))
            $calendar['color'] = $response['{http://apple.com/ns/ical/}calendar-color'];*/
    }
    
    public function initCalendarImport()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mac_OS_X/10.9 (13A603) CalendarAgent/174';
    }
}