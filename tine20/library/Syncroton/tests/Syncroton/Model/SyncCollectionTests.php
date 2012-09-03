<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Tests
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to test <...>
 *
 * @package     Syncroton
 * @subpackage  Tests
 */
class Syncroton_Model_SyncCollectionTests extends Syncroton_Model_ATestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    protected $_exampleXML = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<Sync xmlns="uri:AirSync" xmlns:Contacts="uri:Contacts"><Collections>
<Collection>
<Class>Contacts</Class>
<SyncKey>1</SyncKey>
<CollectionId>addressbook-root</CollectionId>
<DeletesAsMoves/>
<GetChanges/>
<WindowSize>50</WindowSize>
<Options><FilterType>0</FilterType><Truncation>2</Truncation><Conflict>0</Conflict></Options>
<Commands>
<Add><ClientId>1</ClientId><ApplicationData><Contacts:FileAs>ads2f, asdfadsf</Contacts:FileAs><Contacts:FirstName>asdf </Contacts:FirstName><Contacts:LastName>asdfasdfaasd </Contacts:LastName><Contacts:MobilePhoneNumber>+4312341234124</Contacts:MobilePhoneNumber><Contacts:Body>&#13;</Contacts:Body></ApplicationData></Add>
</Commands>
</Collection></Collections></Sync>';
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Syncroton contacts tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    public function testParsingCollection()
    {
        $xml = new SimpleXMLElement($this->_exampleXML);
        
        $collection = new Syncroton_Model_SyncCollection($xml->Collections->Collection[0]);
        
        $this->assertEquals(1, $collection->syncKey);
        $this->assertEquals('Contacts', $collection->class);
        $this->assertEquals(true, $collection->deletesAsMoves);
    }
}
