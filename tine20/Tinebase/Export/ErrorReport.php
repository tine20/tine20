<?php
/**
 * Tinebase export error report class
 *
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Tinebase export error report class
 *
 * @package     Tinebase
 * @subpackage    Export
 *
 */
class Tinebase_Export_ErrorReport extends Tinebase_Export_Abstract
{
    protected $_exception;

    /** @noinspection PhpMissingParentConstructorInspection */
    /**
     * Tinebase_Export_ErrorReport constructor.
     * @param Exception $e
     * @param array $options
     */
    public function __construct(Exception $e, $options)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Generating ERROR REPORT for exception: ' . $e->getMessage());
        }

        $this->_exception = $e;
        $this->_config = new Zend_Config($options);
    }

    /**
     * get download content type
     *
     * @return string
     */
    public function getDownloadContentType()
    {
        return 'text/plain';
    }

    /**
     * return download filename
     *
     * @param string $_appName
     * @param string $_format
     * @return string
     */
    public function getDownloadFilename($_appName = null, $_format = null)
    {
        return 'export_error_report.txt';
    }

    /**
     * outputs exception message to client
     */
    public function write()
    {
        echo $this->getContent();
    }

    public function getContent()
    {
        return get_class($this->_exception) . ': ' . $this->_exception->getMessage();
    }

    /**
     * generate export
     * @throws Tinebase_Exception_NotImplemented
     */
    public function generate()
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' not implementd');
    }

    /**
     * @param string $_key
     * @param string $_value
     * @throws Tinebase_Exception_NotImplemented
     */
    protected function _setValue($_key, $_value)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' not implementd');
    }

    /**
     * @param string $_value
     * @throws Tinebase_Exception_NotImplemented
     */
    protected function _writeValue($_value)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' not implementd');
    }

    /**
     * @param null|string $filename
     * @return Tinebase_Model_Tree_FileLocation
     */
    public function getTargetFileLocation($filename = null)
    {
        if (! $filename) {
            $filename = Tinebase_TempFile::getTempPath();
            file_put_contents($filename, $this->getContent());
        }
        return parent::getTargetFileLocation($filename);
    }
}
