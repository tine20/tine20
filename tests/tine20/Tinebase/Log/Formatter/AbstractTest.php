<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Log
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Tinebase_Log_Formatter_AbstractTest
 */
class Tinebase_Log_Formatter_AbstractTest extends TestCase
{
    /**
     * the logger
     * 
     * @var Zend_Log
     */
    protected $_logger = null;

    /**
     * the writer
     *
     * @var Zend_Log_Writer_Abstract
     */
    protected $_writer = null;

    /**
     * log filename
     *
     * @var string
     */
    protected $_logfile = null;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        $this->_logger = new Zend_Log();
    }

    protected function tearDown(): void
{
        Tinebase_Log_Formatter::reset();
        $this->_writer->shutdown();
        unlink($this->_logfile);
    }

    /**
     * @param $formatter
     * @return string $logfile
     */
    protected function _setWriterWithFormatter($formatter)
    {
        $this->_logfile = tempnam(Tinebase_Core::getTempDir(), 'testlog');
        $this->_writer = new Zend_Log_Writer_Stream($this->_logfile);
        $this->_writer->setFormatter($formatter);

        $this->_logger->addWriter($this->_writer);
    }
}
