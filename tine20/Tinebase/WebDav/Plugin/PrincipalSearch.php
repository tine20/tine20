<?php
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
 * @copyright  Copyright (c) 2014-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author     Lars Kneschke <l.kneschke@metaways.de>
 * @license    http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Tinebase_WebDav_Plugin_PrincipalSearch extends \Sabre\DAV\ServerPlugin {

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
        return array('calendarserver-principal-search');
    }

    /**
     * (non-PHPdoc)
     * @see \Sabre\DAV\ServerPlugin::getPluginName()
     */
    public function getPluginName() 
    {
        return 'calendarserverPrincipalSearch';
    }
    
    /**
     * (non-PHPdoc)
     * @see \Sabre\DAV\ServerPlugin::getSupportedReportSet()
     */
    public function getSupportedReportSet($uri) 
    {
        return array(
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}calendarserver-principal-search'
        );

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

        $server->xmlNamespaces[\Sabre\CalDAV\Plugin::NS_CALDAV] = 'cal';
        $server->xmlNamespaces[\Sabre\CalDAV\Plugin::NS_CALENDARSERVER] = 'cs';

        #$server->subscribeEvent('beforeGetProperties',array($this,'beforeGetProperties'));
        $server->subscribeEvent('report',array($this,'report'));
        
        array_push($server->protectedProperties,
            // CalendarServer extensions
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}record-type',
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}first-name',
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}last-name'
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
    #public function beforeGetProperties($path, \Sabre\DAV\INode $node, &$requestedProperties, &$returnedProperties) 
    #{
    #    if ($node instanceof \Sabre\DAVACL\IPrincipal) {var_dump($path);
    #        // schedule-outbox-URL property
    #        #'{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-user-type'        => 'GROUP',
    #        $property = '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-user-type';
    #        if (in_array($property,$requestedProperties)) {
    #            list($prefix, $nodeId) = Sabre\DAV\URLUtil::splitPath($path);
    #            
    #            unset($requestedProperties[array_search($property, $requestedProperties)]);
    #            $returnedProperties[200][$property] = ($prefix == Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS) ? 'GROUP' : 'INDIVIDUAL';

    #        }
    #    }
    #}
    
    /**
     * This method handles HTTP REPORT requests
     *
     * @param string $reportName
     * @param \DOMNode $dom
     * @return bool
     */
    public function report($reportName, $dom) 
    {
        switch($reportName) {
            case '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}calendarserver-principal-search':
                $this->_principalSearchReport($dom);
                return false;
        }
    }
    
    protected function _principalSearchReport(\DOMDocument $dom) 
    {
        $requestedProperties = array_keys(\Sabre\DAV\XMLUtil::parseProperties($dom->firstChild));
        
        $searchTokens = $dom->firstChild->getElementsByTagName('search-token');

        $searchProperties = array();
        
        if ($searchTokens->length > 0) {
            $searchProperties['{http://calendarserver.org/ns/}search-token'] = $searchTokens->item(0)->nodeValue;
        }
        
        $result = $this->server->getPlugin('acl')->principalSearch($searchProperties, $requestedProperties);

        $prefer = $this->server->getHTTPPRefer();

        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->setHeader('Vary','Brief,Prefer');
        $this->server->httpResponse->sendBody($this->server->generateMultiStatus($result, $prefer['return-minimal']));
    }
}
