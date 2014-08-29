<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle droipbox in CalDAV tree
 *
 * NOTE implementing IACL does not help to get rid of acl checkbox in Lion's Calendar Edit Dialog
 * 
 * @package     Calendar
 * @subpackage  Frontend
 */
class Calendar_Frontend_CalDAV_Dropbox extends \Sabre\DAV\Collection
{
    /**
     * @var Tinebase_Model_FullUser
     */
    protected $_user;
    
    const NAME='dropbox';
    
    public function __construct($_userId)
    {
        $this->_user = $_userId instanceof Tinebase_Model_FullUser ? $_userId : Tinebase_User::getInstance()->get($_userId);
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre\DAV\Collection::createDirectory()
     */
    public function createDirectory($name)
    {
        // client tries to add directory but it already exists => nothing to do
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre\DAV\Collection::getChild()
     */
    public function getChild($_name)
    {
        $eventId = $this->_getIdFromName($_name);
        $path = 'Calendar/records/Calendar_Model_Event/' . $eventId;
        
        return new Tinebase_Frontend_WebDAV_Record($path);
    }
    
    /**
     * @see Sabre\DAV\Collection::getChildren()
     */
    function getChildren()
    {
        // we don't allow dropbox listing yet
        return array();
    }
    
    /**
     * Returns the name of the node
     *
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
    
    /**
     * get id from name => strip of everything after last dot
     * 
     * @param  string  $_name  the name for example vcard.vcf
     * @return string
     */
    protected function _getIdFromName($_name)
    {
        $id = ($pos = strrpos($_name, '.')) === false ? $_name : substr($_name, 0, $pos);
        
        return $id;
    }
}
