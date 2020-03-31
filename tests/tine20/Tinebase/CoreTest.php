<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Tinebase_Core
 * 
 * @package     Tinebase
 */
class Tinebase_CoreTest extends TestCase
{
    protected function tearDown()
    {
        parent::tearDown();
        
        Tinebase_Core::set(Tinebase_Core::REQUEST, null);
    }
    
    public function testGetDispatchServerJSON()
    {
        $request = Tinebase_Http_Request::fromString(
            "OPTIONS /index.php HTTP/1.1\r\n".
            "Host: localhost\r\n".
            "User-Agent: Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; en-US; rv:1.9.1b3pre) Gecko/20081130 Minefield/3.1b3pre\r\n".
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n".
            "Accept-Language: en-us,en;q=0.5\r\n".
            "Accept-Encoding: gzip,deflate\r\n".
            "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n".
            "Connection: keep-alive\r\n".
            "Origin: http://foo.example\r\n".
            "Access-Control-Request-Method: POST\r\n".
            "Access-Control-Request-Headers: X-PINGOTHER"
        );
        Tinebase_Core::set(Tinebase_Core::REQUEST, $request);
        
        $server = Tinebase_Core::getDispatchServer($request);
        
        $this->assertInstanceOf('Tinebase_Server_Json', $server);
        
        $request = Tinebase_Http_Request::fromString(
            "POST /index.php HTTP/1.1\r\n".
            "X-Tine20-Request-Type: JSON\r\n".
            "\r\n".
            '{"jsonrpc":"2.0","method":"Admin.searchUsers","params":{"filter":[{"field":"query","operator":"contains","value":"","id":"ext-record-2"}],"paging":{"sort":"accountLoginName","dir":"ASC","start":0,"limit":50}},"id":37}'
        );
        Tinebase_Core::set(Tinebase_Core::REQUEST, $request);
        
        $server = Tinebase_Core::getDispatchServer($request);
        
        $this->assertInstanceOf('Tinebase_Server_Json', $server);
        
        
        $request = Tinebase_Http_Request::fromString(
            "POST /index.php HTTP/1.1\r\n".
            "Content-Type: application/json\r\n".
            "\r\n".
            '{"jsonrpc":"2.0","method":"Admin.searchUsers","params":{"filter":[{"field":"query","operator":"contains","value":"","id":"ext-record-2"}],"paging":{"sort":"accountLoginName","dir":"ASC","start":0,"limit":50}},"id":37}'
        );
        Tinebase_Core::set(Tinebase_Core::REQUEST, $request);
        
        $server = Tinebase_Core::getDispatchServer($request);
        
        $this->assertInstanceOf('Tinebase_Server_Json', $server);
    }
    
    public function testGetDispatchServerSnom()
    {
        $request = Tinebase_Http_Request::fromString(
            "POST /index.php HTTP/1.1\r\n".
            "User-Agent: Mozilla/4.0 (compatible; snom300-SIP 8.4.35 1.1.3-u)"
        );
        
        $server = Tinebase_Core::getDispatchServer($request);
        
        $this->assertInstanceOf('Voipmanager_Server_Snom', $server);
    }
    
    public function testGetDispatchServerAsterisk()
    {
        $request = Tinebase_Http_Request::fromString(
            "POST /index.php HTTP/1.1\r\n".
            "User-Agent: asterisk-libcurl-agent/1.0"
        );
        
        $server = Tinebase_Core::getDispatchServer($request);
        
        $this->assertInstanceOf('Voipmanager_Server_Asterisk', $server);
    }
    
    public function testGetDispatchServerActiveSync()
    {
        $request = Tinebase_Http_Request::fromString(
            "GET /index.php?frontend=activesync HTTP/1.1\r\n".
            "User-Agent: SAMSUNG-GT-I9300/101.403"
        );
        $request->setQuery(new \Zend\Stdlib\Parameters(array('frontend' => 'activesync')));
        
        $server = Tinebase_Core::getDispatchServer($request);
        
        $this->assertInstanceOf('ActiveSync_Server_Http', $server);
    }
    
    public function testGetDispatchServerWebDAV()
    {
        $request = Tinebase_Http_Request::fromString(
            "GET /index.php?frontend=webdav HTTP/1.1\r\n".
            "User-Agent: SAMSUNG-GT-I9300/101.403"
        );
        $request->setQuery(new \Zend\Stdlib\Parameters(array('frontend' => 'webdav')));
        
        $server = Tinebase_Core::getDispatchServer($request);
        
        $this->assertInstanceOf('Tinebase_Server_WebDAV', $server);
    }

    public function testGetUrl()
    {
        $config = Tinebase_Config::getInstance();
        $oldValue = $config->get(Tinebase_Config::TINE20_URL);
        try {
            $config->set(Tinebase_Config::TINE20_URL, 'https://unittestDomain.test/uri');
            static::assertEquals('https://unittestDomain.test/uri', Tinebase_Core::getUrl());
        } finally {
            $config->set(Tinebase_Config::TINE20_URL, $oldValue);
        }
    }

    /**
     * @group nogitlabci
     */
    public function testGetHostname()
    {
        $config = Tinebase_Config::getInstance();
        $oldValue = $config->get(Tinebase_Config::TINE20_URL);
        Tinebase_Core::set('HOSTNAME', null);
        try {
            $config->set(Tinebase_Config::TINE20_URL, 'https://unittestDomain.test/uri');
            static::assertEquals('https://unittestDomain.test', Tinebase_Core::getHostname());
        } finally {
            $config->set(Tinebase_Config::TINE20_URL, $oldValue);
            Tinebase_Core::set('HOSTNAME', null);
        }
    }

    public function testDIContainer()
    {
        $container = Tinebase_Core::getContainer();

        $requestFromContainer1 = $container->get(\Psr\Http\Message\RequestInterface::class);
        $requestFromContainer2 = $container->get(\Psr\Http\Message\RequestInterface::class);

        static::assertTrue($requestFromContainer1 === $requestFromContainer2, 'container did not return same instance');
    }

    public function testUniqueKeys()
    {
        $db = Tinebase_Core::getDb();
        if (! $db instanceof Zend_Db_Adapter_Pdo_Mysql) {
            static::markTestSkipped('only a mysql test'); // TODO remove this in release 13 once pgsql got dropped
        }

        // TODO we should fix most of them! either the index should not be unique or we need to fix it
        $whiteListed = [
            SQL_TABLE_PREFIX . 'tree_nodes' => [
                'parent_id',
            ],
            SQL_TABLE_PREFIX . 'timemachine_modlog' => [
                'seq',
                'modified_attribute',
                'record_type',
                'record_id',
            ],
            SQL_TABLE_PREFIX . 'snom_phones' => [
                'http_client_user',
            ],
            SQL_TABLE_PREFIX . 'snom_lines' => [
                'linenumber',
            ],
            SQL_TABLE_PREFIX . 'record_observer' => [
                'observable_identifier',
            ],
            SQL_TABLE_PREFIX . 'preferences' => [
                'account_id',
            ],
            SQL_TABLE_PREFIX . 'numberable' => [
                'bucket',
            ],
            SQL_TABLE_PREFIX . 'inventory_item' => [
                'inventory_id',
                'deleted_time',
            ],
            SQL_TABLE_PREFIX . 'groups' => [
                'deleted_time',
            ],
            SQL_TABLE_PREFIX . 'felamimail_cache_message' => [
                'messageuid',
            ],
            SQL_TABLE_PREFIX . 'asterisk_sip_peers' => [
                'name',
            ],
            SQL_TABLE_PREFIX . 'acsync_device' => [
                'owner_id',
            ],
            SQL_TABLE_PREFIX . 'accounts' => [
                'openid',
            ],
            SQL_TABLE_PREFIX . 'role_accounts' => [
                'account_id',
            ],
            // this is 2017.11 only, can be ignored
            SQL_TABLE_PREFIX . 'async_job' => [
                'name',
                'seq',
            ],
        ];

        $result = [];
        foreach ($db->query('SHOW TABLES')->fetchAll(Zend_Db::FETCH_COLUMN, 0) as $table) {
            if (strpos($table, SQL_TABLE_PREFIX) !== 0) {
                continue;
            }

            $result = array_merge($result,
                $db->query('SHOW INDEX FROM `' . $table . '` WHERE `non_unique` = 0 AND `null` = "Yes"' .
                    (isset($whiteListed[$table]) ? ' AND Column_name NOT IN ("' . join('", "', $whiteListed[$table])
                        . '")' : ''))->fetchAll(Zend_Db::FETCH_ASSOC));
        }

        static::assertCount(0, $result,
            'not all index fields are nullable = false: ' . print_r($result, true));
    }
}
