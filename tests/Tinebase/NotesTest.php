<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Notes
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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
        
        $this->_objects['noteType'] = new Tinebase_Notes_Model_NoteType(array(
            'id'        => '5001',
            'name'      => 'phpunit note type',
            'icon'      => '/images/oxygen/16x16/actions/document-properties.png'
        ));
        
        $this->_objects['note'] = new Tinebase_Notes_Model_Note(array(
            'id'                => 1,
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
        $noteTypesPre = $this->_instance->getNoteTypes();
        
        $this->_instance->addNoteType($this->_objects['noteType']);
        
        $noteTypesPost = $this->_instance->getNoteTypes();
        
        $this->assertGreaterThan(count($noteTypesPre), count($noteTypesPost));
    }
    
    /**
     * try to add a note
     *
     */
    public function testAddNote()
    {
        $notesPre = $this->_instance->getNotes(
            $this->_objects['record']['model'], 
            $this->_objects['record']['backend'], 
            $this->_objects['record']['id']
        );
        
        $this->_instance->addNote($this->_objects['note']);
        
        $notesPost = $this->_instance->getNotes(
            $this->_objects['record']['model'], 
            $this->_objects['record']['backend'], 
            $this->_objects['record']['id']
        );
        
        $this->assertGreaterThan(count($notesPre), count($notesPost));
        $this->assertEquals($this->_objects['note']->note, $notesPost[0]->note);        
    }
    
    /**
     * test to array and resolution of account display name
     *
     */
    public function testToArray()
    {
        $notes = $this->_instance->getNotes(
            $this->_objects['record']['model'], 
            $this->_objects['record']['backend'], 
            $this->_objects['record']['id']
        );
        
        $myNote = $notes[0];
        
        $noteArray = $myNote->toArray();
        //print_r($noteArray);
        
        $this->assertEquals('Tine 2.0 Admin Account', $noteArray['created_by']);
    }
    
    /**
     * try to delete a note
     *
     */
    public function testdeleteNote()
    {
        $notesPre = $this->_instance->getNotes(
            $this->_objects['record']['model'], 
            $this->_objects['record']['backend'], 
            $this->_objects['record']['id']
        );
        
        $this->_instance->deleteNotesOfRecord(
            $this->_objects['record']['model'], 
            $this->_objects['record']['backend'], 
            $this->_objects['record']['id']
        );
        
        $notesPost = $this->_instance->getNotes(
            $this->_objects['record']['model'], 
            $this->_objects['record']['backend'], 
            $this->_objects['record']['id']
        );
        
        $this->assertLessThan(count($notesPre), count($notesPost));
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
}

