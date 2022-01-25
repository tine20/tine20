<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2013-2017 Metaways Infosystems GmbH (http://www.metaways.de)
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
class Tinebase_WebDav_Plugin_OwnCloud extends Sabre\DAV\ServerPlugin
{

    const NS_OWNCLOUD = 'http://owncloud.org/ns';

    /**
     * Min version of owncloud
     */
    const OWNCLOUD_MIN_VERSION = '2.0.0';

    /**
     * Max version of owncloud
     *
     * Adjust max version of supported owncloud clients for tine
     */
    const OWNCLOUD_MAX_VERSION = '100.0.0';

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
        array_push($server->protectedProperties,
            '{' . self::NS_OWNCLOUD . '}permissions'
        );
    }

    /**
     * Adds ownCloud specific properties
     *
     * @param string $path
     * @param \Sabre\DAV\INode $node
     * @param array $requestedProperties
     * @param array $returnedProperties
     * @return void
     * @throws \InvalidArgumentException
     */
    public function beforeGetProperties(
        $path,
        Sabre\DAV\INode $node,
        array &$requestedProperties,
        array &$returnedProperties
    ) {
        $version = $this->getOwnCloudVersion();
        if ($version !== null && !$this->isValidOwnCloudVersion()) {
            $message = sprintf(
                '%s::%s OwnCloud client min version is "%s"!',
                __METHOD__,
                __LINE__,
                static::OWNCLOUD_MIN_VERSION
            );

            Tinebase_Core::getLogger()->debug($message);
            throw new InvalidArgumentException($message);
        } elseif (!$version) {
            // If it's not even an owncloud version, don't add any owncloud specific features here.
            return;
        }

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

        $permission = '{' . self::NS_OWNCLOUD . '}permissions';
        if (in_array($permission, $requestedProperties)) {
            unset($requestedProperties[array_search($permission, $requestedProperties)]);
//            if ($node instanceof Tinebase_Frontend_WebDAV_Node) {
                $returnedProperties[200][$permission] = 'SWCKDNV';
//            } else {
                // the path does not change for the other nodes => hence the id is "static"
//                $returnedProperties[200][$permission] = sha1($path);
//            }
        }

        $fingerPrint = '{' . self::NS_OWNCLOUD . '}data-fingerprint';
        if (in_array($fingerPrint, $requestedProperties)) {
            unset($requestedProperties[array_search($fingerPrint, $requestedProperties)]);
            $returnedProperties[200][$fingerPrint] = '';
        }

        $shareTypes = '{' . self::NS_OWNCLOUD . '}share-types';
        if (in_array($shareTypes, $requestedProperties)) {
            unset($requestedProperties[array_search($shareTypes, $requestedProperties)]);
            $returnedProperties[200][$shareTypes] = '';
        }
    }

    /**
     * Return the actuall owncloud version number
     * @throws \InvalidArgumentException
     */
    protected function isValidOwnCloudVersion()
    {
        $version  = $this->getOwnCloudVersion();

        return version_compare($version, static::OWNCLOUD_MIN_VERSION, 'ge')
            && version_compare($version, static::OWNCLOUD_MAX_VERSION, 'le');
    }

    /**
     * Get owncloud version number
     *
     * @return mixed|null
     */
    protected function getOwnCloudVersion() {
        // Mozilla/5.0 (Macintosh) mirall/2.2.4 (build 3709)
        /* @var $request \Zend\Http\PhpEnvironment\Request */
        $request = Tinebase_Core::get(Tinebase_Core::REQUEST);

        // In some cases this is called not out of an request, for example some tests, therefore we should require it here
        // If it's not an owncloud server, we don't need to determine the version!
        if (!$request) {
            return null;
        }

        $useragentHeader = $request->getHeader('user-agent');

        $useragent = $useragentHeader ? $useragentHeader->getFieldValue() : null;

        // If no valid header, this is not an owncloud client
        if ($useragent === null) {
            return null;
        }

        $match = [];

        if (!preg_match('/mirall\/(\d+\.\d+\.\d+)/', $useragent, $match)) {
            return null;
        }

        $version = array_pop($match);

        if ($version === '') {
            $version = null;
        }

        return $version;
    }
}
