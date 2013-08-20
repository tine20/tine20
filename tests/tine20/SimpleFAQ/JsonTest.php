<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     SimpleFAQ
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for SimpleFAQ_Frontend_Json
 */
class SimpleFAQ_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var SimpleFAQ_Frontend_Json
     */
    protected $_instance = NULL;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 SimpleFAQ Json Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        $this->_instance = new SimpleFAQ_Frontend_Json();
    }
    
    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
    
    /**
     * try to add a faq record
     * 
     * @return array
     */
    public function testAddFaq()
    {
        $faq = new SimpleFAQ_Model_Faq(array(
            'answer'               => 'was geht?',
            'question'             => 'einiges',
            'container_id'         => Tinebase_Container::getInstance()->getDefaultContainer('SimpleFAQ_Model_Faq')->getId()
        ));
        
        $result = $this->_instance->saveFaq($faq->toArray());
        
        $this->assertEquals($faq->question, $result['question']);
        return $result;
    }
    
    /**
     * testAttachTag
     * 
     * @see 0008602: Hinzufügen von Tags in der FAQ mit Rechtsklick erzeugt Fehler
     */
    public function testAttachTag()
    {
        $faqArray = $this->testAddFaq();
        $filter = new SimpleFAQ_Model_FaqFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($faqArray['id']))
        ));

        $tagData = array(
            'type'  => Tinebase_Model_Tag::TYPE_SHARED,
            'name'  => 'tag::testAttachTagToMultipleFaqRecords',
            'description' => 'testAttachTagToMultipleFaqRecords',
            'color' => '#009B31',
        );

        $tinebaseJson = new Tinebase_Frontend_Json();
        $tinebaseJson->attachTagToMultipleRecords($filter->toArray(), 'SimpleFAQ_Model_FaqFilter', $tagData);
        
        $result = $this->_instance->getFaq($faqArray['id']);
        $this->assertEquals(1, count($result['tags']));
    }
}
