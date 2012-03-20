<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Tests
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * AllTests class for Wbxml
 * 
 * @package     Syncroton
 * @subpackage  Tests
 */
class Syncroton_Wbxml_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Syncroton all Wbxml tests');
        
        $suite->addTestSuite('Syncroton_Wbxml_DecoderTests');
        $suite->addTestSuite('Syncroton_Wbxml_EncoderTests');
        
        return $suite;
    }
}
