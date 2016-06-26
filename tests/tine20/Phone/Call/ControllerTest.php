<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_Group
 */
class Phone_Call_ControllerTest extends TestCase
{
    /**
     * @see 0011934: show contacts in phone call grid
     */
    public function testContactRelation()
    {
        $phoneNumber = '0406437435';
        $myContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
        $myContact->tel_work = $phoneNumber;
        Addressbook_Controller_Contact::getInstance()->update($myContact);

        $call = new Phone_Model_Call(array(
            'line_id'               => 'phpunitlineid',
            'phone_id'              => 'phpunitphoneid',
            'direction'             => Phone_Model_Call::TYPE_INCOMING,
            'source'                => '26',
            'destination'           => $phoneNumber,
        ));
        $call = Phone_Controller_Call::getInstance()->create($call);

        $this->assertEquals(1, count($call->relations), 'my contact should be added as relation to the call' . print_r($call->toArray(), true));
        $this->assertEquals($myContact->getId(), $call->relations->getFirstRecord()->related_id);
    }
}
