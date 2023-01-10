<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Json
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
class Tinebase_Frontend_JsonTest extends TestCase
{
    /**
     * unit under test (UIT)
     * @var Tinebase_Frontend_Json
     */
    protected $_instance;

    /**
     * @var array test objects
     */
    protected $_objects = array();
    
    /**
     * set up tests
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        
        $this->_instance = new Tinebase_Frontend_Json();
        
        $this->_objects['record'] = array(
            'id'        => 1,
            'model'     => 'Addressbook_Model_Contact',
            'backend'    => 'Sql',
        );

        $this->_objects['group'] = new Tinebase_Model_Group(array(
            'name'            => 'phpunit test group',
            'description'    => 'phpunit test group'
        ));

        $this->_objects['role'] = new Tinebase_Model_Role(array(
            'name'            => 'phpunit test role',
            'description'    => 'phpunit test role'
        ));

        $this->_objects['note'] = new Tinebase_Model_Note(array(
            'note_type_id'      => Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE,
            'note'              => 'phpunit test note',
            'record_model'      => $this->_objects['record']['model'],
            'record_backend'    => $this->_objects['record']['backend'],
        ));
    }
    
    /**
     * tear down
     */
    public function tearDown(): void
    {
        parent::tearDown();
        
        // reset tz in core
        Tinebase_Core::set(Tinebase_Core::USERTIMEZONE, Tinebase_Core::getPreference()->getValue(Tinebase_Preference::TIMEZONE));
    }
    
    /**
     * try to add a note type
     */
    public function testSearchNotes()
    {
        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array('n_family' => 'Schulz')));
        $note = $this->_objects['note'];
        $note->record_id = $contact->getId();
        Tinebase_Notes::getInstance()->addNote($note);

        $filter = array(array(
            'field' => 'query',
            'operator' => 'contains',
            'value' => 'phpunit'
        ), array(
            'field' => "record_model",
            'operator' => "equals",
            'value' => $this->_objects['record']['model']
        ), array(
            'field' => 'record_id',
            'operator' => 'equals',
            'value' => $contact->getId()
        ));
        $paging = array();
        
        $notes = $this->_instance->searchNotes($filter, $paging);
        
        $this->assertGreaterThan(0, $notes['totalcount']);
        $found = false;
        foreach ($notes['results'] as $note) {
            if ($this->_objects['note']->note === $note['note']) {
                $found = true;
            }
        }
        $this->assertTrue($found, 'note not found in notes: ' . print_r($notes['results'], true));
        
        // delete note
        Tinebase_Notes::getInstance()->deleteNotesOfRecord(
            $this->_objects['record']['model'],
            $this->_objects['record']['backend'],
            $contact->getId()
        );
    }
    
    /**
     * try to delete role and then search
     */
    public function testSearchRoles()
    {
        $role = Tinebase_Acl_Roles::getInstance()->createRole($this->_objects['role']);
        
        $filter = array(array(
            'field'     => 'query',
            'operator'     => 'contains',
            'value'     => 'phpunit test role'
        ));
        $paging = array(
            'start'    => 0,
            'limit'    => 1
        );
        
        $roles = $this->_instance->searchRoles($filter, $paging);
        
        $this->assertGreaterThan(0, $roles['totalcount']);
        $this->assertEquals($this->_objects['role']->name, $roles['results'][0]['name']);
        
        // delete role
        Tinebase_Acl_Roles::getInstance()->deleteRoles($role->id);
    }
    
    /**
     * test getCountryList
     *
     */
    public function testGetCountryList()
    {
        $list = $this->_instance->getCountryList();
        $this->assertTrue(count($list['results']) > 200);
    }

    public function testRestoreRevision()
    {
        if (!Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE}) {
            static::markTestSkipped('modlog not active');
        }

        Tinebase_FileSystem::getInstance()->mkdir('/Filemanager/folders/shared/unittest');
        file_put_contents('tine20:///Filemanager/folders/shared/unittest/test.txt', 'data1');
        $node1 = Tinebase_FileSystem::getInstance()->stat('Filemanager/folders/shared/unittest/test.txt');

        file_put_contents('tine20:///Filemanager/folders/shared/unittest/test.txt', 'data2');
        static::assertSame('data2', file_get_contents('tine20:///Filemanager/folders/shared/unittest/test.txt'));
        $node2 = Tinebase_FileSystem::getInstance()->stat('Filemanager/folders/shared/unittest/test.txt');

        $result = $this->_instance->restoreRevision([
            Tinebase_Model_Tree_FileLocation::FLD_TYPE      => Tinebase_Model_Tree_FileLocation::TYPE_FM_NODE,
            Tinebase_Model_Tree_FileLocation::FLD_FM_PATH   => '/shared/unittest/test.txt',
            Tinebase_Model_Tree_FileLocation::FLD_REVISION  => (int)$node2->revision - 1,
        ]);
        $node3 = Tinebase_FileSystem::getInstance()->stat('Filemanager/folders/shared/unittest/test.txt');

        static::assertSame(['success' => true], $result);
        static::assertSame($node1->hash, $node3->hash, 'hash mismatch');
        static::assertSame((int)$node1->revision + 2, (int)$node3->revision, 'revision not as expected');
        static::assertSame('data1', file_get_contents('tine20:///Filemanager/folders/shared/unittest/test.txt'));
    }

    public function testRestoreRevisionPersonal()
    {
        if (!Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE}) {
            static::markTestSkipped('modlog not active');
        }

        $folderNode = Filemanager_Controller_Node::getInstance()->createNodes('/personal/' . Tinebase_Core::getUser()
                ->accountDisplayName . '/unittest', Tinebase_Model_Tree_FileObject::TYPE_FOLDER)->getFirstRecord();

        $path = Tinebase_FileSystem::getInstance()->getPathOfNode($folderNode, true);
        file_put_contents('tine20://' . $path . '/test.txt', 'data1');
        $node1 = Tinebase_FileSystem::getInstance()->stat($path . '/test.txt');

        file_put_contents('tine20://' . $path . '/test.txt', 'data2');
        static::assertSame('data2', file_get_contents('tine20://' . $path . '/test.txt'));
        $node2 = Tinebase_FileSystem::getInstance()->stat($path . '/test.txt');

        $result = $this->_instance->restoreRevision([
            Tinebase_Model_Tree_FileLocation::FLD_TYPE      => Tinebase_Model_Tree_FileLocation::TYPE_FM_NODE,
            Tinebase_Model_Tree_FileLocation::FLD_FM_PATH   => '/personal/' . Tinebase_Core::getUser()
                    ->accountDisplayName . '/unittest/test.txt',
            Tinebase_Model_Tree_FileLocation::FLD_REVISION  => (int)$node2->revision - 1,
        ]);
        $node3 = Tinebase_FileSystem::getInstance()->stat($path . '/test.txt');

        static::assertSame(['success' => true], $result);
        static::assertSame($node1->hash, $node3->hash, 'hash mismatch');
        static::assertSame((int)$node1->revision + 2, (int)$node3->revision, 'revision not as expected');
        static::assertSame('data1', file_get_contents('tine20://' . $path . '/test.txt'));
    }

    public function testRestoreRevisionAttachment()
    {
        if (!Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE}) {
            static::markTestSkipped('modlog not active');
        }

        $recAttachTest = new Tinebase_FileSystem_RecordAttachmentsTest();
        $contact = $recAttachTest->testAddRecordAttachments();

        $path = $contact->getApplication() . '/folders' . $contact->attachments->getFirstRecord()->path;
        $node1 = Tinebase_FileSystem::getInstance()->stat($path);
        $content = file_get_contents('tine20://' . $path);

        file_put_contents('tine20://' . $path, 'data');
        static::assertSame('data', file_get_contents('tine20://' . $path));
        $node2 = Tinebase_FileSystem::getInstance()->stat($path);

        $result = $this->_instance->restoreRevision([
            Tinebase_Model_Tree_FileLocation::FLD_TYPE      => Tinebase_Model_Tree_FileLocation::TYPE_ATTACHMENT,
            Tinebase_Model_Tree_FileLocation::FLD_FILE_NAME => 'Test.txt',
            Tinebase_Model_Tree_FileLocation::FLD_RECORD_ID => $contact->getId(),
            Tinebase_Model_Tree_FileLocation::FLD_MODEL     => get_class($contact),
            Tinebase_Model_Tree_FileLocation::FLD_REVISION  => (int)$node1->revision,
        ]);
        $node3 = Tinebase_FileSystem::getInstance()->stat($path);

        static::assertSame(['success' => true], $result);
        static::assertSame($node1->hash, $node3->hash, 'hash mismatch');
        static::assertSame((int)$node1->revision + 2, (int)$node3->revision, 'revision not as expected');
        static::assertSame($content, file_get_contents('tine20://' . $path));
    }

    public function testRestoreRevisionAttachmentNodeId()
    {
        if (!Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE}) {
            static::markTestSkipped('modlog not active');
        }

        $recAttachTest = new Tinebase_FileSystem_RecordAttachmentsTest();
        $contact = $recAttachTest->testAddRecordAttachments();

        $path = $contact->getApplication() . '/folders' . $contact->attachments->getFirstRecord()->path;
        $node1 = Tinebase_FileSystem::getInstance()->stat($path);
        $content = file_get_contents('tine20://' . $path);

        file_put_contents('tine20://' . $path, 'data');
        static::assertSame('data', file_get_contents('tine20://' . $path));
        $node2 = Tinebase_FileSystem::getInstance()->stat($path);

        $result = $this->_instance->restoreRevision([
            Tinebase_Model_Tree_FileLocation::FLD_TYPE      => Tinebase_Model_Tree_FileLocation::TYPE_ATTACHMENT,
            Tinebase_Model_Tree_FileLocation::FLD_FILE_NAME => 'Test.txt',
            Tinebase_Model_Tree_FileLocation::FLD_RECORD_ID => $contact->getId(),
            Tinebase_Model_Tree_FileLocation::FLD_MODEL     => get_class($contact),
            Tinebase_Model_Tree_FileLocation::FLD_REVISION  => (int)$node1->revision,
            Tinebase_Model_Tree_FileLocation::FLD_NODE_ID   => $node1->getId(),
        ]);
        $node3 = Tinebase_FileSystem::getInstance()->stat($path);

        static::assertSame(['success' => true], $result);
        static::assertSame($node1->hash, $node3->hash, 'hash mismatch');
        static::assertSame((int)$node1->revision + 2, (int)$node3->revision, 'revision not as expected');
        static::assertSame($content, file_get_contents('tine20://' . $path));
        static::assertSame($node1->getId(), $node2->getId());
        static::assertSame($node1->getId(), $node3->getId());
    }

    public function testRestoreRevisionNodeIdFail()
    {
        if (!Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE}) {
            static::markTestSkipped('modlog not active');
        }

        Tinebase_FileSystem::getInstance()->mkdir('/Filemanager/folders/shared/unittest');
        file_put_contents('tine20:///Filemanager/folders/shared/unittest/test.txt', 'data1');
        $node1 = Tinebase_FileSystem::getInstance()->stat('Filemanager/folders/shared/unittest/test.txt');

        file_put_contents('tine20:///Filemanager/folders/shared/unittest/test.txt', 'data2');
        static::assertSame('data2', file_get_contents('tine20:///Filemanager/folders/shared/unittest/test.txt'));
        $node2 = Tinebase_FileSystem::getInstance()->stat('Filemanager/folders/shared/unittest/test.txt');

        $this->expectException(Tinebase_Exception_UnexpectedValue::class);
        $this->expectExceptionMessage(Tinebase_Model_Tree_FileLocation::FLD_FM_PATH . ' and ' .
            Tinebase_Model_Tree_FileLocation::FLD_NODE_ID . ' mismatch');

        $this->_instance->restoreRevision([
            Tinebase_Model_Tree_FileLocation::FLD_TYPE      => Tinebase_Model_Tree_FileLocation::TYPE_FM_NODE,
            Tinebase_Model_Tree_FileLocation::FLD_FM_PATH   => '/shared/unittest/test.txt',
            Tinebase_Model_Tree_FileLocation::FLD_REVISION  => (int)$node2->revision - 1,
            Tinebase_Model_Tree_FileLocation::FLD_NODE_ID   => 'shooo',
        ]);
    }
    
    /**
     * test get translations
     *
     */
    public function testGetAvailableTranslations()
    {
        $list = $this->_instance->getAvailableTranslations();
        $this->assertTrue(count($list['results']) > 3);
    }
    
    /**
     * tests locale fallback
     */
    public function testSetLocaleFallback()
    {
        // de_LU -> de
        $this->_instance->setLocale('de_LU', FALSE, FALSE);
        $this->assertEquals('de', (string)Zend_Registry::get('locale'), 'Fallback to generic german did not succeed');
        
        $this->_instance->setLocale('zh', FALSE, FALSE);
        $this->assertEquals('zh_CN', (string)Zend_Registry::get('locale'), 'Fallback to simplified chinese did not succeed');
        
        $this->_instance->setLocale('foo_bar', FALSE, FALSE);
        $this->assertEquals('en', (string)Zend_Registry::get('locale'), 'Exception fallback to english did not succeed');
    }
    
    /**
     * test set locale and save it in db
     */
    public function testSetLocaleAsPreference()
    {
        $oldPreference = Tinebase_Core::getPreference()->{Tinebase_Preference::LOCALE};
        
        $locale = 'de';
        $result = $this->_instance->setLocale($locale, TRUE, FALSE);
        
        // get config setting from db
        $preference = Tinebase_Core::getPreference()->{Tinebase_Preference::LOCALE};
        $this->assertEquals($locale, $preference, "Didn't get right locale preference.");
        
        // restore old setting
        Tinebase_Core::getPreference()->{Tinebase_Preference::LOCALE} = $oldPreference;
    }

    /**
     * test set timezone and save it in db
     */
    public function testSetTimezoneAsPreference()
    {
        $oldPreference = Tinebase_Core::getPreference()->{Tinebase_Preference::TIMEZONE};
        
        $timezone = 'America/Vancouver';
        $result = $this->_instance->setTimezone($timezone, true);
        
        // check json result
        $this->assertEquals($timezone, $result);
        
        // get config setting from db
        $preference = Tinebase_Core::getPreference()->{Tinebase_Preference::TIMEZONE};
        $this->assertEquals($timezone, $preference, "Didn't get right timezone preference.");
        
        // restore old settings
        Tinebase_Core::set(Tinebase_Core::USERTIMEZONE, $oldPreference);
        Tinebase_Core::getPreference()->{Tinebase_Preference::TIMEZONE} = $oldPreference;
    }

    /**
     * toogle advanced search preference
     */
    public function testAdvancedSearchToogle()
    {
        $toogle = $this->_instance->toogleAdvancedSearch(1);

        $this->assertEquals($toogle, 1);
        $this->assertEquals(Tinebase_Core::getPreference()->getValue(Tinebase_Preference::ADVANCED_SEARCH, 0), 1);
    }

    /**
     * search preferences by application
     *
     */
    public function testSearchPreferences()
    {
        // search prefs
        $result = $this->_instance->searchPreferencesForApplication('Tinebase', $this->_getPreferenceFilter());
        
        // check results
        $this->assertTrue(isset($result['results']));
        $this->assertGreaterThan(2, $result['totalcount']);
        
        //check locale/timezones options
        foreach ($result['results'] as $pref) {
            switch($pref['name']) {
                case Tinebase_Preference::LOCALE:
                    $this->assertGreaterThan(10, count($pref['options']));
                    break;
                case Tinebase_Preference::TIMEZONE:
                    $this->assertGreaterThan(100, count($pref['options']));
                    break;
            }
            // check label and description
            $this->assertTrue(isset($pref['label']) && !empty($pref['label']));
            $this->assertTrue(isset($pref['description']) && !empty($pref['description']));
        }
    }

    /**
     * search preferences by application
     *
     */
    public function testSearchPreferencesWithOptions()
    {
        // add new default pref
        $pref = $this->_getPreferenceWithOptions();
        $pref = Tinebase_Core::getPreference()->create($pref);
        
        // search prefs
        $results = $this->_instance->searchPreferencesForApplication('Tinebase', $this->_getPreferenceFilter());
        
        // check results
        $this->assertTrue(isset($results['results']));
        $this->assertGreaterThan(3, $results['totalcount']);
        
        foreach ($results['results'] as $result) {
            if ($result['name'] == 'defaultapp') {
                $this->assertEquals(Tinebase_Model_Preference::DEFAULT_VALUE, $result['value']);
                $this->assertTrue(is_array($result['options']));
                // ignore DB options. NOTE: if we need them we could introduce a flag to keep db options
                $this->assertGreaterThan(3, count($result['options']));
            } else if ($result['name'] == Tinebase_Preference::TIMEZONE) {
                $this->assertTrue(is_array($result['options'][0]), 'options should be arrays');
            }
        }
        
        Tinebase_Core::getPreference()->delete($pref);
    }
    
    /**
     * search preferences of another user
     *
     * @todo add check for the case that searching user has no admin rights
     */
    public function testSearchPreferencesOfOtherUsers()
    {
        // add new default pref
        $pref = $this->_getPreferenceWithOptions();
        $pref->account_id   = '2';
        $pref->account_type = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER;
        $pref = Tinebase_Core::getPreference()->create($pref);
        
        // search prefs
        $filters = $this->_getPreferenceFilter(TRUE, FALSE, '2');
        $results = $this->_instance->searchPreferencesForApplication('Tinebase', $filters);
        
        // check results
        $this->assertTrue(isset($results['results']));
        $this->assertEquals(1, $results['totalcount']);

        $sclever = Tinebase_Helper::array_value('sclever',Zend_Registry::get('personas'));
        $filters = $this->_getPreferenceFilter(false, FALSE, $sclever->getId());
        $results1 = $this->_instance->searchPreferencesForApplication('ActiveSync', $filters, $sclever->getId());
        
        $filters = $this->_getPreferenceFilter(false, FALSE, Tinebase_Core::getUser()->getId());
        $results2 = $this->_instance->searchPreferencesForApplication('ActiveSync', $filters, Tinebase_Core::getUser()->getId());

        // check results
        $this->assertNotEquals($results2['results'][0]['options'], $results1['results'][0]['options'], 'the options of user application preferences should be different');
    }

    /**
     * search preferences of another user
     *
     * @todo add check for the case that searching user has no admin rights
     */
    public function testSearchPreferencesOfOtherUsersApp()
    {
        $this->_skipWithoutEmailSystemAccountConfig();

        // add new default pref
        $pref = $this->_getPreferenceWithOptions();
        $pref->account_id   = '2';
        $pref->account_type = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER;
        Tinebase_Core::getPreference()->create($pref);

        $sclever = Tinebase_Helper::array_value('sclever',Zend_Registry::get('personas'));
        
        // search prefs
        $filters =  array(
            array(
                'field' => 'account',
                'operator' => 'equals',
                'value' => array(
                    'accountId'     => $sclever->getId(),
                    'accountType'   => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                )
            )
        );
        
        $results = $this->_instance->searchPreferencesForApplication('Addressbook', $filters);

        // check results
        $this->assertTrue(isset($results['results']));
        $this->assertEquals(4, $results['totalcount']);

        $this->assertEquals( 'user', $results['results'][0]['account_type']);

        $results = $this->_instance->searchPreferencesForApplication('Felamimail', $filters);

        // check results
        $this->assertTrue(isset($results['results']));
        $this->assertEquals(7, $results['totalcount']);

        $this->assertEquals( 'user', $results['results'][0]['account_type']);
    }
    
    
    
    /**
     * save preferences for user
     *
     */
    public function testSavePreferences()
    {
        $prefData = $this->_getUserPreferenceData();
        $this->_instance->savePreferences($prefData, null);

        // search saved prefs
        $results = $this->_instance->searchPreferencesForApplication('Tinebase', $this->_getPreferenceFilter(FALSE));
        
        // check results
        $this->assertTrue(isset($results['results']));
        $this->assertGreaterThan(2, $results['totalcount']);
        
        foreach ($results['results'] as $result) {
            $this->assertTrue(is_array($result['options']), 'options missing');
            switch ($result['name']) {
                case 'timezone':
                    $this->assertEquals('Europe/Amsterdam', $result['value']);
                    break;
                case 'locale':
                    $this->assertEquals('de', $result['value']);                   
                    break;
                case 'defaultapp':
                    $this->assertEquals('Timetracker', $result['value']);                    
                    break;
                
            }
            $savedPrefData['Tinebase'][$result['name']] = array('value' => $result['value']);
        }
    }

    /**
     * save preferences for user
     *
     * @todo add test for saving of other users prefs and acl check
     */
    public function testSavePreferencesOtherUser()
    {
        $sclever = Tinebase_Helper::array_value('sclever',Zend_Registry::get('personas'));
        $prefData = $this->_getUserPreferenceData();
        $this->_instance->savePreferences($prefData, $sclever['accountId']);

        // search saved other user prefs
        $sclever = Tinebase_Helper::array_value('sclever',Zend_Registry::get('personas'));
        $filters = $this->_getPreferenceFilter(false, FALSE, $sclever->getId());
        $results = $this->_instance->searchPreferencesForApplication('Tinebase', $filters);

        // check results
        $this->assertTrue(isset($results['results']));
        $this->assertGreaterThan(2, $results['totalcount']);

        $savedPrefData = array();
        foreach ($results['results'] as $result) {
            $savedPrefData['Tinebase'][$result['name']] = array('value' => $result['value']);

            if ($result['name'] === 'timezone') {
                $this->assertTrue(is_array($result['options']), 'options missing');
                $this->assertGreaterThan(100, count($result['options']));
            }
        }
        $this->assertEquals($prefData['Tinebase']['locale'], $savedPrefData['Tinebase']['locale']);
        $this->assertEquals($prefData['Tinebase']['timezone'], $savedPrefData['Tinebase']['timezone']);
        $this->assertEquals($prefData['Tinebase']['defaultapp'], $savedPrefData['Tinebase']['defaultapp']);

        // save without account manage right
        try {
            $this->_originalRoleRights = $this->_removeRoleRight('Admin', Admin_Acl_Rights::MANAGE_ACCOUNTS, true);
            $this->_instance->savePreferences($prefData, $sclever['accountId']);
            self::fail('should throw Tinebase_Exception_AccessDenied');
        } catch (Tinebase_Exception_AccessDenied $e) {
            self::assertEquals("No permission to edit other user preferences !", $e->getMessage());
        }
    }
    
    /**
     * get locale pref
     */
    protected function _getLocalePref()
    {
        $results = $this->_instance->searchPreferencesForApplication('Tinebase', $this->_getPreferenceFilter());
        foreach ($results['results'] as $result) {
            if ($result['name'] == Tinebase_Preference::LOCALE) {
                $locale = $result;
            }
        }
        
        $this->assertTrue(isset($locale));
        
        return $locale;
    }

    /**
     * get admin prefs
     */
    public function testGetAdminPreferences()
    {
        // set new default locale
        $afj = new Admin_Frontend_Json();
        $locale = $this->_getLocalePref();
        $prefData['Tinebase'][$locale['id']] = array('value' => 'de', 'type' => 'default', 'name' => Tinebase_Preference::LOCALE);
        $afj->savePreferences($prefData);
        
        // check as admin
        $results = $this->_instance->searchPreferencesForApplication('Tinebase', $this->_getPreferenceFilter(FALSE, TRUE));
        foreach ($results['results'] as $pref) {
            if ($pref['name'] !== Tinebase_Preference::LOCALE) {
                $this->assertEquals(Tinebase_Model_Preference::DEFAULT_VALUE, $pref['value']);
            } else {
                $this->assertEquals(Tinebase_Model_Preference::TYPE_ADMIN, $pref['type']);
                $this->assertEquals('de', $pref['value'], print_r($pref, TRUE));
            }
        }

        // check as user
        $locale = $this->_getLocalePref();
        $this->assertEquals(Tinebase_Model_Preference::TYPE_ADMIN, $locale['type'], 'pref should be of type admin: ' . print_r($locale, TRUE));
        $this->assertEquals(Tinebase_Model_Preference::DEFAULT_VALUE, $locale['value'], 'pref should be default value: ' . print_r($locale, TRUE));
    }
    
    /**
     * save state and load it with registry data
     */
    public function testSaveAndGetState()
    {
        $testData = array(
            'bla'   => 'blubb',
            'zzing' => 'zzang'
        );
        
        foreach ($testData as $key => $value) {
            Tinebase_State::getInstance()->setState($key, $value);
        }
        
        $stateInfo = Tinebase_State::getInstance()->loadStateInfo();
        
        $this->assertEquals($testData, $stateInfo);
    }
    
    /**
     * test get all registry data
     *
     * @return void
     *
     * @see 0007934: change pw button active even if it is not allowed
     * @see 0008310: apps should be sorted the other way round in menu
     * @see 0009130: Can't open login page on Ubuntu "due to a temporary overloading"
     * @see 0012188: add copyOmitFields to modelconfig
     * @see 0012364: generalize import/export and allow to configure via modelconfig
     */
    public function testGetAllRegistryData()
    {
        $registryData = $this->_instance->getAllRegistryData();
        $currentUser = Tinebase_Core::getUser();

        self::assertTrue(isset($registryData['Tinebase']['currentAccount']), 'currentAccount is missing: '
            . print_r($registryData['Tinebase'], true));
        self::assertEquals($currentUser->toArray(), $registryData['Tinebase']['currentAccount']);
        self::assertEquals(
            Addressbook_Controller_Contact::getInstance()->getContactByUserId($currentUser->getId())->toArray(),
            $registryData['Tinebase']['userContact']
        );
        self::assertEquals(TRUE, $registryData['Tinebase']['config']['changepw']['value'], 'changepw should be TRUE');
        
        Tinebase_Config::getInstance()->set('changepw', 0);
        $registryData = $this->_instance->getAllRegistryData();
        $changepwValue = $registryData['Tinebase']['config']['changepw']['value'];
        self::assertEquals(FALSE, $changepwValue, 'changepw should be (bool) false');
        self::assertTrue(is_bool($changepwValue), 'changepw should be (bool) false: ' . var_export($changepwValue, TRUE));
        
        $userApps = $registryData['Tinebase']['userApplications'];
        self::assertEquals('Tinebase', $userApps[0]['name'], 'first app should be Tinebase: ' . print_r($userApps, TRUE));
        
        $locale = Tinebase_Core::getLocale();
        $symbols = Zend_Locale::getTranslationList('symbols', $locale);
        self::assertEquals($symbols['decimal'], $registryData['Tinebase']['decimalSeparator']);

        if (Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_INVOICES_MODULE)) {
            $configuredSalesModels = array_keys($registryData['Sales']['models']);
            self::assertTrue(in_array('Invoice', $configuredSalesModels), 'Invoices is missing from configured models: '
                . print_r($configuredSalesModels, true));
            $copyOmitFields = array(
                'billed_in',
                'invoice_id',
                'status',
                'cleared_at',
                'relations',
            );
        } else {
            $copyOmitFields = array(
                'billed_in',
                'status',
                'cleared_at',
                'relations',
            );
        }

        self::assertTrue(isset($registryData['Timetracker']['models']['Timeaccount']['copyOmitFields']), 'Timeaccount copyOmitFields empty/missing');
        self::assertEquals($copyOmitFields, $registryData['Timetracker']['models']['Timeaccount']['copyOmitFields']);
        self::assertTrue(is_array(($registryData['Timetracker']['relatableModels'][0])), 'relatableModels needs to be an numbered array');

        $this->_assertImportExportDefinitions($registryData);

        self::assertTrue(isset($registryData['Felamimail']['models']['Account']), 'account model missing from registry');

        try {
            // check alias dispatch flag
            $plugin = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP);
            self::assertTrue(isset($registryData['Tinebase']['smtpAliasesDispatchFlag']), 'smtpAliasesDispatchFlag missing from registry');
            self::assertEquals($plugin instanceof Tinebase_EmailUser_Smtp_Postfix || $plugin instanceof Tinebase_EmailUser_Smtp_PostfixMultiInstance,
                $registryData['Tinebase']['smtpAliasesDispatchFlag'], 'smtpAliasesDispatchFlag is not correct');
        } catch (Tinebase_Exception_NotFound $tenf) {
            // no smtp config found
        }

        self::assertSame(60, $registryData['Tinebase']['serviceMap']['services']['Sales.createPaperSlip']['apiTimeout']);

        self::assertLessThan(2500000, strlen(json_encode($registryData)), 'registry size got too big');
    }

    protected function _assertImportExportDefinitions($registryData)
    {
        // Inventory
        self::assertTrue(isset($registryData['Inventory']['models']['InventoryItem']['export']), 'no InventoryItem export config found: '
            . print_r($registryData['Inventory']['models']['InventoryItem'], true));
        $export = $registryData['Inventory']['models']['InventoryItem']['export'];
        self::assertTrue(isset($export['supportedFormats']));
        self::assertEquals(array('csv', 'ods'), $export['supportedFormats']);
        self::assertTrue(isset($registryData['Inventory']['models']['InventoryItem']['import']));

        // Calendar/Addressbook exportDefinitions
        foreach ([
            ['app' => 'Calendar', 'name' => 'cal_default_vcalendar_report'],
            ['app' => 'Addressbook', 'name' => 'adb_default_vcard_report'],
         ] as $definition) {
            self::assertTrue(isset($registryData[$definition['app']]['exportDefinitions']), 'no exportDefinitions export config found: '
                . print_r($registryData[$definition['app']], true));
            $exports = $registryData[$definition['app']]['exportDefinitions']['results'];
            $filteredExports = array_filter($exports, function ($export) use ($definition) {
                return $export['name'] === $definition['name'];
            });
            self::assertEquals(1, count($filteredExports), 'report definition not found');
            $reportDefinition = array_pop($filteredExports);
            // var_export($reportDefinition);
            self::assertEquals('report', $reportDefinition['scope'], 'scope mismatch');
            self::assertTrue(is_array($reportDefinition['plugin_options_json']));
        }
    }

    /**
     * test get all registry data with persistent filters
     * 
     * @return void
     * 
     * @see 0009610: shared favorites acl
     */
    public function testGetAllPersistentFilters()
    {
        $this->markTestSkipped('@see 0010192: fix persistent filter tests');
        
        $registryData = $this->_instance->getAllRegistryData();
        
        $filterData = $registryData['Tinebase']['persistentFilters'];
        $this->assertTrue($filterData['totalcount'] > 10);
        $this->assertTrue(isset($filterData['results'][0]['grants']), 'grants are missing');
        $grants = $filterData['results'][0]['grants'];
        $this->assertTrue($grants[0]['readGrant']);
        
        // check if accounts are resolved
        $this->assertTrue(is_array($grants[0]['account_name']), 'account should be resolved: ' . print_r($grants[0], true));
    }
    
    /**
     * testGetUserProfile
     */
    public function testGetUserProfile()
    {
        $this->_originalRoleRights = $this->_removeRoleRight('Tinebase', Tinebase_Acl_Rights_Abstract::ADMIN);
        $profile = $this->_instance->getUserProfile(Tinebase_Core::getUser()->getId());

        $this->assertTrue(is_array($profile));
        $this->assertTrue((isset($profile['userProfile']) || array_key_exists('userProfile', $profile)));
        $this->assertTrue(is_array($profile['userProfile']));
        $this->assertTrue((isset($profile['readableFields']) || array_key_exists('readableFields', $profile)));
        $this->assertTrue(is_array($profile['readableFields']));
        $this->assertTrue((isset($profile['updateableFields']) || array_key_exists('updateableFields', $profile)));
        $this->assertTrue(is_array($profile['updateableFields']));
        
        // try to get user profile of different user
        $this->expectException('Tinebase_Exception_AccessDenied');
        
        $sclever = Tinebase_Helper::array_value('sclever',Zend_Registry::get('personas'));
        $this->_instance->getUserProfile($sclever->getId());
    }
    
    /**
     * testGetUserProfileConfig
     */
    public function testGetUserProfileConfig()
    {
        $config = $this->_instance->getUserProfileConfig();
        
        $this->assertTrue(is_array($config));
        $this->assertTrue((isset($config['possibleFields']) || array_key_exists('possibleFields', $config)));
        $this->assertTrue(is_array($config['possibleFields']));
        $this->assertTrue((isset($config['readableFields']) || array_key_exists('readableFields', $config)));
        $this->assertTrue(is_array($config['readableFields']));
        $this->assertTrue((isset($config['updateableFields']) || array_key_exists('updateableFields', $config)));
        $this->assertTrue(is_array($config['updateableFields']));
    }
    
    /**
     * testSetUserProfileConfig
     */
    public function testSetUserProfileConfig()
    {
        $config = $this->_instance->getUserProfileConfig();
        
        $idx = array_search('n_prefix', $config['readableFields']);
        if ($idx !== false) {
            unset ($config['readableFields'][$idx]);
        }
        
        $idx = array_search('tel_home', $config['updateableFields']);
        if ($idx !== false) {
            unset ($config['updateableFields'][$idx]);
        }
        
        $this->_instance->setUserProfileConfig($config);
    }

    public function testCopyNodes()
    {
        $record = (new Tinebase_FileSystem_RecordAttachmentsTest())->testAddRecordAttachments(false);
        $fs = Tinebase_FileSystem::getInstance();
        $fs->mkdir('/Filemanager/folders/shared/unittest');

        $result = $this->_instance->copyNodes([
            (new Tinebase_Model_Tree_FileLocation([
                Tinebase_Model_Tree_FileLocation::FLD_TYPE => Tinebase_Model_Tree_FileLocation::TYPE_ATTACHMENT,
                Tinebase_Model_Tree_FileLocation::FLD_MODEL => get_class($record),
                Tinebase_Model_Tree_FileLocation::FLD_RECORD_ID => $record->getId(),
                Tinebase_Model_Tree_FileLocation::FLD_FILE_NAME => $record->attachments->getFirstRecord()->name,
            ]))->toArray(),
        ], (new Tinebase_Model_Tree_FileLocation([
            Tinebase_Model_Tree_FileLocation::FLD_TYPE => Tinebase_Model_Tree_FileLocation::TYPE_FM_NODE,
            Tinebase_Model_Tree_FileLocation::FLD_NODE_ID => $fs->stat('/Filemanager/folders/shared/unittest')->getId(),
            Tinebase_Model_Tree_FileLocation::FLD_FM_PATH => '/shared/unittest',
        ]))->toArray());

        $this->assertCount(1, $result, print_r($result, true));

        return $record;
    }

    public function testCopyNodesNewName()
    {
        $record = (new Tinebase_FileSystem_RecordAttachmentsTest())->testAddRecordAttachments(false);
        $fs = Tinebase_FileSystem::getInstance();
        $fs->mkdir('/Filemanager/folders/shared/unittest');

        $result = $this->_instance->copyNodes([
            (new Tinebase_Model_Tree_FileLocation([
                Tinebase_Model_Tree_FileLocation::FLD_TYPE => Tinebase_Model_Tree_FileLocation::TYPE_ATTACHMENT,
                Tinebase_Model_Tree_FileLocation::FLD_MODEL => get_class($record),
                Tinebase_Model_Tree_FileLocation::FLD_RECORD_ID => $record->getId(),
                Tinebase_Model_Tree_FileLocation::FLD_FILE_NAME => $record->attachments->getFirstRecord()->name,
            ]))->toArray(),
        ], (new Tinebase_Model_Tree_FileLocation([
            Tinebase_Model_Tree_FileLocation::FLD_TYPE => Tinebase_Model_Tree_FileLocation::TYPE_FM_NODE,
            Tinebase_Model_Tree_FileLocation::FLD_NODE_ID => $fs->stat('/Filemanager/folders/shared/unittest')->getId(),
            Tinebase_Model_Tree_FileLocation::FLD_FM_PATH => '/shared/unittest',
            Tinebase_Model_Tree_FileLocation::FLD_FILE_NAME => 'newName.file',
        ]))->toArray());

        $this->assertCount(1, $result, print_r($result, true));
        $this->assertSame('newName.file', $result[0]['name']);

        return $record;
    }

    public function testCopyNodesForceOverwrite()
    {
        $record = $this->testCopyNodes();
        $fs = Tinebase_FileSystem::getInstance();

        $result = $this->_instance->copyNodes([
            (new Tinebase_Model_Tree_FileLocation([
                Tinebase_Model_Tree_FileLocation::FLD_TYPE => Tinebase_Model_Tree_FileLocation::TYPE_ATTACHMENT,
                Tinebase_Model_Tree_FileLocation::FLD_MODEL => get_class($record),
                Tinebase_Model_Tree_FileLocation::FLD_RECORD_ID => $record->getId(),
                Tinebase_Model_Tree_FileLocation::FLD_FILE_NAME => $record->attachments->getFirstRecord()->name,
            ]))->toArray(),
        ], (new Tinebase_Model_Tree_FileLocation([
            Tinebase_Model_Tree_FileLocation::FLD_TYPE => Tinebase_Model_Tree_FileLocation::TYPE_FM_NODE,
            Tinebase_Model_Tree_FileLocation::FLD_NODE_ID => $fs->stat('/Filemanager/folders/shared/unittest')->getId(),
            Tinebase_Model_Tree_FileLocation::FLD_FM_PATH => '/shared/unittest',
        ]))->toArray(), true);

        $this->assertCount(1, $result, print_r($result, true));
    }

    public function testCopyNodesForceOverwriteException()
    {
        $record = $this->testCopyNodes();
        $fs = Tinebase_FileSystem::getInstance();

        $this->expectException(Tinebase_Exception_UnexpectedValue::class);
        $this->expectExceptionMessage('Destination file exists. Please remove before.');

        $this->_instance->copyNodes([
            (new Tinebase_Model_Tree_FileLocation([
                Tinebase_Model_Tree_FileLocation::FLD_TYPE => Tinebase_Model_Tree_FileLocation::TYPE_ATTACHMENT,
                Tinebase_Model_Tree_FileLocation::FLD_MODEL => get_class($record),
                Tinebase_Model_Tree_FileLocation::FLD_RECORD_ID => $record->getId(),
                Tinebase_Model_Tree_FileLocation::FLD_FILE_NAME => $record->attachments->getFirstRecord()->name,
            ]))->toArray(),
        ], (new Tinebase_Model_Tree_FileLocation([
            Tinebase_Model_Tree_FileLocation::FLD_TYPE => Tinebase_Model_Tree_FileLocation::TYPE_FM_NODE,
            Tinebase_Model_Tree_FileLocation::FLD_NODE_ID => $fs->stat('/Filemanager/folders/shared/unittest')->getId(),
            Tinebase_Model_Tree_FileLocation::FLD_FM_PATH => '/shared/unittest',
        ]))->toArray());
    }
    
    /**
     * testupdateUserProfile
     */
    public function testUpdateUserProfile()
    {
        $profile = $this->_instance->getUserProfile(Tinebase_Core::getUser()->getId());
        $profileData = $profile['userProfile'];
        
        $this->assertFalse(array_search('n_prefix', $profileData));
        
        $profileData['tel_home'] = 'mustnotchange';
        $profileData['email_home'] = 'email@userprofile.set';

        try {
            $this->_instance->updateUserProfile($profileData);
        } catch (Exception $e) {
            self::fail($e . ' profileData: ' . print_r($profileData, true));
        }
        
        $updatedProfile = $this->_instance->getUserProfile(Tinebase_Core::getUser()->getId());
        $updatedProfileData = $updatedProfile['userProfile'];
        $this->assertNotEquals('mustnotchange', $updatedProfileData['tel_home']);
        $this->assertEquals('email@userprofile.set', $updatedProfileData['email_home']);
    }
    
    /**
     * testGetSaveApplicationConfig
     */
    public function testGetSaveApplicationConfig()
    {
        $config = $this->_instance->getConfig('Admin');
        $this->assertGreaterThan(0, count($config));
        
        $data = array(
            'id'        => 'Admin',
            'settings'  => Admin_Controller::getInstance()->getConfigSettings(),
        );
        
        $newConfig = $this->_instance->saveConfig($data);
        
        $this->assertEquals($config, $newConfig);
    }
    
    /**
     * testChangeUserAccount
     * 
     * @see 0009984: allow to change user role
     */
    public function testChangeUserAccount()
    {
        // allow test user to sign in as sclever
        Tinebase_Config::getInstance()->set(Tinebase_Config::ROLE_CHANGE_ALLOWED, new Tinebase_Config_Struct(array(
            Tinebase_Core::getUser()->accountLoginName => array('sclever')
        )));
        
        $sclever = $this->_personas['sclever'];
        $result = $this->_instance->changeUserAccount('sclever');
        
        $this->assertEquals(array('success' => true), $result);
        
        // make sure, we are sclever
        $this->assertEquals('sclever', Tinebase_Core::getUser()->accountLoginName);
        $this->assertEquals('sclever', Tinebase_Session::getSessionNamespace()->currentAccount->accountLoginName);
        
        // reset to original user
        Tinebase_Controller::getInstance()->initUser($this->_originalTestUser, /* $fixCookieHeader = */ false);
        Tinebase_Session::getSessionNamespace()->userAccountChanged = false;
    }
    
    /**
     * testOmitPersonalTagsOnSearch
     * 
     * @see 0010732: add "use personal tags" right to all applications
     */
    public function testOmitPersonalTagsOnSearch()
    {
        $personalTag = $this->_getTag(Tinebase_Model_Tag::TYPE_PERSONAL);
        Tinebase_Tags::getInstance()->createTag($personalTag);
        
        $this->_removeRoleRight('Addressbook', Tinebase_Acl_Rights::USE_PERSONAL_TAGS);
        $filter = array(
            'application' => 'Addressbook',
            'grant' => Tinebase_Model_TagRight::VIEW_RIGHT,
            'type' => Tinebase_Model_Tag::TYPE_PERSONAL
        );
        $result = $this->_instance->searchTags($filter, array());
        
        $this->assertEquals(0, $result['totalCount']);
    }

    public function testGetTerminationDeadline()
    {
        $now = Tinebase_DateTime::now()->setTime(0, 0, 0);
        $expected = new Tinebase_DateTime('2018-10-01 00:00:00');
        while ($expected < $now) $expected->addYear(1);
        
        $result = $this->_instance->getTerminationDeadline('2017-01-01 00:00:00', 12, 12 ,3 ,0, new Tinebase_DateTime('2017-11-1'));
        self::assertTrue(isset($result['terminationDeadline']));
        self::assertEquals($expected->toString(), $result['terminationDeadline']);
    }

    /******************** protected helper funcs ************************/
    
    /**
     * get preference filter
     *
     * @param bool $_savedPrefs
     * @return array
     */
    public function _getPreferenceFilter($_savedPrefs = FALSE, $_adminPrefs = FALSE, $_userId = NULL)
    {
        if ($_userId === NULL) {
            $_userId = Tinebase_Core::getUser()->getId();
        }
        
        $result = array(
            array(
                'field' => 'account',
                'operator' => 'equals',
                'value' => array(
                    'accountId'     => ($_adminPrefs) ? 0 : $_userId,
                    'accountType'   => ($_adminPrefs)
                        ? Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE
                        : Tinebase_Acl_Rights::ACCOUNT_TYPE_USER
                )
            )
        );

        if ($_savedPrefs) {
            $result[] = array(
                'field' => 'name',
                'operator' => 'contains',
                'value' => 'defaultapp'
            );
        }
        
        return $result;
    }

    /**
     * get preference data for testSavePreferences()
     *
     * @return array
     */
    protected function _getUserPreferenceData()
    {
        return array(
            'Tinebase' => array(
                'timezone' => array('value' => 'Europe/Amsterdam'),
                'locale' => array('value' => 'de'),
                'defaultapp' => array('value' => 'Timetracker'),
            )
        );
    }
    
    /**
     * get preference with options
     *
     * @return Tinebase_Model_Preference
     */
    public function _getPreferenceWithOptions()
    {
        return new Tinebase_Model_Preference(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
            'name'              => 'defaultapp',
            'value'             => 'value1',
            'account_id'        => '0',
            'account_type'      => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
            'type'              => Tinebase_Model_Preference::TYPE_ADMIN,
            'options'           => '<?xml version="1.0" encoding="UTF-8"?>
                <options>
                    <option>
                        <label>option1</label>
                        <value>value1</value>
                    </option>
                    <option>
                        <label>option2</label>
                        <value>value2</value>
                    </option>
                </options>'
        ));
    }

    /**
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function testSearchPaths()
    {
        $result = null;
        try {
            $result = $this->_instance->searchPaths([
                ['field' => 'shadow_path', 'operator' => 'contains', 'value' => $this->_personas['sclever']->contact_id],
                ['field' => 'shadow_path', 'operator' => 'contains', 'value' => ''],
            ]);
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            static::assertFalse(Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_SEARCH_PATH),
                'paths are active, yet an exception was thrown');
            return;
        }

        static::assertTrue(Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_SEARCH_PATH),
            'paths are not active, but no exception was thrown');

        static::assertEquals(3, count($result));
        static::assertGreaterThan(0, count($result['results']));
        // the empty filter gets removed
        static::assertEquals(1, count($result['filter']));

        try {
            $result = $this->_instance->searchPaths([
                ['field' => 'shadow_path', 'operator' => 'contains', 'value' => ''],
            ]);
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            static::fail('first time no exception was thrown, but second time one was thrown');
        }
        static::assertEquals(3, count($result));
        static::assertGreaterThan(0, count($result['results']));

        try {
            $result = $this->_instance->searchPaths([
                ['field' => 'query', 'operator' => 'contains', 'value' => 'Clever'],
            ]);
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            static::fail('first time no exception was thrown, but third time one was thrown');
        }
        static::assertEquals(3, count($result));
        static::assertGreaterThan(0, count($result['results']));
        static::assertEquals('query', $result['filter'][0]['field']);

        try {
            $result = $this->_instance->searchPaths([
                [
                    "condition" => "OR",
                    "filters" => [
                        [
                            "condition" => "AND",
                            "filters" => [
                                [
                                    "field" => "query",
                                    "operator" => "contains",
                                    "value" => ""
                                ]]
                        ]
                    ],[
                        "field" => "query",
                        "operator" => "contains",
                        "value" => ""]]]);
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            static::fail('first time no exception was thrown, but forth time one was thrown');
        }
        static::assertEquals(3, count($result));
        static::assertGreaterThan(0, count($result['results']));
        // the empty filter gets removed
        static::assertEquals(1, count($result['filter']));
    }

    /**
     * this test doesnt test anything really useful
     * it heavily depends on config and fails on github (because of config)
     * just read it, either param null and result should be success?... if anything this test shows that the api is broken
    public function testChangePasswordWithNullValues()
    {
        $this->_skipIfLDAPBackend();

        $credentials = TestServer::getInstance()->getTestCredentials();
        $result = $this->_instance->changePassword($credentials['password'], null);
        self::assertEquals(['success' => 1], $result);
        $result = $this->_instance->changePassword( null, $credentials['password']);
        self::assertEquals(['success' => 1], $result);
    }
     * */
}
