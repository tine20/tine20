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
        try {
            $contact = $this->getByProperty($_userId, 'account_id');
        } catch (Tinebase_Exception_NotFound $e) {
            throw new Addressbook_Exception_NotFound('Contact with user id ' . $_userId . ' not found.');
        }
        
        return $contact;
    }
    
    /**
     * Creates new entry
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_InvalidArgument
     * @throws  Tinebase_Exception_UnexpectedValue
     * 
     * @todo    remove autoincremental ids later
     */
    public function create(Tinebase_Record_Interface $_record) 
    {
        $contact = parent::create($_record);
        if (! empty($_record->jpegphoto)) {
            $contact->jpegphoto = $this->_saveImage($contact->getId(), $_record->jpegphoto);
        }
        
        return $contact;
    }
    
    /**
     * Updates existing entry
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Tinebase_Exception_Record_Validation|Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_Interface Record|NULL
     */
    public function update(Tinebase_Record_Interface $_record) 
    {
        $contact = parent::update($_record);
        if (isset($_record->jpegphoto)) {
            $contact->jpegphoto = $this->_saveImage($contact->getId(), $_record->jpegphoto);
        }
        
        return $contact;
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
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier('contact_id'). ' = ?', $_contactId, Zend_Db::INT_TYPE));
        $imageData = $this->_db->fetchOne($select, 'image');
        
        return $imageData ? base64_decode($imageData) : '';
    }
    
    /**
     * saves image to db
     *
     * @param  int $_contactId
     * @param  blob $imageData
     * @return blob
     */
    public function _saveImage($_contactId, $imageData)
    {
        $this->_db->delete($this->_tablePrefix . 'addressbook_image', $this->_db->quoteInto($this->_db->quoteIdentifier('contact_id') . ' = ?', $_contactId, Zend_Db::INT_TYPE));
        if (! empty($imageData)) {
            $this->_db->insert($this->_tablePrefix . 'addressbook_image', array(
                'contact_id'    => $_contactId,
                'image'         => base64_encode($imageData)
            ));
        }
        
        return $imageData;
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
        
        $select->joinLeft(
            /* table  */ array('account' => $this->_tablePrefix . 'accounts'), 
            /* on     */ $this->_db->quoteIdentifier('account.id') . ' = ' . $this->_db->quoteIdentifier($this->_tableName . '.account_id'),
            /* select */ array()
        );
        $select->where("ISNULL(account_id) OR (NOT ISNULL(account_id) AND visibility='displayed')");
        
        if ($_cols == '*' || array_key_exists('jpegphoto', (array)$_cols)) {
            $select->joinLeft(
                /* table  */ array('image' => $this->_tablePrefix . 'addressbook_image'), 
                /* on     */ $this->_db->quoteIdentifier('image.contact_id') . ' = ' . $this->_db->quoteIdentifier($this->_tableName . '.id'),
                /* select */ array('jpegphoto' => 'IF(ISNULL('. $this->_db->quoteIdentifier('image.image') .'), 0, 1)')
            );
        }
        
        // return contact type
        if ($_cols == '*' || array_key_exists('type', (array)$_cols)) {
            $select->columns(array('type' => "IF(ISNULL(account_id),'contact', 'user')"));
        }
        
        return $select;
    }
    
    /**
     * converts record into raw data for adapter
     *
     * @param  Tinebase_Record_Abstract $_record
     * @return array
     */
    protected function _recordToRawData($_record)
    {
        $result = parent::_recordToRawData($_record);

        // some columns got joined from another table and can't be written
        unset($result['type']);

        return $result;
    }
    
}
