<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Michael Spahn <m.spahn@metaways.de>
 * 
 */

/**
 * Test class for Tinebase_MailTest
 */
class Tinebase_MailTest extends PHPUnit_Framework_TestCase
{
    public function testLinkify()
    {
        $testUrl = "http://tine20.org";
        $linkifiedUrl = Tinebase_Mail::linkify($testUrl);
        
        $this->assertEquals($linkifiedUrl, '<a href="http://tine20.org" target="_blank">http://tine20.org</a>');
    }
}
