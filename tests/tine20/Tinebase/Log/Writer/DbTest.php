<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Log
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_Log_Writer_Db
 */
class Tinebase_Log_Writer_DbTest extends TestCase
{
    /**
     * the logger
     *
     * @var Tinebase_Log
     */
    protected $_logger = null;

    /**
     * the writer
     *
     * @var Zend_Log_Writer_Abstract
     */
    protected $_writer = null;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_logger = new Tinebase_Log();
        Tinebase_Controller_LogEntry::getInstance()->cleanUp(Tinebase_DateTime::now());
    }

    protected function tearDown()
    {
        Tinebase_Log_Formatter::reset();
        $this->_writer->shutdown();
    }
    
    public function testWriter(){
        $loggerConfig = new Zend_Config(
            array(
                'database' => true,
                'priority' => '5',
                'active' => true,
                'formatter' => 'db',
            )
        );
        $this->_writer = $this->_logger->getWriter($loggerConfig);
        $this->_writer->setFormatter($this->_logger->getFormatter($loggerConfig));

        $this->_logger->addWriter($this->_writer);
        $this->_logger->notice("test logging");
        $data = Tinebase_Controller_LogEntry::getInstance()->getAll();
        self::assertEquals(1, count($data));
        self::assertEquals('NOTICE', $data[0]['priorityName']);
        self::assertEquals('test logging', $data[0]['message']);
    }
}
