 <?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Milan Mertens <m.mertens@metaways.de>
 */


class Tinebase_FileSystem_Preview_TestZendNetworkAdapter extends Zend_Http_Client_Adapter_Test {
     public function connect($host, $port = 80, $secure = false)
     {
         if ($this->_nextRequestWillFail) {
             require_once 'Zend/Http/Client/Adapter/Exception.php';
             throw new Zend_Http_Client_Adapter_Exception('Request failed');
         }
     }
}