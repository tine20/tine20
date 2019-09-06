<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Log
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_Log_Formatter_Json
 */
class Tinebase_Log_Formatter_JsonTest extends Tinebase_Log_Formatter_AbstractTest
{
    public function testFormatter()
    {
        $formatter = new Tinebase_Log_Formatter_Json();
        $this->_setWriterWithFormatter($formatter);

        $filter = new Zend_Log_Filter_Priority(5);
        $this->_logger->addFilter($filter);

        $this->_logger->notice("test logging");
        $loggerLine = file_get_contents($this->_logfile);

        $logData = Tinebase_Helper::jsonDecode($loggerLine);
        self::assertEquals('test logging', $logData['message'], print_r($logData, true));
        self::assertEquals(5, $logData['priority'], print_r($logData, true));
        self::assertTrue(isset($logData['user']));
        self::assertTrue(isset($logData['request_id']));
        self::assertEquals(Tinebase_Core::getUser()->accountLoginName, $logData['user'], print_r($logData, true));
        self::assertEquals($formatter::getRequestId(), $logData['request_id']);

        $this->_logger->notice("test logging");
        $loggerLines = file_get_contents($this->_logfile);
        self::assertContains(PHP_EOL, $loggerLines, 'no linebreak between log statements');
    }
}
