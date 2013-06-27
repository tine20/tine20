<?php

use Sabre\DAV;
use Sabre\DAVACL;

/**
 * CalDAV plugin for calendar-auto-schedule
 * 
 * This plugin provides functionality added by RFC6638
 * It takes care of additional properties and features
 * 
 * see: http://tools.ietf.org/html/rfc6638
 *
 * @package    Sabre
 * @subpackage CalDAV
 * @copyright  Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author     Lars Kneschke <l.kneschke@metaways.de>
 * @license    http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Calendar_Frontend_CalDAV_PluginAutoSchedule extends DAV\ServerPlugin {

    /**
     * Reference to server object
     *
     * @var Sabre\DAV\Server
     */
    protected $server;

    /**
     * Returns a list of features for the DAV: HTTP header. 
     * 
     * @return array 
     */
    public function getFeatures() {

        return array('calendar-auto-schedule');

    }

    /**
     * Returns a plugin name.
     * 
     * Using this name other plugins will be able to access other plugins
     * using Sabre\DAV\Server::getPlugin 
     * 
     * @return string 
     */
    public function getPluginName() {

        return 'caldavAutoSchedule';

    }

    /**
     * Initializes the plugin 
     * 
     * @param Sabre\DAV\Server $server 
     * @return void
     */
    public function initialize(DAV\Server $server) {

        $this->server = $server;

        $server->subscribeEvent('beforeGetProperties', array($this, 'beforeGetProperties'));

        $server->xmlNamespaces[Sabre\CalDAV\Plugin::NS_CALDAV] = 'cal';

        $server->resourceTypeMapping['\\Sabre\\CalDAV\\ICalendar'] = '{urn:ietf:params:xml:ns:caldav}calendar';

        // auto-scheduling extension
        array_push($server->protectedProperties,
            '{' . Sabre\CalDAV\Plugin::NS_CALDAV . '}schedule-calendar-transp',
            '{' . Sabre\CalDAV\Plugin::NS_CALDAV . '}schedule-default-calendar-URL',
            '{' . Sabre\CalDAV\Plugin::NS_CALDAV . '}schedule-tag'
        );
    }
    
    /**
     * beforeGetProperties
     *
     * This method handler is invoked before any after properties for a
     * resource are fetched. This allows us to add in any CalDAV specific
     * properties.
     *
     * @param string $path
     * @param DAV\INode $node
     * @param array $requestedProperties
     * @param array $returnedProperties
     * @return void
     */
    public function beforeGetProperties($path, DAV\INode $node, &$requestedProperties, &$returnedProperties) {

        if ($node instanceof DAVACL\IPrincipal) {
            // schedule-inbox-URL property
            $scheduleProp = '{' . Sabre\CalDAV\Plugin::NS_CALDAV . '}schedule-inbox-URL';
            if (in_array($scheduleProp,$requestedProperties)) {
                $principalId = $node->getName();
                $outboxPath = Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . $principalId . '/inbox';

                unset($requestedProperties[array_search($scheduleProp, $requestedProperties)]);
                $returnedProperties[200][$scheduleProp] = new DAV\Property\Href($outboxPath);

            }
        }
    }
}
