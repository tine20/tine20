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
        
        $this->_objects['noteType'] = new Tinebase_Notes_Model_NoteType(array(
            'id'        => '5001',
            'name'      => 'phpunit note type',
            'icon'      => '/images/oxygen/16x16/actions/document-properties.png'
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

