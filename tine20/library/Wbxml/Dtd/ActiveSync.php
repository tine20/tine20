<?php
/**
 * Tine 2.0
 *
 * @package     Wbxml
 * @subpackage  ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id:Factory.php 4968 2008-10-17 09:09:33Z l.kneschke@metaways.de $
 */

/**
 * class documentation
 *
 * @package     Wbxml
 * @subpackage  ActiveSync
 */

class Wbxml_Dtd_ActiveSync
{
    const CODEPAGE_AIRSYNC          = 0;
    const CODEPAGE_CONTACTS         = 1;
    const CODEPAGE_EMAIL            = 2;
    const CODEPAGE_AIRNOTIFY        = 3;
    const CODEPAGE_CALENDAR         = 4;
    const CODEPAGE_MOVE             = 5;
    const CODEPAGE_ITEMESTIMATE     = 6;
    const CODEPAGE_FOLDERHIERARCHY  = 7;
    const CODEPAGE_MEETINGRESPONSE  = 8;
    const CODEPAGE_TASKS            = 9;
    const CODEPAGE_RESOLVERECIPIENTS = 10;
    const CODEPAGE_VALIDATECERT     = 11;
    const CODEPAGE_CONTACTS2        = 12;
    const CODEPAGE_PING             = 13;
    const CODEPAGE_PROVISION        = 14;
    const CODEPAGE_SEARCH           = 15;
    const CODEPAGE_GAL              = 16;
    const CODEPAGE_AIRSYNCBASE      = 17;
    const CODEPAGE_SETTINGS         = 18;
    const CODEPAGE_DOCUMENTLIBRARY  = 19;
    const CODEPAGE_ITEMOPERATIONS   = 20;
    
    /**
     * variable to hold currently active codepage
     *
     * @var Wbxml_Dtd_ActiveSync_Abstract
     */
    protected $_currentCodePage;
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_currentCodePage = new Wbxml_Dtd_ActiveSync_CodePage0();
    }
    
    /**
     * returns reference to current codepage
     *
     * @return Wbxml_Dtd_ActiveSync_Abstract
     */
    public function getCurrentCodePage() 
    {
        return $this->_currentCodePage;
    }
    
    /**
     * switch to another codepage
     *
     * @param integer $_codePageId id of the codepage
     * @return Wbxml_Dtd_ActiveSync_Abstract
     */
    public function switchCodePage($_codePageId)
    {
        $className = 'Wbxml_Dtd_ActiveSync_CodePage' . $_codePageId;
        
        $this->_currentCodePage = new $className();
        
        return $this->_currentCodePage;
    }
    
    /**
     * get initial dom document
     *
     * @return DOMDocument
     */
    public function getDomDocument($_nameSpace, $_tag)
    {
        // Creates an instance of the DOMImplementation class
        $imp = new DOMImplementation();
        
        // Creates a DOMDocumentType instance
        $dtd = $imp->createDocumentType('AirSync', "-//AIRSYNC//DTD AirSync//EN", "http://www.microsoft.com/");

        // Creates a DOMDocument instance
        $dom = $imp->createDocument($_nameSpace, $_tag, $dtd);
        
        $dom->encoding      = 'utf-8';
        $dom->formatOutput  = false;
        
        return $dom;
    }
}    
