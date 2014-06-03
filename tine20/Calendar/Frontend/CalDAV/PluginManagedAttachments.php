<?php
/**
 * CalDAV plugin for draft-daboo-caldav-attachments-03
 * 
 * see: http://tools.ietf.org/html/draft-daboo-caldav-attachments-03
 * 
 * NOTE: At the moment Apple's iCal clients seem to support only a small subset of the spec:
 * - deleting is done by PUT and not via managed-remove
 * - client does not update files
 * - client can not cope with recurring exceptions. It always acts on the whole serices and all exceptions
 * 
 * @TODO
 * evaluate "return=representation" header
 * add attachments via PUT with managed ID
 
 *
 * @package    Sabre
 * @subpackage CalDAV
 * @copyright  Copyright (c) 2014-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author     Cornelius Weiss <c.weiss@metaways.de>
 * @license    http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Calendar_Frontend_CalDAV_PluginManagedAttachments extends \Sabre\DAV\ServerPlugin 
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
        return array('calendar-managed-attachments');
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
        return 'calendarManagedAttachments';
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

        $this->server->subscribeEvent('unknownMethod',array($this,'httpPOSTHandler'));
        
        $server->subscribeEvent('beforeGetProperties', array($this, 'beforeGetProperties'));
        
        $server->xmlNamespaces[\Sabre\CalDAV\Plugin::NS_CALENDARSERVER] = 'cs';
        
        $server->resourceTypeMapping['\\Sabre\\CalDAV\\ICalendar'] = '{urn:ietf:params:xml:ns:caldav}calendar';
        
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
    public function beforeGetProperties($path, \Sabre\DAV\INode $node, &$requestedProperties, &$returnedProperties) {
        if ($node instanceof \Sabre\DAVACL\IPrincipal) {
            // dropbox-home-URL property
            $scheduleProp = '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}dropbox-home-URL';
            if (in_array($scheduleProp,$requestedProperties)) {
                $principalId = $node->getName();
                $dropboxPath = \Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . $principalId . '/dropbox';
                
                unset($requestedProperties[array_search($scheduleProp, $requestedProperties)]);
                $returnedProperties[200][$scheduleProp] = new \Sabre\DAV\Property\Href($dropboxPath);
            }
        }
    }
    
    /**
     * Handles POST requests
     *
     * @param string $method
     * @param string $uri
     * @return bool
     */
    public function httpPOSTHandler($method, $uri) 
    {
        if ($method != 'POST') {
            return;
        }
        
        $getVars = array();
        parse_str($this->server->httpRequest->getQueryString(), $getVars);
        
        if (!isset($getVars['action']) || !in_array($getVars['action'], 
                array('attachment-add', 'attachment-update', 'attachment-remove'))) {
            return;
        }
        
        try {
            $node = $this->server->tree->getNodeForPath($uri);
        } catch (DAV\Exception\NotFound $e) {
            // We're simply stopping when the file isn't found to not interfere
            // with other plugins.
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__ .
                " did not find node -> stopping");
            }
            return;
        }
        
        if (!$node instanceof Calendar_Frontend_WebDAV_Event) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__ . 
                    " node is no event -> stopping ");
            }
            return;
        }
        
        $name = 'NO NAME';
        $disposition = $this->server->httpRequest->getHeader('Content-Disposition');
        $contentType = $this->server->httpRequest->getHeader('Content-Type');
        $managedId = isset($getVars['managed-id']) ? $getVars['managed-id'] : NULL;
        $rid = $this->getRecurranceIds($getVars);
        list($contentType) = explode(';', $contentType);
        if (preg_match('/filename=(.*)[ ;]{0,1}/', $disposition, $matches)) {
            $name = trim($matches[1], " \t\n\r\0\x0B\"'");
        }
        
        // NOTE inputstream can not be rewinded
        $inputStream = fopen('php://temp','r+');
        stream_copy_to_stream($this->server->httpRequest->getBody(), $inputStream);
        rewind($inputStream);
        
        list ($attachmentId) = Tinebase_FileSystem::getInstance()->createFileBlob($inputStream);
        
        switch ($getVars['action']) {
            case 'attachment-add':
                
                $attachment = new Tinebase_Model_Tree_Node(array(
                    'name'         => $name,
                    'type'         => Tinebase_Model_Tree_Node::TYPE_FILE,
                    'contenttype'  => $contentType,
                    'hash'         => $attachmentId,
                ), true);
                
                $this->_iterateByRid($node->getRecord(), $rid, function($event) use ($name, $attachment) {
                    $existingAttachment = $event->attachments->filter('name', $name)->getFirstRecord();
                    if ($existingAttachment) {
                        // yes, ... iCal does this :-(
                        $existingAttachment->hash = $attachment->hash;
                    }
                    
                    else {
                        $event->attachments->addRecord(clone $attachment);
                    }
                });
                
                $node->update($node->getRecord());
                
                break;
                
            case 'attachment-update':
                $eventsToUpdate = array();
                // NOTE: iterate base & all exceptions @see 3.5.2c of spec
                $this->_iterateByRid($node->getRecord(), NULL, function($event) use ($managedId, $attachmentId, &$eventsToUpdate) {
                    $attachmentToUpdate = $event->attachments->filter('hash', $managedId)->getFirstRecord();
                    if ($attachmentToUpdate) {
                        $eventsToUpdate[] = $event;
                        $attachmentToUpdate->hash = $attachmentId;
                    }
                });
                
                if (! $eventsToUpdate) {
                    throw new Sabre\DAV\Exception\PreconditionFailed("no attachment with id $managedId found");
                }
                
                $node->update($node->getRecord());
                break;
                
            case 'attachment-remove':
                $eventsToUpdate = array();
                $this->_iterateByRid($node->getRecord(), $rid, function($event) use ($managedId, &$eventsToUpdate) {
                    $attachmentToDelete = $event->attachments->filter('hash', $managedId)->getFirstRecord();
                    if ($attachmentToDelete) {
                        $eventsToUpdate[] = $event;
                        $event->attachments->removeRecord($attachmentToDelete);
                    }
                });
                
                if (! $eventsToUpdate) {
                    throw new Sabre\DAV\Exception\PreconditionFailed("no attachment with id $managedId found");
                }
                    
                $node->update($node->getRecord());
                break;
        }
        
//         @TODO respect Prefer header
        $this->server->httpResponse->setHeader('Content-Type', 'text/calendar; charset="utf-8"');
        $this->server->httpResponse->setHeader('Content-Length', $node->getSize());
        $this->server->httpResponse->setHeader('ETag',           $node->getETag());
        if ($getVars['action'] != 'attachment-remove') {
            $this->server->httpResponse->setHeader('Cal-Managed-ID', $attachmentId);
        }
        
        // only at create!
        $this->server->httpResponse->sendStatus(201);
        $this->server->httpResponse->sendBody($node->get());
        
        return false;

    }
    
    /**
     * calls method with each event matching given rid
     * 
     * breaks if method returns false
     * 
     * @param  Calendar_Model_Event $event
     * @param  array $rid
     * @param  Function $method
     * @return Tinebase_Record_RecordSet affectedEvents
     */
    protected function _iterateByRid($event, $rid, $method)
    {
        $affectedEvents = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        if (! $rid || in_array('M', $rid)) {
            $affectedEvents->addRecord($event);
        }
        
        if ($event->exdate instanceof Tinebase_Record_RecordSet) {
            foreach($event->exdate as $exception) {
                if (! $rid /*|| $exception->recurid ...*/) {
                    $affectedEvents->addRecord($exception);
                }
            }
        }
        foreach($affectedEvents as $record) {
            if ($method($record) === false) break;
        }
        
        return $affectedEvents;
    }
    
    /**
     * returns recurrance ids
     * 
     * NOTE: 
     *  no rid means base & all exceptions
     *  M means base 
     *  specific dates point to the corresponding exceptions of course
     *  
     * @return array
     */
    public function getRecurranceIds($getVars)
    {
        $recurids = array();
        
        if (isset($getVars['rid'])) {
            foreach ( explode(',', $getVars['rid']) as $recurid) {
                if ($recurid) {
                    $recurids[] = strtoupper($recurid);
                }
            }
        }
        
        return $recurids;
    }
    
}
