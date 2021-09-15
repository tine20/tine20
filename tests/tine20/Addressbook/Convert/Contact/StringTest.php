<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Addressbook_Convert_Contact_StringTest extends \PHPUnit\Framework\TestCase {

    protected $_testData = array(array(
        'string' =>
'Max Mustermann
Mustermannstr. 3
34456 Musterstadt',
        'structured' => array(
            'n_given' => 'Max',
            'n_family' => 'Mustermann',
            'adr_one_postalcode' => '34456',
            'adr_one_locality'   => 'Musterstadt',
        )), array(
        'string' =>
'Max Mustermann
Mustermann Straße 3
34456 Musterstadt',
        'structured' => array(
            'n_given' => 'Max',
            'n_family' => 'Mustermann',
            'adr_one_postalcode' => '34456',
            'adr_one_locality'   => 'Musterstadt',
            'adr_one_street'     => 'Mustermann Straße 3',
        )), array(
        'string' =>
            'Max Mustermann
Mustermann Straße 3a
34456 Musterstadt',
        'structured' => array(
            'n_given' => 'Max',
            'n_family' => 'Mustermann',
            'adr_one_postalcode' => '34456',
            'adr_one_locality'   => 'Musterstadt',
            'adr_one_street'     => 'Mustermann Straße 3a',
        )),
    );

    public function testParseSignature()
    {
        foreach ($this->_testData as $data) {
            $this->_testParseSignatures($data);
        }
    }

    protected function _testParseSignatures($testData)
    {
        $converter = new Addressbook_Convert_Contact_String();

        $contact = $converter->toTine20Model($testData['string']);
        $unrecognizedTokens  = $converter->getUnrecognizedTokens();

        foreach($testData['structured'] as $key => $value) {
            $this->assertEquals($value, $contact->{$key}, $key . ' mismatch. contact: ' . print_r($contact->toArray(), true));
        }
        if (isset($testData['unrecognized'])) {
            $this->assertSame($testData['unrecognized'], $unrecognizedTokens);
        } else {
            $this->assertEmpty($unrecognizedTokens, print_r($unrecognizedTokens, true));
        }
    }
}
