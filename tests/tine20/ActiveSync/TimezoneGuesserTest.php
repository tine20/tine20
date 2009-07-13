<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      
 * @version     
 */

class ActiveSync_TimezoneGuesserTest extends PHPUnit_Framework_TestCase
{
	
	protected $_fixtures = array(
           2009 => array(
               'Europe/Berlin' => array(                                                                                                                            
                    'bias' => -60,
                    'standardName' => null,
                    'standardYear' => 0,
                    'standardMonth' => 10,
                    'standardDayOfWeek' => 0,
                    'standardDay' => 5,
                    'standardHour' => 3,
                    'standardMinute' => 0,
                    'standardSecond' => 0,
                    'standardMilliseconds' => 0,
                    'standardBias' => 0,
                    'daylightName' => null,
                    'daylightYear' => 0,
                    'daylightMonth' => 3,
                    'daylightDayOfWeek' => 0,
                    'daylightDay' => 5,
                    'daylightHour' => 2,
                    'daylightMinute' => 0,
                    'daylightSecond' => 0,
                    'daylightMilliseconds' => 0,
                    'daylightBias' => -60                                                         
               ),
               'Europe/Berlin' => array( //fake test with standardYear and daylightYear => will evaluate the specified year when guessing the timezone
                    'bias' => -60,
                    'standardName' => null,
                    'standardYear' => 2009, 
                    'standardMonth' => 10,
                    'standardDayOfWeek' => 0,
                    'standardDay' => 5,
                    'standardHour' => 3,
                    'standardMinute' => 0,
                    'standardSecond' => 0,
                    'standardMilliseconds' => 0,
                    'standardBias' => 0,
                    'daylightName' => null,
                    'daylightYear' => 2009,
                    'daylightMonth' => 3,
                    'daylightDayOfWeek' => 0,
                    'daylightDay' => 5,
                    'daylightHour' => 2,
                    'daylightMinute' => 0,
                    'daylightSecond' => 0,
                    'daylightMilliseconds' => 0,
                    'daylightBias' => -60                                                         
               ),
             'Asia/Tehran' => array(
                  'bias' => -210,
                    'standardName' => null,
                    'standardYear' => 0,
                    'standardMonth' => 9,
                    'standardDayOfWeek' => 2,
                    'standardDay' => 4,
                    'standardHour' => 2,
                    'standardMinute' => 0,
                    'standardSecond' => 0,
                    'standardMilliseconds' => 0,
                    'standardBias' => 0,
                    'daylightName' => null,
                    'daylightYear' => 0,
                    'daylightMonth' => 3,
                    'daylightDayOfWeek' => 0,
                    'daylightDay' => 1,
                    'daylightHour' => 2,
                    'daylightMinute' => 0,
                    'daylightSecond' => 0,
                    'daylightMilliseconds' => 0,
                    'daylightBias' => -60   
             ),
               'US/Arizona' => array(
                    'bias' => 420,
                    'standardName' => null,
                    'standardYear' => 0,
                    'standardMonth' => 0,
                    'standardDayOfWeek' => 0,
                    'standardDay' => 0,
                    'standardHour' => 0,
                    'standardMinute' => 0,
                    'standardSecond' => 0,
                    'standardMilliseconds' => 0,
                    'standardBias' => 0,
                    'daylightName' => null,
                    'daylightYear' => 0,
                    'daylightMonth' => 0,
                    'daylightDayOfWeek' => 0,
                    'daylightDay' => 0,
                    'daylightHour' => 0,
                    'daylightMinute' => 0,
                    'daylightSecond' => 0,
                    'daylightMilliseconds' => 0,
                    'daylightBias' => 0  
               )
           ),
           2010 => array(
               'Europe/Berlin' => array(                                                                                                                            
                    'bias' => -60,
                    'standardName' => null,
                    'standardYear' => 0,
                    'standardMonth' => 10,
                    'standardDayOfWeek' => 0,
                    'standardDay' => 5,
                    'standardHour' => 3,
                    'standardMinute' => 0,
                    'standardSecond' => 0,
                    'standardMilliseconds' => 0,
                    'standardBias' => 0,
                    'daylightName' => null,
                    'daylightYear' => 0,
                    'daylightMonth' => 3,
                    'daylightDayOfWeek' => 0,
                    'daylightDay' => 5,
                    'daylightHour' => 2,
                    'daylightMinute' => 0,
                    'daylightSecond' => 0,
                    'daylightMilliseconds' => 0,
                    'daylightBias' => -60                                                         
               ),
             'Asia/Tehran' => array(
                  'bias' => -210,
                    'standardName' => null,
                    'standardYear' => 0,
                    'standardMonth' => 9,
                    'standardDayOfWeek' => 2,
                    'standardDay' => 4,
                    'standardHour' => 2,
                    'standardMinute' => 0,
                    'standardSecond' => 0,
                    'standardMilliseconds' => 0,
                    'standardBias' => 0,
                    'daylightName' => null,
                    'daylightYear' => 0,
                    'daylightMonth' => 3,
                    'daylightDayOfWeek' => 0,
                    'daylightDay' => 1,
                    'daylightHour' => 2,
                    'daylightMinute' => 0,
                    'daylightSecond' => 0,
                    'daylightMilliseconds' => 0,
                    'daylightBias' => -60   
             ),
               'US/Arizona' => array(
                    'bias' => 420,
                    'standardName' => null,
                    'standardYear' => 0,
                    'standardMonth' => 0,
                    'standardDayOfWeek' => 0,
                    'standardDay' => 0,
                    'standardHour' => 0,
                    'standardMinute' => 0,
                    'standardSecond' => 0,
                    'standardMilliseconds' => 0,
                    'standardBias' => 0,
                    'daylightName' => null,
                    'daylightYear' => 0,
                    'daylightMonth' => 0,
                    'daylightDayOfWeek' => 0,
                    'daylightDay' => 0,
                    'daylightHour' => 0,
                    'daylightMinute' => 0,
                    'daylightSecond' => 0,
                    'daylightMilliseconds' => 0,
                    'daylightBias' => 0  
               )
           )
        );
        
    protected $_datesToTest = array(
       '%d-01-01',
       '%d-12-31',
       '%d-06-01',
    );
    
    public function setUp()
    {
    	$this->uit = new ActiveSync_TimezoneGuesser();
    }

    public function testActiveSyncTimezoneGuesserWithPackedStrings()
    {
    	$packedFixtrues = array(
    	       'Europe/Berlin' => 'xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAFAAMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAIAAAAAAAAAxP///w==',
    	       'Asia/Baghdad' => 'TP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAABAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAAAABAAMAAAAAAAAAxP///w==',
    	       'Asia/Tehran' => 'Lv///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAkAAgAEAAIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAABAAIAAAAAAAAAxP///w==',
    	       'America/Sao_Paulo' => 'tAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAACAAIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAADAAIAAAAAAAAAxP///w==',
    	   );

    	$dateToTest = '2010-03-05';
    	
    	foreach ($packedFixtrues as $timezoneIdentifier => $packedOffsets) {
    		$offsets = $this->uit->unpackTimezoneInfo($packedOffsets);
	        $this->uit->reset($dateToTest, $offsets);
	        $this->assertContains($timezoneIdentifier, $this->uit->guessTimezones());
    	}
    }
	
    public function testActiveSyncTimezoneGuesser()
    {       
        foreach ($this->_fixtures as $year => $timezonesToTest) {
            foreach ($timezonesToTest as $timezoneIdentifier => $offsets) {
                foreach ($this->_datesToTest as $dateToTest) {
                    $dateToTest = sprintf($dateToTest, $year);
                    $this->uit->reset($dateToTest, $offsets);
                    $this->assertContains($timezoneIdentifier, $this->uit->guessTimezones(), "Testing date $dateToTest");
                }
            }
        }
        
    }
    
    public function testActiveSyncTimezoneGuesserWithExpectedTimezoneOption()
    {
        $timezonesToTest = $this->_fixtures[2009];
        $dateToTest = '2009-03-05';
        foreach ($timezonesToTest as $timezoneIdentifier => $offsets) {
            $this->uit->reset($dateToTest, $offsets);
            $this->uit->setExpectedTimezone($timezoneIdentifier);
            $matchedTimezones = $this->uit->guessTimezones();
            $this->assertContains($timezoneIdentifier, $matchedTimezones);
            $this->assertEquals($timezoneIdentifier, $matchedTimezones[0]);
        }
    }

    public function testActiveSyncTimezoneGuesserWithUnknownOffsets()
    {
        $offsets = array(
	                    'bias' => 1,
	                    'standardName' => null,
	                    'standardYear' => 0,
	                    'standardMonth' => 1,
	                    'standardDayOfWeek' => 2,
	                    'standardDay' => 3,
	                    'standardHour' => 4,
	                    'standardMinute' => 5,
	                    'standardSecond' => 6,
	                    'standardMilliseconds' => 7,
	                    'standardBias' => 8,
	                    'daylightName' => null,
	                    'daylightYear' => 0,
	                    'daylightMonth' => 1,
	                    'daylightDayOfWeek' => 2,
	                    'daylightDay' => 3,
	                    'daylightHour' => 4,
	                    'daylightMinute' => 5,
	                    'daylightSecond' => 6,
	                    'daylightMilliseconds' => 7,
	                    'daylightBias' => 8
                   );

        $dateToTest = '2009-03-05';
        $this->uit->reset($dateToTest, $offsets);
        $matchedTimezones = $this->uit->guessTimezones();
        $this->assertTrue(is_array($matchedTimezones));
        $this->assertTrue(count($matchedTimezones) === 0);
    }
    
}
?>