<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Milan Mertens <m.mertens@metaways.de>
 */


class Tinebase_FileSystem_Preview_TestNetworkAdapter implements Tinebase_FileSystem_Preview_NetworkAdapter
{
    protected $adapter;

    public function __construct()
    {
        $this->adapter = new Tinebase_FileSystem_Preview_TestZendNetworkAdapter();
    }

    /**
     * @param null $config Zend Http Client config
     * @return Zend_Http_Client
     */
    public function getHttpsClient($config = null)
    {
        return new Zend_Http_Client('https://docservice.notld', array(
            'adapter' => $this->adapter
        ));
    }

    public function getAdapter()
    {
        return $this->adapter;
    }
}