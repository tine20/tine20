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
 * Test class for GDPR_Controller_DataProvenance
 */
class GDPR_Controller_DataProvenanceTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Addressbook_Controller_Contact::getInstance()->setRequestContext([]);
        GDPR_Config::getInstance()->clearMemoryCache();
        Addressbook_Model_Contact::resetConfiguration();
    }

    /** by default this test runs with an admin user -> should work */
    public function testCreateUpdateSearchDelete()
    {
        $expirationDate = Tinebase_DateTime::now()->addYear(1);
        $dataProvenance = new GDPR_Model_DataProvenance([
            'name'          => 'test',
            'expiration'    => $expirationDate,
        ], true);


        /*** TEST CREATE ***/
        /** @var GDPR_Model_DataProvenance $createdProvenance */
        $createdProvenance = GDPR_Controller_DataProvenance::getInstance()->create($dataProvenance);
        static::assertEquals($dataProvenance->name, $createdProvenance->name);
        static::assertEquals($dataProvenance->expiration, $createdProvenance->expiration);


        /*** TEST GET ***/
        static::assertEquals($createdProvenance->getId(),
            GDPR_Controller_DataProvenance::getInstance()->get($createdProvenance->getId())->getId());


        /*** TEST SEARCH ***/
        static::assertEquals($createdProvenance->getId(),
            GDPR_Controller_DataProvenance::getInstance()->search(new GDPR_Model_DataProvenanceFilter([
                ['field' => 'name'      , 'operator' => 'equals', 'value' => $dataProvenance->name],
                ['field' => 'expiration', 'operator' => 'equals', 'value' => $dataProvenance->expiration],
            ]))->getFirstRecord()->getId());


        /*** TEST UPDATE ***/
        $createdProvenance->name = 'testUpated';
        $createdProvenance->expiration->addYear(1);
        /** @var GDPR_Model_DataProvenance $updatedProvenance */
        $updatedProvenance = GDPR_Controller_DataProvenance::getInstance()->update($createdProvenance);
        static::assertEquals($createdProvenance->name, $updatedProvenance->name);
        static::assertEquals($createdProvenance->expiration, $updatedProvenance->expiration);


        /*** TEST DELETE ***/
        try {
            Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);
            GDPR_Controller_DataProvenance::getInstance()->delete($updatedProvenance);
            static::fail('delete must not work');
        } catch (Tinebase_Exception_AccessDenied $e) {}
        static::assertEquals($createdProvenance->getId(),
            GDPR_Controller_DataProvenance::getInstance()->search(new GDPR_Model_DataProvenanceFilter([
                ['field' => 'name'      , 'operator' => 'equals', 'value' => $updatedProvenance->name],
                ['field' => 'expiration', 'operator' => 'equals', 'value' => $updatedProvenance->expiration],
            ]))->getFirstRecord()->getId());
    }

    public function testAcl()
    {
        $this->_removeRoleRight(GDPR_Config::APP_NAME, Tinebase_Acl_Rights_Abstract::ADMIN);
        // we still have the MANAGE right, so everything should work
        $this->testCreateUpdateSearchDelete();

        $expirationDate = Tinebase_DateTime::now()->addYear(1);
        $dataProvenance = new GDPR_Model_DataProvenance([
            'name'          => 'test2',
            'expiration'    => $expirationDate,
        ], true);

        $createdProvenance = GDPR_Controller_DataProvenance::getInstance()->create($dataProvenance);

        // so, no admin right, no manage right => only get/search should work
        $this->_removeRoleRight(GDPR_Config::APP_NAME, GDPR_Acl_Rights::MANAGE_CORE_DATA_DATA_PROVENANCE);

        static::assertEquals($createdProvenance->getId(),
            GDPR_Controller_DataProvenance::getInstance()->get($createdProvenance->getId())->getId());

        static::assertEquals($createdProvenance->getId(),
            GDPR_Controller_DataProvenance::getInstance()->search(new GDPR_Model_DataProvenanceFilter([
                ['field' => 'name'      , 'operator' => 'equals', 'value' => $dataProvenance->name],
                ['field' => 'expiration', 'operator' => 'equals', 'value' => $dataProvenance->expiration],
            ]))->getFirstRecord()->getId());

        $dataProvenance->name = 'test3';
        try {
            GDPR_Controller_DataProvenance::getInstance()->create($dataProvenance);
            static::fail('without admin and manage right, creating should not be possible');
        } catch (Tinebase_Exception_AccessDenied $tead) {}

        try {
            GDPR_Controller_DataProvenance::getInstance()->update($createdProvenance);
            static::fail('without admin and manage right, updating should not be possible');
        } catch (Tinebase_Exception_AccessDenied $tead) {}

        try {
            GDPR_Controller_DataProvenance::getInstance()->delete($createdProvenance);
            static::fail('without admin and manage right, updating should not be possible');
        } catch (Tinebase_Exception_AccessDenied $tead) {}
    }

    protected function _createADBContact($additionalData = [])
    {
        $c = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
            'n_family' => 'unitTestN_Family',
        ] + $additionalData, true));

        return $c;
    }

    public function testADBMandatoryDefault()
    {
        Addressbook_Controller_Contact::getInstance()->setRequestContext(['jsonFE' => true]);
        GDPR_Config::getInstance()->set(GDPR_Config::ADB_CONTACT_DATA_PROVENANCE_MANDATORY,
            GDPR_Config::ADB_CONTACT_DATA_PROVENANCE_MANDATORY_DEFAULT);
        Addressbook_Model_Contact::resetConfiguration();

        /** @var GDPR_Model_DataProvenance $defaultProvenance */
        $defaultProvenance = GDPR_Controller_DataProvenance::getInstance()->get(GDPR_Config::getInstance()
            ->{GDPR_Config::DEFAULT_ADB_CONTACT_DATA_PROVENANCE});
        $c = $this->_createADBContact();
        $c->isValid();
        static::assertTrue(isset($c->{GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME}),
            'expect customfield to be set');

        $notes = Tinebase_Notes::getInstance()
            ->getNotesOfRecord(Addressbook_Model_Contact::class, $c->getId(), 'Sql', false)
            ->filter('note', '/'.GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME.'/', true);
        static::assertEquals(1, $notes->count(), 'excpect customfield in notes');
        static::assertStringContainsString('GDPR_DataProvenance ( -> ' . $defaultProvenance->name . ')',
            $notes->getFirstRecord()->note);
    }

    public function testADBMandatoryNo()
    {
        Addressbook_Controller_Contact::getInstance()->setRequestContext(['jsonFE' => true]);
        GDPR_Config::getInstance()->set(GDPR_Config::ADB_CONTACT_DATA_PROVENANCE_MANDATORY,
            GDPR_Config::ADB_CONTACT_DATA_PROVENANCE_MANDATORY_NO);
        Addressbook_Model_Contact::resetConfiguration();

        $c = $this->_createADBContact();
        $c->isValid();
        static::assertEmpty($c->{GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME},
            'expect no customfield');

        $c->n_given = 'unitTestN_Given';
        $c = Addressbook_Controller_Contact::getInstance()->update($c);
        $c->isValid();
        static::assertEmpty($c->{GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME},
            'expect no customfield');

        $notes = Tinebase_Notes::getInstance()
            ->getNotesOfRecord(Addressbook_Model_Contact::class, $c->getId(), 'Sql', false)
            ->filter('note', '/'.GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME.'/', true);
        static::assertEquals(0, $notes->count(), 'excpect no customfields in notes');
    }

    public function testADBMandatoryYes()
    {
        Addressbook_Controller_Contact::getInstance()->setRequestContext(['jsonFE' => true]);
        GDPR_Config::getInstance()->set(GDPR_Config::ADB_CONTACT_DATA_PROVENANCE_MANDATORY,
            GDPR_Config::ADB_CONTACT_DATA_PROVENANCE_MANDATORY_YES);
        Addressbook_Model_Contact::resetConfiguration();

        try {
            $this->_createADBContact();
            static::fail('expect to fail creating a contact without data provenance provided');
        } catch (Tinebase_Exception_Record_Validation $terv) {
            static::assertEquals('Some fields (GDPR_DataProvenance) have invalid content (Addressbook_Model_Contact)', $terv->getMessage());
        }

        /** @var Addressbook_Model_Contact $c */
        $c = $this->_createADBContact([
            GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME => GDPR_Config::getInstance()
                ->{GDPR_Config::DEFAULT_ADB_CONTACT_DATA_PROVENANCE},
            GDPR_Controller_DataProvenance::ADB_CONTACT_REASON_CUSTOM_FIELD_NAME => 'creation',
        ]);
        $notes = Tinebase_Notes::getInstance()
            ->getNotesOfRecord(Addressbook_Model_Contact::class, $c->getId(), 'Sql', false)
            ->filter('note', '/creation/', true);
        self::assertEquals(1, $notes->count(), 'expect 1 creation note');
        self::assertStringContainsString(GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME,
            $notes->getFirstRecord()->note);

        $c->n_given = 'unitTestN_Given';
        try {
            Addressbook_Controller_Contact::getInstance()->update($c);
            static::fail('expect to fail updating a contact without data provenance provided');
        } catch (Tinebase_Exception_Record_Validation $terv) {
            static::assertEquals('Some fields (GDPR_DataProvenance) have invalid content (Addressbook_Model_Contact)', $terv->getMessage());
        }

        $c->{GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME} = GDPR_Config::getInstance()
            ->{GDPR_Config::DEFAULT_ADB_CONTACT_DATA_PROVENANCE};
        $c->{GDPR_Controller_DataProvenance::ADB_CONTACT_REASON_CUSTOM_FIELD_NAME} = 'update';
        Addressbook_Controller_Contact::getInstance()->update($c);
        $notes = Tinebase_Notes::getInstance()
            ->getNotesOfRecord(Addressbook_Model_Contact::class, $c->getId(), 'Sql', false)
            ->filter('note', '/update/', true);
        static::assertEquals(1, $notes->count(), 'expect 1 update note');
        static::assertStringContainsString(GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME,
            $notes->getFirstRecord()->note);

        $c->n_given = 'anotherName';
        $c->{GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME} = GDPR_Config::getInstance()
            ->{GDPR_Config::DEFAULT_ADB_CONTACT_DATA_PROVENANCE};
        $c->{GDPR_Controller_DataProvenance::ADB_CONTACT_REASON_CUSTOM_FIELD_NAME} = 'update';
        Addressbook_Controller_Contact::getInstance()->update($c);

        $notes = Tinebase_Notes::getInstance()
            ->getNotesOfRecord(Addressbook_Model_Contact::class, $c->getId(), 'Sql', false)
            ->filter('note', '/update/', true);
        static::assertEquals(2, $notes->count(), 'expect 2 update note');
        static::assertStringContainsString(GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME,
            $notes->getFirstRecord()->note);
    }
}
