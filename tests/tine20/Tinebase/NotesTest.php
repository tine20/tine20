<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Notes
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    Tinebase_NotesTest::main();
}

/**
 * Test class for Tinebase_Group
 */
class Tinebase_NotesTest extends PHPUnit_Framework_TestCase
{
    /**
     * unit under test (UIT)
     * @var Tinebase_Notes
     */
    protected $_instance;

    /**
     * @var array test objects
     */
    protected $_objects = array();
    
    /**
     * Runs the test methods of this class.
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_NotesTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * set up tests
     *
     */
    public function setUp()
    {
        $this->_instance = Tinebase_Notes::getInstance();
        
        $this->_objects['record'] = array(
            'id'        => 1,
            'model'     => 'Addressbook_Model_Contact',
            'backend'    => 'Sql',
        );

        $this->_objects['contact'] = new Addressbook_Model_Contact(array(
            'id'        => 1,
            'n_family'  => 'phpunit notes contact'
        ));
        
        $this->_objects['noteType'] = new Tinebase_Model_NoteType(array(
            'id'            => '5001',
            'name'          => 'phpunit note type',
            'icon'          => '/images/oxygen/16x16/actions/document-properties.png',
            'is_user_type'  => TRUE
        ));
        
        $this->_objects['note'] = new Tinebase_Model_Note(array(
            'id'                => 123,
            'note_type_id'      => $this->_objects['noteType']->getId(),
            'note'              => 'phpunit test note',    
            'record_model'      => $this->_objects['record']['model'],
            'record_backend'    => $this->_objects['record']['backend'],       
            'record_id'         => $this->_objects['record']['id']
        ));
    }
    
    /**
     * try to add a note type
     *
     */
    public function testAddNoteType()
    {
        $this->_instance->addNoteType($this->_objects['noteType']);
        
        // find our note type
        $testNoteType = $this->_instance->getNoteTypeByName($this->_objects['noteType']->name);
                
        $this->assertEquals($this->_objects['noteType']->name, $testNoteType->name);
        $this->assertEquals(1, $testNoteType->is_user_type, 'user type not set');
    }
    
    /**
     * try to add a note
     *
     */
    public function testAddNote()
    {
        $this->_instance->addNote($this->_objects['note']);
        
        $note = $this->_instance->getNote($this->_objects['note']->getId());
        
        $this->assertEquals($this->_objects['note']->note, $note->note);
    }

    /**
     * try to add a system note
     *
     */
    public function testAddSystemNote()
    {
        $translate = Tinebase_Translation::getTranslation('Tinebase');
        $translatedNoteString = $translate->_('created') . ' ' . $translate->_('by');
        
        $this->_instance->addSystemNote(
            $this->_objects['contact'], 
            Zend_Registry::get('currentAccount')->getId(), 
            Tinebase_Model_Note::SYSTEM_NOTE_NAME_CREATED
        );
        
        $filter = new Tinebase_Model_NoteFilter(array(array(
            'field' => 'query',
            'operator' => 'contains',
            'value' => $translatedNoteString
        )));
        $notes = $this->_instance->searchNotes($filter, new Tinebase_Model_Pagination());
        
        $this->assertGreaterThan(0, count($notes));
        $found = FALSE;
        foreach ($notes as $note) {
            if ($translatedNoteString . ' ' . Zend_Registry::get('currentAccount')->accountDisplayName == $note->note) {
                $found = TRUE;
                break;
            }
        }
        $this->assertTrue($found);
    }
    
    /**
     * test search notes
     *
     */
    public function testSearchNotes()
    {
        $filter = new Tinebase_Model_NoteFilter(array(array(
            'field' => 'query',
            'operator' => 'contains',
            'value' => 'phpunit'
        )));
        
        $notes = $this->_instance->searchNotes($filter, new Tinebase_Model_Pagination());
        $notesCount = $this->_instance->searchNotesCount($filter);
        
        //print_r($notes->toArray());
        
        $this->assertEquals($this->_objects['note']->note, $notes[0]->note);
        $this->assertGreaterThan(0, $notesCount);
    }

    /**
     * test to array and resolution of account display name
     *
     */
    public function testToArray()
    {
        $note = $this->_instance->getNote($this->_objects['note']->getId());
        
        $noteArray = $note->toArray();
        //print_r($noteArray);
        
        $this->assertEquals(Zend_Registry::get('currentAccount')->accountDisplayName, $noteArray['created_by']);
    }
    
    /**
     * try to delete a note
     *
     */
    public function testDeleteNote()
    {
        $this->_instance->deleteNotesOfRecord(
            $this->_objects['record']['model'], 
            $this->_objects['record']['backend'], 
            $this->_objects['record']['id']
        );
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        $note = $this->_instance->getNote($this->_objects['note']->getId());
    }
    
    /**
     * try to delete a note type
     *
     */
    public function testDeleteNoteType()
    {
        $noteTypesPre = $this->_instance->getNoteTypes();
        
        $this->_instance->deleteNoteType($this->_objects['noteType']->getId());
        
        $noteTypesPost = $this->_instance->getNoteTypes();
        
        $this->assertLessThan(count($noteTypesPre), count($noteTypesPost));
    }
    
    /**
     * try to get notes of multiple records (adding 'changed' note first)
     * 
     * @return void
     */
    public function testGetMultipleNotes()
    {
        $personas = Zend_Registry::get('personas');
        $personasContactIds = array();
        foreach ($personas as $persona) {
            $personasContactIds[] = $persona->contact_id;
        }
        $contacts = Addressbook_Controller_Contact::getInstance()->getMultiple($personasContactIds);
        
        // add note to contacts
        foreach ($contacts as $contact) {
            $this->_instance->addNote(new Tinebase_Model_Note(array(
                'note'          => 'very important note!',
                'note_type_id'  => Tinebase_Notes::getInstance()->getNoteTypes()->getFirstRecord()->getId(),
                'record_id'     => $contact->getId(),
                'record_model'  => 'Addressbook_Model_Contact',
            )));
        }
        
        $this->_instance->getMultipleNotesOfRecords($contacts);
        foreach ($contacts as $contact) {
            $this->assertGreaterThan(0, count($contact->notes), 'No notes found for contact ' . $contact->n_fn);
        }
    }
}
