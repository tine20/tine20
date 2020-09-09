<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for Addressbook_Model_Contact
 *
 * @package     Addressbook
 */
class Addressbook_Model_ContactTest extends TestCase
{
    public function testNormalizeTelephoneNumber()
    {
        static::assertSame('+4940234556', Addressbook_Model_Contact::normalizeTelephoneNum('(040) 2345,,56'));
        static::assertSame('+4940234556', Addressbook_Model_Contact::normalizeTelephoneNum('(040) 2345 -56'));
        static::assertSame('+4940234556', Addressbook_Model_Contact::normalizeTelephoneNum('(040) 2345 /s56'));
        static::assertSame('+4940234556', Addressbook_Model_Contact::normalizeTelephoneNum('(040) 2345 s56'));
        static::assertSame('+4940234556', Addressbook_Model_Contact::normalizeTelephoneNum('(040) 2345 #56'));
        static::assertSame('+4940234556', Addressbook_Model_Contact::normalizeTelephoneNum('(040) 2345 #s56'));
        static::assertSame('+4940234556', Addressbook_Model_Contact::normalizeTelephoneNum('+49(0)40 234556'));

        static::assertSame('+4440234556', Addressbook_Model_Contact::normalizeTelephoneNum('+44 (40) 2345 -56'));
        static::assertSame('+4440234556', Addressbook_Model_Contact::normalizeTelephoneNum('+44 (40) 2345 /s56'));
        static::assertSame('+4440234556', Addressbook_Model_Contact::normalizeTelephoneNum('0044 (40) 2345 s56'));
        static::assertSame('+4440234556', Addressbook_Model_Contact::normalizeTelephoneNum('0044(40) 2345 #56'));
        static::assertSame('+4440234556', Addressbook_Model_Contact::normalizeTelephoneNum('0044 (40) 2345 #s56'));
    }
}
