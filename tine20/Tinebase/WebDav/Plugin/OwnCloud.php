<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2013-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * ownCloud Integrator plugin
 *
 * This plugin provides functionality reuqired by ownCloud sync clients
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 */

class Tinebase_WebDav_Plugin_OwnCloud extends Sabre\DAV\ServerPlugin {

    const NS_OWNCLOUD = 'http://owncloud.org/ns';
 
    /**
     * Reference to server object 
     * 
     * @var Sabre\DAV\Server 
     */
    private $server;

    /**
     * Initializes the plugin 
     * 
     * @param Sabre\DAV\Server $server 
     * @return void
     */
    public function initialize(Sabre\DAV\Server $server) 
    {
        $this->server = $server;
        
        $server->subscribeEvent('beforeGetProperties', array($this, 'beforeGetProperties'));
        
        /* Namespaces */
        $server->xmlNamespaces[self::NS_OWNCLOUD] = 'owncloud';
        
        array_push($server->protectedProperties,
            '{' . self::NS_OWNCLOUD . '}id'
        );
    }
    
    /**
     * Adds ownCloud specific properties
     *
     * @param string $path
     * @param DAV\INode $node
     * @param array $requestedProperties
     * @param array $returnedProperties
     * @return void
     */
    public function beforeGetProperties($path, Sabre\DAV\INode $node, array &$requestedProperties, array &$returnedProperties) 
    {
        $id = '{' . self::NS_OWNCLOUD . '}id';
        
        if (in_array($id, $requestedProperties)) {
            unset($requestedProperties[array_search($id, $requestedProperties)]);
            if ($node instanceof Tinebase_Frontend_WebDAV_Node) {
                $returnedProperties[200][$id] = $node->getId();
            } else {
                // the path does not change for the other nodes => hence the id is "static"
                $returnedProperties[200][$id] = sha1($path);
            }
        }
    }
}
