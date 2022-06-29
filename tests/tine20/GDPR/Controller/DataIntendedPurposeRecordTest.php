<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     GDPR
 * @subpackage  Test
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Test class for GDPR_Controller_DataIntendedPurposeRecord
 */
class GDPR_Controller_DataIntendedPurposeRecordTest extends TestCase
{
    /** @var GDPR_Model_DataIntendedPurpose */
    protected $_dataIntendedPurpose1 = null;
    /** @var GDPR_Model_DataIntendedPurpose */
    protected $_dataIntendedPurpose2 = null;

    /**
     * set up tests
     */
    protected function setUp(): void
{
        parent::setUp();

        $this->_dataIntendedPurpose1 = GDPR_Controller_DataIntendedPurpose::getInstance()->create(
            new GDPR_Model_DataIntendedPurpose([
                'name' => 'unittest1',
            ], true));
        $this->_dataIntendedPurpose2 = GDPR_Controller_DataIntendedPurpose::getInstance()->create(
            new GDPR_Model_DataIntendedPurpose([
                'name' => 'unittest2',
            ], true));
    }

    public function testCreateByAdbContact()
    {
        $contact = new Addressbook_Model_Contact([
            'n_given' => 'unittest',
            'email' => Tinebase_Record_Abstract::generateUID() . '@unittest.de',
            GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME => [
                new GDPR_Model_DataIntendedPurposeRecord([
                    'intendedPurpose' => $this->_dataIntendedPurpose1->getId(),
                    'agreeDate' => Tinebase_DateTime::now(),
                    'agreeComment' => 'well, I talked the contact into it',
                ], true),
                new GDPR_Model_DataIntendedPurposeRecord([
                    'intendedPurpose' => $this->_dataIntendedPurpose2->getId(),
                    'agreeDate' => Tinebase_DateTime::now(),
                    'agreeComment' => 'well, I talked the contact into that too',
                ], true)
            ]
        ], true);

        /** @var Addressbook_Model_Contact $createdContact */
        $createdContact = Addressbook_Controller_Contact::getInstance()->create($contact);

        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter(['record' => $createdContact->getId()]));
        static::assertSame(2, $createdDipr->count(), 'expect to find 2 data intended purpose records for this contact');

        // after update with dependent record property === null -> no changes
        $createdContact->n_family = 'n_family';
        $updatedContact = Addressbook_Controller_Contact::getInstance()->update($createdContact);

        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter(['record' => $createdContact->getId()]));
        static::assertSame(2, $createdDipr->count(), 'expect to find 2 data intended purpose records for this contact');

        return $updatedContact;
    }

    public function testSearch()
    {
        $createdContact = $this->testCreateByAdbContact();
        $c2 = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
            'n_given' => 'unittest'.uniqid(),
            'email' => Tinebase_Record_Abstract::generateUID() . '@unittest.de',
            GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME => [
                new GDPR_Model_DataIntendedPurposeRecord([
                    'intendedPurpose' => $this->_dataIntendedPurpose1->getId(),
                    'agreeDate' => Tinebase_DateTime::now(),
                    'agreeComment' => 'well, I talked the contact into it',
                ], true),
            ]
        ], true));

        $result = Addressbook_Controller_Contact::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
                ['field' => GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME, 'operator' => 'definedBy', 'value' => [
                    ['field' => 'intendedPurpose', 'operator' => 'equals', 'value' => $this->_dataIntendedPurpose2->getId()],
                ]],
            ]
        ));

        $this->assertSame(1, $result->count());
        $this->assertSame($createdContact->getId(), $result->getFirstRecord()->getId());

        $result = Addressbook_Controller_Contact::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
                ['field' => GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME, 'operator' => 'definedBy', 'value' => [
                    ['field' => 'intendedPurpose', 'operator' => 'not', 'value' => $this->_dataIntendedPurpose1->getId()],
                ]],
            ]
        ));

        $this->assertSame(1, $result->count());
        $this->assertSame($createdContact->getId(), $result->getFirstRecord()->getId());

        $result = Addressbook_Controller_Contact::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
                ['field' => GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME, 'operator' => 'notDefinedBy', 'value' => [
                    ['field' => 'intendedPurpose', 'operator' => 'equals', 'value' => $this->_dataIntendedPurpose2->getId()],
                ]],
            ]
        ));

        $this->assertGreaterThan(1, $result->count());
        $ids = $result->getArrayOfIds();
        $this->assertNotContains($createdContact->getId(), $ids);
        $this->assertContains($c2->getId(), $ids);
    }

    public function testUpdate()
    {
        $createdContact = $this->testCreateByAdbContact();
        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter(['record' => $createdContact->getId()]));

        $createdDipr->getFirstRecord()->withdrawComment = 'foo';
        $createdDipr->getLastRecord()->withdrawComment = 'foo';
        $createdContact->{GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME} = $createdDipr;
        Addressbook_Controller_Contact::getInstance()->update($createdContact);

        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter(['record' => $createdContact->getId()]));
        static::assertSame(2, $createdDipr->count(), 'expect to find 2 data intended purpose records for this contact');
        foreach ($createdDipr as $dipr) {
            static::assertNull($dipr->withdrawDate, 'expect withdrawDate to be null');
            static::assertSame('foo', $dipr->withdrawComment, 'expect withdrawComment failed');
        }
    }

    public function testDirectDelete()
    {
        $createdContact = $this->testCreateByAdbContact();
        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter(['record' => $createdContact->getId()]));

        $createdContact->{GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME} =
            new Tinebase_Record_RecordSet($createdDipr->getRecordClassName(), [$createdDipr->getFirstRecord()]);

        Addressbook_Controller_Contact::getInstance()->update($createdContact);

        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter(['record' => $createdContact->getId()]));
        static::assertSame(2, $createdDipr->count(), 'expect to find 2 data intended purpose records for this contact');
    }

    public function testContactDelete()
    {
        $createdContact = $this->testCreateByAdbContact();

        Addressbook_Controller_Contact::getInstance()->delete($createdContact);

        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter(['record' => $createdContact->getId()]));
        static::assertSame(0, $createdDipr->count(), 'expect to find 0 data intended purpose records for this contact');
    }

    public function testBlackList()
    {
        $createdContact = $this->testCreateByAdbContact();
        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter(['record' => $createdContact->getId()]));
        static::assertSame(2, $createdDipr->count(), 'expect to find 2 data intended purpose records for this contact');
        foreach ($createdDipr as $dipr) {
            static::assertNull($dipr->withdrawDate, 'expect withdrawDate to be null');
            static::assertEmpty($dipr->withdrawComment, 'expect withdrawComment to be empty');
        }

        $createdContact->{GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_BLACKLIST_CUSTOM_FIELD_NAME} = true;
        $updatedContact = Addressbook_Controller_Contact::getInstance()->update($createdContact);

        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter([
                ['field' => 'record', 'operator' => 'equals', 'value' => $createdContact->getId()],
                ['field' => 'withdrawDate', 'operator' => 'after', 'value' => '1970-01-01'],
            ]));
        static::assertSame(2, $createdDipr->count(), 'expect to find 2 data intended purpose records for this contact');
        foreach ($createdDipr as $dipr) {
            static::assertNotNull($dipr->withdrawDate, 'expect withdrawDate to be not null');
            static::assertSame('Blacklist', $dipr->withdrawComment, 'expect withdrawComment failed');
        }


        // this should not work, we set the blacklist
        $createdDipr->getFirstRecord()->withdrawComment = 'foo';
        $updatedContact->{GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME} = $createdDipr;
        Addressbook_Controller_Contact::getInstance()->update($updatedContact);

        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter([
                ['field' => 'record', 'operator' => 'equals', 'value' => $createdContact->getId()],
                ['field' => 'withdrawDate', 'operator' => 'after', 'value' => '1970-01-01'],
            ]));
        static::assertSame(2, $createdDipr->count(), 'expect to find 2 data intended purpose records for this contact');
        foreach ($createdDipr as $dipr) {
            static::assertNotNull($dipr->withdrawDate, 'expect withdrawDate to be not null');
            static::assertSame('Blacklist', $dipr->withdrawComment, 'expect withdrawComment failed');
        }
    }
}
