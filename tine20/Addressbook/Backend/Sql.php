<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * sql backend class for the addressbook
 *
 * @package     Addressbook
 */
class Addressbook_Backend_Sql extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'addressbook';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Addressbook_Model_Contact';

    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;

    /**
     * fetch one contact of a user identified by his user_id
     *
     * @param   int $_userId
     * @return  Addressbook_Model_Contact 
     * @throws  Addressbook_Exception_NotFound if contact not found
     */
    public function getByUserId($_userId)
    {
        $select = $this->_db->select()->from(SQL_TABLE_PREFIX . $this->_tableName)
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier('account_id') . ' = ?', $_userId));
        $row = $this->_db->fetchRow($select);
        if (! $row) {
            throw new Addressbook_Exception_NotFound('Contact with user id ' . $_userId . ' not found.');
        }
        $result = new Addressbook_Model_Contact($row);
        return $result;
    }
    
    /**
     * returns contact image
     *
     * @param int $_contactId
     * @return blob
     */
    public function getImage($_contactId)
    {
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'addressbook_image', array('image'))
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier('contact_id'). ' = ?', $_contactId), Zend_Db::INT_TYPE);
        $imageData = $this->_db->fetchOne($select, 'image');
        
        return $imageData ? base64_decode($imageData) : '';
    }
    
    /**
     * get the basic select object to fetch records from the database
     *  
     * @param array|string|Zend_Db_Expr $_cols columns to get, * per default
     * @param boolean $_getDeleted get deleted records (if modlog is active)
     * @return Zend_Db_Select
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {
        $select = parent::_getSelect($_cols, $_getDeleted);
        
        if (! array_key_exists('count', (array)$_cols)) {
            $select->joinLeft(
                /* table  */ array('image' => $this->_tablePrefix . 'addressbook_image'), 
                /* on     */ $this->_db->quoteIdentifier('image.contact_id') . ' = ' . $this->_db->quoteIdentifier($this->_tableName . '.id'),
                /* select */ array('jpegphoto' => 'IF(ISNULL('. $this->_db->quoteIdentifier('image.image') .'), 0, 1)'));
        }
        
        return $select;
    }
}
