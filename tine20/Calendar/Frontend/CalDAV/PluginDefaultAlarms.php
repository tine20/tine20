<?php
/**
 * CalDAV plugin for draft-daboo-valarm-extensions-04
 * 
 * This plugin provides functionality added by RFC6638
 * It takes care of additional properties and features
 * 
 * see: http://tools.ietf.org/html/draft-daboo-valarm-extensions-04
 *
 * NOTE: At the moment we disable all default alarms as iCal shows alarms
 *       for events having no alarm. Acknowliging this alarms may lead to problems.
 *       
 * NOTE: iCal Montain Lion & Mavericks sets default alarms for the whole account, 
 *       but respects when we set default alarms per calendar. 
 *       
 *       So in future we might disable default alarms for shared cals and
 *       use the default alarms configured for each personal cal.
 *       
 * @package    Sabre
 * @subpackage CalDAV
 * @copyright  Copyright (c) 2014-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author     Lars Kneschke <l.kneschke@metaways.de>
 * @license    http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Calendar_Frontend_CalDAV_PluginDefaultAlarms extends \Sabre\DAV\ServerPlugin 
{
    /**
     * Reference to server object
     *
     * @var \Sabre\DAV\Server
     */
    protected $server;

    /**
     * Returns a list of features for the DAV: HTTP header. 
     * 
     * @return array 
     */
    public function getFeatures() 
    {
        return array('calendar-default-alarms');
    }

    /**
     * Returns a plugin name.
     * 
     * Using this name other plugins will be able to access other plugins
     * using \Sabre\DAV\Server::getPlugin 
     * 
     * @return string 
     */
    public function getPluginName() 
    {
        return 'calendarDefaultAlarms';
    }

    /**
     * Initializes the plugin 
     * 
     * @param \Sabre\DAV\Server $server 
     * @return void
     */
    public function initialize(\Sabre\DAV\Server $server) 
    {
        $this->server = $server;

        $server->subscribeEvent('beforeGetProperties', array($this, 'beforeGetProperties'));

        $server->xmlNamespaces[\Sabre\CalDAV\Plugin::NS_CALDAV] = 'cal';
    }
    
    /**
     * beforeGetProperties
     *
     * This method handler is invoked before any after properties for a
     * resource are fetched. This allows us to add in any CalDAV specific
     * properties.
     *
     * @param string $path
     * @param \Sabre\DAV\INode $node
     * @param array $requestedProperties
     * @param array $returnedProperties
     * @return void
     */
    public function beforeGetProperties($path, \Sabre\DAV\INode $node, &$requestedProperties, &$returnedProperties) 
    {
        if ($node instanceof \Sabre\CalDAV\ICalendar || $node instanceof Calendar_Frontend_CalDAV) {
            $vcalendar = new \Sabre\VObject\Component\VCalendar();
            
            $property = '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}default-alarm-vevent-datetime';
            if (in_array($property, $requestedProperties)) {
                unset($requestedProperties[array_search($property, $requestedProperties)]);
                
                $valarm = $vcalendar->create('VALARM');
                $valarm->add('ACTION',  'NONE');
                $valarm->add('TRIGGER', '19760401T005545Z', array('VALUE' => 'DATE-TIME'));
                $valarm->add('UID',     'E35C3EB2-4DC1-4223-AA5D-B4B491F2C111');
                
                // Taking out \r to not screw up the xml output
                $returnedProperties[200][$property] = str_replace("\r","", $valarm->serialize()); 
            }
            
            $property = '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}default-alarm-vevent-date';
            if (in_array($property, $requestedProperties)) {
                unset($requestedProperties[array_search($property, $requestedProperties)]);
                
                $valarm = $vcalendar->create('VALARM');
//                 $valarm->add('ACTION',  'AUDIO');
//                 $valarm->add('ATTACH',  'Basso', array('VALUE' => 'URI'));
//                 $valarm->add('TRIGGER', '-PT15H');
                $valarm->add('ACTION',  'NONE');
                $valarm->add('TRIGGER', '19760401T005545Z', array('VALUE' => 'DATE-TIME'));
                $valarm->add('UID',     '17DC9682-230E-47D6-A035-EEAB602B1229');
                
                // Taking out \r to not screw up the xml output
                $returnedProperties[200][$property] = str_replace("\r","", $valarm->serialize()); 
            }
            
            $property = '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}default-alarm-vtodo-datetime';
            if (in_array($property, $requestedProperties)) {
                unset($requestedProperties[array_search($property, $requestedProperties)]);
                
                $valarm = $vcalendar->create('VALARM');
                $valarm->add('ACTION',  'NONE');
                $valarm->add('TRIGGER', '19760401T005545Z', array('VALUE' => 'DATE-TIME'));
                $valarm->add('UID',     'D35C3EB2-4DC1-4223-AA5D-B4B491F2C111');
                
                // Taking out \r to not screw up the xml output
                $returnedProperties[200][$property] = str_replace("\r","", $valarm->serialize()); 
            }
            
            $property = '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}default-alarm-vtodo-date';
            if (in_array($property, $requestedProperties)) {
                unset($requestedProperties[array_search($property, $requestedProperties)]);
                
                $valarm = $vcalendar->create('VALARM');
//                 $valarm->add('ACTION',  'AUDIO');
//                 $valarm->add('ATTACH',  'Basso', array('VALUE' => 'URI'));
//                 $valarm->add('TRIGGER', '-PT15H');
                $valarm->add('ACTION',  'NONE');
                $valarm->add('TRIGGER', '19760401T005545Z', array('VALUE' => 'DATE-TIME'));
                $valarm->add('UID',     '27DC9682-230E-47D6-A035-EEAB602B1229');
                
                // Taking out \r to not screw up the xml output
                $returnedProperties[200][$property] = str_replace("\r","", $valarm->serialize()); 
            }
        }
    }
}
