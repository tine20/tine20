<?php
/**
 * Syncroton
 *
 * @package     Wbxml
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id:AirSync.php 4968 2008-10-17 09:09:33Z l.kneschke@metaways.de $
 */

/**
 * class documentation
 *
 * @package     Wbxml
 * @subpackage  ActiveSync
 */
 
abstract class Syncroton_Wbxml_Dtd_ActiveSync_Abstract
{
    /**
     * codepage number
     *
     * @var integer
     */
    protected $_codePageNumber  = NULL;
            
    /**
     * codepage name
     *
     * @var string
     */
    protected $_codePageName    = NULL;

    /**
     * document page identifier
     * not needed for ActiveSync
     *
     * @var integer
     */
    protected $_dpi             = NULL;
    
    /**
     * mapping of tags to id's
     *
     * @var array
     */
    protected $_tags            = array();
    
    /**
     * return document page identifier
     * is always NULL for activesync
     *
     * @return unknown
     */
    public function getDPI()
    {
        return $this->_dpi;
    }
    
    /**
     * get codepage name
     *
     * @return string
     */
    public function getCodePageName()
    {
        return $this->_codePageName;
    }
    
    /**
     * get namespace identifier
     *
     * @return string
     */
    public function getNameSpace()
    {
        return 'uri:' . $this->_codePageName;
    }
    
    /**
     * get tag identifier
     *
     * @param string $_tag the tag name
     * @return integer
     */
    public function getIdentity($_tag)
    {
        if(!isset($this->_tags[$_tag])) {
            //var_dump($this->_tags);
            throw new Syncroton_Wbxml_Exception("tag $_tag not found");
        }

        return $this->_tags[$_tag];
    }
    
    /**
     * return tag by given identity
     *
     * @param unknown_type $_identity
     * @return unknown
     */
    public function getTag($_identity)
    {
        $tag = array_search($_identity, $this->_tags);
        
        if($tag === false) {
            throw new Syncroton_Wbxml_Exception("identity $_identity not found");
        }
        
        return $tag;
    }
}