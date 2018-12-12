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

interface Tinebase_FileSystem_Preview_NetworkAdapter
{
    /**
     * @param null $config Zend Http Client config
     * @return Zend_Http_Client
     */
    public function getHttpsClient($config = null);
}
