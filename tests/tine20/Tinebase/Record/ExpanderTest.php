<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Record Expander test class
 */
class Tinebase_Record_ExpanderTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();

        Tinebase_Record_Expander_DataRequest::clearCache();
    }

    protected function _createAndUpdateContact()
    {
        $adbController = Addressbook_Controller_Contact::getInstance();
        $createdContact = $adbController->create(new Addressbook_Model_Contact(['n_fn' => 'test'], true));
        $createdContact->email = 'test@test.de';
        $createdContact->relations = [[
            'related_degree'    => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model'     => Addressbook_Model_Contact::class,
            'related_backend'   => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
            'related_id'        => Tinebase_Core::getUser()->contact_id,
            'type'              => ''
        ]];
        $createdContact->tags = [['name' =>'bla']];
        $createdContact->notes = ['bla note'];

        $file = Tinebase_TempFile::getTempPath();
        file_put_contents($file, 'test');
        $createdContact->attachments = [new Tinebase_Model_Tree_Node([
            'tempFile' => Tinebase_TempFile::getInstance()->createTempFile($file)->getId(),
        ], true)];

        return $adbController->update($createdContact);
    }

    public function testExpandUserRecordProperty()
    {
        $createdContact = $this->_createAndUpdateContact();
        $adbController = Addressbook_Controller_Contact::getInstance();
        $oldCustomfields = $adbController->resolveCustomfields(false);

        try {
            $contacts = $adbController->getMultiple([$createdContact->getId()]);
            $contact = $contacts->getFirstRecord();
            static::assertTrue(is_string($contact->created_by), 'created_by is not a string');

            $expander = new Tinebase_Record_Expander(Addressbook_Model_Contact::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    'created_by' => [
                        Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                            'last_modified_by' => [],
                            'created_by' => [],
                        ],
                    ],
                ],
            ]);
            $expander->expand($contacts);
            static::assertTrue(is_string($contact->last_modified_by), 'last_modified_by is not a string');
            static::assertTrue(is_object($contact->created_by), 'created_by is not a object');
            if (!empty(Tinebase_Core::getUser()->created_by)) {
                static::assertTrue(is_object($contact->created_by->created_by),
                    'created_by->created_by is not a object');
            }
        } finally {
            $adbController->resolveCustomfields($oldCustomfields);
        }
    }

    public function testExpandUserTypeProperties()
    {
        $createdContact = $this->_createAndUpdateContact();
        $adbController = Addressbook_Controller_Contact::getInstance();
        $oldCustomfields = $adbController->resolveCustomfields(false);

        try {
            $contacts = $adbController->getMultiple([$createdContact->getId()]);
            $contact = $contacts->getFirstRecord();
            static::assertTrue(is_string($contact->created_by), 'created_by is not a string');

            $expander = new Tinebase_Record_Expander(Addressbook_Model_Contact::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTY_CLASSES => [
                    Tinebase_Record_Expander::PROPERTY_CLASS_USER => [
                        Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                            'created_by' => [],
                        ],
                    ],
                ],
            ]);
            $expander->expand($contacts);
            static::assertTrue(is_object($contact->last_modified_by), 'last_modified_by is not a object');
            static::assertTrue(is_object($contact->created_by), 'created_by is not a object');
            if (!empty(Tinebase_Core::getUser()->created_by)) {
                static::assertTrue(is_object($contact->created_by->created_by),
                    'created_by->created_by is not a object');
            }
        } finally {
            $adbController->resolveCustomfields($oldCustomfields);
        }
    }

    public function testExpandRelationsSimple()
    {
        $createdContact = $this->_createAndUpdateContact();
        $adbController = Addressbook_Controller_Contact::getInstance();
        $oldCustomfields = $adbController->resolveCustomfields(false);

        try {
            $contacts = $adbController->getMultiple([$createdContact->getId()]);
            $contact = $contacts->getFirstRecord();
            static::assertTrue(empty($contact->relations), 'relations is not empty');

            // we resolve only the relations, not further
            $expander = new Tinebase_Record_Expander(Addressbook_Model_Contact::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    'relations' => [],
                ],
            ]);
            $expander->expand($contacts);
            static::assertTrue(is_object($contact->relations), 'relations is not a object');
            static::assertEquals(1, $contact->relations->count(), 'one relation expected');
            static::assertTrue(is_string($contact->relations->getFirstRecord()->created_by), 'created_by resolved');

            // now we get the relations from the cache, yet we resolve them further
            $expander = new Tinebase_Record_Expander(Addressbook_Model_Contact::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    'relations' => [
                        Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                            'created_by' => [],
                        ],
                    ],
                ],
            ]);
            $expander->expand($contacts);
            static::assertTrue(is_object($contact->relations), 'relations is not a object');
            static::assertEquals(1, $contact->relations->count(), 'one relation expected');
            static::assertTrue(is_object($contact->relations->getFirstRecord()->created_by), 'created_by not resolved');

            // we get the relations again from the cache and they are resolved deeper from the previous run
            $expander = new Tinebase_Record_Expander(Addressbook_Model_Contact::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    'relations' => [],
                ],
            ]);
            $expander->expand($contacts);
            static::assertTrue(is_object($contact->relations), 'relations is not a object');
            static::assertEquals(1, $contact->relations->count(), 'one relation expected');
            static::assertTrue(is_object($contact->relations->getFirstRecord()->created_by), 'created_by not resolved');

        } finally {
            $adbController->resolveCustomfields($oldCustomfields);
        }
    }

    public function testExpandTags()
    {
        $createdContact = $this->_createAndUpdateContact();
        $adbController = Addressbook_Controller_Contact::getInstance();
        $oldCustomfields = $adbController->resolveCustomfields(false);

        try {
            $contacts = $adbController->getMultiple([$createdContact->getId()]);
            $contact = $contacts->getFirstRecord();
            static::assertTrue(empty($contact->tags), 'tags is not empty');

            // we resolve only the tags, not further
            $expander = new Tinebase_Record_Expander(Addressbook_Model_Contact::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    'tags' => [],
                ],
            ]);
            $expander->expand($contacts);
            static::assertTrue(is_object($contact->tags), 'tags is not a object');
            static::assertEquals(1, $contact->tags->count(), 'one tag expected');
            static::assertTrue(is_string($contact->tags->getFirstRecord()->created_by), 'created_by resolved');

            // tags never come from the cache btw.
            $expander = new Tinebase_Record_Expander(Addressbook_Model_Contact::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    'tags' => [
                        Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                            'created_by' => [],
                        ],
                    ],
                ],
            ]);
            $expander->expand($contacts);
            static::assertTrue(is_object($contact->tags), 'tags is not a object');
            static::assertEquals(1, $contact->tags->count(), 'one tag expected');
            static::assertTrue(is_object($contact->tags->getFirstRecord()->created_by), 'created_by not resolved');

            // we get the tags again, as they dont come from cache, they shouldn't be resolved
            $expander = new Tinebase_Record_Expander(Addressbook_Model_Contact::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    'tags' => [],
                ],
            ]);
            $expander->expand($contacts);
            static::assertTrue(is_object($contact->tags), 'tags is not a object');
            static::assertEquals(1, $contact->tags->count(), 'one tag expected');
            static::assertTrue(is_string($contact->tags->getFirstRecord()->created_by), 'created_by resolved');

        } finally {
            $adbController->resolveCustomfields($oldCustomfields);
        }
    }

    public function testExpandAttachements()
    {
        $createdContact = $this->_createAndUpdateContact();
        $adbController = Addressbook_Controller_Contact::getInstance();
        $oldCustomfields = $adbController->resolveCustomfields(false);

        try {
            $contacts = $adbController->getMultiple([$createdContact->getId()]);
            $contact = $contacts->getFirstRecord();
            static::assertNull($contact->attachments, 'attachments is not null');

            // we resolve only the attachments, not further
            $expander = new Tinebase_Record_Expander(Addressbook_Model_Contact::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    'attachments' => [],
                ],
            ]);
            $expander->expand($contacts);
            static::assertTrue(is_object($contact->attachments), 'attachments is not a object');
            static::assertEquals(1, $contact->attachments->count(), 'one attachments expected');
            static::assertTrue(is_string($contact->attachments->getFirstRecord()->created_by), 'created_by resolved');

            // attachments never come from the cache btw.
            $expander = new Tinebase_Record_Expander(Addressbook_Model_Contact::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    'attachments' => [
                        Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                            'created_by' => [],
                        ],
                    ],
                ],
            ]);
            $expander->expand($contacts);
            static::assertTrue(is_object($contact->attachments), 'attachments is not a object');
            static::assertEquals(1, $contact->attachments->count(), 'one attachments expected');
            static::assertTrue(is_object($contact->attachments->getFirstRecord()->created_by),
                'created_by not resolved');

            // we resolve only the attachments, not further
            $expander = new Tinebase_Record_Expander(Addressbook_Model_Contact::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    'attachments' => [],
                ],
            ]);
            $expander->expand($contacts);
            static::assertTrue(is_object($contact->attachments), 'attachments is not a object');
            static::assertEquals(1, $contact->attachments->count(), 'one attachments expected');
            static::assertTrue(is_string($contact->attachments->getFirstRecord()->created_by), 'created_by resolved');

        } finally {
            $adbController->resolveCustomfields($oldCustomfields);
        }
    }

    public function testExpandGetDeleted()
    {
        $supplierTest = new Sales_SuppliersTest();
        $supplierTest->publicSetUp();
        $supplier = $supplierTest->_createSupplier();

        $supplier = Sales_Controller_Supplier::getInstance()->get($supplier['id']);

        static::assertTrue(is_string($supplier->cpextern_id), 'cpextern_id is not a string');

        $suppliers = new Tinebase_Record_RecordSet(Sales_Model_Supplier::class, [$supplier]);
        $expander = new Tinebase_Record_Expander(Sales_Model_Supplier::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'cpextern_id' => [],
            ],
        ]);
        $expander->expand($suppliers);

        static::assertTrue(is_object($supplier->cpextern_id), 'cpextern_id is not a object');
        $supplier->cpextern_id = $supplier->cpextern_id->getId();


        Tinebase_Record_Expander_DataRequest::clearCache();
        Addressbook_Controller_Contact::getInstance()->delete($supplier->cpextern_id);
        $expander->expand($suppliers);
        static::assertTrue(is_string($supplier->cpextern_id), 'cpextern_id is not a string');

        $expander = new Tinebase_Record_Expander(Sales_Model_Supplier::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'cpextern_id' => [Tinebase_Record_Expander::GET_DELETED => true,],
            ],
        ]);
        $expander->expand($suppliers);

        static::assertTrue(is_object($supplier->cpextern_id), 'cpextern_id is not a object');
    }

    public function testExpandNotes()
    {
        $createdContact = $this->_createAndUpdateContact();
        $adbController = Addressbook_Controller_Contact::getInstance();
        $oldCustomfields = $adbController->resolveCustomfields(false);

        try {
            $contacts = $adbController->getMultiple([$createdContact->getId()]);
            $contact = $contacts->getFirstRecord();
            static::assertNull($contact->notes, 'notes is not null');

            // we resolve only the notes, not further
            $expander = new Tinebase_Record_Expander(Addressbook_Model_Contact::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    'notes' => [],
                ],
            ]);
            $expander->expand($contacts);
            static::assertTrue(is_object($contact->notes), 'notes is not a object');
            static::assertEquals(1, $contact->notes->count(), 'one note expected');
            static::assertTrue(is_string($contact->notes->getFirstRecord()->created_by), 'created_by resolved');

            // notes never come from the cache btw.
            $expander = new Tinebase_Record_Expander(Addressbook_Model_Contact::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    'notes' => [
                        Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                            'created_by' => [],
                        ],
                    ],
                ],
            ]);
            $expander->expand($contacts);
            static::assertTrue(is_object($contact->notes), 'notes is not a object');
            static::assertEquals(1, $contact->notes->count(), 'one note expected');
            static::assertTrue(is_object($contact->notes->getFirstRecord()->created_by),
                'created_by not resolved');

            // we resolve only the notes, not further
            $expander = new Tinebase_Record_Expander(Addressbook_Model_Contact::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    'notes' => [],
                ],
            ]);
            $expander->expand($contacts);
            static::assertTrue(is_object($contact->notes), 'notes is not a object');
            static::assertEquals(1, $contact->notes->count(), 'one note expected');
            static::assertTrue(is_string($contact->notes->getFirstRecord()->created_by), 'created_by resolved');

        } finally {
            $adbController->resolveCustomfields($oldCustomfields);
        }
    }
}