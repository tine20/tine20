<?php

abstract class Calendar_Import_CalDav_Decorator_Abstract
{
    protected $client;
    
    public function __construct($client)
    {
        $this->client = $client;
    }
    
    public function preparefindAllCalendarsRequest($request)
    {
        return $request;
    }
    
    public function processAdditionalCalendarProperties(array &$calendar, array $response) {}
    
    public function initCalendarImport() {}
    
    public function setCalendarProperties(Tinebase_Model_Container $calendarContainer, array $calendar)
    {
        if (isset($calendar['color']))
            $calendarContainer->color = $calendar['color'];
    }
}