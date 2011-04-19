<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        move visibility='displayed' check from getSelect to contact filter
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
     * should the class return contacts of disabled users
     * 
     * @var boolean
     */
    protected $_getDisabledContacts = FALSE;

    /**
     * foreign tables (key => tablename)
     *
     * @var array
     */
    protected $_foreignTables = array(
        'jpegphoto'    => array(
            'table'         => 'addressbook_image',
            'joinOn'        => 'contact_id',
            'select'        => array('jpegphoto' => 'IF(ISNULL(addressbook_image.contact_id), 0, 1)'),
            'singleValue'   => TRUE,
            'preserve'      => TRUE,
        ),
        'account_id'    => array(
            'table'         => 'accounts',
            'joinOn'        => 'contact_id',
            'singleValue'   => TRUE,
        ),
    );
    
    /**
     * fetch one contact of a user identified by his user_id
     *
     * @param   int $_userId
     * @return  Addressbook_Model_Contact 
     * @throws  Addressbook_Exception_NotFound if contact not found
     */
    public function getByUserId($_userId)
    {
        $select = $this->_getSelect()
            ->where($this->_db->quoteIdentifier('accounts.id') . ' = ?', $_userId)
            ->limit(1);
        
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();
                
        if (!$queryResult) {
            throw new Addressbook_Exception_NotFound('Contact with user id ' . $_userId . ' not found.');
        }
        
        $contact = $this->_rawDataToRecord($queryResult);
               
        return $contact;
    }
    
    /**
     * Creates new entry
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_InvalidArgument
     * @throws  Tinebase_Exception_UnexpectedValue
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
     * get disabled contacts?
     * 
     * @param boolean $_value
     */
    public function setGetDisabledContacts($_value)
    {
        $this->_getDisabledContacts = !!$_value;
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
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier('contact_id'). ' = ?', $_contactId));
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
        $this->_db->delete($this->_tablePrefix . 'addressbook_image', $this->_db->quoteInto($this->_db->quoteIdentifier('contact_id') . ' = ?', $_contactId));
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
     * 
     * @todo remove this when visibility/status check is moved to contact filter
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {        
        $select = parent::_getSelect($_cols, $_getDeleted);
        
        // @todo move this to a 'disabled' contact filter
        if ($this->_getDisabledContacts !== true && ($_cols == '*' || array_key_exists('account_id', $_cols))) {
            if (Tinebase_Core::getUser() instanceof Tinebase_Model_FullUser) {
                $where = "/* is no user */ ISNULL(accounts.id) OR /* is user */ (NOT ISNULL(accounts.id) AND " . 
                    $this->_db->quoteInto($this->_db->quoteIdentifier('accounts.status') . ' = ?', 'enabled') . 
                    " AND " . 
                    '('. $this->_db->quoteInto($this->_db->quoteIdentifier('accounts.visibility') . ' = ?', 'displayed') . ' OR ' . $this->_db->quoteInto($this->_db->quoteIdentifier('accounts.id') . ' = ?', Tinebase_Core::getUser()->getId()) . ')' .
                ")";
            } else {
                $where = "/* is no user */ ISNULL(accounts.id) OR /* is user */ (NOT ISNULL(accounts.id) AND " . 
                    $this->_db->quoteInto($this->_db->quoteIdentifier('accounts.status') . ' = ?', 'enabled') . 
                    " AND " . 
                    $this->_db->quoteInto($this->_db->quoteIdentifier('accounts.visibility') . ' = ?', 'displayed') . 
                ")";
            }
            
            $select->where($where);
        }
        
        return $select;
    }
    
    /**
     * add columns from filter
     * 
     * @param array $_colsToFetch
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return array
     * 
     * @todo remove this when visibility/status check is moved to contact filter
     */
    protected function _addFilterColumns($_colsToFetch, Tinebase_Model_Filter_FilterGroup $_filter)
    {
        $result = parent::_addFilterColumns($_colsToFetch, $_filter);
        
        // add account.id column as we always need this filter for disabled/invisible users
        $result['account_id'] = 'accounts.id';
        return $result;
    }
}
