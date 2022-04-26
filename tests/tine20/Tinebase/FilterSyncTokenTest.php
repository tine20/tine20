<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for Tinebase_FilterSyncToken
 */
class Tinebase_FilterSyncTokenTest extends TestCase
{
    public function testFilterHash()
    {
        $contactFilter = new Addressbook_Model_ContactFilter([
            ['field' => 'id', 'operator' => 'equals', 'value' => 1],
        ]);
        $contactFilter1 = new Addressbook_Model_ContactFilter([
            ['field' => 'id', 'operator' => 'equals', 'value' => 1],
        ]);
        static::assertSame($contactFilter->hash(), $contactFilter1->hash());


        $eventFilter = new Calendar_Model_EventFilter([
            ['field' => 'id', 'operator' => 'equals', 'value' => 1]
        ]);
        static::assertNotSame($contactFilter->hash(), $eventFilter->hash());


        $contactFilter = new Addressbook_Model_ContactFilter([
            ['field' => 'n_given', 'operator' => 'equals', 'value' => 'a'],
            ['field' => 'id', 'operator' => 'equals', 'value' => 1],
        ]);
        $contactFilter1 = new Addressbook_Model_ContactFilter([
            ['field' => 'id', 'operator' => 'equals', 'value' => 1],
            ['field' => 'n_given', 'operator' => 'equals', 'value' => 'a'],
        ]);
        static::assertSame($contactFilter->hash(), $contactFilter1->hash());


        $contactFilter = new Addressbook_Model_ContactFilter([
            ['field' => 'n_given', 'operator' => 'equals', 'value' => 'a'],
            [
                'condition' => 'AND',
                'filters' => [
                    ['field' => 'n_given', 'operator' => 'equals', 'value' => 'a'],
                    ['field' => 'id', 'operator' => 'equals', 'value' => 1],
                ],
            ],
            ['field' => 'id', 'operator' => 'equals', 'value' => 1],
        ]);
        $contactFilter1 = new Addressbook_Model_ContactFilter([
            ['field' => 'id', 'operator' => 'equals', 'value' => 1],
            ['field' => 'n_given', 'operator' => 'equals', 'value' => 'a'],
            [
                'condition' => 'AND',
                'filters' => [
                    ['field' => 'id', 'operator' => 'equals', 'value' => 1],
                    ['field' => 'n_given', 'operator' => 'equals', 'value' => 'a'],
                ],
            ],
        ]);
        static::assertSame($contactFilter->hash(), $contactFilter1->hash());

        $contactFilter = new Addressbook_Model_ContactFilter([
            ['field' => 'n_given', 'operator' => 'equals', 'value' => 'a'],
            [
                'condition' => 'AND',
                'filters' => [
                    ['field' => 'n_given', 'operator' => 'equals', 'value' => 'a'],
                    ['field' => 'id', 'operator' => 'equals', 'value' => 1],
                ],
            ],
            ['field' => 'id', 'operator' => 'equals', 'value' => 1],
        ]);
        $contactFilter1 = new Addressbook_Model_ContactFilter([
            ['field' => 'id', 'operator' => 'equals', 'value' => 1],
            ['field' => 'n_given', 'operator' => 'equals', 'value' => 'a'],
            [
                'condition' => 'AND',
                'filters' => [
                    ['field' => 'id', 'operator' => 'equals', 'value' => 1],
                    ['field' => 'n_given', 'operator' => 'equals', 'value' => 'b'],
                ],
            ],
        ]);
        static::assertNotSame($contactFilter->hash(), $contactFilter1->hash());


        $contactFilter = new Addressbook_Model_ContactFilter([
            ['field' => 'n_given', 'operator' => 'equals', 'value' => 'a'],
            [
                'condition' => 'AND',
                'filters' => [
                    ['field' => 'n_given', 'operator' => 'equals', 'value' => 'a'],
                    ['field' => 'id', 'operator' => 'equals', 'value' => 1],
                ],
            ],
            ['field' => 'id', 'operator' => 'equals', 'value' => 1],
        ], Tinebase_Model_Filter_FilterGroup::CONDITION_OR);
        $contactFilter1 = new Addressbook_Model_ContactFilter([
            ['field' => 'id', 'operator' => 'equals', 'value' => 1],
            ['field' => 'n_given', 'operator' => 'equals', 'value' => 'a'],
            [
                'condition' => 'AND',
                'filters' => [
                    ['field' => 'id', 'operator' => 'equals', 'value' => 1],
                    ['field' => 'n_given', 'operator' => 'equals', 'value' => 'a'],
                ],
            ],
        ], Tinebase_Model_Filter_FilterGroup::CONDITION_AND);
        static::assertNotSame($contactFilter->hash(), $contactFilter1->hash());
    }


    public function testFilterSyncTokenMigration()
    {
        $containerId = Addressbook_Controller::getDefaultInternalAddressbook();
        $contactFilter = new Addressbook_Model_ContactFilter([
            'n_given' => 'filterSyncToken',
            'container_id' => $containerId
        ]);
        $syncToken = Tinebase_FilterSyncToken::getInstance();


        // get filterSyncToken for an empty result set
        $emptySyncToken = $syncToken->getFilterSyncToken($contactFilter);
        static::assertTrue(is_string($emptySyncToken));


        // create a contact and get new sync token
        $firstContact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
            'n_given'       => 'filterSyncToken',
            'n_middle'      => 'firstContact',
            'container_id'  => $containerId,
        ]));

        $oneContactSyncToken = $syncToken->getFilterSyncToken($contactFilter);
        static::assertTrue(is_string($oneContactSyncToken));
        static::assertNotSame($oneContactSyncToken, $emptySyncToken);


        // check migration with one added record
        $migration = $syncToken->getMigration($emptySyncToken, $oneContactSyncToken);
        static::assertTrue(count($migration) === 3 && isset($migration['added'])
            && isset($migration['updated']) && isset($migration['deleted']));
        static::assertSame($firstContact->getId(), $migration['added'][0]);


        // create a second & third & forth contact and get new sync token
        $secondContact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
            'n_given'       => 'filterSyncToken',
            'n_middle'      => '2ndContact',
            'container_id'  => $containerId,
        ]));
        $thirdContact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
            'n_given'       => 'filterSyncToken',
            'n_middle'      => '3rdContact',
            'container_id'  => $containerId,
        ]));
        $forthContact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
            'n_given'       => 'filterSyncToken',
            'n_middle'      => '4thContact',
            'container_id'  => $containerId,
        ]));

        $fourContactSyncToken = $syncToken->getFilterSyncToken($contactFilter);
        static::assertTrue(is_string($fourContactSyncToken));
        static::assertNotSame($oneContactSyncToken, $fourContactSyncToken);


        // check migration with three added record
        $migration = $syncToken->getMigration($oneContactSyncToken, $fourContactSyncToken);
        static::assertSame(3, count($migration['added']));
        static::assertTrue(in_array($secondContact->getId(), $migration['added']));
        static::assertTrue(in_array($thirdContact->getId(), $migration['added']));
        static::assertTrue(in_array($forthContact->getId(), $migration['added']));

        // check migration with four added record
        $migration = $syncToken->getMigration($emptySyncToken, $fourContactSyncToken);
        static::assertSame(4, count($migration['added']));


        // create 2 contacts, update 2 contacts, delete 2 contacts
        $fifthContact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
            'n_given'       => 'filterSyncToken',
            'n_middle'      => '5thContact',
            'container_id'  => $containerId,
        ]));
        $sixthContact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
            'n_given'       => 'filterSyncToken',
            'n_middle'      => '6thContact',
            'container_id'  => $containerId,
        ]));
        Addressbook_Controller_Contact::getInstance()->delete([$firstContact->getId(), $secondContact->getId()]);
        $thirdContact->email = 'a@bc.de';
        $forthContact->email = 'z@bc.de';
        Addressbook_Controller_Contact::getInstance()->update($thirdContact);
        Addressbook_Controller_Contact::getInstance()->update($forthContact);

        $sixContactSyncToken = $syncToken->getFilterSyncToken($contactFilter);
        static::assertTrue(is_string($sixContactSyncToken));
        static::assertNotSame($fourContactSyncToken, $sixContactSyncToken);


        // check migration with 2 added record, 2 updated and 2 deleted
        $migration = $syncToken->getMigration($fourContactSyncToken, $sixContactSyncToken);
        static::assertSame(2, count($migration['added']));
        static::assertTrue(in_array($fifthContact->getId(), $migration['added']));
        static::assertTrue(in_array($sixthContact->getId(), $migration['added']));
        static::assertSame(2, count($migration['updated']));
        static::assertTrue(in_array($thirdContact->getId(), $migration['updated']));
        static::assertTrue(in_array($forthContact->getId(), $migration['updated']));
        static::assertSame(2, count($migration['deleted']));
        static::assertTrue(in_array($secondContact->getId(), $migration['deleted']));
        static::assertTrue(in_array($firstContact->getId(), $migration['deleted']));
    }

    public function testBackendDeleteByAge()
    {
        $db = Tinebase_Core::getDb();
        $backend = new Tinebase_FilterSyncToken_Backend_Sql();

        $backend->create(new Tinebase_Model_FilterSyncToken([
            'filterHash' => 'abc',
            'filterSyncToken' => 'abc',
            'idLastModifiedMap' => [],
            'created' => Tinebase_DateTime::now()->subDay(2)
        ], true));
        $backend->create(new Tinebase_Model_FilterSyncToken([
            'filterHash' => 'abcd',
            'filterSyncToken' => 'abcd',
            'idLastModifiedMap' => [],
            'created' => Tinebase_DateTime::now()
        ], true));

        $stmt = $db->select()->from($backend->getTablePrefix() . $backend->getTableName(), ['count(*)']);
        static::assertSame('2', $stmt->query()->fetchColumn());

        static::assertSame(1, $backend->deleteByAge(1));
        static::assertSame('1', $stmt->query()->fetchColumn());
    }

    public function testBackendDeleteByFilterMax()
    {
        $db = Tinebase_Core::getDb();
        $backend = new Tinebase_FilterSyncToken_Backend_Sql();

        $backend->create(new Tinebase_Model_FilterSyncToken([
            'filterHash' => 'abc',
            'filterSyncToken' => 'abc',
            'idLastModifiedMap' => [],
            'created' => Tinebase_DateTime::now()->subDay(2)
        ], true));
        $survivor = $backend->create(new Tinebase_Model_FilterSyncToken([
            'filterHash' => 'abc',
            'filterSyncToken' => 'abcd',
            'idLastModifiedMap' => [],
            'created' => Tinebase_DateTime::now()
        ], true));

        $stmt = $db->select()->from($backend->getTablePrefix() . $backend->getTableName(), ['count(*)']);
        static::assertSame('2', $stmt->query()->fetchColumn());

        static::assertSame(1, $backend->deleteByFilterMax(1));
        static::assertSame('1', $stmt->query()->fetchColumn());

        // test survivor is still there:
        $backend->get($survivor->getId());
    }

    public function testBackendDeleteByMaxTotal()
    {
        $db = Tinebase_Core::getDb();
        $backend = new Tinebase_FilterSyncToken_Backend_Sql();

        $backend->create(new Tinebase_Model_FilterSyncToken([
            'filterHash' => 'abc',
            'filterSyncToken' => 'abc',
            'idLastModifiedMap' => [],
            'created' => Tinebase_DateTime::now()->subDay(2)
        ], true));
        $survivor = $backend->create(new Tinebase_Model_FilterSyncToken([
            'filterHash' => 'abc',
            'filterSyncToken' => 'abcd',
            'idLastModifiedMap' => [],
            'created' => Tinebase_DateTime::now()
        ], true));

        $stmt = $db->select()->from($backend->getTablePrefix() . $backend->getTableName(), ['count(*)']);
        static::assertSame('2', $stmt->query()->fetchColumn());

        static::assertSame(1, $backend->deleteByMaxTotal(1));
        static::assertSame('1', $stmt->query()->fetchColumn());

        // test survivor is still there:
        $backend->get($survivor->getId());
    }
}
