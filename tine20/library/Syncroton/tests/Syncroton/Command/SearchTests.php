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
class Syncroton_Command_SearchTests extends Syncroton_Command_ATestCase
{
    #protected $_logPriority = Zend_Log::DEBUG;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('ActiveSync FolderCreate command tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * test xml generation for IPhone
     */
    public function testSearch()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Search xmlns="uri:Search">
                <Store>
                    <Name>GAL</Name>
                    <Query>Lars</Query>
                    <Options>
                        <Range>0-3</Range>
                        <RebuildResults/>
                        <DeepTraversal/>
                    </Options>
                </Store>
            </Search>
        ');
        
        $search = new Syncroton_Command_Search($doc, $this->_device, null);
        
        $search->handle();
        
        $responseDoc = $search->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Search', 'uri:Search');
        
        $nodes = $xpath->query('//Search:Search/Search:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Search::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Search:Search/Search:Response/Search:Store/Search:Total');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(5, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Search:Search/Search:Response/Search:Store/Search:Result');
        $this->assertEquals(4, $nodes->length, $responseDoc->saveXML());
        #$this->assertEquals(Syncroton_Command_Search::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
}
