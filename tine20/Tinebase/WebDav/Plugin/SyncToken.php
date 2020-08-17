<?php
/**
 * WebDAV plugin for sync-token support
 * 
 * This plugin provides functionality to request sync-tokens
 * It is a backport of sabre/dav/sync/plugin.php
 *
 * see: https://tools.ietf.org/html/rfc6578
 * see: http://sabre.io/dav/building-a-caldav-client/#speeding-up-sync-with-webdav-sync
 *
 * NOTE: xxx
 *       xxx
 *       
 * @package    Tinebase
 * @subpackage WebDav
 * @copyright  Copyright (c) 2015-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author     Paul Mehrer <p.mehrer@metaways.de>
 * @license    http://sabre.io/license/ Modified BSD License
 */
class Tinebase_WebDav_Plugin_SyncToken extends \Sabre\DAV\ServerPlugin
{
    /**
     * Reference to server object
     *
     * @var \Sabre\DAV\Server
     */
    protected $server;

    const SYNCTOKEN_PREFIX = 'http://tine20.net/ns/sync/';

    /**
     * Returns a list of features for the DAV: HTTP header. 
     * 
     * @return array 
     */
    public function getFeatures() 
    {
        return array('sync-token');
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
        return 'calendarSyncToken';
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

        $self = $this;
        $server->subscribeEvent('report', function($reportName, $dom, $uri) use ($self, $server) {
            if ($reportName === '{DAV:}sync-collection') {
                $server->transactionType = 'report-sync-collection';
                $self->syncCollection($uri, $dom);
                return false;
            }
        });
    }

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
    function getSupportedReportSet($uri)
    {
        $node = $this->server->tree->getNodeForPath($uri);

        if ($node instanceof Tinebase_WebDav_Container_Abstract && $node->supportsSyncToken()) {
            return array(
                '{DAV:}sync-collection',
            );
        }
        return array();
    }

    /**
     * This method handles the {DAV:}sync-collection HTTP REPORT.
     *
     * @param string $uri
     * @param \DOMDocument $report
     * @return void
     */
    function syncCollection($uri, \DOMDocument $report)
    {
        // Getting the sync token of the data requested
        /**
         * @var $node Tinebase_WebDav_Container_Abstract
         */
        $node = $this->server->tree->getNodeForPath($uri);
        if (!($node instanceof Tinebase_WebDav_Container_Abstract) || !$node->supportsSyncToken()) {
            throw new Sabre\DAV\Exception\ReportNotSupported('The {DAV:}sync-collection REPORT is not supported on this url.');
        }

        // getting the sync token send with the request
        $syncToken = '';
        $syncTokenList = $report->getElementsByTagNameNS('urn:DAV', 'sync-token');
        if ($syncTokenList->length == 1) {
            $syncToken = $syncTokenList->item(0)->textContent; //?!? //nodeValue;
        }
        if (strlen($syncToken) > 0 ) {
            // Sync-token must start with our prefix
            if (substr($syncToken, 0, strlen(self::SYNCTOKEN_PREFIX)) !== self::SYNCTOKEN_PREFIX || strlen($syncToken) <= strlen(self::SYNCTOKEN_PREFIX)) {
                throw new Sabre\DAV\Exception\BadRequest('Invalid or unknown sync token');
            }
            $syncToken = substr($syncToken, strlen(self::SYNCTOKEN_PREFIX));
        } else {
            $syncToken = 0;
        }

        // get the list of properties the client requested
        $properties = array_keys(Sabre\DAV\XMLUtil::parseProperties($report->documentElement));

        // get changes since client sync token
        $changeInfo = $node->getChanges($syncToken);
        if (is_null($changeInfo)) {
            throw new Sabre\DAV\Exception\BadRequest('Invalid or unknown sync token');
        }

        // Encoding the response
        $this->sendSyncCollectionResponse(
            $changeInfo['syncToken'],
            $uri,
            $changeInfo[Tinebase_Model_ContainerContent::ACTION_CREATE],
            $changeInfo[Tinebase_Model_ContainerContent::ACTION_UPDATE],
            $changeInfo[Tinebase_Model_ContainerContent::ACTION_DELETE],
            $properties
        );
    }

    /**
     * Sends the response to a sync-collection request.
     *
     * @param string $syncToken
     * @param string $collectionUrl
     * @param array $added
     * @param array $modified
     * @param array $deleted
     * @param array $properties
     * @return void
     */
    protected function sendSyncCollectionResponse($syncToken, $collectionUrl, array $added, array $modified, array $deleted, array $properties)
    {
        $resolvedProperties = array();
        foreach (array_merge($added, $modified) as $item) {
            $fullPath = $collectionUrl . '/' . $item;
            try {
                $resolvedProperties[$fullPath] = $this->server->getPropertiesForPath($fullPath, $properties);

                // in case the user doesnt have access to this
            } catch (Sabre\DAV\Exception\NotFound $e) {
                unset($resolvedProperties[$fullPath]);
            }
        }
        foreach($deleted as $item) {
            $fullPath = $collectionUrl . '/' . $item;
            $resolvedProperties[$fullPath] = array();
        }

        $data = $this->generateMultiStatus($resolvedProperties, $syncToken);

        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->sendBody($data);
    }

    protected function generateMultiStatus($properties, $syncToken)
    {
        $dom = new \DOMDocument('1.0', 'utf-8');

        //$dom->formatOutput = true;
        $multiStatus = $dom->createElement('d:multistatus');

        // Adding in default namespaces
        foreach ($this->server->xmlNamespaces as $namespace => $prefix) {
            $multiStatus->setAttribute('xmlns:' . $prefix, $namespace);
        }

        foreach ($properties as $href => $entries) {
            if (count($entries) === 0) { //404
                $response = $dom->createElement('d:response');
                $href = $dom->createElement('d:href', $href);
                $response->appendChild($href);
                $status = $dom->createElement('d:status', $this->server->httpResponse->getStatusMessage(404));
                $response->appendChild($status);
                $multiStatus->appendChild($response);
            } else {
                foreach($entries as $entry) {
                    $ehref = $entry['href'];
                    unset($entry['href']);

                    $response = new Sabre\DAV\Property\Response($ehref, $entry);
                    $response->serialize($this->server, $multiStatus);
                }
            }
        }

        $multiStatus->appendChild($dom->createElement('d:sync-token', self::SYNCTOKEN_PREFIX . $syncToken));
        $dom->appendChild($multiStatus);

        return $dom->saveXML();
    }
}
