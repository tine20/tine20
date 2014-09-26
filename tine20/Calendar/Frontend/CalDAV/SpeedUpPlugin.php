<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2014-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Sabre speedup plugin
 *
 * This plugin prefetches data in ONE request, to speedup multiget report
 *
 * @package     Calendar
 * @subpackage  Frontend
 */

class Calendar_Frontend_CalDAV_SpeedUpPlugin extends Sabre\DAV\ServerPlugin 
{
    /**
     * Reference to server object 
     * 
     * @var Sabre\DAV\Server 
     */
    private $server;
    
    /**
     * Returns a list of reports this plugin supports.
     *
     * This will be used in the {DAV:}supported-report-set property.
     * Note that you still need to subscribe to the 'report' event to actually
     * implement them
     *
     * @param string $uri
     * @return array
     */
    public function getSupportedReportSet($uri) 
    {
        $node = $this->server->tree->getNodeForPath($uri);

        $reports = array();
        if ($node instanceof ICalendar || $node instanceof ICalendarObject) {
            $reports[] = '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-multiget';
        }
        
        return $reports;
    }

    
    /**
     * Initializes the plugin 
     * 
     * @param Sabre\DAV\Server $server 
     * @return void
     */
    public function initialize(Sabre\DAV\Server $server) 
    {
        $this->server = $server;
        
        $server->subscribeEvent('report', array($this,'report'));
    }
    
    /**
     * This functions handles REPORT requests specific to CalDAV
     *
     * @param string $reportName
     * @param \DOMNode $dom
     * @return bool
     */
    public function report($reportName,$dom) 
    {
        switch($reportName) {
            case '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-multiget' :
                $this->calendarMultiGetReport($dom);
        }
    }
    
    /**
     * This function handles the calendar-multiget REPORT.
     *
     * prefetch events into calendar container class, to avoid single lookup of events
     *
     * @param \DOMNode $dom
     * @return void
     */
    public function calendarMultiGetReport($dom) 
    {
        $properties = array_keys(\Sabre\DAV\XMLUtil::parseProperties($dom->firstChild));
        $hrefElems = $dom->getElementsByTagNameNS('urn:DAV','href');

        $filters = array(
            'name'         => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name'         => 'VEVENT',
                    'prop-filters' => array()
                )
            )
        );
        
        foreach($hrefElems as $elem) {
            list($dirName, $baseName) = \Sabre\DAV\URLUtil::splitPath($elem->nodeValue);
            
            $filters['comp-filters'][0]['prop-filters'][] = array(
                'name' => 'UID',
                'text-match' => array(
                    'value' => $baseName
                )
            );
        };
        
        $node = $this->server->tree->getNodeForPath($this->server->getRequestUri());
        $node->calendarQuery($filters);
    }
}
