<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * abstract class for all commands using wbxml encoded content
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 */
 
abstract class ActiveSync_Command_Wbxml
{
    /**
     * the domDocument containing the xml response from the server
     *
     * @var DOMDocument
     */
    protected $_outputDom;

    /**
     * the domDocucment containing the xml request from the client
     *
     * @var DOMDocument
     */
    protected $_inputDom;
        
    /**
     * informations about the currently device
     *
     * @var ActiveSync_Model_Device
     */
    protected $_device;
    
    /**
     * the default namespace
     *
     * @var string
     */
    protected $_defaultNameSpace;
    
    /**
     * the main xml tag
     *
     * @var string
     */
    protected $_documentElement;
    
    /**
     * timestamp to use for all sync requests
     *
     * @var Zend_Date
     */
    protected $_syncTimeStamp;
        
    const FILTERTYPE_ALL            = 0;
    const FILTERTYPE_1DAY           = 1;
    const FILTERTYPE_3DAYS          = 2;
    const FILTERTYPE_1WEEK          = 3;
    const FILTERTYPE_2WEEKS         = 4;
    const FILTERTYPE_1MONTH         = 5;
    const FILTERTYPE_3MONTHS        = 6;
    const FILTERTYPE_6MONTHS        = 7;

    const TRUNCATION_HEADERS        = 0;
    const TRUNCATION_512B           = 1;
    const TRUNCATION_1K             = 2;
    const TRUNCATION_5K             = 4;
    const TRUNCATION_ALL            = 9;
    
    /**
     * the constructor
     *
     * @param ActiveSync_Model_Device $_device
     */
    public function __construct(ActiveSync_Model_Device $_device)
    {
        $this->_device      = $_device;
        $inputStream = fopen("php://input", "r");
        
        try {
            $decoder = new Wbxml_Decoder($inputStream);
            $this->_inputDom = $decoder->decode();
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " decoded wbxml content: " . $this->_inputDom->saveXML());
        } catch(Wbxml_Exception_UnexpectedEndOfFile $e) {
            $this->_inputDom = NULL;
        }
        
        $this->_syncTimeStamp = Zend_Date::now();
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " sync timestamp: " . $this->_syncTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG));
        
        $dtd = DOMImplementation::createDocumentType('AirSync', "-//AIRSYNC//DTD AirSync//EN", "http://www.microsoft.com/");
        $this->_outputDom = DOMImplementation::createDocument($this->_defaultNameSpace, $this->_documentElement, $dtd);
        $this->_outputDom->formatOutput = false;
        $this->_outputDom->encoding     = 'utf-8';
        
    }
    
    /**
     * this abstract function must be implemented the commands
     * this function processes the incoming request
     *
     */
    abstract public function handle();    
    
    /**
     * this function generates the response for the client
     * could get overwriten by the command
     * 
     * @param boolean $_keepSession keep session active(don't logout user) when true
     */
    public function getResponse($_keepSession = false)
    {
        if($_keepSession !== true) {
            Tinebase_Controller::getInstance()->logout($_SERVER['REMOTE_ADDR']);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " " . $this->_outputDom->saveXML());
        
        $outputStream = fopen("php://temp", 'r+');
        
        $encoder = new Wbxml_Encoder($outputStream, 'UTF-8', 3);
        $encoder->encode($this->_outputDom);
        
        header("Content-Type: application/vnd.ms-sync.wbxml");
        
        rewind($outputStream);
        while (!feof($outputStream)) {
            #$buffer .= fgets($outputStream, 4096);
            echo fgets($outputStream, 4096);
        }

        #echo $buffer;        
    }
}