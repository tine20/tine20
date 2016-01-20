<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test class for Tinebase_MailTest
 */
class Tinebase_MailTest extends PHPUnit_Framework_TestCase
{
    /**
     * @see 0011556: sending mails to multiple recipients fails
     */
    public function testParseAdresslist()
    {
        $addressesString = 'abc@example.org, abc2@example.org';
        $addresses = Tinebase_Mail::parseAdresslist($addressesString);
        
        $this->assertEquals(array(
            array('name' => null, 'address' => 'abc@example.org'),
            array('name' => null, 'address' => 'abc2@example.org')
        ), $addresses, print_r($addresses, true));
    }
}
