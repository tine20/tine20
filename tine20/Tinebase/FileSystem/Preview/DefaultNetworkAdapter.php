<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Milan Mertens <m.mertens@metaways.de>
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Tinebase_FileSystem_Preview_DefaultNetworkAdapter implements Tinebase_FileSystem_Preview_NetworkAdapter
{
    protected $_url;

    /**
     * Tinebase_FileSystem_Preview_NetworkAdapter constructor.
     * @param $url
     * @param $licensePath
     * @param $caPath
     */
    public function __construct($url)
    {
        $this->_url = $url;
    }
    
    /**
     * @param null $config
     * @return Zend_Http_Client
     */
    public function getHttpsClient($config = null)
    {
        return Tinebase_Core::getHttpClient($this->_url, $config);
    }
}
