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
 * @author      Jonas Fischer <j.fischer@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class ActiveSync_TimezoneConverterTest extends PHPUnit_Framework_TestCase
{
    
    protected $_uit = null;
    
    protected $_fixtures = array(
               /*'Europe/Berlin' => array(                                                                                                                            
                    'bias' => -60,
                    'standardName' => '',
                    'standardYear' => 0,
                    'standardMonth' => 10,
                    'standardDayOfWeek' => 0,
                    'standardDay' => 5,
                    'standardHour' => 3,
                    'standardMinute' => 0,
                    'standardSecond' => 0,
                    'standardMilliseconds' => 0,
                    'standardBias' => 0,
                    'daylightName' => '',
                    'daylightYear' => 0,
                    'daylightMonth' => 3,
                    'daylightDayOfWeek' => 0,
                    'daylightDay' => 5,
                    'daylightHour' => 2,
                    'daylightMinute' => 0,
                    'daylightSecond' => 0,
                    'daylightMilliseconds' => 0,
                    'daylightBias' => -60                                                         
               ),*/
               'Europe/Berlin' => array( //fake test with standardYear and daylightYear => will evaluate the specified year when guessing the timezone
                    'bias' => -60,
                    'standardName' => '',
                    'standardYear' => 2009, 
                    'standardMonth' => 10,
                    'standardDayOfWeek' => 0,
                    'standardDay' => 5,
                    'standardHour' => 3,
                    'standardMinute' => 0,
                    'standardSecond' => 0,
                    'standardMilliseconds' => 0,
                    'standardBias' => 0,
                    'daylightName' => '',
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
               'America/Phoenix' => array(
                    'bias' => 420,
                    'standardName' => '',
                    'standardYear' => 0,
                    'standardMonth' => 0,
                    'standardDayOfWeek' => 0,
                    'standardDay' => 0,
                    'standardHour' => 0,
                    'standardMinute' => 0,
                    'standardSecond' => 0,
                    'standardMilliseconds' => 0,
                    'standardBias' => 0,
                    'daylightName' => '',
                    'daylightYear' => 0,
                    'daylightMonth' => 0,
                    'daylightDayOfWeek' => 0,
                    'daylightDay' => 0,
                    'daylightHour' => 0,
                    'daylightMinute' => 0,
                    'daylightSecond' => 0,
                    'daylightMilliseconds' => 0,
                    'daylightBias' => 0  
               ),
//             'Asia/Tehran' => array(
//                  'bias' => -210,
//                    'standardName' => null,
//                    'standardYear' => 0,
//                    'standardMonth' => 9,
//                    'standardDayOfWeek' => 2,
//                    'standardDay' => 4,
//                    'standardHour' => 2,
//                    'standardMinute' => 0,
//                    'standardSecond' => 0,
//                    'standardMilliseconds' => 0,
//                    'standardBias' => 0,
//                    'daylightName' => null,
//                    'daylightYear' => 0,
//                    'daylightMonth' => 3,
//                    'daylightDayOfWeek' => 0,
//                    'daylightDay' => 1,
//                    'daylightHour' => 2,
//                    'daylightMinute' => 0,
//                    'daylightSecond' => 0,
//                    'daylightMilliseconds' => 0,
//                    'daylightBias' => -60   
//             ),
        );
        
    protected $_packedFixtrues = array(
        'Europe/Berlin'     => 'xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAFAAMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAIAAAAAAAAAxP///w==',
        'America/Phoenix'   => 'pAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==',
        'Africa/Douala'     => 'xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==',
        'Pacific/Kwajalein' => '0AIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==',
        // broken daylight from Motorola Milestone sends 60 instead of -60
        // @todo write test for broken timezonestring
//        'Europe/Stockholm'  => 'xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAFAAMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAIAAAAAAAAAPAAAAA==',
//        'Europe/Berlin'        => 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAFAAEAAAAAAAAAxP///w=='
//        'Asia/Tehran' => 'Lv///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAkAAgAEAAIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAABAAIAAAAAAAAAxP///w==',
//        'America/Sao_Paulo' => 'tAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAACAAIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAADAAIAAAAAAAAAxP///w==',
         );
         
    protected $_timezoneIdentifierToAbbreviation = array(
        'Europe/Berlin'     => 'CET',
        'America/Phoenix'   => 'MST',
        'Africa/Algiers'    => 'CET',
        'Africa/Douala'     => 'WAT',
        'Pacific/Kwajalein' => 'MHT',
    );
    
    public function setUp()
    {
        $this->_uit = ActiveSync_TimezoneConverter::getInstance(Tinebase_Core::getLogger());
    }
        
    public function testGetPackedStringForTimezone()
    {
         foreach ($this->_packedFixtrues as $timezoneIdentifier => $packedString) {
            $this->assertEquals($packedString, $this->_uit->encodeTimezone($timezoneIdentifier), "Testing for timezone $timezoneIdentifier");
        }
    }

    public function testGetListOfTimezonesForOffsets()
    {
        foreach ($this->_fixtures as $timezoneIdentifier => $offsets) {
            $timezoneAbbr = $this->_timezoneIdentifierToAbbreviation[$timezoneIdentifier];
            $result = $this->_uit->getListOfTimezones($offsets);
            $this->assertTrue(array_key_exists($timezoneIdentifier, $result));
            $this->assertEquals($timezoneAbbr,$result[$timezoneIdentifier]);
        }        
    }
    
    public function testGetListOfTimezonesForPackedStrings()
    {
        foreach ($this->_packedFixtrues as $timezoneIdentifier => $packedTimezoneInfo) {
            $timezoneAbbr = $this->_timezoneIdentifierToAbbreviation[$timezoneIdentifier];
            $result = $this->_uit->getListOfTimezones($packedTimezoneInfo);
            $this->assertTrue(array_key_exists($timezoneIdentifier, $result));
            $this->assertEquals($timezoneAbbr, $result[$timezoneIdentifier]);
            
//            $result = $this->_uit->getTimezoneForPackedTimezoneInfo($packedTimezoneInfo);
//            $this->assertEquals($timezoneIdentifier, $result);
        }
    }
    
    public function testExpectedTimezoneOption()
    {
        foreach ($this->_fixtures as $timezoneIdentifier => $offsets) {
            $timezoneAbbr = $this->_timezoneIdentifierToAbbreviation[$timezoneIdentifier];
            $matchedTimezones = $this->_uit->getListOfTimezones($offsets, $timezoneIdentifier);
            $this->assertTrue(array_key_exists($timezoneIdentifier, $matchedTimezones));
            $this->assertEquals($timezoneAbbr, $matchedTimezones[$timezoneIdentifier]);
        }
        
        //Africa/Algiers exceptionally belongs to CET insetad of WAT
        $packed = 'xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==';
        $expectedTimezone = 'Africa/Algiers';
        $expectedAbbr = 'CET';
        
        $result = $this->_uit->getTimezone($packed, $expectedTimezone);
        $this->assertEquals($expectedTimezone, $result);
    }

    public function testUnknownOffsets()
    {
        $this->setExpectedException('ActiveSync_TimezoneNotFoundException');
        $offsets = array(                                                                                                                            
                    'bias' => -600000,
                    'standardName' => '',
                    'standardYear' => 0,
                    'standardMonth' => 10,
                    'standardDayOfWeek' => 0,
                    'standardDay' => 5,
                    'standardHour' => 3,
                    'standardMinute' => 0,
                    'standardSecond' => 0,
                    'standardMilliseconds' => 0,
                    'standardBias' => 0,
                    'daylightName' => '',
                    'daylightYear' => 0,
                    'daylightMonth' => 3,
                    'daylightDayOfWeek' => 0,
                    'daylightDay' => 5,
                    'daylightHour' => 2,
                    'daylightMinute' => 0,
                    'daylightSecond' => 0,
                    'daylightMilliseconds' => 0,
                    'daylightBias' => -60                                                         
               );
        $matchedTimezones = $this->_uit->getTimezone($offsets);
    }
    
    public function testInvalidOffsets()
    {
        $this->setExpectedException('ActiveSync_Exception');
        //When specifiying standardOffsest then it is invalid provide empty daylight offsets and vice versa 
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
                        'daylightDayOfWeek' => 0,
                        'daylightDay' => 0,
                        'daylightHour' => 0,
                        'daylightMinute' => 0,
                        'daylightSecond' => 0,
                        'daylightMilliseconds' => 0,
                        'daylightBias' => 0
                   );

        $this->_uit->getTimezone($offsets);

    }

    public function testCachedResults()
    {
        $this->_uit->setCache(Tinebase_Core::get('cache'));
        $this->testGetListOfTimezonesForOffsets();
        $this->testGetListOfTimezonesForPackedStrings();
        $this->_uit->setCache(null);
    }
    
}
