<?php
/**
 * Syncroton
 *
 * @package     Wbxml
 * @subpackage  Syncml
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id:Abstract.php 4968 2008-10-17 09:09:33Z l.kneschke@metaways.de $
 */

/**
 * class documentation
 *
 * @package     Wbxml
 * @subpackage  Syncml
 */
 
class Syncroton_Wbxml_Dtd_Syncml_Abstract
{     
    protected $_tags; 
     
    protected $_identity;
    
    protected $_codePages;
    
    protected $_currentCodePage;
    
    public function __construct($_initialCodePage = 0x00)
    {
        $this->switchCodePage($_initialCodePage);
    }
    
    /**
     * switch codepage
     *
     * @param integer $_id id of the codePage
     * @return array
     */
    public function switchCodePage($_id)
    {
        if(!isset($this->_codePages[$_id])) {
            throw new Syncroton_Wbxml_Dtd_Exception_CodePageNotFound('invalid codePage id: ' . $_id);
        }
        $this->_currentCodePage = $_id;
        $this->_tags = $this->_codePages[$this->_currentCodePage]['tags'];
        $this->_identity = array_flip($this->_tags);
        
        return $this->_codePages[$this->_currentCodePage];
    }
    
    /**
     * get currently active codepage
     *
     * @return array
     */
    public function getCurrentCodePage()
    {
        return $this->_codePages[$this->_currentCodePage];
    }
    
    public function getTag($_identity)
    {
        if(!isset($this->_identity[$_identity])) {
            throw new Syncroton_Wbxml_Exception("identity $_identity not found");
        }
        
        return $this->_identity[$_identity];
    }
    
    public function getIdentity($_tag)
    {
        if(!isset($this->_tags[$_tag])) {
            var_dump($this->_tags);
            throw new Syncroton_Wbxml_Exception("tag $_tag not found");
        }
        
        return $this->_tags[$_tag];
    }
    
    /**
     * switch codepage by urn
     *
     * @param string $_urn
     * @return array
     */
    public function switchCodePageByUrn($_urn)
    {
        $codePageNumber = NULL;
        foreach($this->_codePages as $codePage) {
            if($codePage['urn'] == $_urn) {
                $codePageNumber = $codePage['codePageNumber'];
            }
        }
        
        if($codePageNumber === NULL) {
            throw new Syncroton_Wbxml_Dtd_Exception_CodePageNotFound("codePage with URN $_urn not found");
        }
        
        return $this->switchCodePage($codePageNumber);
    }
}