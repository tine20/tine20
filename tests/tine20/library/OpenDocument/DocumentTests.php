<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tests
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */


class OpenDocument_DocumentTests extends PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
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
     * tests the correct replacement of markers with different contents
     */
    public function testMarkerReplacement()
    {
        $doc = new OpenDocument_Document(OpenDocument_Document::SPREADSHEET);
        $table = $doc->getBody()->appendTable('UNITTEST');
        
        $titleText = 'Hello unittest!';
        
        $row  = $table->appendRow();
        $cell = $row->appendCell($titleText);
        
        $row  = $table->appendRow();
        
        $row  = $table->appendRow();
        $cell = $row->appendCell('<{MATRIX}>');
        
        $row  = $table->appendRow();
        
        $row  = $table->appendRow();
        $cell = $row->appendCell('<{MARKER}>');
        
        $filename = Tinebase_Config::getInstance()->get('tmpdir') . DIRECTORY_SEPARATOR . Tinebase_Record_Abstract::generateUID(4) . '-ods-unittest.ods';
        
        $ccc = Sales_Controller_CostCenter::getInstance();
        
        $uid = Tinebase_Record_Abstract::generateUID(4);
        $cc1 = $ccc->create(new Sales_Model_CostCenter(array('number' => $uid, 'remark' => 'unittest-' . $uid)));
        
        $uid = Tinebase_Record_Abstract::generateUID(4);
        $cc2 = $ccc->create(new Sales_Model_CostCenter(array('number' => $uid, 'remark' => 'unittest-' . $uid)));
        
        $colInfo = array();
        $colInfo[$cc1->getId()] = $cc1->number;
        $colInfo[$cc2->getId()] = $cc2->number;
        
        $matrixArray = array(
            $cc1->getId() => array($cc2->getId() => '100'),
            $cc2->getId() => array($cc1->getId() => '200')
        );
        
        $matrix = new OpenDocument_Matrix($matrixArray, $colInfo, $colInfo, OpenDocument_Matrix::TYPE_FLOAT);
        
        $matrix->setColumnLegendDescription('Cat');
        $matrix->setRowLegendDescription('Dog');
        
        $markerText = 'unittest-marker';
        $doc->replaceMarker('marker', $markerText)->replaceMatrix('matrix', $matrix);
        $doc->getDocument($filename);
        
        $contentXml = file_get_contents('zip://' . $filename . '#content.xml');
        $xml = simplexml_load_string($contentXml);
        
        unlink($filename);
        
        $spreadSheets = $xml->xpath('//office:body/office:spreadsheet');
        $spreadSheet  = $spreadSheets[0];
        
        $results = $spreadSheet->xpath("//text()[contains(., '$markerText')]");
        $this->assertEquals(1, count($results));
        
        $results = $spreadSheet->xpath("//text()[contains(., '$titleText')]");
        $this->assertEquals(1, count($results));
        
        $results = $spreadSheet->xpath("//text()[contains(., '$cc1->number')]");
        $this->assertEquals(2, count($results));
        
        $results = $spreadSheet->xpath("//text()[contains(., '$cc2->number')]");
        $this->assertEquals(2, count($results));
        
        $results = $spreadSheet->xpath("//text()[contains(., 'Sum')]");
        $this->assertEquals(2, count($results));
        
        $results = $spreadSheet->xpath("//text()[contains(., 'Cat')]");
        $this->assertEquals(1, count($results));
        
        $results = $spreadSheet->xpath("//text()[contains(., 'Dog')]");
        $this->assertEquals(1, count($results));
        
        $results = $spreadSheet->xpath("//text()[contains(., '100')]");
        $this->assertEquals(3, count($results));
        
        $results = $spreadSheet->xpath("//text()[contains(., '200')]");
        $this->assertEquals(3, count($results));
        
        $results = $spreadSheet->xpath("//text()[contains(., '300')]");
        $this->assertEquals(1, count($results));
    }
}