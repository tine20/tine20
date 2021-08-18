<?php

/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * this test class mainly tests the Community Identification Number grants and the controller functions
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test class for Tinebase_CommunityIdentNr_ControllerTest
 */
class Tinebase_CommunityIdentNr_ControllerTest extends TestCase
{
    /**
     * @var Tinebase_Controller_CommunityIdentNr
     */
    protected $_communityIdentNrController = array();
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        parent::setUp();
        
        $this->_communityIdentNrController = Tinebase_Controller_CommunityIdentNr::getInstance();
    }
    
    
    /************ test functions follow **************/

    /**
     * Calculate Population aggregate of different Community Numbers
     *
     */
    public function testAggregatePopulation()
    {
        $this->_createTestCommunityIdentNumbers();
        $schleswigHolstein = $this->_communityIdentNrController->get(1);
        $this->assertEquals(168991, $schleswigHolstein->bevoelkerungGesamt,
            'Schleswig-Holstein = Kreis Flensburg + Stadt Flensburg + Stadt Neumünster');

        $kreisFlesburg = $this->_communityIdentNrController->get(2);
        $this->assertEquals(89504, $kreisFlesburg->bevoelkerungGesamt, 'Kreis Flensburg = Stadt Flensburg');

        $stadtNeumuenster = $this->_communityIdentNrController->get(4);
        $this->assertEquals(79487, $stadtNeumuenster->bevoelkerungGesamt, ('Stadt Neumünster = Stadt Neumünster'));

        // check if json function also works
        $feJson = new Tinebase_Frontend_Json();
        $stadtNeumuensterArray = $stadtNeumuenster->toArray();
        unset($stadtNeumuensterArray['bevoelkerungGesamt']);
        $result = $feJson->aggregatePopulation($stadtNeumuensterArray);
        $this->assertEquals(79487, $result['bevoelkerungGesamt'], ('Stadt Neumünster = Stadt Neumünster'));
    }
    
    
    /**
     * get a Community Identification Number
     * @param array $data
     * @return Tinebase_Model_CommunityIdentNr
     */
    protected function _getCommunityIdentNumber($data = array())
    {
        return new Tinebase_Model_CommunityIdentNr(array_merge(array(
            'satzArt'         => '10',
            'textkenzeichen'   => '',
            'arsLand'       => '01',
            'arsRB'      => '',
            'arsKreis'      => '',
            'arsVB'      => '',
            'arsGem'      => '',
            'arsCombined'      => '01',
            'gemeindenamen'      => 'Schleswig-Holstein',
            'bevoelkerungGesamt'      => null
        ),$data), TRUE);
    }

    /**
     * creates some different Community Identification Numbers for testing
     * @param array $data
     * @return Tinebase_Model_CommunityIdentNr
     */
    protected function _createTestCommunityIdentNumbers()
    {
        $this->_communityIdentNrController->create($this->_getCommunityIdentNumber(array(
            'id'            => 1,
            'satzArt'         => '10',
            'textkenzeichen'   => '',
            'arsLand'       => '01',
            'arsRB'      => '',
            'arsKreis'      => '',
            'arsVB'      => '',
            'arsGem'      => '',
            'arsCombined'      => '01',
            'gemeindenamen'      => 'Schleswig-Holstein',
            'bevoelkerungGesamt'      => null
        )));
        $this->_communityIdentNrController->create($this->_getCommunityIdentNumber(array(
            'id'            => 2,
            'satzArt'         => '50',
            'textkenzeichen'   => '50',
            'arsLand'       => '01',
            'arsRB'      => '0',
            'arsKreis'      => '01',
            'arsVB'      => '0000',
            'arsGem'      => '',
            'arsCombined'      => '010010000',
            'gemeindenamen'      => 'Flensburg, Stadt',
            'bevoelkerungGesamt'      => null
        )));
        $this->_communityIdentNrController->create($this->_getCommunityIdentNumber(array(
            'id'            => 3,
            'satzArt'         => '60',
            'textkenzeichen'   => '61',
            'arsLand'       => '01',
            'arsRB'      => '0',
            'arsKreis'      => '01',
            'arsVB'      => '0000',
            'arsGem'      => '000',
            'arsCombined'      => '010010000000',
            'gemeindenamen'      => 'Flensburg, Stadt',
            'bevoelkerungGesamt'      => 89504
        )));
        $this->_communityIdentNrController->create($this->_getCommunityIdentNumber(array(
            'id'            => 4,
            'satzArt'         => '60',
            'textkenzeichen'   => '61',
            'arsLand'       => '01',
            'arsRB'      => '0',
            'arsKreis'      => '04',
            'arsVB'      => '0000',
            'arsGem'      => '000',
            'arsCombined'      => '010040000000',
            'gemeindenamen'      => 'Neumünster, Stadt',
            'bevoelkerungGesamt'      => 79487
        )));
    }
}
